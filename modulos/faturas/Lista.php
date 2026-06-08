<?php
$faturasJson = file_get_contents(__DIR__ . '/action/faturas.json');
$faturas = json_decode($faturasJson, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'Erro ao decodificar faturas.json: ' . json_last_error_msg();
    $faturas = [];
}

$produtosJson = file_get_contents(__DIR__ . '/../produtos/action/produtos.json');
$produtos = json_decode($produtosJson, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'Erro ao decodificar produtos.json: ' . json_last_error_msg();
    $produtos = [];
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3" style="max-width: 700px;">
        <!-- Filtro -->
        <input type="text" class="form-control" id="filtro-faturas" 
            style="width: 400px;" placeholder="Pesquisar por código ou descrição...">

        <!-- Botões -->
        <button class="btn btn-outline-secondary" id="btn-imprimir" title="Imprimir">
            🖨️ Imprimir
        </button>
    </div>
    <!-- Tabela de Produtos -->
    <div id="mostrar-impressao">
        
        <table class="table table-bordered table-hover mt-3 pesquisa" id="tabela-produtos">
            <thead class="table-dark">  
                <tr>
                    <th class="menor">Código</th>
                    <th class="descricao">Descrição</th>
                    <th class="menor">UN</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtos as $p): 
                $codigo    = $p["Item"]           ?? '';
                $descricao = $p["Descricao"]      ?? '';
                $unMedida  = $p["UM"]             ?? '';
                $tipo      = $p["tipo"]           ?? '';
                $jsonAttr  = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
                ?>
                <tr data-json="<?= $jsonAttr ?>" class="table-warning linha-fatura" style="display: none;">
                    <td class="filtravel"><?= htmlspecialchars($codigo) ?></td>
                    <td class="filtravel"><?= htmlspecialchars($descricao) ?></td>
                    <td><?= htmlspecialchars($unMedida) ?></td>
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

<style>    
    th.menor { width: 120px; }
    #tabela-faturas, #tabela-produtos {width: 700px; }
    .descricao { width: 350px; }
    .table th { padding: 0.3rem; vertical-align: middle; text-align: center; }
    .action-buttons .btn { padding: 0.2rem 0.4rem; margin: right; width: 600px }
    .exportar { display: flex; margin-right: auto; }
    .d-inline-flex.gap-1 > * { margin-right: 2px; }
    th.sortable { cursor: pointer; user-select: none; }
    
    @media print {
        body * { visibility: hidden; }
        #image, #image * { visibility: visible; }
        #mostrar-impressao, #mostrar-impressao * { visibility: visible; }
        #image { position: absolute; left: 0; top: 0; }
        #mostrar-impressao { position: absolute; left: 0; top: 50px; }
    }

</style>

<script>
    document.getElementById('filtro-faturas').addEventListener('input', function () {
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

        linhas.forEach(function (linha) {
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

    function aplicarFiltroFaturas(valorSelecionado) {
        const linhasImportadas = document.querySelectorAll('.cliente-importado');

        if (valorSelecionado === 'todos') {
            linhasImportadas.forEach(l => l.style.display = '');
            linhasCadastradas.forEach(l => l.style.display = '');
        } else if (valorSelecionado === 'importado') {
            linhasImportadas.forEach(l => l.style.display = '');
            linhasCadastradas.forEach(l => l.style.display = 'none');
        }
    }

    // Espera o select existir e aplica o filtro
    function inicializarFiltroFaturas() {
        const filtro = document.getElementById('filtro-faturas');
        if (filtro) {
            filtro.addEventListener('change', function () {
                aplicarFiltroFaturas(this.value);
            });

            // Aplica filtro inicial (cadastrados por padrão)
            aplicarFiltroFaturas(filtro.value);
        } else {
            // Tenta de novo se o elemento ainda não estiver no DOM
            setTimeout(inicializarFiltroFaturas, 100); // tenta a cada 100ms
        }
    }

    inicializarFiltroFaturas();

    if (typeof ordemCrescente === 'undefined') {
        var ordemCrescente = true;
    }
    document.querySelector('th.sortable').addEventListener('click', () => {
        const tbody = document.querySelector('#tabela-faturas tbody');
        const allRows = Array.from(tbody.querySelectorAll('tr'));

        const rowPairs = [];
        for (let i = 0; i < allRows.length; i += 2) {
            rowPairs.push([allRows[i], allRows[i + 1]]);
        }

        rowPairs.sort((a, b) => {
            const nomeA = a[0].children[0].textContent.trim().toLowerCase();
            const nomeB = b[0].children[0].textContent.trim().toLowerCase();
            return ordemCrescente ? nomeA.localeCompare(nomeB) : nomeB.localeCompare(nomeA);
        });

        tbody.innerHTML = '';
        rowPairs.forEach(pair => {
            tbody.appendChild(pair[0]);
            tbody.appendChild(pair[1]);
        });

        ordemCrescente = !ordemCrescente;
    });

    const printBtn = document.getElementById('btn-imprimir');
    
    printBtn.addEventListener('click', function() {
        print();
    })

</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>