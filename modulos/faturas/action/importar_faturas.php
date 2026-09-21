<?php
header('Content-Type: application/json');

require_once dirname(__DIR__, 3) . '/logger.php';   

session_start();
$usuario = $_SESSION["usuario"]["nome"] ?? "Desconhecido";

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

        $nomeFatura      = 'FATURA PROFORMA';
        $numeroFaturaRaw = null; // número cru da fatura (ex: "449"), usado depois pra achar no quadro/estoque
        $headers         = null;
        $produtos        = [];

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            // Pega o número da fatura — célula isolada no formato "449-26"
            // (aceita opcionalmente um prefixo tipo "JOHIL 449-26")
            foreach ($row as $cell) {
                $cellTrim = trim($cell);
                if ($cellTrim === '') continue;
                if (preg_match('/^(?:[A-Z]+\s+)?(\d{1,4})-(\d{2,4})$/i', $cellTrim, $m)) {
                    $nomeFatura      = 'FAT ' . '0' . $m[1] . '/20' . $m[2];
                    $numeroFaturaRaw = $m[1];
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

        // ============================================================
        // Remove do quadro.json e do estoque.json a fatura que acabou
        // de ser importada, casando pelo número cru (ex: "449")
        // ============================================================
        $removidoQuadro  = 0;
        $removidoEstoque = 0;

        if (!empty($numeroFaturaRaw)) {
            $removidoQuadro  = removerCorrespondencias(__DIR__ . '/quadro.json', 'nomeFatura', $numeroFaturaRaw);
            $removidoEstoque = removerCorrespondencias(__DIR__ . '/estoque.json', 'nome', $numeroFaturaRaw);
        }

        $mensagem = count($produtos) . ' produtos importados com sucesso.';
        if ($removidoQuadro > 0 || $removidoEstoque > 0) {
            $mensagem .= " ({$removidoQuadro} item(ns) removido(s) do quadro, {$removidoEstoque} do estoque)";
        } elseif (empty($numeroFaturaRaw)) {
            $mensagem .= ' Aviso: não foi possível identificar o número da fatura no CSV, nada foi removido do quadro/estoque.';
        }
        registrarLog($usuario, 'faturas', 'lista', 'importar', "Importou a fatura {$nomeFatura}");
        echo json_encode(['status' => 'success', 'message' => $mensagem]);
    }

/**
 * Remove do arquivo JSON informado todos os registros cujo campo
 * indicado corresponda ao número da fatura (comparação exata como
 * string, e também numérica pra ignorar zeros à esquerda, ex:
 * "001" === "1").
 *
 * @return int Quantidade de registros removidos.
 */
function removerCorrespondencias(string $caminhoJson, string $campo, string $numeroFaturaRaw): int
{
    if (!file_exists($caminhoJson)) {
        return 0;
    }

    $itens = json_decode(file_get_contents($caminhoJson), true) ?? [];
    $totalAntes = count($itens);

    $itensFiltrados = array_filter($itens, function ($item) use ($campo, $numeroFaturaRaw) {
        $valor = trim((string)($item[$campo] ?? ''));

        if ($valor === $numeroFaturaRaw) {
            return false; // remove (igual como string)
        }
        if (is_numeric($valor) && is_numeric($numeroFaturaRaw) && (int)$valor === (int)$numeroFaturaRaw) {
            return false; // remove (igual numericamente, ex: "001" vs "1")
        }
        return true; // mantém
    });

    $totalRemovidos = $totalAntes - count($itensFiltrados);

    if ($totalRemovidos > 0) {
        file_put_contents($caminhoJson, json_encode(array_values($itensFiltrados), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    return $totalRemovidos;
}
?>