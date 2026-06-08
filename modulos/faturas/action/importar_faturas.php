<?php
header('Content-Type: application/json');

use Smalot\PdfParser\Parser;
// ============================================================
// PDF
// ============================================================

/*
if (isset($_FILES['pdfFile']) && $_FILES['pdfFile']['error'] === UPLOAD_ERR_OK) {

    require_once __DIR__ . '/../../../vendor/autoload.php';


    $mime = mime_content_type($_FILES['pdfFile']['tmp_name']);
    if ($mime !== 'application/pdf') {
        echo json_encode(['status' => 'error', 'error' => 'O arquivo enviado não é um PDF válido.']);
        exit();
    }

    try {
        $parser = new Parser();
        $pdf    = $parser->parseFile($_FILES['pdfFile']['tmp_name']);
        $pages  = $pdf->getPages();

        $nomeFatura    = 'FATURA PROFORMA';
        $firstPageText = $pages[0]->getText();

        if (preg_match('/FATURA\s+PROFORMA\s+([\w\s\-\/]+)/i', $firstPageText, $mf)) {
            $nomeFatura = 'FATURA PROFORMA ' . trim(preg_replace('/\s+/', ' ', $mf[1]));
        }

        $unidades = 'UN|MT|RL|JG|PR|M2';
        $pattern  = '/^\d+\s+(BM\s[\d.]+)\s+[\d.]+\s+(.+?)\s+(' . $unidades . ')\s+(\d+)\s+[\d,]+\s+USS\s+([\d\s.,]+?)\s+[\d.,]+\s+USS/m';

        $produtos = [];

        foreach ($pages as $page) {
            $text  = $page->getText();
            $lines = explode("\n", $text);

            foreach ($lines as $line) {
                $line = trim($line);
                if (!preg_match($pattern, $line, $m)) continue;

                $precoRaw = preg_replace('/\s+/', '', $m[5]);
                $precoRaw = str_replace('.', '', $precoRaw);
                $precoRaw = str_replace(',', '.', $precoRaw);

                $produtos[] = [
                    'nomeFatura'    => $nomeFatura,
                    'codigo'        => trim($m[1]),
                    'descricao'     => trim($m[2]),
                    'unidadeMedida' => trim($m[3]),
                    'quantidade'    => trim($m[4]),
                    'preco'         => $precoRaw,
                ];
            }
        }

        if (empty($produtos)) {
            echo json_encode(['status' => 'error', 'error' => 'Nenhum item encontrado. Verifique se o PDF segue o padrão Brazmix.']);
            exit();
        }

        $jsonFilePath = __DIR__ . '/faturas.json';

        // Lê o que já existe no arquivo
        $existentes = [];
        if (file_exists($jsonFilePath)) {
            $conteudoAtual = file_get_contents($jsonFilePath);
            $existentes = json_decode($conteudoAtual, true) ?? [];
        }

        // Junta os existentes com os novos no final
        $todosProdutos = array_merge($existentes, $produtos);
        $json = json_encode($todosProdutos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (file_put_contents($jsonFilePath, $json) === false) {
            echo json_encode(['status' => 'error', 'error' => 'Falha ao salvar o arquivo JSON.']);
            exit();
        }

        echo json_encode([
            'status'  => 'success',
            'message' => count($produtos) . ' produtos importados com sucesso.',
            'count'   => count($produtos),
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'error' => 'Erro ao processar PDF: ' . $e->getMessage()]);
    }

// ============================================================
// CSV
// ============================================================
} else*/
    if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {

        $csvFile = $_FILES['csvFile']['tmp_name'];
        $handle  = fopen($csvFile, 'r');

        $taxaDolar  = $_POST['taxaDolar']  ?? '';
        $DolarRaw = str_replace(',', '.', $taxaDolar);
        $notaFiscal = $_POST['notaFiscal'] ?? '';

        if ($handle === false) {
            echo json_encode(['status' => 'error', 'error' => 'Não foi possível abrir o arquivo CSV.']);
            exit();
        }

        // Remove BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);

        $nomeFatura = 'FATURA PROFORMA';
        $headers    = null;
        $produtos   = [];

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            // Pega nome da fatura — linha que contém "JOHIL"
            foreach ($row as $cell) {
                if (preg_match('/JOHIL\s+(\d+)-(\d+)/i', trim($cell), $m)) {
                    $nomeFatura = 'FAT ' . '0' . $m[1] . '/20' . $m[2];
                    break;
                }
            }

            // Detecta a linha de headers — começa com "ITEM"
            if (trim($row[0]) === 'ITEM' && $headers === null) {
                $headers = array_map('trim', $row);
                continue;
            }

            // Só processa linhas de dados depois dos headers
            if ($headers === null) continue;

            // Para quando acabam os itens (linha de TOTAIS ou vazia)
            if (empty(trim($row[0])) || !is_numeric(trim($row[0]))) break;

            $combined = array_combine($headers, array_pad($row, count($headers), ''));

            // Limpa o preço: remove "USS", espaços, troca vírgula por ponto
            $precoRaw = preg_replace('/[^\d,]/', '', trim($combined['UNIT VALUE'] ?? $combined['PREÇO UNITÁRIO'] ?? ''));
            $precoRaw = str_replace(',', '.', $precoRaw);

            $produtos[] = [
                'nomeFatura'    => $nomeFatura,
                'notaFiscal'    => 'NF ' . $notaFiscal,
                'txDolar'       => $DolarRaw,
                'codigo'        => trim($combined['CODE']        ?? $combined['CÓDIGO']        ?? ''),
                'descricao'     => trim($combined['DESCRIPTION'] ?? $combined['DESCRIÇÃO']     ?? ''),
                'unidadeMedida' => trim($combined['UN.']         ?? ''),
                'quantidade'    => trim($combined['QTY']         ?? $combined['QTD']           ?? ''),
                'preco'         => $precoRaw,
            ];
        }

        fclose($handle);

        if (empty($produtos)) {
            echo json_encode(['status' => 'error', 'error' => 'Nenhum produto encontrado no CSV.']);
            exit();
        }

        // Junta com o que já existe no JSON
        $jsonFilePath  = __DIR__ . '/faturas.json';
        $existentes    = [];
        if (file_exists($jsonFilePath)) {
            $existentes = json_decode(file_get_contents($jsonFilePath), true) ?? [];
        }

        $todosProdutos = array_merge($existentes, $produtos);
        $json          = json_encode($todosProdutos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (file_put_contents($jsonFilePath, $json) === false) {
            echo json_encode(['status' => 'error', 'error' => 'Falha ao salvar o arquivo JSON.']);
            exit();
        }

        echo json_encode(['status' => 'success', 'message' => count($produtos) . ' produtos importados com sucesso.']);
    }
?>