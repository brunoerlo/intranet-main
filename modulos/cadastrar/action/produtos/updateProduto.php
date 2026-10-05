<?php

$produtosImportadosPath = __DIR__ . '/produtos.json';
$produtosCadastradosPath = __DIR__ . '/produto_cadastrado.json';

require_once dirname(__DIR__, 4) . '/logger.php';   

session_start();
$usuario = $_SESSION["usuario"]["nome"];

// Verifica se os arquivos existem
if (!file_exists($produtosImportadosPath) || !file_exists($produtosCadastradosPath)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo(s) de produtos não encontrado(s).']);
    exit;
}

// Carrega os arquivos JSON
$produtosImportados = json_decode(file_get_contents($produtosImportadosPath), true);
$produtosCadastrados = json_decode(file_get_contents($produtosCadastradosPath), true);

// Verifica se os dados foram enviados corretamente
if (!isset($_POST['item'])) {
    echo json_encode(['success' => false, 'message' => 'Código do produto não especificado.']);
    exit;
}

$codigoproduto = $_POST['item'];
$produtoImportadoEncontrado = false;

// === FLUXO 1: Atualiza produto importado ===
if ($codigoproduto) {
    $itemEditado = null;
    foreach ($produtosImportados as &$produto) {
        if (($produto['Item'] ?? '') === $codigoproduto) {
            // Mapeia os campos do formulário para os nomes exatos do JSON de Importados
            if (isset($_POST['descricao'])) $produto['Descricao'] = $_POST['descricao'];
            if (isset($_POST['descComp'])) $produto['Descricao Complementar'] = $_POST['descComp'];
            if (isset($_POST['unidade'])) $produto['UM'] = $_POST['unidade'];
            if (isset($_POST['preco'])) $produto['Preco Venda'] = $_POST['preco'];
            if (isset($_POST['ncm'])) $produto['NCM'] = $_POST['ncm'];
            if (isset($_POST['peso'])) $produto['peso'] = $_POST['peso'];
            if (isset($_POST['empresa_id'])) $produto['empresa_id'] = $_POST['empresa_id'];

            $itemEditado = $produto;
            $produtoImportadoEncontrado = true;
            break;
        }
    }
    unset($produto);

    if ($produtoImportadoEncontrado) {
        if (file_put_contents($produtosImportadosPath, json_encode($produtosImportados, JSON_PRETTY_PRINT))) {
            $nomeLog = $itemEditado['Descricao'] ?? 'Produto sem descrição';
            registrarLog($usuario, 'cadastrar', 'produtos', 'editar', "Editou o produto {$nomeLog}");
            echo json_encode(['success' => true, 'message' => 'produto importado atualizado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao salvar o arquivo de produtos importados.']);
        }
        exit;
    }
}

// === FLUXO 2: Atualiza produto cadastrado ===
if ($codigoproduto) {
    $produtoCadastradoEncontrado = false;
    $itemEditado = null;

    foreach ($produtosCadastrados as &$produto) {
        if ($produto['codigo'] === $codigoproduto) {
            // Mapeia os campos do formulário para os nomes do JSON de Cadastrados
            if (isset($_POST['descricao'])) $produto['descricao'] = $_POST['descricao'];
            if (isset($_POST['descComp'])) $produto['descComp'] = $_POST['descComp'];
            if (isset($_POST['unidade'])) $produto['unidade'] = $_POST['unidade'];
            if (isset($_POST['preco'])) $produto['preco'] = $_POST['preco'];
            if (isset($_POST['ncm'])) $produto['ncm'] = $_POST['ncm'];
            if (isset($_POST['peso'])) $produto['peso'] = $_POST['peso'];
            if (isset($_POST['empresa_id'])) $produto['empresa_id'] = $_POST['empresa_id'];

            $itemEditado = $produto;
            $produtoCadastradoEncontrado = true;
            break;
        }
    }
    unset($produto);

    if ($produtoCadastradoEncontrado) {
        if (file_put_contents($produtosCadastradosPath, json_encode($produtosCadastrados, JSON_PRETTY_PRINT))) {
            $nomeLog = $itemEditado['descricao'];
            registrarLog($usuario, 'cadastrar', 'produtos', 'editar', "Editou o produto {$nomeLog}");
            echo json_encode(['success' => true, 'message' => 'Produto cadastrado atualizado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao salvar o arquivo de produtos cadastrados.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Produto não encontrado em nenhum dos fluxos.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Código do produto cadastrado não informado.']);
}
