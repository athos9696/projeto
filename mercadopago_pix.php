<?php
// Access Token do Mercado Pago
$access_token = "APP_USR-2271288988737632-010309-5350383d8b7ab9284edbbd90f335a205-147806192";

// Verifica se o valor foi passado via GET
$valor = isset($_GET['valor']) ? number_format($_GET['valor'], 2, '.', '') : null;

if (!$valor) {
    echo "<h1>Erro: Nenhum valor informado para o pagamento.</h1>";
    exit;
}

// Gera uma chave idempotente única para a requisição
$idempotency_key = uniqid('pix_', true);

// Configuração dos dados do pagamento
$payload = [
    "transaction_amount" => (float)$valor,
    "description" => "Pagamento via Pix",
    "payment_method_id" => "pix",
    "payer" => [
        "email" => "cliente@exemplo.com",
        "first_name" => "Cliente",
        "last_name" => "Teste"
    ]
];

// Configura a requisição para a API do Mercado Pago
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.mercadopago.com/v1/payments",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $access_token",
        "Content-Type: application/json",
        "X-Idempotency-Key: $idempotency_key"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
]);

$response = curl_exec($curl);
curl_close($curl);

if (!$response) {
    echo "<h1>Erro na comunicação com o Mercado Pago.</h1>";
    exit;
}

// Decodifica a resposta da API
$response_data = json_decode($response, true);

// Verifica se o QR Code foi gerado com sucesso
if (isset($response_data['point_of_interaction']['transaction_data']['qr_code_base64']) &&
    isset($response_data['point_of_interaction']['transaction_data']['qr_code'])) {

    $qr_code_base64 = $response_data['point_of_interaction']['transaction_data']['qr_code_base64'];
    $qr_code_text = $response_data['point_of_interaction']['transaction_data']['qr_code']; // Chave Pix

    echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Pagamento via Pix</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            margin-top: 50px;
            padding: 20px;
            background: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            border-radius: 8px;
        }
        img {
            width: 100%;
            max-width: 300px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 20px;
            font-size: 0.9em;
            color: #555;
        }
        .footer a {
            color: #007BFF;
            text-decoration: none;
        }
        .chave-pix {
            font-size: 1em;
            margin: 20px 0;
            color: #007BFF;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Pagamento via Pix</h1>
        <p>Valor: <strong>R$ {$valor}</strong></p>
        <img src='data:image/png;base64,{$qr_code_base64}' alt='QR Code Pix'>
        <p class='chave-pix'>Chave Pix: <strong>{$qr_code_text}</strong></p>
        <p>Escaneie o QR Code ou copie a chave Pix acima para realizar o pagamento.</p>
        <p class='footer'>Voltar para <a href='/'>página inicial</a></p>
    </div>
</body>
</html>";
} else {
    echo "<h1>Erro ao gerar QR Code Pix.</h1>";
    echo "<pre>";
    print_r($response_data);
    echo "</pre>";
}
?>
                                                        
















































































































































































