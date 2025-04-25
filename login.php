<?php
// login.php
include('conexao.php');
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Detecta se o login veio do aplicativo (via parâmetro "app=true")
$appMode = (isset($_GET['app']) && $_GET['app'] === 'true');
$returnUrl = $appMode ? 'validar_ingresso.php?app=true' : ($_GET['returnUrl'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo (isset($_GET['action']) && $_GET['action'] == 'reset') ? 'Redefinir Senha - Ticket Sync' : 'Login - Ticket Sync'; ?></title>
  <link rel="icon" href="uploads/ticketsync.ico" type="image/x-icon"/>
  <link rel="stylesheet" href="css/login.css">
  <style>
    /* Estilo do Toast */
    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      background-color: #002F6D;
      color: #fff;
      padding: 16px;
      border-radius: 4px;
      z-index: 10000;
      opacity: 0;
      transition: opacity 0.5s ease;
    }
    .toast.show {
      opacity: 1;
    }
    /* Estilo do Spinner */
    .spinner-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 9999;
    }
    .spinner {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      border: 8px solid #f3f3f3;
      border-top: 8px solid #002F6D;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      0% { transform: translate(-50%, -50%) rotate(0deg); }
      100% { transform: translate(-50%, -50%) rotate(360deg); }
    }
  </style>
  <script>
    // Função para exibir o toast de notificação
    function showToast(message) {
      var toast = document.getElementById('toast');
      toast.textContent = message;
      toast.classList.add('show');
      setTimeout(function() {
        toast.classList.remove('show');
      }, 3000);
    }
    // Exibe o spinner ao submeter o formulário
    function showSpinner() {
      document.getElementById('spinner-overlay').style.display = 'block';
    }
  </script>
</head>
<body class="login-page-body">

<!-- Spinner de carregamento -->
<div id="spinner-overlay" class="spinner-overlay">
  <div class="spinner"></div>
</div>
<!-- Container do Toast -->
<div id="toast" class="toast"></div>

<?php
// ----------------------------------------------------------------
// FLUXO DE REDEFINIÇÃO DE SENHA (quando a URL tiver ?action=reset)
// ----------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] == 'reset') {

    // Se o formulário de redefinição foi submetido
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_submit'])) {
        $email_reset = $_POST['email_reset'];
        $userType = "";
        $reset_error = "";
        
        // Procura na tabela administradores
        $sql_admin_reset = "SELECT * FROM administradores WHERE email='$email_reset'";
        $result_admin_reset = $conn->query($sql_admin_reset);
        if ($result_admin_reset && $result_admin_reset->num_rows > 0) {
            $userType = "admin";
            $token = bin2hex(random_bytes(32));
            $token_validade = date("Y-m-d H:i:s", strtotime("+1 hour"));
            $update_query = "UPDATE administradores SET token_red = '$token', token_validade = '$token_validade' WHERE email='$email_reset'";
            if (!$conn->query($update_query)) {
                $reset_error = "Erro ao gerar token: " . $conn->error;
            }
        } else {
            // Procura na tabela clientes
            $sql_cliente_reset = "SELECT * FROM clientes WHERE email='$email_reset'";
            $result_cliente_reset = $conn->query($sql_cliente_reset);
            if ($result_cliente_reset && $result_cliente_reset->num_rows > 0) {
                $userType = "cliente";
                $token = bin2hex(random_bytes(32));
                $token_validade = date("Y-m-d H:i:s", strtotime("+1 hour"));
                $update_query = "UPDATE clientes SET token_red = '$token', token_validade = '$token_validade' WHERE email='$email_reset'";
                if (!$conn->query($update_query)) {
                    $reset_error = "Erro ao gerar token: " . $conn->error;
                }
            } else {
                $reset_error = "Email não encontrado no sistema.";
            }
        }
        
        if (empty($reset_error)) {
            // Envio de e‑mail com PHPMailer
            require 'vendor/autoload.php';
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.hostinger.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'kaua@ticketsync.com.br';
                $mail->Password   = 'Aaku_2004@';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                $mail->setFrom('contato@ticketsync.com.br', 'Ticket Sync');
                $mail->addAddress($email_reset);
                
                // Gera o link de redefinição com o parâmetro "tipo"
                $reset_link = "https://ticketsync.com.br/redefinir_senha.php?token=" . $token . "&tipo=" . $userType;
                $mail->Subject = "Reinicialização de Senha - Ticket Sync";
                // Corpo do e‑mail HTML estruturado com logo e estilos
                $body = '
                <html>
                <head>
                  <style>
                    .email-container { font-family: Arial, sans-serif; color: #002F6D; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .header img { width: 200px; }
                    .content { font-size: 16px; }
                    .footer { margin-top: 20px; font-size: 12px; color: #555; }
                  </style>
                </head>
                <body>
                  <div class="email-container">
                    <div class="header">
                      <img src="https://ticketsync.com.br/uploads/ticketsyhnklogo_branco.png" alt="Ticket Sync Logo">
                    </div>
                    <div class="content">
                      <p>Olá,</p>
                      <p>Recebemos uma solicitação para redefinir sua senha.</p>
                      <p>Clique no link abaixo para redefini-la. Este link é válido por 1 hora:</p>
                      <p><a href="' . $reset_link . '" style="color:#002F6D; text-decoration:none;">Redefinir Senha</a></p>
                      <p>Se você não solicitou a redefinição, ignore este e‑mail.</p>
                    </div>
                    <div class="footer">
                      <p>Atenciosamente,</p>
                      <p>Equipe Ticket Sync</p>
                    </div>
                  </div>
                </body>
                </html>';
                $mail->Body = $body;
                $mail->isHTML(true);

                $mail->send();
                // Mostra o toast e redireciona para a página de login
                echo '
                <script>
                  document.addEventListener("DOMContentLoaded", function() {
                    showToast("Um e‑mail com as instruções para redefinir sua senha foi enviado.");
                    setTimeout(function() {
                      window.location.href = "login.php";
                    }, 3000);
                  });
                </script>
                ';
                exit();
            } catch (Exception $e) {
                $reset_error = "Falha ao enviar o e‑mail: " . $mail->ErrorInfo;
            }
        }
    }
    ?>
    <!-- Tela de Redefinição de Senha -->
    <div class="login-container">
      <div class="login-logo">
        <a href="index.php">
          <img src="uploads/ticketsyhnklogo.png" alt="Ticket Sync Logo">
        </a>
      </div>
      <h2 class="login-heading">Redefinir Senha</h2>
      <?php if (isset($reset_error) && !empty($reset_error)): ?>
        <p style="color:red;"><?php echo $reset_error; ?></p>
      <?php endif; ?>
      <form action="login.php?action=reset" method="post" class="login-form" onsubmit="showSpinner()">
        <label for="email_reset" class="login-form-label">Digite seu e‑mail:</label>
        <input type="email" id="email_reset" name="email_reset" class="login-form-input" required>
        <input type="submit" name="reset_submit" value="Enviar Redefinição" class="login-form-submit">
      </form>
      <p class="login-register-p"><a href="login.php">Voltar ao Login</a></p>
    </div>
    <?php
    exit();
} // Fim do fluxo de redefinição

// ----------------------------------------------------------------
// FLUXO DE LOGIN NORMAL
// ----------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    if (isset($_POST['returnUrl'])) {
        $returnUrl = $_POST['returnUrl'];
    }
    $erro = "";
    // Verifica usuário nas diferentes tabelas
    $sql_cliente = "SELECT * FROM clientes WHERE email='$email'";
    $result_cliente = $conn->query($sql_cliente);

    $sql_cliente_paula = "SELECT * FROM clientes_paula WHERE email='$email'";
    $result_cliente_paula = $conn->query($sql_cliente_paula);

    $sql_aniversariante = "SELECT * FROM aniversariantes WHERE email='$email'";
    $result_aniversariante = $conn->query($sql_aniversariante);

    $sql_funcionario = "SELECT * FROM funcionarios WHERE email='$email'";
    $result_funcionario = $conn->query($sql_funcionario);

    $sql_admin = "SELECT * FROM administradores WHERE email='$email'";
    $result_admin = $conn->query($sql_admin);

    if ($result_cliente && $result_cliente->num_rows > 0) {
        $row_cliente = $result_cliente->fetch_assoc();
        if (password_verify($senha, $row_cliente['senha'])) {
            $_SESSION['userid'] = $row_cliente['id'];
            $_SESSION['nome']   = $row_cliente['nome'];
            $_SESSION['email']  = $row_cliente['email'];
            header("Location: " . $returnUrl);
            exit();
        } else {
            $erro = "Senha incorreta para cliente!";
        }
    } elseif ($result_cliente_paula && $result_cliente_paula->num_rows > 0) {
        $row_cliente_paula = $result_cliente_paula->fetch_assoc();
        if (password_verify($senha, $row_cliente_paula['senha'])) {
            $_SESSION['userid'] = $row_cliente_paula['id'];
            $_SESSION['nome']   = $row_cliente_paula['nome'];
            $_SESSION['email']  = $row_cliente_paula['email'];
            header("Location: " . $returnUrl);
            exit();
        } else {
            $erro = "Senha incorreta para cliente Paula!";
        }
    } elseif ($result_aniversariante && $result_aniversariante->num_rows > 0) {
        $row_aniversariante = $result_aniversariante->fetch_assoc();
        if (password_verify($senha, $row_aniversariante['senha'])) {
            $_SESSION['userid'] = $row_aniversariante['id'];
            $_SESSION['nome']   = $row_aniversariante['nome'];
            $_SESSION['email']  = $row_aniversariante['email'];
            header("Location: " . $returnUrl);
            exit();
        } else {
            $erro = "Senha incorreta para aniversariante!";
        }
    } elseif ($result_funcionario && $result_funcionario->num_rows > 0) {
        $row_funcionario = $result_funcionario->fetch_assoc();
        if (password_verify($senha, $row_funcionario['senha'])) {
            $_SESSION['funcionarioid'] = $row_funcionario['id'];
            $_SESSION['nome']          = $row_funcionario['nome'];
            $_SESSION['email']         = $row_funcionario['email'];
            header("Location: " . $returnUrl);
            exit();
        } else {
            $erro = "Senha incorreta para funcionário!";
        }
    } elseif ($result_admin && $result_admin->num_rows > 0) {
        $row_admin = $result_admin->fetch_assoc();
        if (password_verify($senha, $row_admin['senha'])) {
            $_SESSION['adminid'] = $row_admin['id'];
            $_SESSION['nome']    = $row_admin['nome'];
            $_SESSION['email']   = $row_admin['email'];
            header("Location: admin.php");
            exit();
        } else {
            $erro = "Senha incorreta para administrador!";
        }
    } else {
        $erro = "Usuário não encontrado!";
    }
    $conn->close();
}
?>
<!-- Tela de Login -->
<div class="login-container">
  <div class="login-logo">
    <a href="index.php">
      <img src="uploads/ticketsyhnklogo.png" alt="Ticket Sync Logo">
    </a>
  </div>
  <h2 class="login-heading">Login</h2>
  <?php if (isset($erro) && !empty($erro)): ?>
    <p style="color:red;"><?php echo $erro; ?></p>
  <?php endif; ?>
  <form action="login.php<?php echo $appMode ? '?app=true' : ''; ?>" method="post" class="login-form" onsubmit="showSpinner()">
    <input type="hidden" name="returnUrl" value="<?php echo htmlspecialchars($returnUrl); ?>">
    <label for="email" class="login-form-label">Email:</label>
    <input type="email" id="email" name="email" class="login-form-input" required>
    <label for="senha" class="login-form-label">Senha:</label>
    <input type="password" id="senha" name="senha" class="login-form-input" required>
    <input type="submit" value="Login" class="login-form-submit">
  </form>
  <p class="login-register-p">
      Não tem uma conta? <a href="registro.php" class="login-register-link">Registre‑se aqui</a>
  </p>
  <p class="login-forgot-p">
      <a href="login.php?action=reset" class="login-forgot-link">Esqueceu sua senha?</a>
  </p>
</div>
</body>
</html>
