<?php
// Carregar as informações de configuração do banco de dados e do token
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$db = getenv('DB_NAME');
$accessToken = getenv('ACCESS_TOKEN');

// Verifica se a requisição é POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(400);
    echo "Método de requisição não suportado. Utilize POST.";
    return;
}

// Verifica se o 'id' e 'topic' estão presentes
if (!isset($_GET['id']) || !isset($_GET['topic'])) {
    http_response_code(400);
    echo "Parâmetros 'id' ou 'topic' ausentes.";
    return;
}

// Verifica se o tópico é de pagamento
if ($_GET['topic'] != "payment") {
    http_response_code(400);
    echo "Este endpoint lida apenas com notificações de pagamento.";
    return;
}

$id = $_GET['id'];

// Faz a requisição para a API do Mercado Pago para obter os detalhes do pagamento
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api.mercadopago.com/v1/payments/' . $id,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $accessToken
    ),
));

$response = curl_exec($curl);
curl_close($curl);

$payment = json_decode($response, true);

if ($payment["status"] === "approved") {
    // Conecta ao banco de dados MySQL
    $conn = new mysqli($host, $user, $password, $db);

    if ($conn->connect_error) {
        http_response_code(500);
        echo "Erro ao conectar ao banco de dados: " . $conn->connect_error;
        return;
    }

    // Pega o jogador relacionado ao pagamento
    $player = $payment["external_reference"];

    // Insere um registro na tabela de pendências
    $insertSql = "INSERT INTO autopix_pendings (id, player) VALUES (?, ?)";
    
    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param("ss", $id, $player);

    if ($stmt->execute()) {
        http_response_code(201);
        echo "Pagamento aprovado e salvo com sucesso.";
    } else {
        http_response_code(500);
        echo "Erro ao salvar informações de pagamento.";
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(400);
    echo "Pagamento não aprovado.";
}

?>
