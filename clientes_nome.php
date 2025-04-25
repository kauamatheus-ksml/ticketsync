<?php
// clientes_nome.php

// Inclui o arquivo que inicia a sessão e carrega as permissões
include_once('check_permissions.php');

// Extrair o primeiro nome do administrador (ou promotor) logado
if (isset($_SESSION['nome'])) {
    $nomeCompleto = $_SESSION['nome'];
    $nomePartes = explode(" ", $nomeCompleto);
    $primeiroNome = $nomePartes[0];
} else {
    $primeiroNome = "Cliente";
}

// Define a chave dinâmica para a permissão de clientes, baseada no primeiro nome em letras minúsculas
$dynamicClientesKey = "clientes_" . strtolower($primeiroNome);

// Verifica se o usuário possui a permissão dinâmica
if (!checkPermission($dynamicClientesKey)) {
    echo "Você não possui permissão para acessar esta página!";
    exit();
}

include('conexao.php');

$message = "";

// Processa o cadastro de cliente
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome  = $_POST['nome'];
    $email = $_POST['email'];
    // Define senha padrão "1234"
    $senha = password_hash("1234", PASSWORD_DEFAULT);
    $foto  = $_FILES['foto']['name'];

    // Verifica se o diretório 'uploads' existe, se não, cria-o
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0775, true);
    }

    if ($foto) {
        $target_file = $target_dir . basename($_FILES["foto"]["name"]);
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
            $foto = $target_file;
        } else {
            $foto = NULL;
        }
    } else {
        $foto = NULL;
    }

    $stmt = $conn->prepare("INSERT INTO clientes_paula (nome, email, senha, foto) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nome, $email, $senha, $foto);

    if ($stmt->execute()) {
        $message = "Cliente " . $primeiroNome . " cadastrado com sucesso!";
    } else {
        $message = "Erro ao cadastrar cliente " . $primeiroNome . ": " . $stmt->error;
    }
    $stmt->close();

    header("Location: clientes_nome.php?message=" . urlencode($message));
    exit();
}

// Processamento para exclusão de cliente
if (isset($_GET['excluir_id'])) {
    $id = intval($_GET['excluir_id']);

    $stmtExcluir = $conn->prepare("DELETE FROM clientes_paula WHERE id = ?");
    $stmtExcluir->bind_param("i", $id);

    if ($stmtExcluir->execute()) {
        $message = "Cliente " . $primeiroNome . " excluído com sucesso!";
    } else {
        $message = "Erro ao excluir cliente " . $primeiroNome . ": " . $stmtExcluir->error;
    }
    $stmtExcluir->close();

    header("Location: clientes_nome.php?message=" . urlencode($message));
    exit();
}

// Consulta os clientes cadastrados
$sqlClientesPaula = "SELECT id, nome, email, foto FROM clientes_paula ORDER BY nome ASC";
$resultClientesPaula = $conn->query($sqlClientesPaula);

if (!$resultClientesPaula) {
    die("Erro na consulta SQL de clientes " . $primeiroNome . ": " . $conn->error);
}

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes <?php echo htmlspecialchars($primeiroNome); ?></title>
    <link rel="stylesheet" href="css/clientes_paula.css">
    <link rel="icon" href="uploads\ticketsync.ico" type="image/x-icon"/>
</head>
<body>
    <?php include('header_admin.php'); ?>
    <div class="clientes-paula-header">
        <h2>Clientes <?php echo htmlspecialchars($primeiroNome); ?> Registrados</h2>
    </div>
    
    <!-- Botão para abrir o modal de cadastro -->
    <button id="openModalButton" class="open-modal-button">Cadastrar Clientes</button>
    
    <!-- Estrutura do Modal -->
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="clientes-paula-form-container">
                <h3>Cadastrar Cliente <?php echo htmlspecialchars($primeiroNome); ?></h3>
                <form action="clientes_nome.php" method="post" enctype="multipart/form-data">
                    <label for="nome" class="clientes-paula-form-label">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" class="clientes-paula-form-input" required><br>

                    <label for="email" class="clientes-paula-form-label">Email:</label>
                    <input type="email" id="email" name="email" class="clientes-paula-form-input" required><br>

                    <label for="foto" class="clientes-paula-form-label">Foto (opcional):</label>
                    <input type="file" id="foto" name="foto" class="clientes-paula-form-file-input"><br><br>

                    <input type="submit" name="submit" value="Cadastrar Cliente <?php echo htmlspecialchars($primeiroNome); ?>" class="clientes-paula-form-submit">
                </form>
            </div>
        </div>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="clientes-paula-message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <div class="clientes-paula-lista">
        <h3>Clientes <?php echo htmlspecialchars($primeiroNome); ?> Cadastrados</h3>
        <?php while ($row = $resultClientesPaula->fetch_assoc()): ?>
            <div class="clientes-paula-item">
                <span class="clientes-paula-id"><?php echo $row['id']; ?></span>
                <?php if (!empty($row['foto'])): ?>
                    <img src="<?php echo htmlspecialchars($row['foto']); ?>" alt="Foto de <?php echo htmlspecialchars($row['nome']); ?>" class="clientes-paula-pic">
                <?php endif; ?>
                <div class="clientes-paula-info">
                    <span><strong>Nome:</strong> <?php echo htmlspecialchars($row['nome']); ?></span>
                    <span><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></span>
                    <span>
                        <button class="clientes-paula-delete-button" onclick="excluirClientePaula(<?php echo $row['id']; ?>)">Excluir</button>
                    </span>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <script>
        // Modal: abrir e fechar
        var modal = document.getElementById("myModal");
        var btn = document.getElementById("openModalButton");
        var span = document.getElementsByClassName("close")[0];

        btn.onclick = function() {
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Função para excluir cliente
        function excluirClientePaula(id) {
            if (confirm("Tem certeza que deseja excluir este cliente <?php echo htmlspecialchars($primeiroNome); ?>?")) {
                window.location.href = 'clientes_nome.php?excluir_id=' + id;
            }
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>
