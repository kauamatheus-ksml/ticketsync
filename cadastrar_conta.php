<?php
// cadastrar_conta.php
session_start();
include('conexao.php');

// Para fins de teste, se 'userid' não estiver definida na sessão, utiliza o ID 1.
$user_id = $_SESSION['userid'] ?? 1;

// Consulta os dados da conta Mercado Pago do usuário
$stmt = $conn->prepare("SELECT mp_access_token, mp_public_key FROM administradores WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$mp_access_token = $row['mp_access_token'] ?? '';
$mp_public_key   = $row['mp_public_key'] ?? '';

$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Conectar Conta Mercado Pago</title>
    <link rel="icon" href="uploads\ticketsync.ico" type="image/x-icon"/>
    <link rel="stylesheet" href="css/style.css">
    <style>
      body {
          font-family: Arial, sans-serif;
          background: #f4f4f4;
          padding: 20px;
      }
      .container {
          background: #fff;
          padding: 20px;
          border-radius: 5px;
          max-width: 600px;
          margin: 0 auto;
          box-shadow: 0 0 10px rgba(0,0,0,0.1);
      }
      h1 {
          text-align: center;
      }
      p {
          margin: 10px 0;
      }
      button {
          padding: 10px 20px;
          background: #3498db;
          color: #fff;
          border: none;
          border-radius: 3px;
          cursor: pointer;
      }
      button:hover {
          background: #2980b9;
      }
      a {
          text-decoration: none;
      }
    </style>
</head>
<body>
    <div class="container">
        <h1>Conectar Conta Mercado Pago</h1>
        <?php if (!empty($mp_access_token) && !empty($mp_public_key)): ?>
            <p>Sua conta do Mercado Pago já está conectada.</p>
            <p><strong>Access Token:</strong> <?php echo htmlspecialchars($mp_access_token); ?></p>
            <p><strong>Public Key:</strong> <?php echo htmlspecialchars($mp_public_key); ?></p>
            <p><a href="connect.php"><button>Atualizar Conexão</button></a></p>
        <?php else: ?>
            <p>Sua conta do Mercado Pago não está conectada.</p>
            <p>Clique no botão abaixo para conectar sua conta. Você será redirecionado para autorizar a conexão.</p>
            <p><a href="connect.php"><button>Conectar Conta Mercado Pago</button></a></p>
        <?php endif; ?>
    </div>
</body>
</html>
