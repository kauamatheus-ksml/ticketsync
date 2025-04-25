<?php
// processa_pagamento_cartao.php  (SDK v2)
session_start();
require 'vendor/autoload.php';
require 'config_mp.php';     // define MP_ACCESS_TOKEN

use MercadoPago\SDK;
use MercadoPago\Payment;

// ------------------------------------------------------------------
// 1) Segurança
// ------------------------------------------------------------------
$body = json_decode(file_get_contents('php://input'), true);
$token           = $body['token']            ?? null;
$paymentMethodId = $body['paymentMethodId']  ?? null;
$installments    = (int)($body['installments'] ?? 1);

if (!$token || !$paymentMethodId){
    http_response_code(400);
    echo json_encode(['error'=>'Parâmetros ausentes']); exit;
}

// ------------------------------------------------------------------
// 2) Cria pagamento
// ------------------------------------------------------------------
SDK::setAccessToken(MP_ACCESS_TOKEN);

$payment = new Payment();
$payment->transaction_amount = (float) ($_SESSION['total_pedido'] ?? 0);
$payment->token              = $token;
$payment->installments       = $installments;
$payment->payment_method_id  = $paymentMethodId;
$payment->description        = "Ingressos – evento #{$_SESSION['evento_id']}";
$payment->payer = [
    "email" => $_SESSION['form_email'] ?? 'comprador@example.com',
    "first_name" => $_SESSION['form_nome'] ?? 'Comprador'
];
$payment->save();

// ------------------------------------------------------------------
// 3) Retorno
// ------------------------------------------------------------------
if ($payment->status === 'approved'){
    echo json_encode(['status'=>'approved','id'=>$payment->id]);
} else {
    http_response_code(402);
    echo json_encode([
        'status'=>$payment->status,
        'detail'=>$payment->status_detail
    ]);
}
