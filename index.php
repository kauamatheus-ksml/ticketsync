<?php
// index.php
session_start();
include('conexao.php');

// Se o usuário estiver logado e for visitante, atualiza para cliente
if (isset($_SESSION['userid']) && isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 1) {
    $userId = $_SESSION['userid'];
    $sql_update = "UPDATE clientes SET tipo_usuario = 0 WHERE id = $userId";
    if ($conn->query($sql_update) === TRUE) {
       $_SESSION['tipo_usuario'] = 0;
       $_SESSION['update_message'] = "Seu cadastro foi atualizado de visitante para cliente com sucesso!";
    }
}

// Consulta os eventos futuros aprovados usando data_inicio
$current_date = date("Y-m-d");
$sql = "SELECT * FROM eventos 
        WHERE data_inicio >= '$current_date' 
          AND status = 'aprovado'
        ORDER BY data_inicio ASC";
$result = $conn->query($sql);

$eventos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $eventos[] = $row;
    }
}
$conn->close();

// ------ CARROSSEL ------
// Filtrar eventos com em_carrossel = 1 e ordenar
$carouselEventos = array_filter($eventos, function($ev) {
    return ($ev['em_carrossel'] == 1);
});
usort($carouselEventos, function($a, $b) {
    if ($a['prioridade'] == $b['prioridade']) {
        return strtotime($a['data_inicio']) - strtotime($b['data_inicio']);
    }
    return $a['prioridade'] - $b['prioridade'];
});
// Limite de 5
$carouselEventos = array_slice($carouselEventos, 0, 5);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ticket Sync - Página Inicial</title>
  <link rel="icon" href="uploads/ticketsync.ico" type="image/x-icon"/>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
  <link rel="stylesheet" href="css/index.css">
  <style>
    
  </style>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const carouselInner = document.getElementById("carouselInner");
      const carouselIndicator = document.getElementById("carouselIndicator");
      const prevBtn = document.getElementById("prevBtn");
      const nextBtn = document.getElementById("nextBtn");

      if (carouselInner) {
        const items = carouselInner.querySelectorAll(".carousel-item");
        let currentIndex = 0;
        const totalItems = items.length;

        function updateCarousel() {
          items.forEach((item, index) => {
            item.classList.toggle("active", index === currentIndex);
          });
          carouselIndicator.textContent = (currentIndex + 1) + " / " + totalItems;
        }

        function slideNext() {
          currentIndex = (currentIndex + 1) % totalItems;
          updateCarousel();
        }

        function slidePrev() {
          currentIndex = (currentIndex - 1 + totalItems) % totalItems;
          updateCarousel();
        }

        updateCarousel();

        if (prevBtn) {
          prevBtn.addEventListener("click", function() {
            slidePrev();
            resetAutoSlide();
          });
        }
        if (nextBtn) {
          nextBtn.addEventListener("click", function() {
            slideNext();
            resetAutoSlide();
          });
        }

        let autoSlide = null;
        if (totalItems > 1) {
          autoSlide = setInterval(slideNext, 5000);
        }
        function resetAutoSlide() {
          if (autoSlide) {
            clearInterval(autoSlide);
            autoSlide = setInterval(slideNext, 5000);
          }
        }
      }
    });
  </script>
</head>
<body>
  <!-- HEADER -->
  <?php 
    /* 
       Se o usuário estiver logado como cliente (com userid e nome)
       OU se estiver logado como administrador (flag $_SESSION['admin'] === true),
       inclui o header adequado.
    */
    if ((isset($_SESSION['userid']) && !empty($_SESSION['nome'])) || (isset($_SESSION['admin']) && $_SESSION['admin'] === true)) {
        if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
            include('header_admin.php');
        } else {
            include('header_cliente.php');
        }
    } else {
        // Header padrão para visitantes (não logados)
        ?>
        <header>
          <div class="header-container">
            <div class="logo">
              <a href="index"><img src="uploads/ticketsyhnklogo.png" alt="Ticket Sync Logo"></a>
            </div>
            <nav>
              <div class="nav-links">
                <a href="login" class="login-btn">
                  <i class="fa-solid fa-user"></i> Acessar conta
                </a>
              </div>
            </nav>
          </div>
        </header>
    <?php 
    } 
  ?>

  <!-- CARROSSEL -->
  <?php if (!empty($carouselEventos)): ?>
    <div class="fullwidth-carousel" id="carouselContainer">
      <!-- Botões (prev/next) -->
      <button class="carousel-control prev" id="prevBtn">&#10094;</button>
      <button class="carousel-control next" id="nextBtn">&#10095;</button>

      <div class="carousel-inner" id="carouselInner">
        <?php foreach ($carouselEventos as $index => $evento): ?>
          <div class="carousel-item" style="cursor:pointer;" onclick="window.location.href='detalhes_evento.php?id=<?php echo $evento['id']; ?>'">
            <img src="<?php echo (!empty($evento['logo'])) ? htmlspecialchars($evento['logo']) : 'default-event.jpg'; ?>" alt="<?php echo htmlspecialchars($evento['nome']); ?>" style="width:100%; height:100%; object-fit: cover;">
            <div class="carousel-overlay">
              <h2><?php echo htmlspecialchars($evento['titulo_carrossel'] ?: $evento['nome']); ?></h2>
              <?php if (!empty($evento['descricao_curta'])): ?>
                <p><?php echo htmlspecialchars($evento['descricao_curta']); ?></p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="carousel-indicator" id="carouselIndicator">1 / <?php echo count($carouselEventos); ?></div>
    </div>
  <?php endif; ?>

  <!-- CONTEÚDO PRINCIPAL -->
  <div class="container">
    <div class="welcome">
      <h2>Bem-vindo ao Ticket Sync</h2>
      <p>Confira os eventos mais próximos e garanta já o seu ingresso!</p>
    </div>
    
    <!-- Mensagem de atualização -->
    <?php if(isset($_SESSION['update_message'])): ?>
      <div class="alert alert-success">
        <?php
          echo $_SESSION['update_message'];
          unset($_SESSION['update_message']);
        ?>
      </div>
    <?php endif; ?>

    <!-- Barra de Pesquisa -->
    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="Pesquisar eventos..." />
    </div>

    <!-- CARDS -->
    <div class="cards-container" id="cardsContainer">
      <?php if (!empty($eventos)): ?>
        <?php foreach ($eventos as $evento): ?>
          <div class="card">
            <?php if (!empty($evento['logo'])): ?>
              <img src="<?php echo htmlspecialchars($evento['logo']); ?>" alt="Logo do Evento">
            <?php else: ?>
              <img src="default-event.jpg" alt="Logo Padrão">
            <?php endif; ?>
            <div class="card-body">
              <div class="card-info">
                <h3><?php echo htmlspecialchars($evento['nome']); ?></h3>
                <p><strong>Data:</strong> <i class="fa-regular fa-calendar"></i> <?php echo date("d/m/Y", strtotime($evento['data_inicio'])); ?></p>
                <p><strong>Horário:</strong> <i class="fa-regular fa-clock"></i> <?php echo date("H:i", strtotime($evento['hora_inicio'])); ?></p>
                <p><strong>Local:</strong> <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($evento['local']); ?></p>
              </div>
              <div class="card-actions">
                <a href="detalhes_evento?id=<?php echo $evento['id']; ?>" class="btn">
                  <i class="fa-solid fa-circle-info"></i> Ver Detalhes
                </a>
                <a href="https://www.google.com/maps/place/<?php echo urlencode($evento['local']); ?>" class="btn btn-outline" target="_blank">
                  <i class="fa-solid fa-map-location-dot"></i> Ver no mapa
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-center">Nenhum evento disponível no momento.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- FOOTER -->
  <?php include('footer.php'); ?>

  <!-- JS DO CARROSSEL E BUSCA -->
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const searchInput = document.getElementById("searchInput");
      const cardsContainer = document.getElementById("cardsContainer");
      const carouselContainer = document.getElementById("carouselContainer");

      if (searchInput) {
        searchInput.addEventListener("input", function() {
          const filter = this.value.toLowerCase().trim();
          if (carouselContainer) {
            carouselContainer.style.display = filter ? "none" : "";
          }
          const cards = cardsContainer.querySelectorAll(".card");
          cards.forEach(function(card) {
            const text = card.textContent.toLowerCase();
            card.style.display = text.indexOf(filter) > -1 ? "" : "none";
          });
        });
      }
    });
  </script>
  <script src="jdkfront.js"></script>
</body>
</html>
