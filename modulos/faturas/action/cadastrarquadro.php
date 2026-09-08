<?php
header('Content-Type: application/json');

// Define o caminho para o arquivo JSON
$file_path = __DIR__ . '/quadro.json';

// Recebe os dados enviados via POST
$data = json_decode(file_get_contents('php://input'), true);

// Verifica se os dados foram recebidos corretamente
if (isset($data['nomeFatura']) && isset($data['cliente']) && isset($data['porto'])) {
    
    // Lê os dados existentes do arquivo JSON
    if(file_exists($file_path)) {
        $json_data = file_get_contents($file_path);    
        $quadro = json_decode($json_data, true);
    }
    else {
        $quadro = [];
    }

    // Gera um ID único para o registro
    $ids = array_column($quadro, 'id');
    $novoId = count($ids) > 0 ? max($ids) + 1 : 1;

    // Atribui o ID ao novo registro
    $data['id'] = $novoId;

    // Adiciona o novo registro aos dados
    $quadro[] = $data;
        
    // Salva os dados de volta no arquivo JSON
    if (file_put_contents($file_path, json_encode($quadro, JSON_PRETTY_PRINT))) {
        // Retorna uma resposta de sucesso
        echo json_encode([
            'success' => true,
            'id' => $novoId
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
