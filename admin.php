<?php 
/**
 * admin.php
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('conexao.php');
session_start();

// Se não estiver logado como admin, redireciona
if (!isset($_SESSION['adminid'])) { 
    header("Location: login.php"); 
    exit(); 
} 

// Extrair o primeiro nome do administrador logado
if (isset($_SESSION['nome'])) {
    $nomeCompleto = $_SESSION['nome'];
    $nomePartes = explode(" ", $nomeCompleto);
    $primeiroNome = $nomePartes[0];
} else {
    $primeiroNome = "Cliente";
}

// Define a chave dinâmica para a permissão de clientes, com base no primeiro nome em letras minúsculas
$dynamicClientesKey = "clientes_" . strtolower($primeiroNome);

// Caminho para o arquivo de permissões
$permissionsFile = "permissoes_geral.json";

// Valores padrão para um administrador
$defaultUserPermissions = [
    "clientes"               => true,
    "eventos"                => true,
    "ingressos"              => true,
    "aniversariantes"        => true,
    // Chave dinâmica
    $dynamicClientesKey      => true,
    "vendas"                 => true,
    "promotores"             => true,
    "gerencia_acesso"        => true,
    "cadastrar_conta"        => true,
    "gerenciar_eventos"      => true,
    "ingressos_vendidos"     => true,
    "cadastrar_funcionarios" => true,
    // NOVA PERMISSÃO para o botão "Validar Ingressos"
    "validar_ingressos"      => true,
    // NOVA PERMISSÃO para o botão "PDV"
    "pdv"                    => true
];

// Carrega as permissões do arquivo (se existir) ou inicializa vazio
if (file_exists($permissionsFile)) {
    $permissionsData = json_decode(file_get_contents($permissionsFile), true);
} else {
    $permissionsData = [];
}

$userId = $_SESSION['adminid'];

// Verifica se já existem permissões definidas para este usuário; caso contrário, usa as padrão
$userPermissions = isset($permissionsData[$userId]) ? $permissionsData[$userId] : $defaultUserPermissions;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Página do Administrador</title>
  <link rel="icon" href="uploads/ticketsync.ico" type="image/x-icon"/>
  <link rel="stylesheet" href="css/admin.css">
  <!-- Font Awesome para os ícones -->
  <link rel="stylesheet" 
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" 
        integrity="sha512-Fo3rlrZj/k7ujTnH2N2bNqykVNpyFJpN7Mx5jZ0ip2qZf9ObK0MZJxY9w+cn0bXn8N+Ge2B9RVzOfX6swbZZmw==" 
        crossorigin="anonymous" referrerpolicy="no-referrer" />
  <?php include('header_admin.php'); ?>
  <style>
    /* Estilos adicionais para melhorar a performance dos cards */
    .admin-menu-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      will-change: transform, box-shadow;
    }
  </style>
</head>
<body class="admin-page-body">
  <div class="admin-container">
      <div class="admin-welcome">
          <h2 class="admin-welcome-heading">
              Bem-vindo, Administrador <?php echo htmlspecialchars($_SESSION['nome']); ?>!
          </h2>
          <p>Você está logado na página do administrador.</p>
      </div>
      <div class="admin-menu-container">
          <?php
          // Define os botões com label, ícone e link
          $menuButtons = [
              "clientes" => [
                  "label" => "Clientes", 
                  "icon"  => "fas fa-users", 
                  "link"  => "clientes"
              ],
              "eventos" => [
                  "label" => "Eventos", 
                  "icon"  => "fas fa-calendar-alt", 
                  "link"  => "eventos"
              ],
              "ingressos" => [
                  "label" => "Ingressos", 
                  "icon"  => "fas fa-ticket-alt", 
                  "link"  => "ingressos"
              ],
              "aniversariantes" => [
                  "label" => "Aniversariantes", 
                  "icon"  => "fas fa-birthday-cake", 
                  "link"  => "aniversariantes"
              ],
              // Botão com chave dinâmica
              $dynamicClientesKey => [
                  "label" => "Clientes " . $primeiroNome,
                  "icon"  => "fas fa-user-tag",
                  "link"  => "clientes_nome"
              ],
              "vendas" => [
                  "label" => "Vendas", 
                  "icon"  => "fas fa-chart-line", 
                  "link"  => "gerenciar_vendas"
              ],
              "promotores" => [
                  "label" => "Promotores", 
                  "icon"  => "fas fa-user-tie", 
                  "link"  => "promotores"
              ],
              "gerencia_acesso" => [
                  "label" => "Gerência de Acesso", 
                  "icon"  => "fas fa-lock", 
                  "link"  => "gerencia_acesso"
              ],
              "cadastrar_conta" => [
                  "label" => "Cadastrar Conta",
                  "icon"  => "fas fa-money-check-alt",
                  "link"  => "cadastrar_conta"
              ],
              "gerenciar_eventos" => [
                  "label" => "Gerenciar Eventos",
                  "icon"  => "fas fa-tasks",
                  "link"  => "Gerenciar_eventos"
              ],
              "ingressos_vendidos" => [
                  "label" => "Ingressos Vendidos",
                  "icon"  => "fas fa-hand-holding-usd",
                  "link"  => "ingressos_vendidos"
              ],
              "cadastrar_funcionarios" => [
                  "label" => "Cadastrar Funcionários",
                  "icon"  => "fas fa-user-plus",
                  "link"  => "cadastrar_funcionarios"
              ],
              // NOVO BOTÃO "Validar Ingressos"
              "validar_ingressos" => [
                  "label" => "Validar Ingressos",
                  "icon"  => "fas fa-qrcode",
                  "link"  => "validar_ingresso"
              ],
              // NOVO BOTÃO "PDV"
              "pdv" => [
                  "label" => "PDV",
                  "icon"  => "fas fa-cash-register",
                  "link"  => "pdv"
              ],
              // NOVO BOTÃO: Conectar Mercado Pago (para obter recipient_id)
              "conectar_mp" => [
                  "label" => "Conectar Mercado Pago",
                  "icon"  => "fab fa-cc-visa",
                  "link"  => "mp_auth"
              ]
          ];
          
          // Exibe o botão somente se a permissão estiver ativa ou, no caso de conectar, sempre exibe para o admin master
          foreach ($menuButtons as $key => $button) {
              // Para o botão de conexão, exibe sempre (ou conforme regra interna)
              if ($key === "conectar_mp") {
                  echo '<a href="' . $button["link"] . '" class="admin-menu-card">';
                  echo '<i class="' . $button["icon"] . ' admin-card-icon" aria-hidden="true"></i>';
                  echo '<span class="admin-card-title">' . $button["label"] . '</span>';
                  echo '</a>';
              } else if (!empty($userPermissions[$key])) {
                  echo '<a href="' . $button["link"] . '" class="admin-menu-card">';
                  echo '<i class="' . $button["icon"] . ' admin-card-icon" aria-hidden="true"></i>';
                  echo '<span class="admin-card-title">' . $button["label"] . '</span>';
                  echo '</a>';
              }
          }
          ?>
      </div>
  </div>
  
  <script>
    // Efeitos de hover nos cards
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.admin-menu-card').forEach(card => {
          card.addEventListener('mouseenter', () => {
              card.style.transform = 'translateY(-10px)';
              card.style.boxShadow = '0 12px 20px rgba(0, 0, 0, 0.2)';
          });
          card.addEventListener('mouseleave', () => {
              card.style.transform = 'translateY(0)';
              card.style.boxShadow = '0 6px 10px rgba(0, 0, 0, 0.1)';
          });
      });
    });
    
    // Polling para atualização das permissões
    let lastModTime = 0;
    function checkPermissionsUpdate() {
      fetch('get_permissions_time.php')
        .then(response => response.json())
        .then(data => {
          if (!lastModTime) {
            lastModTime = data.mod_time;
          } else if (data.mod_time > lastModTime) {
            window.location.reload();
          }
        })
        .catch(error => console.error('Erro ao verificar permissões:', error));
    }
    setInterval(checkPermissionsUpdate, 10000);
  </script>
</body>
</html>
<?php $conn->close(); ?>
