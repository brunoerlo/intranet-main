<?php
$faturasJson = file_get_contents(__DIR__ . '/action/faturas.json');
$faturas = json_decode($faturasJson, true) ?? [];

$estoqueJson = file_get_contents(__DIR__ . '/action/estoque.json');
$estoque = json_decode($estoqueJson, true) ?? [];

// Pega os números de fatura únicos (exceto "Fábrica")
$numeros = array_unique(array_column($estoque, 'numero'));
$faturasBotoes = array_filter($numeros, fn($n) => $n !== 'Fábrica');
sort($faturasBotoes);

// Monta estrutura: descricao => [ numero => quantidade ]
$tabela = [];
foreach ($estoque as $e) {
    $cod  = $e['codigo']    ?? '';
    $desc = $e['descricao'] ?? '';
    $num  = $e['numero']    ?? '';
    $qtd  = (int)($e['quantidade'] ?? 0);
    if (!isset($tabela[$desc])) $tabela[$desc] = ['codigo' => $cod, 'quantidades' => []];
    $tabela[$desc]['quantidades'][$num] = $qtd;
}
ksort($tabela);
?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-body">
            <h4>NOVO ESTOQUE</h4>
            <form id="formCadastroEstoque">
                <div class="tipo-grupo">
                    <div class="mb-3">
                        <label for="numero" class="form-label">Nome da Fatura <small class="text-muted">(deixe vazio para Fábrica)</small></label>
                        <input type="text" class="form-control" id="numero" name="numero">
                    </div>
                    <div class="mb-3">
                        <label for="codigo" class="form-label">Código</label>
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
        <?php foreach ($faturasBotoes as $num): ?>
            <button class="btn btn-outline-primary btn-sm btn-fatura" 
                    data-fatura="<?= htmlspecialchars($num) ?>"
                    style="flex-shrink:0;">
                <?= htmlspecialchars($num) ?>
            </button>
        <?php endforeach; ?>
        <button class="btn btn-outline-secondary btn-sm btn-fatura" 
                data-fatura="Fábrica"
                style="flex-shrink:0;">
            Fábrica
        </button>
    </div>

    <!-- Tabela dinâmica -->
    <div id="mostrar-impressao" style="overflow-x: auto;">
        <table class="table table-bordered table-hover mt-2" id="tabela-estoque">
            <thead class="table-dark" id="thead-estoque">
                <tr>
                    <th style="width: 200px" >Descrição</th>
                    <th>Código</th>
                    <!-- colunas de fatura inseridas via JS -->
                    <th id="th-total">Estoque Total</th>
                </tr>
            </thead>
            <tbody id="tbody-estoque">
                <?php foreach ($tabela as $desc => $item):
                    $cod = $item['codigo'];
                    $quantidades = $item['quantidades'];
                    $totalGeral = array_sum($quantidades);

                    $jsonAttr = htmlspecialchars(json_encode([
                        'codigo' => $cod,
                        'descricao' => $desc,
                        'quantidades' => $quantidades,
                        'total' => $totalGeral
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                <tr class="table-warning linha-estoque" data-json="<?= $jsonAttr ?>" style="display:none;">
                    <td><?= htmlspecialchars($cod) ?></td>
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
    #tabela-estoque th, #tabela-estoque td { text-align: center; padding: 0.3rem 0.5rem; vertical-align: middle; }
    #tabela-estoque td:first-child, #tabela-estoque th:first-child { text-align: left; }
    .btn-fatura.ativo { background-color: #0d6efd; color: white; }
    @media print {
        body * { visibility: hidden; }
        #mostrar-impressao, #mostrar-impressao * { visibility: visible; }
        #mostrar-impressao { position: absolute; left: 0; top: 0; }
    }
</style>

<script>
// Dados vindos do PHP — estrutura: { descricao: { numero: quantidade } }
const dadosEstoque = <?= json_encode($tabela) ?>;

// Faturas atualmente selecionadas (toggle)
let faturasSelecionadas = [];

document.querySelectorAll('.btn-fatura').forEach(btn => {
    btn.addEventListener('click', function () {
        const fatura = this.dataset.fatura;

        if (faturasSelecionadas.includes(fatura)) {
            // Desmarca
            faturasSelecionadas = faturasSelecionadas.filter(f => f !== fatura);
            this.classList.remove('ativo');
        } else {
            // Marca
            faturasSelecionadas.push(fatura);
            this.classList.add('ativo');
        }

        renderizarTabela();
    });
});

function renderizarTabela() {
    const thead = document.querySelector('#tabela-estoque thead tr');
    const linhas = document.querySelectorAll('.linha-estoque');
    const semResultados = document.getElementById('sem-resultados');

    // Reconstrói o cabeçalho
    thead.innerHTML = `<th>Código</th>
                       <th>Descrição</th>`;

    faturasSelecionadas.forEach(fatura => {
        const th = document.createElement('th');
        th.textContent = fatura === 'Fábrica'
            ? 'Fábrica'
            : fatura;

        thead.appendChild(th);
    });

    const thTotal = document.createElement('th');
    thTotal.textContent = 'Estoque Total';
    thead.appendChild(thTotal);

    // Sem nenhuma fatura selecionada — esconde tudo
    if (faturasSelecionadas.length === 0) {
        linhas.forEach(l => l.style.display = 'none');
        semResultados.style.display = 'none';
        return;
    }

    let encontrou = false;

    linhas.forEach(linha => {
        const dados = JSON.parse(linha.getAttribute('data-json'));
        const quantidades = dados.quantidades;

        // Verifica se essa linha tem pelo menos uma das faturas selecionadas
        const temDados = faturasSelecionadas.some(f => quantidades[f] !== undefined);

        if (!temDados) {
            linha.style.display = 'none';
            return;
        }

        linha.style.display = '';
        encontrou = true;

        // Reconstrói as células dessa linha
        // Remove células antigas de fatura (mantém só descrição e total)
        const tds = linha.querySelectorAll('td');
        // Limpa tudo exceto o primeiro (descrição)
        while (linha.children.length > 2) linha.removeChild(linha.lastChild);

        let total = 0;
        faturasSelecionadas.forEach(fatura => {
            const td = document.createElement('td');
            const qtd = quantidades[fatura];
            td.textContent = qtd !== undefined ? qtd : '-';
            if (qtd) total += parseInt(qtd);
            linha.appendChild(td);
        });

        const tdTotal = document.createElement('td');
        tdTotal.textContent = total;
        linha.appendChild(tdTotal);
    });

    semResultados.style.display = encontrou ? 'none' : 'block';
}
</script>

<script>
// Busca descrição ao digitar código
const campoCodigo = document.getElementById('codigo');
const campoDescricao = document.getElementById('descricao');

campoCodigo.addEventListener('blur', async function () {
    const codigo = this.value.trim();
    if (!codigo) { campoDescricao.value = ''; return; }

    try {
        const response = await fetch(`./modulos/faturas/action/buscarProduto.php?codigo=  BM ${encodeURIComponent(codigo)}`);
        const produto = await response.json();
        campoDescricao.value = produto.encontrado ? produto.descricao : 'Produto não encontrado';
    } catch (error) {
        campoDescricao.value = 'Erro ao buscar produto';
    }
});

// Submit do formulário
document.getElementById('formCadastroEstoque').addEventListener('submit', function (event) {
    event.preventDefault();
    const formData = new FormData(this);
    const data = {};
    formData.forEach((value, key) => { data[key] = value; });

    fetch('./modulos/faturas/action/cadastrar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Estoque cadastrado com sucesso!');
            document.getElementById('formCadastroEstoque').reset();
            location.reload();
        } else {
            alert('Erro ao cadastrar estoque: ' + data.error);
        }
    })
    .catch(() => alert('Ocorreu um erro ao cadastrar.'));
});
</script>
