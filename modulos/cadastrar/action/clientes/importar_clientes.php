<?php
header('Content-Type: application/json');

if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {
    $csvFile = $_FILES['csvFile']['tmp_name'];
    $handle = fopen($csvFile, 'r');

    if ($handle === false) {
        echo json_encode(['status' => 'error', 'error' => 'Não foi possível abrir o arquivo CSV.']);
        exit();
    }

    $headers = fgetcsv($handle, 0, ';'); // Separador de campos é o ponto e vírgula
    if ($headers === false) {
        echo json_encode(['status' => 'error', 'error' => 'Erro ao ler os cabeçalhos do arquivo CSV.']);
        exit();
    }

    $clientes = [];

    while (($data = fgetcsv($handle, 0, ';')) !== false) {
        if (count($data) === count($headers)) { // Certifica que o número de dados corresponde ao número de headers
            $cliente = array_combine($headers, $data);
            $clientes[] = $cliente;
        }
    }

    fclose($handle);

    if (empty($clientes)) {
        echo json_encode(['status' => 'error', 'error' => 'Nenhum cliente foi encontrado no arquivo CSV.']);
        exit();
    }

    // Converte o array de clientes para JSON
    $json = json_encode($clientes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // Salva o JSON em um arquivo
    $jsonFilePath = __DIR__ . '/clientes.json';
    if (file_put_contents($jsonFilePath, $json) === false) {
        echo json_encode(['status' => 'error', 'error' => 'Falha ao salvar o arquivo JSON.']);
        exit();
    }

    echo json_encode(['status' => 'success', 'message' => 'Clientes importados com sucesso.']);
} else {
    echo json_encode(['status' => 'error', 'error' => 'Nenhum arquivo CSV foi enviado ou ocorreu um erro no upload.']);
}
