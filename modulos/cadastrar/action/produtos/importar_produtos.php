<?php
header('Content-Type: application/json');

// Verifica se o arquivo CSV foi enviado
if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {
    // Obtém o arquivo CSV
    $csvFile = $_FILES['csvFile']['tmp_name'];
    $handle = fopen($csvFile, 'r');

    if ($handle === false) {
        echo json_encode(['status' => 'error', 'error' => 'Não foi possível abrir o arquivo CSV.']);
        exit();
    }

    // Lê os cabeçalhos do arquivo CSV
    $headers = fgetcsv($handle, 0, ';'); // Separador de campos é o ponto e vírgula
    if ($headers === false) {
        echo json_encode(['status' => 'error', 'error' => 'Erro ao ler os cabeçalhos do arquivo CSV.']);
        exit();
    }

    // Obtém o empresa_id via POST
    if (!isset($_POST['empresa_id']) || empty($_POST['empresa_id'])) {
        echo json_encode(['status' => 'error', 'error' => 'O campo empresa_id é obrigatório.']);
        exit();
    }
    $empresa_id = $_POST['empresa_id']; // Valor enviado via POST

    $produtos = [];

    // Lê as linhas do arquivo CSV e cria os produtos
    while (($data = fgetcsv($handle, 0, ';')) !== false) {
        if (count($data) === count($headers)) { // Certifica que o número de dados corresponde ao número de headers
            $produto = array_combine($headers, $data);

            // Adiciona o empresa_id a cada produto
            $produto['empresa_id'] = $empresa_id;

            $produtos[] = $produto;
        }
    }

    fclose($handle);

    // Verifica se há produtos
    if (empty($produtos)) {
        echo json_encode(['status' => 'error', 'error' => 'Nenhum produto foi encontrado no arquivo CSV.']);
        exit();
    }

    // Converte o array de produtos para JSON
    $json = json_encode($produtos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // Salva o JSON em um arquivo
    $jsonFilePath = __DIR__ . '/produtos.json';
    if (file_put_contents($jsonFilePath, $json) === false) {
        echo json_encode(['status' => 'error', 'error' => 'Falha ao salvar o arquivo JSON.']);
        exit();
    }

    echo json_encode(['status' => 'success', 'message' => 'produtos importados com sucesso.']);
} else {
    echo json_encode(['status' => 'error', 'error' => 'Nenhum arquivo CSV foi enviado ou ocorreu um erro no upload.']);
}
?>
