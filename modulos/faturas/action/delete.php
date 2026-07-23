<?php

header('Content-Type: application/json');

// Recebe os dados do corpo da requisição
$data = json_decode(file_get_contents("php://input"), true);

// ============================================================
// FLUXO 1: Exclusão do estoque.json (por "Codigo estoque")
// Suporta exclusão individual (id) ou em lote (ids)
// ============================================================
if (isset($data['ids']) || (isset($data['id']) && !isset($data['id_tipo']))) {
    $estoquePath = __DIR__ . '/estoque.json';

    if (!file_exists($estoquePath)) {
        echo json_encode(['success' => false, 'message' => 'Arquivo de estoque não encontrado.']);
        exit;
    }

    $estoque = json_decode(file_get_contents($estoquePath), true);

    $idsParaExcluir = [];
    if (isset($data['ids']) && is_array($data['ids'])) {
        $idsParaExcluir = $data['ids'];
    } elseif (isset($data['id'])) {
        $idsParaExcluir = [$data['id']];
    }

    if (empty($idsParaExcluir)) {
        echo json_encode(['success' => false, 'message' => 'ID(s) do item não fornecido(s).']);
        exit;
    }

    $totalAntes = count($estoque);

    // Tenta excluir por "Codigo estoque" (estoque.json)
    $estoqueFiltrado = array_filter($estoque, function ($item) use ($idsParaExcluir) {
        return !in_array($item['Codigo estoque'] ?? '', $idsParaExcluir);
    });

    $totalRemovidos = $totalAntes - count($estoqueFiltrado);

    if ($totalRemovidos > 0) {
        if (file_put_contents($estoquePath, json_encode(array_values($estoqueFiltrado), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode([
                'success' => true,
                'message' => $totalRemovidos . ' item(ns) excluído(s) com sucesso.',
                'removidos' => $totalRemovidos
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar arquivo após exclusão.']);
        }
        exit;
    }

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

    echo json_encode(['success' => false, 'message' => 'Nenhum item encontrado com o(s) ID(s) fornecido(s).']);
    exit;
}

// Nenhum parâmetro válido
echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
