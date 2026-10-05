<?php
$estoqueJson = file_get_contents(__DIR__ . '/action/estoque.json');
$estoque = json_decode($estoqueJson, true) ?? [];

$faturasBotoes = [];
foreach ($estoque as $e) {
    $nome = trim($e['nome'] ?? '');
    $cliente = trim($e['cliente'] ?? '');
    if ($nome === '' || strtolower($nome) === 'sobra') {
        continue;
    }

    if ($cliente === 'JOHIL') {
        $label = $nome;
    } else {
        $label = $cliente !== '' ? "{$nome} - {$cliente}" : $nome;
    }

    if (!in_array($label, $faturasBotoes, true)) {
        $faturasBotoes[] = $label;
    }
}
sort($faturasBotoes);

// Monta estrutura: descricao => [ _codigo => ..., quantidades => [ nome => qtd ], itens => [...] ]
$tabela = [];
foreach ($estoque as $e) {
    $cod  = $e['codigo']    ?? '';
    $cliente = $e['cliente'] ?? '';
    $desc = $e['descricao'] ?? '';
    $nome = $e['nome']      ?? '';
    $qtd  = (int)($e['quantidade'] ?? 0);
    $id   = $e['Codigo estoque'] ?? '';

    if ($cliente === 'JOHIL') {
        $label = $nome;
    } else {
        $label = ($cliente !== '' && strtolower($nome) !== 'sobra') ? "{$nome} - {$cliente}" : $nome;
    }
    if (!isset($tabela[$desc])) {
        $tabela[$desc] = ['_codigo' => $cod, 'quantidades' => [], 'itens' => []];
    }
    $tabela[$desc]['quantidades'][$label] = $qtd;
    // Guarda cada item individual para edição/exclusão
    $tabela[$desc]['itens'][] = [
        'id'         => $id,
        'nome'       => $nome,
        'cliente'    => $cliente,
        'label'      => $label,
        'codigo'     => $cod,
        'descricao'  => $desc,
        'quantidade' => $qtd
    ];
}
ksort($tabela);
?>
<!-- Formulário -->
<div class="container-fluid mt-4">
    <button class="btn btn-success mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEstoque" aria-expanded="false" aria-controls="collapseEstoque">
        <i class="fa-solid fa-plus"></i> Novo Estoque
    </button>
    <div class="collapse" id="collapseEstoque">
        <div class="card card-body">
            <h4>NOVO ESTOQUE</h4>
            <form id="formCadastroEstoque">
                <div class="tipo-grupo">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome da Fatura</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="cliente" class="form-label">Cliente</label>
                        <input type="text" class="form-control" id="cliente" name="cliente" required>
                    </div>
                    <div class="mb-3">
                        <label for="codigo" class="form-label">Código <small class="text-body-secondary">(Quando for fazer uma importação lembrar mudar os dois espaços antes do codigo)</small></label>
                        <input type="text" class="form-control" id="codigo" name="codigo" required>
                    </div>
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição do Produto</label>
                        <input type="text" class="form-control" id="descricao" name="descricao" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="quantidade" class="form-label">Quantidade</label>
                        <input type="number" class="form-control" id="quantidade" name="quantidade" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Cadastrar</button>
            </form>
        </div>
    </div>
</div>

<div class="container-fluid mt-4">

    <!-- Botões de fatura -->
    <div class="mb-3 d-flex align-items-center gap-2" style="overflow-x: auto; white-space: nowrap; padding-bottom: 6px; max-width: 100%;">
        <span class="text-muted me-2" style="font-size:13px; flex-shrink:0;">Filtrar faturas:</span>
        <?php foreach ($faturasBotoes as $nome): ?>
            <button class="btn btn-outline-primary btn-sm btn-fatura"
                data-fatura="<?= htmlspecialchars($nome) ?>"
                style="flex-shrink:0;">
                <?= htmlspecialchars($nome) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div id="acoes-faturas"></div>

    <!-- MODAL EDITAR -->
    <div class="modal fade" id="modalEditarEstoque" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Fatura: <span id="modalNomeFatura"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body" id="modalFormulario">
                    <!-- Conteúdo inserido via JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela dinâmica -->
    <div class="table-responsive shadow-sm rounded mb-3">
        <table class="table table-hover table-bordered align-middle text-center mb-0">
            <thead class="table-dark">
                <tr id="tr-thead">
                    <th class="sortable">Código</th>
                    <th class="sortable">Descrição</th>
                    <!-- colunas de fatura inseridas via JS -->
                    <th>Sobra</th>
                    <th>Estoque Total</th>
                </tr>
            </thead>
            <tbody id="tbody-estoque">
                <?php foreach ($tabela as $desc => $dados):
                    $totalGeral = array_sum($dados['quantidades']);
                    $jsonAttr = htmlspecialchars(json_encode([
                        'codigo'      => $dados['_codigo'],
                        'descricao'   => $desc,
                        'quantidades' => $dados['quantidades'],
                        'itens'       => $dados['itens'],
                        'total'       => $totalGeral
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr class="linha-estoque" data-json="<?= $jsonAttr ?>" style="display:none;">
                        <td><?= htmlspecialchars($dados['_codigo']) ?></td>
                        <td><?= htmlspecialchars($desc) ?></td>
                        <!-- colunas de quantidade inseridas via JS -->
                        <td class="td-total"><?= $totalGeral ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p id="sem-resultados" class="text-muted text-center" style="display:none;">
        Nenhum item encontrado para essa busca.
    </p>
</div>

<style>
    #tabela-estoque th,
    #tabela-estoque td {
        text-align: center;
        padding: 0.3rem 0.5rem;
        vertical-align: middle;
    }

    #tabela-estoque td:nth-child(2) {
        text-align: left;
        min-width: 250px;
    }

    th.sortable {
        cursor: pointer;
        user-select: none;
    }

    .btn-fatura.ativo {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }

    #acoes-faturas .acao-fatura {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        margin-bottom: 4px;
        background: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #dee2e6;
    }

    #acoes-faturas .acao-fatura span {
        font-weight: 500;
        flex: 1;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #mostrar-impressao,
        #mostrar-impressao * {
            visibility: visible;
        }

        #mostrar-impressao {
            position: absolute;
            left: 0;
            top: 0;
        }
    }
</style>

<script>
    window.dadosEstoque = <?= json_encode($tabela) ?>;
    window.todosItensEstoque = <?= json_encode($estoque) ?>;
</script>

<script src="js/faturas/estoque.js"></script>