<?php
session_start();
include('conexao.php');

$message = "";
$message_class = "";

// Se houver mensagens armazenadas na sessão, recupera-as e limpa-as
if(isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_class = $_SESSION['message_class'];
    unset($_SESSION['message'], $_SESSION['message_class']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $foto = $_FILES['foto']['name'];
    // Para cadastro como CLIENTE usamos tipo_usuario = 0
    $novo_tipo = 0;

    // Verifica se o diretório 'uploads' existe, se não, cria-o
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0775, true);
    }

    // Processa o upload da foto, se houver
    if ($foto) {
        $target_file = $target_dir . basename($_FILES["foto"]["name"]);
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
            $message .= "<p>Foto carregada com sucesso.</p>";
        } else {
            $message .= "<p>Erro ao carregar a foto.</p>";
            $target_file = NULL;
        }
    } else {
        $target_file = NULL;
    }

    // Verifica se já existe um usuário com o mesmo email
    $sql_select = "SELECT id, tipo_usuario, foto FROM clientes WHERE email = '$email'";
    $result = $conn->query($sql_select);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id'];
        // Se o usuário estiver cadastrado como visitante (tipo_usuario = 1), atualiza para cliente (0)
        if ($row['tipo_usuario'] == 1) {
            if ($target_file === NULL) {
                $sql_update = "UPDATE clientes 
                               SET nome = '$nome', telefone = '$telefone', senha = '$senha', tipo_usuario = '$novo_tipo' 
                               WHERE id = $id";
            } else {
                $sql_update = "UPDATE clientes 
                               SET nome = '$nome', telefone = '$telefone', senha = '$senha', foto = '$target_file', tipo_usuario = '$novo_tipo' 
                               WHERE id = $id";
            }
            if ($conn->query($sql_update) === TRUE) {
                $message .= "<p>Você estava cadastrado como visitante e agora foi atualizado para cliente com sucesso!</p>";
                $message_class = "alert-success";
            } else {
                $message .= "<p>Ocorreu um erro ao atualizar seus dados. Por favor, tente novamente mais tarde.</p>";
                $message_class = "alert-error";
            }
        } else {
            // Já está cadastrado como cliente
            $message .= "<p>Este email já está registrado como cliente. Caso tenha esquecido sua senha, utilize a recuperação.</p>";
            $message_class = "alert-error";
        }
    } else {
        // Insere um novo registro
        $sql_insert = "INSERT INTO clientes (nome, email, telefone, senha, foto, tipo_usuario) 
                       VALUES ('$nome', '$email', '$telefone', '$senha', '$target_file', '$novo_tipo')";
        if ($conn->query($sql_insert) === TRUE) {
            $message .= "<p>Registro realizado com sucesso!</p>";
            $message_class = "alert-success";
        } else {
            $message .= "<p>Ocorreu um erro ao registrar. Por favor, tente novamente mais tarde.</p>";
            $message_class = "alert-error";
        }
    }

    $conn->close();
    
    // Armazena as mensagens na sessão e redireciona (PRG Pattern)
    $_SESSION['message'] = $message;
    $_SESSION['message_class'] = $message_class;
    header("Location: registro.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro de Cliente - Ticket Sync</title>
  <link rel="icon" href="uploads/ticketsync.ico" type="image/x-icon"/>
  <style>
    /* Reset básico */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Helvetica Neue', Arial, sans-serif;
      background-color: #fafafa;
      color: #333;
      line-height: 1.6;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }
    
    /* Alerts (não removidos) */
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border: 1px solid transparent;
      border-radius: 4px;
    }
    .alert-success {
      color: #155724;
      background-color: #d4edda;
      border-color: #c3e6cb;
    }
    .alert-error {
      color: #721c24;
      background-color: #f8d7da;
      border-color: #f5c6cb;
    }
    
    /* Container de registro */
    .register-container {
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
      padding: 40px;
      width: 100%;
      max-width: 500px;
      text-align: center;
      animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    /* Logo */
    .register-logo {
      margin-bottom: 20px;
    }
    .register-logo img {
      max-width: 150px;
      height: auto;
      transition: transform 0.3s ease;
    }
    .register-logo img:hover {
      transform: scale(1.05);
    }
    
    /* Cabeçalho */
    .register-heading {
      font-size: 2rem;
      margin-bottom: 20px;
      color: #0d3b66;
    }
    
    /* Formulário */
    .register-form {
      display: flex;
      flex-direction: column;
      gap: 15px;
      text-align: left;
    }
    .register-form-label {
      font-weight: bold;
      color: #333;
    }
    .register-form-input, 
    .register-form-file-input {
      padding: 10px 15px;
      border: 2px solid #ddd;
      border-radius: 5px;
      font-size: 1rem;
      transition: border-color 0.3s ease;
      width: 100%;
    }
    .register-form-input:focus,
    .register-form-file-input:focus {
      border-color: #0d3b66;
      outline: none;
    }
    .register-form-submit {
      padding: 12px 20px;
      border: none;
      background-color: #0d3b66;
      color: #fff;
      border-radius: 5px;
      font-size: 1rem;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.3s ease;
    }
    .register-form-submit:hover {
      background-color: #0056b3;
      transform: translateY(-2px);
    }
    
    /* Link para login */
    .register-login-link {
      margin-top: 20px;
      font-size: 0.9rem;
    }
    .register-login-link-a {
      color: #0d3b66;
      font-weight: bold;
      transition: color 0.3s ease;
    }
    .register-login-link-a:hover {
      color: #0056b3;
    }
    
    /* Responsividade */
    @media (max-width: 480px) {
      .register-container {
        padding: 30px 20px;
      }
      .register-heading {
        font-size: 1.8rem;
      }
    }
  </style>
</head>
<body class="register-page-body">
  <div class="register-container">
    <div class="register-logo">
      <a href="index.php">
        <img src="uploads/ticketsyhnklogo.png" alt="Ticket Sync Logo">
      </a>
    </div>
    <h2 class="register-heading">Registro de Cliente</h2>
    
    <!-- Exibe a mensagem de retorno, se houver -->
    <?php if (!empty($message)) : ?>
      <div class="alert <?php echo $message_class; ?>">
          <?php echo $message; ?>
      </div>
    <?php endif; ?>
    
    <form action="registro.php" method="post" enctype="multipart/form-data" class="register-form">
      <input type="hidden" name="tipo_usuario" value="0">
      
      <label for="nome" class="register-form-label">Nome:</label>
      <input type="text" id="nome" name="nome" class="register-form-input" required>
      
      <label for="email" class="register-form-label">Email:</label>
      <input type="email" id="email" name="email" class="register-form-input" required>
      
      <label for="telefone" class="register-form-label">Telefone:</label>
      <input type="text" id="telefone" name="telefone" class="register-form-input" required>
      
      <label for="senha" class="register-form-label">Senha:</label>
      <input type="password" id="senha" name="senha" class="register-form-input" required>
      
      <label for="foto" class="register-form-label">Foto (opcional):</label>
      <input type="file" id="foto" name="foto" class="register-form-file-input">
      
      <input type="submit" value="Registrar" class="register-form-submit">
    </form>
    
    <p class="register-login-link">Já tem uma conta? <a href="login.php" class="register-login-link-a">Faça login</a></p>
  </div>
</body>
</html>
