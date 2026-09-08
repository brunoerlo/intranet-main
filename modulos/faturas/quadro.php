<?php
$caminhoEstoque = './modulos/faturas/action/estoque.json';
$listaFaturas = file_exists($caminhoEstoque) ? file_get_contents($caminhoEstoque) : '[]';
$lFaturas = json_decode($listaFaturas, true) ?? [];

$nomes = array_unique(array_column($lFaturas, 'nome'));
asort($nomes);

$clientes = array_unique(array_column($lFaturas, 'cliente'));
asort($clientes);

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
?>

<!-- Formulario -->
<div class="container-fluid mt-4">
    <button class="btn btn-success mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseQuadro" aria-expanded="false" aria-controls="collapseQuadro">
        <i class="fa-solid fa-plus"></i> Novo Cadastro
    </button>
    <div class="collapse" id="collapseQuadro">
        <div class="card card-body">
            <h4>NOVO CADASTRO</h4>
            <form id="formularioQuadro">
                <div class="tipo-grupo">
                    <div class="mb-3">
                        <label for="nomeFatura" class="form-label">Fatura</label>
                        <select class="form-select" name="nomeFatura" id="nomeFatura" required>
                            <option value="" disabled selected>Escolha a fatura</option>
                            <?php foreach ($faturas as $label => $valorNome): ?>
                                <option value="<?= htmlspecialchars($valorNome) ?>">
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-none" id="dados">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="cliente" class="form-label">Cliente</label>
                                <div class="form-check">
                                    <label for="consolidado" class="form-check-label">Consolidado?</label>
                                    <input type="checkbox" class="form-check-input" name="consolidado" id="consolidado">
                                </div>
                            </div>
                            <select class="form-select" name="cliente" id="cliente" required>
                                <option value="" disabled selected>Escolha o Cliente</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>">
                                        <?= htmlspecialchars($c) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const faturaSelecionada = document.getElementById('nomeFatura')

    faturaSelecionada.addEventListener('change', function() {
        if (this.value !== '') {
            document.getElementById('dados').classList.remove('d-none');
        }
    })

    const checkConsolidado = document.getElementById('consolidado');
    const divNomeConsolidado = document.getElementById('divNomeConsolidado');
    const inputNomeConsolidado = document.getElementById('nomeConsolidado');

    checkConsolidado.addEventListener('change', function() {
        if (this.checked) {
            divNomeConsolidado.classList.remove('d-none');
            inputNomeConsolidado.required = true;
            inputNomeConsolidado.focus(); // Para ir direto para essa linha preencher os dados
        } else {
            divNomeConsolidado.classList.add('d-none');
            inputNomeConsolidado.required = false;
            inputNomeConsolidado.value = '';
        }
    });


    document.getElementById('formularioQuadro').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        // Checkbox não é incluído no FormData quando desmarcado; forçar explicitamente
        data['consolidado'] = document.getElementById('consolidado').checked;

        fetch('./modulos/faturas/action/cadastrarquadro.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Cadastro efetuado com sucesso!');
                    document.getElementById('formularioQuadro').reset();
                    location.reload();
                } else {
                    alert('Erro ao efetuar cadastro: ' + data.error);
                }
            })
            .catch(() => alert('Ocorreu um erro ao cadastrar.'));
    })
</script>

<div>
    <table class="table table-bordered table-hover mt-3">
        <thead class="table-dark">
            <tr id="tr-thead" style="text-align: center;">
                <th class="sortable" style="width: 120px;">Fatura</th>
                <th>Cliente</th>
                <th>Consolidado</th>
                <th class="sortable">Estufagem</th>
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
                <tr data-json="<?= $jsonAttr ?>" class="table-warning">
                    <td class="linhaQuadro"><?= htmlspecialchars($nome) ?></td>
                    <td class="linhaQuadro"><?= htmlspecialchars($cliente) ?></td>
                    <td class="linhaQuadro"><?= htmlspecialchars($consolidado) ?></td>
                    <td class="linhaQuadro"><?= htmlspecialchars($dataExibicao) ?></td>
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
                                    <select class="form-select form-select-sm" name="nomeFatura" required>
                                        <option value="" disabled>Escolha</option>
                                        <?php foreach ($faturas as $labelOpt => $valorNomeOpt): ?>
                                            <option value="<?= htmlspecialchars($valorNomeOpt) ?>" <?= ($valorNomeOpt === $nome) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($labelOpt) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label mb-0 small">Cliente</label>
                                    <select class="form-select form-select-sm" name="cliente" required>
                                        <option value="" disabled>Escolha o Cliente</option>
                                        <?php foreach ($clientes as $c): ?>
                                            <option value="<?= htmlspecialchars($c) ?>" <?= ($c === $cliente) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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

<script>
    // EDITAR
    document.querySelectorAll('.edit-cad-btn').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const editRow = row.nextElementSibling;

            if (editRow && editRow.classList.contains('edit-row')) {
                const form = editRow.querySelector('.edit-form-cad');
                if (!form) return;

                // Alterna a exibição da linha de edição
                if (editRow.style.display === 'none' || editRow.style.display === '') {
                    // Pega os dados do atributo data-json da linha
                    const jsonData = JSON.parse(row.getAttribute('data-json'));

                    // Preenche os campos do formulário
                    if (form.elements['id']) form.elements['id'].value = jsonData['id'] || '';
                    if (form.elements['nomeFatura']) form.elements['nomeFatura'].value = jsonData['nomeFatura'] || '';
                    if (form.elements['cliente']) form.elements['cliente'].value = jsonData['cliente'] || '';
                    if (form.elements['nomeConsolidado']) form.elements['nomeConsolidado'].value = jsonData['nomeConsolidado'] || '';
                    if (form.elements['data']) form.elements['data'].value = jsonData['data'] || '';
                    if (form.elements['ctr']) form.elements['ctr'].value = jsonData['ctr'] || '';
                    if (form.elements['porto']) form.elements['porto'].value = jsonData['porto'] || '';
                    if (form.elements['booking']) form.elements['booking'].value = jsonData['booking'] || '';

                    editRow.style.display = 'table-row';
                } else {
                    editRow.style.display = 'none';
                }
            }
        });
    });

    // CANCELAR EDIÇÃO
    document.querySelectorAll('.cancel-btn').forEach(button => {
        button.addEventListener('click', function() {
            const editRow = this.closest('.edit-row');
            if (editRow) {
                editRow.style.display = 'none';
            }
        });
    });

    // SALVAR EDIÇÃO
    document.querySelectorAll('.edit-form-cad').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            fetch('./modulos/faturas/action/updatequadro.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Atualizado com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao atualizar o quadro: ' + (data.message || data.error || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro ao tentar atualizar o quadro.');
                });
        });
    });

    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const faturaId = this.getAttribute('data-id');
            if (confirm("Tem certeza que deseja excluir esta fatura?")) {
                fetch('./modulos/faturas/action/deletequadro.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: faturaId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Fatura excluída com sucesso!');
                            location.reload();
                        } else {
                            alert('Erro ao excluir fatura: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Ocorreu um erro ao tentar excluir a fatura.');
                    });
            }
        });
    })

    const trThead = document.getElementById('tr-thead');
    if (trThead) {
        const thead = trThead.closest('thead');
        if (thead) {
            thead.addEventListener('click', function(e) {
                const th = e.target.closest('th.sortable');
                if (!th) return;

                const table = th.closest('table');
                const tbody = table.querySelector('tbody');
                const headerRow = th.parentElement;
                const colIndex = Array.from(headerRow.children).indexOf(th);

                // Pegar somente linhas de dados principais (não as edit-row)
                const rows = Array.from(tbody.querySelectorAll('tr.table-warning'));

                // Determinar direção: none→asc, asc→desc, desc→asc
                const currentOrder = th.dataset.order || 'none';
                const newOrder = (currentOrder === 'asc') ? 'desc' : 'asc';

                // Limpar indicadores de todas as colunas da mesma tabela
                headerRow.querySelectorAll('th.sortable').forEach(otherTh => {
                    otherTh.dataset.order = 'none';
                    const seta = otherTh.querySelector('.sort-arrow');
                    if (seta) seta.remove();
                });

                // Definir nova direção e indicador visual
                th.dataset.order = newOrder;
                const setaSpan = document.createElement('span');
                setaSpan.className = 'sort-arrow';
                setaSpan.textContent = (newOrder === 'asc') ? ' ▲' : ' ▼';
                th.appendChild(setaSpan);

                // Função para criar um "peso" numérico para ordenação
                function obterValorOrdenacao(str) {
                    str = str.trim().toUpperCase();
                    if (!str || str === '-') return 0;

                    // Tentar data no formato dd/mm/yyyy
                    const parts = str.split('/');
                    if (parts.length === 3) {
                        // Converte 08/09/2026 para 20260908 para fazer conta depois
                        return parseInt(parts[2], 10) * 10000 + parseInt(parts[1], 10) * 100 + parseInt(parts[0], 10);
                    }

                    // Dicionário de meses
                    const meses = {
                        'JAN': 1,
                        'FEV': 2,
                        'MAR': 3,
                        'ABR': 4,
                        'MAI': 5,
                        'JUN': 6,
                        'JUL': 7,
                        'AGO': 8,
                        'SET': 9,
                        'OUT': 10,
                        'NOV': 11,
                        'DEZ': 12
                    };

                    for (const [mes, num] of Object.entries(meses)) {
                        if (str.startsWith(mes)) {
                            // Extrai a semana (Ex: "S1" -> 1, "S2" -> 2)
                            let semana = 0;
                            const match = str.match(/S(\d+)/);
                            if (match) semana = parseInt(match[1], 10);

                            // Assume 2026 como ano base para as semanas alinharem com as datas completas.
                            // Isso converte "OUT S1" para 20261001, que será maior que 08/09/2026 (20260908)
                            // Usa o ano corrente em vez de fixo, evita virar lixo ano que vem
                            const anoAtual = new Date().getFullYear();
                            return anoAtual * 10000 + (num * 100) + semana;
                        }
                    }

                    return null; // Fallback se não for nem data nem mês conhecido
                }

                // Ordenar as linhas pela coluna clicada
                rows.sort((a, b) => {
                    const cellA = (a.children[colIndex]?.textContent || '').trim();
                    const cellB = (b.children[colIndex]?.textContent || '').trim();

                    // Ordenação por data (dd/mm/yyyy)
                    const dateA = obterValorOrdenacao(cellA);
                    const dateB = obterValorOrdenacao(cellB);
                    if (dateA && dateB) {
                        return newOrder === 'asc' ? dateA - dateB : dateB - dateA;
                    }

                    // Comparação numérica
                    const numA = parseFloat(cellA.replace(',', '.'));
                    const numB = parseFloat(cellB.replace(',', '.'));
                    if (!isNaN(numA) && !isNaN(numB) && cellA == numA && cellB == numB) {
                        return newOrder === 'asc' ? numA - numB : numB - numA;
                    }

                    // Comparação de texto padrão
                    return newOrder === 'asc' ?
                        cellA.localeCompare(cellB, 'pt-BR', {
                            sensitivity: 'base'
                        }) :
                        cellB.localeCompare(cellA, 'pt-BR', {
                            sensitivity: 'base'
                        });
                });

                // Reinserir as linhas ordenadas mantendo a tr.edit-row correspondente junta
                rows.forEach(row => {
                    const editRow = row.nextElementSibling;
                    tbody.appendChild(row);
                    if (editRow && editRow.classList.contains('edit-row')) {
                        tbody.appendChild(editRow);
                    }
                });
            });
        }
    }
</script>

<?php
$faturasJson = file_get_contents(__DIR__ . '/action/faturas.json');
$faturas = json_decode($faturasJson, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'Erro ao decodificar faturas.json: ' . json_last_error_msg();
    $faturas = [];
}

$codigo = array_column($faturas, 'codigo');

$noRepeat = array_unique($codigo);

?>

<div class="container mt-4">
    <button class="btn btn-success mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaturas" aria-expanded="false" aria-controls="collapseFaturas">
        <i class="fa-solid fa-plus"></i> Ver Histórico
    </button>
    <div class="collapse" id="collapseFaturas">


        <div class="d-flex justify-content-between align-items-center mb-3" style="max-width: 700px;">
            <!-- Filtro -->
            <input type="text" class="form-control" id="filtro-faturas"
                style="width: 400px;" placeholder="Pesquisar por código ou descrição...">

            <!-- Botões -->
            <button class="btn btn-outline-secondary" id="btn-imprimir" title="Imprimir">
                🖨️ Imprimir
            </button>

            <!-- Exportar -->
            <button class="btn btn-outline-secondary" id="btn-exportar" title="Exportar CSV">
                Exportar CSV
            </button>
        </div>
        <!-- Tabela de Produtos -->
        <div id="mostrar-impressao">

            <table class="table table-bordered table-hover mt-3 pesquisa" id="tabela-produtos">
                <thead class="table-dark">
                    <tr>
                        <th class="menor sortable">Código</th>
                        <th class="descricao sortable">Descrição</th>
                        <th class="menor">UN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($noRepeat as $nome):
                        $index = array_search($nome, array_column($faturas, 'codigo'));
                        $descricao        = $faturas[$index]["descricao"]        ?? '';
                        $unidadeMedida    = $faturas[$index]["unidadeMedida"]    ?? '';
                    ?>
                        <tr class="table-warning linha-fatura" style="display: none;">
                            <td class="filtravel"><?= htmlspecialchars($nome) ?></td>
                            <td class="filtravel"><?= htmlspecialchars($descricao) ?></td>
                            <td><?= htmlspecialchars($unidadeMedida) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Tabela de Faturas -->
            <table class="table table-bordered table-hover mt-3" id="tabela-faturas">
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
                    <?php foreach ($faturas as $f):
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
                        <tr data-json="<?= $jsonAttr ?>" class="table-warning linha-fatura" style="display: none;">
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

        <p id="sem-resultados" class="text-muted text-center" style="display: none;">
            Nenhum item encontrado para essa busca.
        </p>
    </div>
</div>

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
    document.getElementById('filtro-faturas').addEventListener('input', function() {
        const termo = this.value.toLowerCase().trim();
        const linhas = document.querySelectorAll('.linha-fatura');
        let encontrou = false;

        if (termo === '') {
            linhas.forEach(linha => {
                linha.style.display = 'none';
                const editRow = linha.nextElementSibling;
                if (editRow && editRow.classList.contains('edit-row')) {
                    editRow.style.display = 'none';
                }
            });
            document.getElementById('sem-resultados').style.display = 'none';
            return;
        }

        linhas.forEach(function(linha) {
            const textoFiltro = Array.from(linha.querySelectorAll('.filtravel'))
                .map(td => td.textContent.toLowerCase())
                .join(' ');

            const editRow = linha.nextElementSibling;

            if (textoFiltro.includes(termo)) {
                linha.style.display = '';
                encontrou = true;
            } else {
                linha.style.display = 'none';
                if (editRow && editRow.classList.contains('edit-row')) {
                    editRow.style.display = 'none';
                }
            }
        });

        document.getElementById('sem-resultados').style.display = encontrou ? 'none' : 'block';
    });



    // Ordenação por coluna — somente para tabelas do histórico
    // (a tabela do quadro é tratada pelo sorter específico acima via #tr-thead)
    document.querySelectorAll('th.sortable').forEach(th => {
        // Ignora th que pertencem à tabela do quadro
        if (th.closest('table')?.querySelector('#tr-thead')) return;

        th.dataset.order = 'none';

        th.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const headerRow = this.parentElement;
            const colIndex = Array.from(headerRow.children).indexOf(this);
            const rows = Array.from(tbody.querySelectorAll('tr'));

            const currentOrder = this.dataset.order;
            const newOrder = (currentOrder === 'asc') ? 'desc' : 'asc';

            // Limpar indicadores de todas as colunas da mesma tabela
            headerRow.querySelectorAll('th.sortable').forEach(otherTh => {
                otherTh.dataset.order = 'none';
                otherTh.textContent = otherTh.textContent.replace(/ [▲▼]$/, '');
            });

            this.dataset.order = newOrder;
            this.textContent += (newOrder === 'asc') ? ' ▲' : ' ▼';

            rows.sort((a, b) => {
                const cellA = (a.children[colIndex]?.textContent || '').trim().toLowerCase();
                const cellB = (b.children[colIndex]?.textContent || '').trim().toLowerCase();

                const numA = parseFloat(cellA.replace(',', '.'));
                const numB = parseFloat(cellB.replace(',', '.'));
                if (!isNaN(numA) && !isNaN(numB)) {
                    return newOrder === 'asc' ? numA - numB : numB - numA;
                }

                return newOrder === 'asc' ?
                    cellA.localeCompare(cellB, 'pt-BR') :
                    cellB.localeCompare(cellA, 'pt-BR');
            });

            rows.forEach(row => tbody.appendChild(row));
        });
    });

    const printBtn = document.getElementById('btn-imprimir');

    //impressao
    printBtn.addEventListener('click', function() {
        print();
    })

    const btnExportar = document.getElementById('btn-exportar');
    if (btnExportar) {
        btnExportar.addEventListener('click', () => {
            const faturasData = <?= json_encode($faturas) ?>;
            if (!Array.isArray(faturasData) || faturasData.length === 0) {
                alert('Nenhuma fatura disponível para exportar.');
                return;
            }
            const headers = Object.keys(faturasData[0]);
            const main = faturasData.map((item) => {
                return Object.values(item).map(val => `"${String(val ?? '').replace(/"/g, '""')}"`).join(',');
            });
            const csv = [headers.join(','), ...main].join('\n');

            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.download = 'faturas.csv';
            a.href = url;
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        });
    }
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>