<?php
session_start();

$client_id = '3202249576906512'; // Substitua pelo seu Client ID
$redirect_uri = 'https://ticketsync.com.br/mp_callback.php'; // URL registrada no Mercado Pago

// Monta a URL de autorização do Mercado Pago
$auth_url = 'https://auth.mercadopago.com.br/authorization'
          . '?response_type=code'
          . '&client_id=' . $client_id
          . '&redirect_uri=' . urlencode($redirect_uri);

header("Location: " . $auth_url);
exit();
?>
