<?php
header('Content-Type: application/json');

$quadroPath = __DIR__ . '/quadro.json';

// Verifica se os arquivos existem
if (!file_exists($quadroPath)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo de quadro não encontrado.']);
    exit;
}

// Carrega os arquivos JSON
$quadro = json_decode(file_get_contents($quadroPath), true);

// Recebe os dados do corpo da requisição (suporta FormData/POST e JSON raw)
$inputRaw = json_decode(file_get_contents("php://input"), true);
$data = !empty($_POST) ? $_POST : ($inputRaw ?? []);

// Verifica se os dados foram enviados corretamente
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do quadro não especificado.']);
    exit;
}

$id = $data['id'];
$encontrado = false;

//Atualiza fatura do estoque selecionado ===

foreach ($quadro as &$q) {
    if (($q['id'] ?? '') == $id) {
        if (isset($data['nomeFatura']))       $q['nomeFatura']       = $data['nomeFatura'];
        if (isset($data['cliente']))     $q['cliente']     = $data['cliente'];
        if (isset($data['nomeConsolidado']))  $q['nomeConsolidado']  = $data['nomeConsolidado'];
        if (isset($data['data'])) $q['data'] = $data['data'];
        if (isset($data['ctr'])) $q['ctr'] = $data['ctr'];
        if (isset($data['porto'])) $q['porto'] = $data['porto'];
        if (isset($data['booking'])) $q['booking'] = $data['booking'];

        $encontrado = true;
        break;
    }
}
unset($q);

if (!$encontrado) {
    echo json_encode(['success' => false, 'message' => 'Registro não encontrado.']);
    exit;
}

if (file_put_contents($quadroPath, json_encode($quadro, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Quadro atualizado com sucesso.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Falha ao salvar o arquivo.']);
}
