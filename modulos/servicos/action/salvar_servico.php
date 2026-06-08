<?php
header('Content-Type: application/json');

// Caminho para o arquivo JSON
$arquivo = __DIR__ . '/servicos.json';

// Coleta os dados do formulário
$nome = $_POST['nome'] ?? '';
$descricao = $_POST['descricao'] ?? '';
$preco = $_POST['preco'] ?? '';
$empresa_id = $_POST['empresa_id'] ?? '';
$codigo = $_POST['codigo'] ?? '';
$descricao_en = $_POST['descricao_en'] ?? '';
$descricao_es = $_POST['descricao_es'] ?? '';
$unidade = $_POST['unidade'] ?? '';


if (!$nome || !$descricao || !$preco || !$empresa_id) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit;
}

// Gera um ID único
$id = uniqid('serv_');

// Carrega os serviços existentes
$servicos = file_exists($arquivo) ? json_decode(file_get_contents($arquivo), true) : [];

// Adiciona o novo serviço
$servicos[] = [
    'id' => $id,
    'empresa_id' => (int)$empresa_id,
    'codigo' => $codigo,
    'nome' => $nome,
    'descricao' => $descricao,
    'descricao_en' => $descricao_en,
    'descricao_es' => $descricao_es,
    'unidade' => $unidade,
    'preco' => (float)$preco
];


// Salva de volta no arquivo
if (file_put_contents($arquivo, json_encode($servicos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar o arquivo.']);
}
?>
