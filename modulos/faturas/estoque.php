<?php
$estoqueJson = file_get_contents(__DIR__ . '/action/estoque.json');
$estoque = json_decode($estoqueJson, true) ?? [];

// Pega os números de fatura únicos (exceto "fabrica")
$numeros = array_unique(array_column($estoque, 'numero'));
$faturasBotoes = array_filter($numeros, fn($n) => $n !== 'fabrica');
sort($faturasBotoes);

// Monta estrutura: descricao => [ _codigo => ..., quantidades => [ numero => qtd ] ]
$tabela = [];
foreach ($estoque as $e) {
    $cod  = $e['codigo']    ?? '';
    $desc = $e['descricao'] ?? '';
    $num  = $e['numero']    ?? '';
    $qtd  = (int)($e['quantidade'] ?? 0);

    if (!isset($tabela[$desc])) {
        $tabela[$desc] = ['_codigo' => $cod, 'quantidades' => []];
    }
    $tabela[$desc]['quantidades'][$num] = $qtd;
}
ksort($tabela);
?>
<!-- Formulário -->
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
            data-fatura="fabrica"
            style="flex-shrink:0;">
            Fábrica
        </button>
    </div>

    <div id="acoes-faturas"></div>

    <!-- Tabela dinâmica -->
    <div id="mostrar-impressao" style="overflow-x: auto;">
        <table class="table table-bordered table-hover mt-2" id="tabela-estoque">
            <thead class="table-dark">
                <tr id="tr-thead">
                    <th>Descrição</th>
                    <th>Código</th>
                    <!-- colunas de fatura inseridas via JS -->
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
                        'total'       => $totalGeral
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr class="table-warning linha-estoque" data-json="<?= $jsonAttr ?>" style="display:none;">
                        <td><?= htmlspecialchars($desc) ?></td>
                        <td><?= htmlspecialchars($dados['_codigo']) ?></td>
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
    #tabela-estoque td:first-child, #tabela-estoque th:first-child { text-align: left; min-width: 250px; }
    .btn-fatura.ativo { background-color: #0d6efd; color: white; border-color: #0d6efd; }

    @media print {
        body * { visibility: hidden; }
        #mostrar-impressao, #mostrar-impressao * { visibility: visible; }
        #mostrar-impressao { position: absolute; left: 0; top: 0; }
    }
</style>

<script>
    //FORMULÁRIO
    // Busca descrição ao digitar código
    const campoCodigo = document.getElementById('codigo');
    const campoDescricao = document.getElementById('descricao');

    campoCodigo.addEventListener('blur', async function() {
        const codigo = this.value.trim();
        if (!codigo) {
            campoDescricao.value = '';
            return;
        }

        try {
            const response = await fetch(`./modulos/faturas/action/buscarProduto.php?codigo=  BM ${encodeURIComponent(codigo)}`);
            const produto = await response.json();
            campoDescricao.value = produto.encontrado ? produto.descricao : 'Produto não encontrado';
        } catch (error) {
            campoDescricao.value = 'Erro ao buscar produto';
        }
    });

    // Submit do formulário
    document.getElementById('formCadastroEstoque').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        fetch('./modulos/faturas/action/cadastrar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
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

<script>
    //LISTA DE FATURAS E AÇÕES

    function atualizarListaAcoes() {
        // limpa a div
        const container = document.getElementById('acoes-fatura');
        container.innerHTML = '';

        // percorre todas as faturas selecionadas
        faturasSelecionadas.forEach(fatura => {
            const linha = document.createElement('div');
            const nome = document.createElement('span');
            const acoes = document.createElement('button');

            // adiciona tudo na div
            nome.textContent = fatura;
            editar.innerHTML = '<i class="bi bi-pencil"></i>';
            excluir.innerHTML = '<i class="bi bi-trash"></i>';

            acoes.appendChild(editar);
            acoes.appendChild(excluir);

            // adiciona parte das linhas
            linha.appendChild(nome);
            linha.appendChild(acoes);
            // adiciona linha
            container.appendChild(linha);

            console.log(linha);

            editar.addEventListener('click', () => {
                editarFatura(fatura);
            });

            excluir.addEventListener('click', () => {
                removerFatura(fatura);
            });
        })
    }

    function editarFatura() {
        document.querySelectorAll('.bi-pencil').forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('tr'); // linha com os dados
                const editRow = row.nextElementSibling; // linha com o formulário

                if (editRow && editRow.classList.contains('edit-row')) {
                    const form = editRow.querySelector('.edit-form');
                    if (!form) return;

                    // Pega os dados do atributo data-json da linha
                    const jsonData = JSON.parse(row.getAttribute('data-json'));

                    // Preenche os campos do formulário
                    form.elements['codigo'].value = jsonData['codigo'] || '';
                    form.elements['descricao'].value = jsonData['descricao'] || '';
                    form.elements['quantidade'].value = jsonData['quantidade'] || '';

                    // Exibe a linha do formulário
                    editRow.style.display = 'table-row';
                }
            });
        });

        // CANCELAR EDIÇÃO
        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', function() {
                const editRow = this.closest('.edit-row');
                editRow.style.display = 'none';
            });
        });

        // SALVAR EDIÇÃO
        document.querySelectorAll('.edit-form').forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                const formData = new FormData(this);
                fetch('./modulos/faturas/action/update.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Cliente atualizado com sucesso!');
                            location.reload();
                        } else {
                            alert('Erro ao atualizar o cliente: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Ocorreu um erro ao tentar atualizar o cliente.');
                    });
            });
        });
    }

    function removerFatura() {
        document.querySelectorAll('.bi-trash').forEach(button => {
            button.addEventListener('click', function() {
                const faturaId = this.getAttribute('data-id');

                if (confirm("Tem certeza que deseja excluir esta fatura?")) {
                    fetch('./modulos/faturas/action/estoque.php', {
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
                                alert('Erro ao excluir a fatura: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Erro:', error);
                            alert('Ocorreu um erro ao tentar excluir a fatura.');
                        });
                }
            });
        });
    }
</script>

<script>
    //TABELA
    const dadosEstoque = <?= json_encode($tabela) ?>;
    let faturasSelecionadas = [];

    document.querySelectorAll('.btn-fatura').forEach(btn => {
        btn.addEventListener('click', function() {
            const fatura = this.dataset.fatura;

            if (faturasSelecionadas.includes(fatura)) {
                faturasSelecionadas = faturasSelecionadas.filter(f => f !== fatura);
                this.classList.remove('ativo');
            } else {
                faturasSelecionadas.push(fatura);
                this.classList.add('ativo');
            }

            renderizarTabela();
        });
    });

    function renderizarTabela() {
        const thead = document.getElementById('tr-thead');
        const linhas = document.querySelectorAll('.linha-estoque');
        const semResultados = document.getElementById('sem-resultados');

        // Reconstrói o cabeçalho mantendo Descrição e Código fixos
        thead.innerHTML = '<th>Descrição</th><th>Código</th>';
        faturasSelecionadas.forEach(fatura => {
            const th = document.createElement('th');
            th.textContent = fatura === 'fabrica' ? 'Fábrica' : fatura;
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

            // Mantém as duas primeiras células (descrição e código) e reconstrói o resto
            const descricaoTd = linha.children[0].outerHTML;
            const codigoTd = linha.children[1].outerHTML;
            linha.innerHTML = descricaoTd + codigoTd;

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