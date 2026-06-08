<?php
// Função para limpar e tratar entradas
function limpar($str) {
    return trim(htmlspecialchars($str));
}

// Validação de campos obrigatórios (incluindo empresa_id)
$campos = ['empresa_id', 'codigo', 'descricao_pt', 'descricao_en', 'descricao_es', 'unidade', 'ncm', 'peso', 'preco'];
foreach ($campos as $campo) {
    if (empty($_POST[$campo])) {
        die("Erro: campo '$campo' é obrigatório.");
    }
}

// Coleta e sanitiza os dados
$produto = [
    'empresa_id'   => limpar($_POST['empresa_id']),
    'codigo'       => limpar($_POST['codigo']),
    'descricao_pt' => limpar($_POST['descricao_pt']),
    'descricao_en' => limpar($_POST['descricao_en']),
    'descricao_es' => limpar($_POST['descricao_es']),
    'unidade'      => limpar($_POST['unidade']),
    'ncm'          => limpar($_POST['ncm']),
    'peso'         => floatval($_POST['peso']),
    'preco'        => limpar($_POST['preco']),
];

// Tratamento da imagem
if (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
    die("Erro ao enviar a imagem.");
}

$ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
$permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($ext, $permitidas)) {
    die("Erro: formato de imagem inválido.");
}

// Diretório e nome da imagem
$dir = 'uploads/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$nomeImagem = uniqid('produto_', true) . "." . $ext;
$caminhoImagem = $dir . $nomeImagem;

if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $caminhoImagem)) {
    die("Erro ao salvar a imagem.");
}

$produto['imagem'] = $caminhoImagem;

// Caminho do arquivo JSON
$jsonPath = __DIR__ . '/produto_cadastrado.json';

// Lê dados existentes (se houver)
$produtos = [];
if (file_exists($jsonPath)) {
    $conteudo = file_get_contents($jsonPath);
    $produtos = json_decode($conteudo, true) ?? [];
}

// Adiciona o novo produto
$produtos[] = $produto;

// Salva de volta no JSON
if (file_put_contents($jsonPath, json_encode($produtos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo "Produto cadastrado com sucesso!";
} else {
    echo "Erro ao salvar os dados no arquivo JSON.";
}
?>
