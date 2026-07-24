<?php
$estoqueJson = file_get_contents(__DIR__ . '/action/estoque.json');
$estoque = json_decode($estoqueJson, true) ?? [];

// Pega os números de fatura únicos (exceto "fabrica")
$numeros = array_unique(array_column($estoque, 'numero'));
$faturasBotoes = array_filter($numeros, fn($n) => $n !== 'fabrica');
sort($faturasBotoes);

// Monta estrutura: descricao => [ _codigo => ..., quantidades => [ numero => qtd ], itens => [...] ]
$tabela = [];
foreach ($estoque as $e) {
    $cod  = $e['codigo']    ?? '';
    $desc = $e['descricao'] ?? '';
    $num  = $e['numero']    ?? '';
    $qtd  = (int)($e['quantidade'] ?? 0);
    $id   = $e['Codigo estoque'] ?? '';

    if (!isset($tabela[$desc])) {
        $tabela[$desc] = ['_codigo' => $cod, 'quantidades' => [], 'itens' => []];
    }
    $tabela[$desc]['quantidades'][$num] = $qtd;
    // Guarda cada item individual para edição/exclusão
    $tabela[$desc]['itens'][] = [
        'id'         => $id,
        'numero'     => $num,
        'codigo'     => $cod,
        'descricao'  => $desc,
        'quantidade' => $qtd
    ];
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
                        'itens'       => $dados['itens'],
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
    #tabela-estoque th,
    #tabela-estoque td {
        text-align: center;
        padding: 0.3rem 0.5rem;
        vertical-align: middle;
    }

    #tabela-estoque td:first-child,
    #tabela-estoque th:first-child {
        text-align: left;
        min-width: 250px;
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
    // ===================== FORMULÁRIO DE CADASTRO =====================
    const campoCodigo = document.getElementById('codigo');
    const campoDescricao = document.getElementById('descricao');

    campoCodigo.addEventListener('blur', async function() {
        const codigo = this.value.trim();
        if (!codigo) {
            campoDescricao.value = '';
            return;
        }

        try {
            const response = await fetch(`./modulos/faturas/action/buscarProduto.php?codigo=${encodeURIComponent(codigo)}`);
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
    // DADOS E ESTADO
    const dadosEstoque = <?= json_encode($tabela) ?>;
    const todosItensEstoque = <?= json_encode($estoque) ?>;
    let faturasSelecionadas = [];

    // LISTA DE AÇÕES POR FATURA
    function atualizarListaAcoes() {
        const container = document.getElementById('acoes-faturas');
        container.innerHTML = '';

        faturasSelecionadas.forEach(fatura => {
            const linha = document.createElement('div');
            linha.className = 'acao-fatura';

            const nome = document.createElement('span');
            nome.textContent = fatura === 'fabrica' ? 'Fábrica' : fatura;

            const editar = document.createElement('button');
            editar.className = 'btn btn-sm btn-warning';
            editar.innerHTML = '<i class="bi bi-pencil-fill"></i> Editar';

            const excluir = document.createElement('button');
            excluir.className = 'btn btn-sm btn-danger';
            excluir.innerHTML = '<i class="bi bi-trash-fill"></i> Excluir';

            editar.addEventListener('click', () => {
                editarFatura(fatura);
            });

            excluir.addEventListener('click', () => {
                removerFatura(fatura);
            });

            linha.appendChild(nome);
            linha.appendChild(editar);
            linha.appendChild(excluir);
            container.appendChild(linha);
        });
    }

    // EDITAR FATURA (MODAL) 
    function editarFatura(fatura) {
        // Filtra os itens dessa fatura
        const itensFatura = todosItensEstoque.filter(item => item.numero === fatura);

        const modalBody = document.getElementById('modalFormulario');
        document.getElementById('modalNomeFatura').textContent = fatura === 'fabrica' ? 'Fábrica' : fatura;

        if (itensFatura.length === 0) {
            modalBody.innerHTML = '<p class="text-center text-muted p-3">Nenhum item encontrado para esta fatura.</p>';
        } else {
            let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
            html += '<thead class="table-light"><tr><th>Código</th><th>Descrição</th><th>Quantidade</th><th>Ações</th></tr></thead><tbody>';

            itensFatura.forEach(item => {
                const itemId = item['Codigo estoque'] || '';
                html += `
                    <tr data-item-id="${itemId}">
                        <td>${item.codigo || ''}</td>
                        <td>${item.descricao || ''}</td>
                        <td>
                            <input type="number" class="form-control form-control-sm edit-qtd"
                                   value="${item.quantidade || 0}" style="width:80px; display:inline-block;">
                        </td>
                        <td>
                            <button class="btn btn-sm btn-success btn-salvar-item" title="Salvar">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-excluir-item" title="Excluir item">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            modalBody.innerHTML = html;

            // Bind botões de salvar
            modalBody.querySelectorAll('.btn-salvar-item').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tr = this.closest('tr');
                    const itemId = tr.getAttribute('data-item-id');
                    const novaQtd = tr.querySelector('.edit-qtd').value;

                    fetch('./modulos/faturas/action/update.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            'Codigo estoque': itemId,
                            'quantidade': novaQtd
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            alert('Item atualizado com sucesso!');
                            location.reload();
                        } else {
                            alert('Erro ao atualizar: ' + (data.message || 'Erro desconhecido'));
                        }
                    })
                    .catch(() => alert('Erro de conexão ao atualizar.'));
                });
            });

            // Bind botões de excluir item individual
            modalBody.querySelectorAll('.btn-excluir-item').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!confirm('Tem certeza que deseja excluir este item?')) return;

                    const tr = this.closest('tr');
                    const itemId = tr.getAttribute('data-item-id');

                    fetch('./modulos/faturas/action/delete.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: itemId })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            alert('Item excluído com sucesso!');
                            location.reload();
                        } else {
                            alert('Erro ao excluir: ' + (data.message || 'Erro desconhecido'));
                        }
                    })
                    .catch(() => alert('Erro de conexão ao excluir.'));
                });
            });
        }

        // Abre o modal (Bootstrap)
        const modal = new bootstrap.Modal(document.getElementById('modalEditarEstoque'));
        modal.show();
    }

    // REMOVER FATURA INTEIRA
    function removerFatura(fatura) {
        const itensFatura = todosItensEstoque.filter(item => item.numero === fatura);

        if (itensFatura.length === 0) {
            alert('Nenhum item encontrado para esta fatura.');
            return;
        }

        if (!confirm(`Tem certeza que deseja excluir TODOS os ${itensFatura.length} itens da fatura "${fatura === 'fabrica' ? 'Fábrica' : fatura}"?`)) {
            return;
        }

        // Coleta todos os IDs para excluir
        const ids = itensFatura.map(item => item['Codigo estoque']).filter(Boolean);

        fetch('./modulos/faturas/action/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Fatura excluída com sucesso!');
                location.reload();
            } else {
                alert('Erro ao excluir fatura: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(() => alert('Erro de conexão ao excluir fatura.'));
    }

    // BOTÕES DE FATURA
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
            atualizarListaAcoes();
        });
    });

    // RENDERIZAR TABELA
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>