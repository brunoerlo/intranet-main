<?php
header('Content-Type: application/json');

// Define o caminho para o arquivo JSON
$file_path = __DIR__ . '/clientes_cadastrados.json';

require_once dirname(__DIR__, 4) . '/logger.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$usuario = $_SESSION["usuario"]["nome"] ?? 'desconhecido';

// Recebe os dados enviados via POST
$data = json_decode(file_get_contents('php://input'), true);

// Verifica se os dados foram recebidos corretamente
if (isset($data['tipo']) && isset($data['email']) && isset($data['telefone'])) {
    // Gera um ID único para o cliente
    $data['Codigo Cliente'] = uniqid('cli_', true); // Ex: cli_66147087a7f79.15609252

    // Lê os dados existentes do arquivo JSON
    if (file_exists($file_path)) {
        $json_data = file_get_contents($file_path);
        $clientes = json_decode($json_data, true);
    } else {
        $clientes = [];
    }

    // Adiciona o novo cliente aos dados
    $clientes[] = $data;

    // Salva os dados de volta no arquivo JSON
    if (file_put_contents($file_path, json_encode($clientes, JSON_PRETTY_PRINT))) {
        registrarLog($usuario, 'cadastrar', 'clientes', 'cadastrar', "Cadastrou o cliente {$data['nomeCompleto']}");
        // Retorna uma resposta de sucesso
        echo json_encode([
            'success' => true,
            'codigo_cliente' => $data['Codigo Cliente']
        ]);
    } else {
        // Retorna uma resposta de erro
        echo json_encode(['success' => false, 'error' => 'Não foi possível salvar os dados.']);
    }
} else {
    // Retorna erro caso os dados não sejam válidos
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
}
?>
