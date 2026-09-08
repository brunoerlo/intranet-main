<?php

header('Content-Type: application/json');

$faturasPath = __DIR__ . '/faturas.json';

// Verifica se os arquivos existem
if (!file_exists($faturasPath)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo(s) de faturas não encontrado(s).']);
    exit;
}

// Carrega os dados do faturas JSON
$faturas = json_decode(file_get_contents($faturasPath), true);

// Recebe os dados do corpo da requisição
$data = json_decode(file_get_contents("php://input"), true);

// Verifica se o ID ou nomeFatura foi enviado
$faturaId = $data['nomeFatura'] ?? $data['id'] ?? null;

if (!$faturaId) {
    echo json_encode(['success' => false, 'message' => 'ID da fatura não fornecido.']);
    exit;
}

// === FLUXO 1: Deleta do quadro ===
$faturasFiltrado = array_filter($faturas, function ($fatura) use ($faturaId) {
    return $fatura["nomeFatura"] !== $faturaId;
});

if (count($faturasFiltrado) < count($faturas)) {
    if (file_put_contents($faturasPath, json_encode(array_values($faturasFiltrado), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo json_encode(['success' => true, 'message' => 'Fatura deletada do quadro.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar fatura.']);
    }
    exit;
}

// Nenhuma fatura foi encontrada
echo json_encode(['success' => false, 'message' => 'Fatura não encontrada.']);

/*
// Se não encontrou no estoque, tenta no faturas.json (compatibilidade com faturas.php)
    $faturasImportadasPath = __DIR__ . '/faturas.json';
    if (file_exists($faturasImportadasPath)) {
        $faturasImportadas = json_decode(file_get_contents($faturasImportadasPath), true);
        $faturaId = $idsParaExcluir[0]; // faturas.php envia um único id (nomeFatura)

        $faturasFiltradasImportadas = array_filter($faturasImportadas, function ($fatura) use ($faturaId) {
            return $fatura["nomeFatura"] !== $faturaId;
        });

        if (count($faturasFiltradasImportadas) < count($faturasImportadas)) {
            if (file_put_contents($faturasImportadasPath, json_encode(array_values($faturasFiltradasImportadas), JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true, 'message' => 'Fatura deletada.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir faturas.']);
            }
            exit;
        }
    }
*/