<?php
//pixConfigWebhook.php
if (file_exists($autoload = realpath(__DIR__ . "/../../../vendor/autoload.php"))) {
	require_once $autoload;
} else {
	print_r("Autoload not found or on path <code>$autoload</code>");
}

use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;

if (file_exists($options = realpath(__DIR__ . "/../../credentials/options.php"))) {
	require_once $options;
}

$options["headers"] = [
	"x-skip-mtls-checking" => "false",
];

$params = [
	"chave" => "16c2277e-04b9-49c7-ac40-44cdeb91f0c1"
];

$body = [
	"webhookUrl" => "https://italiadocconstrutora.com/webhook/"
];

try {
	$api = Gerencianet::getInstance($options);
	$response = $api->pixConfigWebhook($params, $body);

	print_r("<pre>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</pre>");
} catch (GerencianetException $e) {
	print_r($e->code);
	print_r($e->error);
	print_r($e->errorDescription);
} catch (Exception $e) {
	print_r($e->getMessage());
}
