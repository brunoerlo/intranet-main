<?php
// Define o caminho para o arquivo JSON
$file_path = 'estoque.json';

// Recebe os dados enviados via POST
$data = json_decode(file_get_contents('php://input'), true);

// Verifica se os dados foram recebidos corretamente
if (isset($data['descricao']) && isset($data['quantidade'])) {
    // Gera um ID único para o estoque
    $data['Codigo estoque'] = uniqid('cli_', true); // Ex: cli_66147087a7f79.15609252

    // Lê os dados existentes do arquivo JSON
    if (file_exists($file_path)) {
        $json_data = file_get_contents($file_path);
        $estoque = json_decode($json_data, true);
    } else {
        $estoque = [];
    }

    // Adiciona o novo estoque aos dados
    $estoque[] = $data;

    // Salva os dados de volta no arquivo JSON
    if (file_put_contents($file_path, json_encode($estoque, JSON_PRETTY_PRINT))) {
        // Retorna uma resposta de sucesso
        echo json_encode([
            'success' => true,
            'codigo_estoque' => $data['Codigo estoque']
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
