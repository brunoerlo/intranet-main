<?php

header('Content-Type: application/json');

require_once dirname(__DIR__, 4) . '/logger.php';   

session_start();
$usuario = $_SESSION["usuario"]["nome"];

$produtosImportadosPath = __DIR__ . '/produtos.json';
$produtosCadastradosPath = __DIR__ . '/produto_cadastrado.json';

// Verifica se os arquivos existem
if (!file_exists($produtosImportadosPath) || !file_exists($produtosCadastradosPath)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo(s) de produtos não encontrado(s).']);
    exit;
}

// Carrega os dados dos dois arquivos JSON
$produtosImportados = json_decode(file_get_contents($produtosImportadosPath), true);
$produtosCadastrados = json_decode(file_get_contents($produtosCadastradosPath), true);

// Recebe os dados do corpo da requisição
$data = json_decode(file_get_contents("php://input"), true);

// Verifica se o ID foi enviado
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do produto não fornecido.']);
    exit;
}

$produtoId = $data['id'];

// === FLUXO 1: Deleta do produtos importados ===

// Captura o produto antes de filtrar
$itemDeletado = null;
foreach ($produtosImportados as $produto) {
    if (($produto['Item'] ?? '') === $produtoId) {
        $itemDeletado = $produto;
        break;
    }
}

$produtosFiltradosImportados = array_filter($produtosImportados, function ($produto) use ($produtoId) {
    return ($produto["Item"] ?? '') !== $produtoId;
});

if (count($produtosFiltradosImportados) < count($produtosImportados)) {
    if (file_put_contents($produtosImportadosPath, json_encode(array_values($produtosFiltradosImportados), JSON_PRETTY_PRINT))) {
        registrarLog($usuario, 'cadastrar', 'produtos', 'deletar', "Deletou o produto {$itemDeletado['Descricao']}");
        echo json_encode(['success' => true, 'message' => 'produto deletado dos importados.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar produtos importados.']);
    }
    exit;
}

// === FLUXO 2: Deleta do produtos cadastrados ===

// Captura o produto antes de filtrar
$itemDeletado = null;
foreach ($produtosCadastrados as $produto) {
    if ($produto['codigo'] === $produtoId) {
        $itemDeletado = $produto;
        break;
    }
}

$produtosFiltradosCadastrados = array_filter($produtosCadastrados, function ($produto) use ($produtoId) {
    return $produto["codigo"] !== $produtoId;
});

if (count($produtosFiltradosCadastrados) < count($produtosCadastrados)) {
    if (file_put_contents($produtosCadastradosPath, json_encode(array_values($produtosFiltradosCadastrados), JSON_PRETTY_PRINT))) {
        registrarLog($usuario, 'cadastrar', 'produtos', 'deletar', "Deletou o produto {$itemDeletado['descricao']}");
        echo json_encode(['success' => true, 'message' => 'Produto deletado dos cadastrados.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar produtos cadastrados.']);
    }
    exit;
}

// Nenhum produto foi encontrado
echo json_encode(['success' => false, 'message' => 'Produto não encontrado em nenhum dos arquivos.']);
