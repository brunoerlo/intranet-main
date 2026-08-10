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

        if (valorSelecionado === 'todos') {
            linhasImportadas.forEach(l => l.style.display = '');
            linhasCadastradas.forEach(l => l.style.display = '');
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

    // Ordenação por coluna — funciona para ambas as tabelas
    document.querySelectorAll('th.sortable').forEach(th => {
        th.dataset.order = 'none'; // none, asc, desc

        th.addEventListener('click', function () {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const headerRow = this.parentElement;
            const colIndex = Array.from(headerRow.children).indexOf(this);
            const rows = Array.from(tbody.querySelectorAll('tr'));

            // Determinar direção: none→asc, asc→desc, desc→asc
            const currentOrder = this.dataset.order;
            const newOrder = (currentOrder === 'asc') ? 'desc' : 'asc';

            // Limpar indicadores de todas as colunas da mesma tabela
            headerRow.querySelectorAll('th.sortable').forEach(otherTh => {
                otherTh.dataset.order = 'none';
                otherTh.textContent = otherTh.textContent.replace(/ [▲▼]$/, '');
            });

            // Definir nova direção e indicador visual
            this.dataset.order = newOrder;
            this.textContent += (newOrder === 'asc') ? ' ▲' : ' ▼';

            // Ordenar as linhas pela coluna clicada
            rows.sort((a, b) => {
                const cellA = (a.children[colIndex]?.textContent || '').trim().toLowerCase();
                const cellB = (b.children[colIndex]?.textContent || '').trim().toLowerCase();

                // Tentar comparação numérica se ambos forem números
                const numA = parseFloat(cellA.replace(',', '.'));
                const numB = parseFloat(cellB.replace(',', '.'));
                if (!isNaN(numA) && !isNaN(numB)) {
                    return newOrder === 'asc' ? numA - numB : numB - numA;
                }

                return newOrder === 'asc'
                    ? cellA.localeCompare(cellB, 'pt-BR')
                    : cellB.localeCompare(cellA, 'pt-BR');
            });

            // Reinserir as linhas ordenadas
            rows.forEach(row => tbody.appendChild(row));
        });
    });

    const printBtn = document.getElementById('btn-imprimir');
    
    //impressao
    printBtn.addEventListener('click', function() {
        print();
    })
    
    //pega o json e coloca dentro de uma variavel
    const faturas = <?= json_encode($faturas) ?>;
    handle(faturas);

    //detalha como eh a formatação do csv
    function handle(inputData) {
        console.log(inputData);
        const headers = Object.keys(inputData[0]);
        const main = inputData.map((item) => {
            return Object.values(item).toString();
        });
        const csv = [headers, ...main].join('\n');
        comecaDownloadCSV(csv);
    }
    
    //faz download do csv
    function comecaDownloadCSV(input) {
        const blob = new Blob([input], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        
        //faz download ao clicar no botao
        document.getElementById('btn-exportar').addEventListener('click', () => {
            const a = document.createElement('a');
            a.download = 'faturas.csv'; //nome do arquivo
            a.href = url; //pega a url que foi criada
            a.style.display = 'none'; //não mostra o download sendo feito
            document.body.appendChild(a); 
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        });
    }

</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
