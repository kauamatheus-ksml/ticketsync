<?php
// gerenciar_vendas.php

// Inclui o arquivo que inicia a sessão e carrega as permissões
include_once('check_permissions.php');

// Verifica se o usuário tem acesso à página "vendas"
if (!checkPermission("vendas")) {
    echo "Você não possui permissão para acessar esta página!";
    exit();
}

include('conexao.php');

// Recupera o ID do promotor logado
$promotorId = $_SESSION['adminid'];

$message = "";

// Processamento do formulário de liberação de ingressos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['liberar'])) {
    $ingresso_id = $_POST['ingresso_id'];

    // Atualiza a tabela de ingressos para indicar que foi liberado para venda
    $sqlLibera = "UPDATE ingressos SET liberado = 1 WHERE id = ? AND promotor_id = ?";
    $stmtLibera = $conn->prepare($sqlLibera);
    $stmtLibera->bind_param("ii", $ingresso_id, $promotorId);

    if ($stmtLibera->execute()) {
        $message = "Ingresso autorizado com sucesso!";
    } else {
        $message = "Erro ao autorizar ingresso: " . $stmtLibera->error;
    }
    $stmtLibera->close();
}

// Processamento do formulário de desliberação de ingressos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['desliberar'])) {
    $ingresso_id = $_POST['ingresso_id'];

    $sqlDeslibera = "UPDATE ingressos SET liberado = 0 WHERE id = ? AND promotor_id = ?";
    $stmtDeslibera = $conn->prepare($sqlDeslibera);
    $stmtDeslibera->bind_param("ii", $ingresso_id, $promotorId);

    if ($stmtDeslibera->execute()) {
        $message = "Ingresso desautorizado com sucesso!";
    } else {
        $message = "Erro ao desautorizar ingresso: " . $stmtDeslibera->error;
    }
    $stmtDeslibera->close();
}

// Consulta os ingressos disponíveis de eventos futuros e ainda não liberados
$dataAtual = date("Y-m-d");
$sqlIngressos = "SELECT ingressos.*, eventos.nome AS evento_nome 
                 FROM ingressos 
                 JOIN eventos ON ingressos.evento_id = eventos.id 
                 WHERE eventos.data >= ? 
                 AND ingressos.liberado = 0 
                 AND ingressos.promotor_id = ?
                 ORDER BY eventos.nome ASC";
$stmtIngressos = $conn->prepare($sqlIngressos);
$stmtIngressos->bind_param("si", $dataAtual, $promotorId);
$stmtIngressos->execute();
$resultIngressos = $stmtIngressos->get_result();

// Consulta os ingressos já autorizados
$sqlIngressosAutorizados = "SELECT ingressos.*, eventos.nome AS evento_nome 
                            FROM ingressos 
                            JOIN eventos ON ingressos.evento_id = eventos.id 
                            WHERE ingressos.liberado = 1 
                            AND ingressos.promotor_id = ?
                            ORDER BY eventos.nome ASC";
$stmtIngressosAutorizados = $conn->prepare($sqlIngressosAutorizados);
$stmtIngressosAutorizados->bind_param("i", $promotorId);
$stmtIngressosAutorizados->execute();
$resultIngressosAutorizados = $stmtIngressosAutorizados->get_result();

$conn->close();
?>

<?php include('header_admin.php'); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Venda de Ingressos</title>
    <link rel="stylesheet" href="css/gerenciar_vendas.css">
    <link rel="icon" href="uploads\ticketsync.ico" type="image/x-icon"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="body12">
    
    <div class="admin-welcome-container">
        <h2 class="admin-welcome-title">Gerenciamento de Venda de Ingressos</h2>
        <p>Gerencie a autorização e desautorização dos ingressos cadastrados.</p>
    </div>
    <?php if (!empty($message)): ?>
        <div class="admin-message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <div class="ingresso-form-container">
        <h3 class="ingresso-form-title">Autorizar Ingresso</h3>
        <form action="gerenciar_vendas.php" method="post">
            <label for="ingresso_id" class="ingresso-form-label">Ingresso:</label>
            <select id="ingresso_id" name="ingresso_id" class="ingresso-form-input" required>
                <option value="">SELECIONE UM INGRESSO PARA AUTORIZAR</option>
                <?php while($row = $resultIngressos->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>">
                        Evento: <?php echo htmlspecialchars($row['evento_nome']); ?> - Tipo: <?php echo htmlspecialchars($row['tipo_ingresso']); ?> - Preço: R$ <?php echo number_format($row['preco'], 2, ',', '.'); ?>
                    </option>
                <?php endwhile; ?>
            </select><br>
            <input type="submit" name="liberar" value="Autorizar Ingresso" class="ingresso-form-btn">
        </form>
    </div>

    <div class="authorized-ingressos-container">
        <h3 class="authorized-ingressos-title">Ingressos Autorizados</h3>
        <table class="authorized-ingressos-table">
            <tr class="authorized-ingressos-table-header">
                <th class="authorized-ingressos-table-column">Evento</th>
                <th class="authorized-ingressos-table-column">Tipo de Ingresso</th>
                <th class="authorized-ingressos-table-column">Preço</th>
                <th class="authorized-ingressos-table-column">Ação</th>
            </tr>
            <?php while ($row = $resultIngressosAutorizados->fetch_assoc()): ?>
                <tr class="authorized-ingressos-table-row">
                    <td class="authorized-ingressos-table-cell"><?php echo htmlspecialchars($row['evento_nome']); ?></td>
                    <td class="authorized-ingressos-table-cell"><?php echo htmlspecialchars($row['tipo_ingresso']); ?></td>
                    <td class="authorized-ingressos-table-cell">R$ <?php echo number_format($row['preco'], 2, ',', '.'); ?></td>
                    <td class="authorized-ingressos-table-cell">
                        <form action="gerenciar_vendas.php" method="post" style="display:inline;">
                            <input type="hidden" name="ingresso_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="desliberar" class="desautorizar-ingresso-btn">
                                <i class="fas fa-times"></i> Desautorizar
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
