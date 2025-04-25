<?php
session_start();
include_once('check_permissions.php');

// Verifica se o usuário tem acesso à página "clientes"
if (!checkPermission("clientes")) {
    echo "Você não possui permissão para acessar esta página!";
    exit();
}

include('conexao.php');

if (!isset($_SESSION['adminid'])) {
    header("Location: login.php");
    exit();
}

// Extrai o primeiro nome do administrador (promotor) logado
if (isset($_SESSION['nome'])) {
    $nomeCompleto = $_SESSION['nome'];
    $nomePartes = explode(" ", $nomeCompleto);
    $primeiroNome = $nomePartes[0];
} else {
    $primeiroNome = "Cliente";
}

// O ID do promotor é armazenado na sessão (adminid)
$promotorId = $_SESSION['adminid'];

// Consulta os clientes associados ao promotor através da tabela relacional "cliente_promotor"
$sql = "SELECT c.* 
        FROM clientes c 
        INNER JOIN cliente_promotor cp ON c.id = cp.cliente_id 
        WHERE cp.promotor_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}
$stmt->bind_param("i", $promotorId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clientes do Promotor <?php echo htmlspecialchars($primeiroNome); ?></title>
  <link rel="icon" href="uploads\ticketsync.ico" type="image/x-icon"/>
  <link rel="stylesheet" href="css/clientes.css">
</head>
<body>
  <?php include('header_admin.php'); ?>
  <h2 class="clientes-registros-titulo">Clientes Registrados do Promotor <?php echo htmlspecialchars($primeiroNome); ?></h2>
  <div class="clientes-lista">
    <?php if($result->num_rows == 0): ?>
      <p>Nenhum cliente registrado.</p>
    <?php else: ?>
      <?php while($row = $result->fetch_assoc()): ?>
        <div class="cliente-item">
           <span class="cliente-id"><?php echo htmlspecialchars($row['id']); ?></span>
           <?php if (!empty($row['foto'])): ?>
             <img src="<?php echo htmlspecialchars($row['foto']); ?>" alt="Foto de Perfil" class="cliente-foto">
           <?php endif; ?>
           <div class="cliente-info">
             <span><strong>Nome:</strong> <?php echo htmlspecialchars($row['nome']); ?></span>
             <span><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></span>
             <span><strong>Telefone:</strong> <?php echo htmlspecialchars($row['telefone']); ?></span>
           </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
