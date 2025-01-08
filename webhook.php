<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Salvar logs de entrada
$logFile = '/var/www/html/webhook.log';
file_put_contents($logFile, "Webhook recebido: " . file_get_contents('php://input') . PHP_EOL, FILE_APPEND);

// Conexão com o Mikrotik
require('routeros_api.class.php');
$api = new RouterosAPI();

// Verificar conexão com Mikrotik
file_put_contents($logFile, "Tentando conectar ao Mikrotik..." . PHP_EOL, FILE_APPEND);
if ($api->connect('192.168.2.3', 'admin', '33868301')) {
    // Obter o MAC/IP do usuário
    $mac_user = $_GET['mac'] ?? null;
    $ip_user = $_GET['ip'] ?? null;

    if ($mac_user && $ip_user) {
        // Adicionar o usuário à lista de acesso
        $api->comm('/ip/hotspot/active/add', [
            'user' => 'pagante',
            'address' => $ip_user,
            'mac-address' => $mac_user,
        ]);
        file_put_contents($logFile, "Usuário $mac_user ($ip_user) adicionado ao Mikrotik." . PHP_EOL, FILE_APPEND);
    } else {
        file_put_contents($logFile, "MAC ou IP do usuário ausente: MAC=$mac_user, IP=$ip_user" . PHP_EOL, FILE_APPEND);
    }

    $api->disconnect();
} else {
    file_put_contents($logFile, "Erro ao conectar ao Mikrotik." . PHP_EOL, FILE_APPEND);
}

// Access Token do Mercado Pago
$access_token = "APP_USR-2271288988737632-010309-5350383d8b7ab9284edbbd90f335a205-147806192";

// Lê os dados enviados pelo webhook
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Salvar os dados do webhook para depuração
file_put_contents($logFile, "Dados recebidos: " . json_encode($data) . PHP_EOL, FILE_APPEND);

// Verifica se os dados foram recebidos corretamente
if (!$data) {
    http_response_code(400);
    echo "Dados inválidos.";
    file_put_contents($logFile, "Erro: Dados inválidos recebidos." . PHP_EOL, FILE_APPEND);
    exit;
}

// Verifica se a notificação contém o ID do pagamento
if (!isset($data['id'])) {
    http_response_code(400);
    echo "ID do pagamento não encontrado.";
    file_put_contents($logFile, "Erro: ID do pagamento não encontrado." . PHP_EOL, FILE_APPEND);
    exit;
}

// Obtém detalhes do pagamento na API do Mercado Pago
$payment_id = $data['id'];
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.mercadopago.com/v1/payments/{$payment_id}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $access_token"
    ],
]);

$response = curl_exec($curl);
curl_close($curl);

if (!$response) {
    http_response_code(500);
    echo "Erro ao consultar o pagamento.";
    file_put_contents($logFile, "Erro ao consultar os detalhes do pagamento para ID: $payment_id." . PHP_EOL, FILE_APPEND);
    exit;
}

$payment_details = json_decode($response, true);

// Verifica o status do pagamento
$status = $payment_details['status'] ?? null;

if ($status === 'approved') {
    // Pagamento aprovado: processe aqui
    http_response_code(200);
    echo "Pagamento aprovado e processado com sucesso.";
    file_put_contents($logFile, "Pagamento aprovado para ID: $payment_id. Ação processada com sucesso." . PHP_EOL, FILE_APPEND);
} else {
    // Status diferente de "approved"
    http_response_code(400);
    echo "Pagamento não aprovado.";
    file_put_contents($logFile, "Pagamento com ID: $payment_id não aprovado. Status: $status" . PHP_EOL, FILE_APPEND);
}

