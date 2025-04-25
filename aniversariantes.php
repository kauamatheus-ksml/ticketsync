<?php
// aniversariantes.php

// Inclui o arquivo de verificação de permissões e inicia a sessão
include_once('check_permissions.php');

// Verifica se o usuário tem acesso à página "aniversariantes"
if (!checkPermission("aniversariantes")) {
    echo "Você não possui permissão para acessar esta página!";
    exit();
}

include('conexao.php');

// A sessão já foi iniciada em check_permissions.php
if (!isset($_SESSION['adminid'])) {
    header("Location: login.php");
    exit();
}

// Recupera o ID do promotor (administrador) logado
$promotorId = $_SESSION['adminid'];

// Mensagem para feedback
$message = "";

// Cadastrar aniversariante (apenas os cadastrados pelo promotor logado serão associados)
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['update_user'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $data_nascimento = $_POST['data_nascimento'];
    $senha = password_hash("1234", PASSWORD_DEFAULT); // Senha padrão "1234"
    $foto = $_FILES['foto']['name'];
    $tipo_usuario = 'ANIVERSARIANTE';

    // Verifica se o diretório 'uploads' existe, se não, cria o diretório
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

    // Insere o aniversariante e associa o registro ao promotor logado (campo promotor_id)
    $stmt = $conn->prepare("INSERT INTO aniversariantes (nome, email, data_nascimento, senha, foto, tipo_usuario, promotor_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Erro na preparação da query: " . $conn->error);
    }
    $stmt->bind_param("ssssssi", $nome, $email, $data_nascimento, $senha, $foto, $tipo_usuario, $promotorId);

    if ($stmt->execute()) {
        $message = "Aniversariante cadastrado com sucesso!";
    } else {
        $message = "Erro ao cadastrar aniversariante: " . $stmt->error;
    }
    $stmt->close();

    header("Location: aniversariantes.php?message=" . urlencode($message));
    exit();
}

// Excluir aniversariante (apenas permite excluir registros do próprio promotor)
if (isset($_GET['excluir_id'])) {
    $id = intval($_GET['excluir_id']);

    // Aqui, você pode optar por verificar se o aniversariante pertence ao promotor logado (opcional)
    $stmtExcluir = $conn->prepare("DELETE FROM aniversariantes WHERE id = ? AND promotor_id = ?");
    if (!$stmtExcluir) {
        die("Erro na preparação da query: " . $conn->error);
    }
    $stmtExcluir->bind_param("ii", $id, $promotorId);

    if ($stmtExcluir->execute()) {
        $message = "Aniversariante excluído com sucesso!";
    } else {
        $message = "Erro ao excluir aniversariante: " . $stmtExcluir->error;
    }
    $stmtExcluir->close();

    header("Location: aniversariantes.php?message=" . urlencode($message));
    exit();
}

// Consulta os aniversariantes cadastrados pelo promotor logado
$sqlAniversariantes = "SELECT id, nome, email, DATE_FORMAT(data_nascimento, '%d/%m/%Y') AS data_nascimento_formatada, foto FROM aniversariantes WHERE promotor_id = ? ORDER BY data_nascimento ASC";
$stmt = $conn->prepare($sqlAniversariantes);
if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}
$stmt->bind_param("i", $promotorId);
$stmt->execute();
$resultAniversariantes = $stmt->get_result();

if (!$resultAniversariantes) {
    die("Erro na consulta SQL de aniversariantes: " . $conn->error);
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
    <title>Aniversariantes</title>
    <link rel="icon" href="uploads\ticketsync.ico" type="image/x-icon"/>
    <link rel="stylesheet" href="css/aniversariantes.css">
</head>
<body>
    <?php include('header_admin.php'); ?>
    <div class="admin-welcome-container">
        <h2 class="admin-welcome-title">Aniversariantes</h2>
        <p class="admin-welcome-text">Registrar aniversariantes no sistema.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message-container"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Botão para abrir o modal -->
    <div class="button-container">
        <button class="modal-open-button" onclick="openModal()">Cadastrar Aniversariante</button>
    </div>

    <!-- Modal -->
    <div id="cadastroModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">&times;</span>
            <h3 class="modal-title">Cadastrar Aniversariante</h3>
            <form action="aniversariantes.php" method="post" enctype="multipart/form-data" class="aniversariantes-form">
                <input type="hidden" name="tipo_usuario" value="ANIVERSARIANTE">
                
                <label for="nome" class="aniversariantes-form-label">Nome Completo:</label>
                <input type="text" id="nome" name="nome" class="aniversariantes-form-input" required>

                <label for="email" class="aniversariantes-form-label">Email:</label>
                <input type="email" id="email" name="email" class="aniversariantes-form-input" required>

                <label for="data_nascimento" class="aniversariantes-form-label">Data de Nascimento:</label>
                <input type="date" id="data_nascimento" name="data_nascimento" class="aniversariantes-form-input" required>

                <label for="foto" class="aniversariantes-form-label">Foto (opcional):</label>
                <input type="file" id="foto" name="foto" class="form-file-input">

                <input type="submit" name="submit" value="Cadastrar" class="aniversariantes-form-submit">
            </form>
        </div>
    </div>

    <div class="aniversariantes-lista">
        <h3 class="aniversariantes-lista-title">Aniversariantes Cadastrados</h3>
        <?php while ($row = $resultAniversariantes->fetch_assoc()): ?>
            <div class="aniversariante-item">
                <div class="aniversariante-info">
                    <?php if (!empty($row['foto'])): ?>
                        <img src="<?php echo htmlspecialchars($row['foto']); ?>" alt="Foto de <?php echo htmlspecialchars($row['nome']); ?>" class="aniversariante-pic">
                    <?php endif; ?>
                    <span><strong>Nome:</strong> <?php echo htmlspecialchars($row['nome']); ?></span>
                    <span><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></span>
                    <span><strong>Data de Nascimento:</strong> <?php echo htmlspecialchars($row['data_nascimento_formatada']); ?></span>
                    <span>
                        <button class="aniversariantes-delete-button" onclick="excluirAniversariante(<?php echo $row['id']; ?>)">Excluir</button>
                    </span>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <script>
        // Funções do Modal
        function openModal() {
            document.getElementById('cadastroModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('cadastroModal').style.display = 'none';
        }
        // Fecha o modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('cadastroModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        // Fecha o modal com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        function excluirAniversariante(id) {
            if (confirm("Tem certeza que deseja excluir este aniversariante?")) {
                window.location.href = 'aniversariantes.php?excluir_id=' + id;
            }
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>
