<?php
$caminhoEstoque = './modulos/faturas/action/estoque.json';
$listaFaturas = file_exists($caminhoEstoque) ? file_get_contents($caminhoEstoque) : '[]';
$lFaturas = json_decode($listaFaturas, true) ?? [];

$caminhoQuadro = './modulos/faturas/action/quadro.json';
$quadroJson = file_exists($caminhoQuadro) ? file_get_contents($caminhoQuadro) : '[]';
$quadro = json_decode($quadroJson, true) ?? [];

$faturas = [];
foreach ($lFaturas as $e) {
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

    // Guarda valor real (nome) separado do label exibido, evita explode() gambiarra
    if (!isset($faturas[$label])) {
        $faturas[$label] = $nome;
    }
}
ksort($faturas); // ordena pelas chaves (labels)

$faturasJson = file_exists(__DIR__ . '/action/faturas.json') ? file_get_contents(__DIR__ . '/action/faturas.json') : '[]';
$faturasHistorico = json_decode($faturasJson, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $faturasHistorico = [];
}

sort($faturasHistorico);
$codigo = array_column($faturasHistorico, 'codigo');
$noRepeat = array_unique($codigo);
        
?>

<!-- Formulario -->
<div class="container-fluid mt-4">

    <div class="d-flex gap-2 mb-3">
        <button class="btn btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#collapseQuadro" aria-expanded="false" aria-controls="collapseQuadro" id="botaoCadastro">
            <i class="fa-solid fa-plus"></i> Novo Cadastro
        </button>
        <button class="btn btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaturas" aria-expanded="false" aria-controls="collapseFaturas" id="botaoHistorico">
            <i class="fa-solid fa-clock-rotate-left"></i> Ver Histórico
        </button>
    </div>

    <div class="collapse mb-4" id="collapseFaturas">
        <div class="card card-body shadow-sm">
            <h4 class="mb-3">CONSULTA HISTÓRICO DE FATURAS</h4>
            <div class="d-flex justify-content-between align-items-center mb-3" style="max-width: 700px;">
                <!-- Filtro -->
                <input type="text" class="form-control" id="filtro-faturas"
                    style="width: 400px;" placeholder="Pesquisar por código ou descrição...">

                <!-- Botões -->
                <button class="btn btn-outline-secondary" id="btn-imprimir" title="Imprimir">
                    🖨️ Imprimir
                </button>

                <!-- Exportar -->
                <button class="btn btn-outline-secondary" id="btn-exportar-faturas" title="Exportar CSV">
                    Exportar CSV
                </button>
            </div>
            <!-- Tabela de Produtos -->
            <div id="mostrar-impressao">

                <div class="table-responsive shadow-sm rounded mb-4" style="max-width: 700px;">
                    <table class="table table-hover table-bordered align-middle mb-0 pesquisa" id="tabela-produtos">
                        <thead class="table-dark">
                            <tr>
                                <th class="menor sortable">Código</th>
                                <th class="descricao sortable">Descrição</th>
                                <th class="menor">UN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($noRepeat as $nome):
                                $index = array_search($nome, array_column($faturasHistorico, 'codigo'));
                                $descricao        = $faturasHistorico[$index]["descricao"]        ?? '';
                                $unidadeMedida    = $faturasHistorico[$index]["unidadeMedida"]    ?? '';
                            ?>
                                <tr class="linha-fatura" style="display: none;">
                                    <td class="filtravel"><?= htmlspecialchars($nome) ?></td>
                                    <td class="filtravel"><?= htmlspecialchars($descricao) ?></td>
                                    <td><?= htmlspecialchars($unidadeMedida) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tabela de Faturas -->
                <div class="table-responsive shadow-sm rounded mb-4" style="max-width: 700px;">
                    <table class="table table-hover table-bordered align-middle mb-0" id="tabela-faturas">
                        <thead class="table-dark">
                            <tr>
                                <th class="sortable">Fatura</th>
                                <th class="menor">NF</th>
                                <th class="menor">Taxa Dólar</th>
                                <th class="menor">Quantidade</th>
                                <th class="menor">Preço</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($faturasHistorico) as $f):
                                $nome       = $f["nomeFatura"]    ?? '';
                                $nf         = $f["notaFiscal"]    ?? '';
                                $taxaDolar  = $f["txDolar"]       ?? '';
                                $quantidade = $f["quantidade"]    ?? '';
                                $preco      = $f["preco"]         ?? '';
                                $tipo       = $f["tipo"]          ?? '';
                                $codigo     = $f["codigo"]        ?? '';
                                $descricao  = $f["descricao"]     ?? '';
                                $jsonAttr   = htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr data-json="<?= $jsonAttr ?>" class="linha-fatura" style="display: none;">
                                    <td><?= htmlspecialchars($nome) ?></td>
                                    <td><?= htmlspecialchars($nf) ?></td>
                                    <td><?= htmlspecialchars($taxaDolar) ?></td>
                                    <td><?= htmlspecialchars($quantidade) ?></td>
                                    <td class="filtravel" style="display: none;"><?= htmlspecialchars($codigo) ?></td>
                                    <td class="filtravel" style="display: none;"><?= htmlspecialchars($descricao) ?></td>
                                    <td><?= htmlspecialchars($preco) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="max-width: 700px;">
                <p id="sem-resultados" class="text-muted text-center" style="display: none;">
                    Nenhum item encontrado para essa busca.
                </p>
            </div>
        </div>
    </div>

    <div class="collapse" id="collapseQuadro">
        <div class="card card-body">
            <h4>NOVO CADASTRO</h4>
            <form id="formularioQuadro">
                <div class="tipo-grupo">
                    <div class="mb-3">
                        <label for="nomeFatura" class="form-label">Fatura</label>
                        <input type="text" class="form-control" name="nomeFatura" id="nomeFatura">
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="cliente" class="form-label">Cliente</label>
                            <div class="form-check">
                                <label for="consolidado" class="form-check-label">Consolidado?</label>
                                <input type="checkbox" class="form-check-input" name="consolidado" id="consolidado">
                            </div>
                        </div>
                        <input type="text" class="form-control" name="cliente" id="cliente">
                        <div id="divNomeConsolidado" class="mb-3 d-none">
                            <label for="nomeConsolidado" class="form-label">Nome Consolidado</label>
                            <input type="text" class="form-control" name="nomeConsolidado" id="nomeConsolidado">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="data" class="form-label">Estufagem</label>
                        <input type="text" class="form-control" name="data" id="data" placeholder="Ex: 14/09/2026 ou SET S4">
                    </div>
                    <div class="mb-3">
                        <label for="ctr" class="form-label">CTR</label>
                        <select class="form-select" name="ctr" id="ctr" required>
                            <option value="" disabled selected>Escolha forma de envio</option>
                            <option value="20'">20'</option>
                            <option value="40'">40'</option>
                            <option value="Rodoviário">Rodoviário</option>
                            <option value="Aéreo">Aéreo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="porto" class="form-label">Porto</label>
                        <input type="text" class="form-control" name="porto" id="porto" required>
                    </div>
                    <div class="mb-3">
                        <label for="booking" class="form-label">Booking</label>
                        <input type="text" class="form-control" name="booking" id="booking" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="table-responsive shadow-sm rounded mb-3">
    <table class="table table-hover table-bordered align-middle mb-0">
        <thead class="table-dark">
            <tr id="tr-thead" style="text-align: center;">
                <th class="sortable" style="width: 120px;">Fatura</th>
                <th>Cliente</th>
                <th>Consolidado</th>
                <th class="sortable ordenaPadrao">Estufagem</th>
                <th>CTR</th>
                <th>Porto</th>
                <th>Booking</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($quadro as $q):
                $nome        = $q["nomeFatura"]      ?? '';
                $cliente     = $q["cliente"]         ?? '';
                $consolidado = !empty($q["nomeConsolidado"]) ? $q["nomeConsolidado"] : (!empty($q["consolidado"]) ? 'Sim' : 'Não');
                $data        = $q["data"]            ?? '';
                $ctr         = $q["ctr"]             ?? '';
                $porto       = $q["porto"]           ?? '';
                $booking     = $q["booking"]         ?? '';
                $jsonAttr   = htmlspecialchars(json_encode($q), ENT_QUOTES, 'UTF-8');

                // Converte a data para d/m/Y (suporta Y-m-d legado e d/m/Y novo)
                $dataExibicao = '-';
                if (!empty($data)) {
                    $objIso = DateTime::createFromFormat('Y-m-d', $data);
                    $objBr  = DateTime::createFromFormat('d/m/Y', $data);
                    if ($objIso && $objIso->format('Y-m-d') === $data) {
                        $dataExibicao = $objIso->format('d/m/Y'); // legado ISO → exibe BR
                    } elseif ($objBr && $objBr->format('d/m/Y') === $data) {
                        $dataExibicao = $data; // já está em BR, exibe direto
                    } else {
                        $dataExibicao = $data; // texto livre: SET S4, OUT S1, etc.
                    }
                }
            ?>
                <tr data-json="<?= $jsonAttr ?>" class="">
                    <td class="linhaQuadro"><?= htmlspecialchars($nome) ?></td>
                    <td class="linhaQuadro"><?= strtoupper(htmlspecialchars($cliente)) ?></td>
                    <td class="linhaQuadro"><?= ucfirst(htmlspecialchars($consolidado)) ?></td>
                    <td class="linhaQuadro"><?= strtoupper(htmlspecialchars($dataExibicao)) ?></td>
                    <td class="linhaQuadro"><?= htmlspecialchars($ctr) ?></td>
                    <td class="linhaQuadro"><?= htmlspecialchars($porto) ?></td>
                    <td class="linhaQuadro"><?= htmlspecialchars($booking) ?></td>
                    <td class="linhaQuadro action-buttons" style="width: 100px;">
                        <div class="d-inline-flex gap-1">
                            <button class="btn btn-warning btn-sm edit-cad-btn" data-id="<?= htmlspecialchars($q['id'] ?? '') ?>">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-danger btn-sm delete-btn" data-id="<?= htmlspecialchars($q['id'] ?? '') ?>">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr class="edit-row" style="display: none;">
                    <td colspan="8">
                        <form class="edit-form-cad">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($q['id'] ?? '') ?>">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-2">
                                    <label class="form-label mb-0 small">Fatura</label>
                                    <input type="text" class="form-control" name="nomeFatura" value="<?= htmlspecialchars($nomeFatura) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label mb-0 small">Cliente</label>
                                    <input type="text" class="form-control" name="cliente" value="<?= htmlspecialchars($cliente) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label mb-0 small">Consolidado</label>
                                    <input type="text" class="form-control form-control-sm" name="nomeConsolidado" value="<?= htmlspecialchars($q['nomeConsolidado'] ?? '') ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label mb-0 small">Estufagem</label>
                                    <input type="text" class="form-control form-control-sm" name="data" value="<?= htmlspecialchars($data) ?>" required>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label mb-0 small">CTR</label>
                                    <select class="form-select form-select-sm" name="ctr" required>
                                        <option value="20'" <?= ($ctr === "20'") ? 'selected' : '' ?>>20'</option>
                                        <option value="40'" <?= ($ctr === "40'") ? 'selected' : '' ?>>40'</option>
                                        <option value="Rodoviário" <?= ($ctr === 'Rodoviário') ? 'selected' : '' ?>>Rodoviário</option>
                                        <option value="Aéreo" <?= ($ctr === 'Aéreo') ? 'selected' : '' ?>>Aéreo</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label mb-0 small">Porto</label>
                                    <input type="text" class="form-control form-control-sm" name="porto" value="<?= htmlspecialchars($porto) ?>" required>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label mb-0 small">Booking</label>
                                    <input type="text" class="form-control form-control-sm" name="booking" value="<?= htmlspecialchars($booking) ?>" required>
                                </div>
                                <div class="col-md-1 text-end mt-3">
                                    <button type="submit" class="btn btn-sm btn-success mb-1 me-1" title="Salvar"><i class="fa-solid fa-check"></i></button>
                                    <button type="button" class="btn btn-sm btn-secondary cancel-btn mb-1" title="Cancelar"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
    th.sortable {
        cursor: pointer;
        user-select: none;
    }

    td.linhaQuadro {
        text-align: center;
    }
</style>

<?php
$faturasJson = file_get_contents(__DIR__ . '/action/faturas.json');
$faturas = json_decode($faturasJson, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'Erro ao decodificar faturas.json: ' . json_last_error_msg();
    $faturas = [];
}

$codigo = array_column($faturasHistorico, 'codigo');
$noRepeat = array_unique($codigo);
?>

<style>
    th.menor {
        width: 120px;
    }

    #tabela-faturas,
    #tabela-produtos {
        width: 700px;
    }

    .descricao {
        width: 350px;
    }

    .table th {
        padding: 0.3rem;
        vertical-align: middle;
        text-align: center;
    }

    .action-buttons .btn {
        padding: 0.2rem 0.4rem;
        margin-right: auto;
    }

    .exportar {
        display: flex;
        margin-right: auto;
    }

    .gap-1>* {
        margin-right: 2px;
    }

    th.sortable {
        cursor: pointer;
        user-select: none;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #image,
        #image * {
            visibility: visible;
        }

        #mostrar-impressao,
        #mostrar-impressao * {
            visibility: visible;
        }

        #image {
            position: absolute;
            left: 0;
            top: 0;
        }

        #mostrar-impressao {
            position: absolute;
            left: 0;
            top: 50px;
        }
    }
</style>

<script>
    window.historicoFaturas =  <?= json_encode($faturasHistorico) ?>
</script>

<script src="js/faturas/quadro.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>