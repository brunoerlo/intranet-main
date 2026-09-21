<?php
header('Content-Type: application/json');

require_once dirname(__DIR__, 3) . '/logger.php';

session_start();
$usuario = $_SESSION["usuario"]["nome"] ?? "Desconhecido";

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

    // Captura os itens que serão removidos (antes de filtrar)
    $itensRemovidos = array_filter($estoque, function ($item) use ($idsParaExcluir) {
        return in_array($item['Codigo estoque'] ?? '', $idsParaExcluir);
    });

    // Tenta excluir por "Codigo estoque" (estoque.json)
    $estoqueFiltrado = array_filter($estoque, function ($item) use ($idsParaExcluir) {
        return !in_array($item['Codigo estoque'] ?? '', $idsParaExcluir);
    });

    $totalRemovidos = $totalAntes - count($estoqueFiltrado);

    if ($totalRemovidos > 0) {
        $primeiroItem = reset($itensRemovidos);
        $faturaNome = $primeiroItem['nome'];
        $cliente = $primeiroItem['cliente'];

        if (isset($data['ids'])) {
            $mensagemLog = "Deletou a fatura inteira {$faturaNome} - {$cliente} ({$totalRemovidos} itens)";
        } else {
            $codigo = $primeiroItem['codigo'];
            $mensagemLog = "Deletou o item '{$codigo}' da fatura {$faturaNome} - {$cliente}";
        }

        if (file_put_contents($estoquePath, json_encode(array_values($estoqueFiltrado), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            registrarLog($usuario, 'faturas', 'estoque', 'deletar', $mensagemLog);
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

    echo json_encode(['success' => false, 'message' => 'Nenhum item encontrado com o(s) ID(s) fornecido(s).']);
    exit;
}

// Nenhum parâmetro válido
echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
