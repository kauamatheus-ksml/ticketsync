<?php
// header_cliente.php

// Determina qual página está ativa para destacar no menu
$current_page = basename($_SERVER['PHP_SELF']);
$current_page = str_replace('.php', '', $current_page);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <!-- Roboto (para espelhar o admin) -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/header_cliente.css">
  <title>Ticket Sync - Cliente</title>
</head>
<body>
  <header class="client-header-container">
    <div class="client-container-inner">
      <!-- Logo -->
      <a href="index" class="client-logo-link">
        <img src="uploads/ticketsyhnklogo.png" alt="Ticket Sync Logo" class="client-logo">
      </a>

      <!-- Menu Hamburguer para mobile -->
      <div class="menu-toggle" id="menuToggle">
        <span></span>
        <span></span>
        <span></span>
      </div>

      <!-- Navegação -->
      <nav class="client-nav-menu" id="clientNav">
        <a href="index" class="client-nav-link <?php echo ($current_page=='index') ? 'active' : '' ?>">
          <i class="fas fa-home"></i> Início
        </a>
        <a href="perfil" class="client-nav-link <?php echo ($current_page=='perfil') ? 'active' : '' ?>">
          <i class="fas fa-user"></i> Meu Perfil
        </a>
        <a href="meus_ingressos" class="client-nav-link <?php echo ($current_page=='meus_ingressos') ? 'active' : '' ?>">
          <i class="fas fa-ticket-alt"></i> Meus Ingressos
        </a>
        <a href="logout" class="client-nav-link">
          <i class="fas fa-sign-out-alt"></i> Sair
        </a>
      </nav>
    </div>
  </header>

  <script>
    // Toggle menu mobile
    document.getElementById('menuToggle').addEventListener('click', function() {
      this.classList.toggle('active');
      document.getElementById('clientNav').classList.toggle('active');
    });
    // Fecha menu ao clicar em link
    document.querySelectorAll('.client-nav-link').forEach(link => {
      link.addEventListener('click', () => {
        document.getElementById('menuToggle').classList.remove('active');
        document.getElementById('clientNav').classList.remove('active');
      });
    });
  </script>
</body>
</html>
