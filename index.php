<?php
// Gerar QR Code Pix dinâmico

// Dados Pix (substitua pelas suas informações)
$chave_pix = 61992183232;
$nome_recebedor = "Brasília Telefonia";
$cidade = "Brasília";
$valor = isset($_GET['valor']) ? number_format($_GET['valor'], 2, '.', '') : "10.00";
$identificador = "04924055140"; // Identificador único (ex.: CPF ou IP)

// Criar o Payload Pix
$payload = "000201"
    . "26580014br.gov.bcb.pix" // ID do Pix no Brasil
    . "0136" . $chave_pix      // Chave Pix
    . "52040000"
    . "5303986"                // Moeda (BRL)
    . "540" . str_pad(strlen($valor), 2, '0', STR_PAD_LEFT) . $valor
    . "5802BR"                 // País
    . "590" . str_pad(strlen($nome_recebedor), 2, '0', STR_PAD_LEFT) . $nome_recebedor
    . "600" . str_pad(strlen($cidade), 2, '0', STR_PAD_LEFT) . $cidade
    . "62070503" . $identificador
    . "6304"; // CRC

// Função para calcular o CRC16
function crc16($str) {
    $crc = 0xFFFF;
    for ($x = 0; $x < strlen($str); $x++) {
        $crc ^= ord($str[$x]) << 8;
        for ($y = 0; $y < 8; $y++) {
            if (($crc & 0x8000) != 0) {
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc <<= 1;
            }
        }
    }
    return strtoupper(dechex($crc & 0xFFFF));
}

// Adiciona o CRC ao payload
$payload .= crc16($payload);

// Gerar QR Code
echo "<h2>Pagamento Pix</h2>";
echo "<p>Valor: R$ {$valor}</p>";
echo "<img src='https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=$payload' alt='QR Code'>";
echo "<p>Chave Pix: $chave_pix</p>";
?>

