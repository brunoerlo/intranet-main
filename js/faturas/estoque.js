//FORMULÁRIO DE CADASTRO
const campoCodigo = document.getElementById('codigo');
const campoDescricao = document.getElementById('descricao');

campoCodigo.addEventListener('blur', async function () {
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
document.getElementById('formCadastroEstoque').addEventListener('submit', function (event) {
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

// DADOS E ESTADO
const dadosEstoque = window.dadosEstoque;
const todosItensEstoque = window.todosItensEstoque;
let faturasSelecionadas = [];

// LISTA DE AÇÕES POR FATURA
function atualizarListaAcoes() {
    const container = document.getElementById('acoes-faturas');
    container.innerHTML = '';

    const lista = [...new Set([
        ...faturasSelecionadas,
        "sobra"
    ])];

    lista.forEach(fatura => {
        const linha = document.createElement('div');
        linha.className = 'acao-fatura';

        const nome = document.createElement('span');
        nome.textContent =
            fatura === "sobra" //if fatura é estritamente igual a 'sobra'
                ?
                "Sobra" // então mostra 'Sobra'
                :
                fatura; // senão fatura mantem igual

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
    // Filtra os itens dessa fatura / combinação fatura-cliente
    const itensFatura = todosItensEstoque.filter(item => {
        const nome = (item.nome || '').trim();
        const cliente = (item.cliente || '').trim();
        const label = (cliente !== '' && nome.toLowerCase() !== 'sobra') ? `${nome} - ${cliente}` : nome;
        return label === fatura || nome === fatura;
    });

    const modalBody = document.getElementById('modalFormulario');
    document.getElementById('modalNomeFatura').textContent = fatura

    if (itensFatura.length === 0) {
        modalBody.innerHTML = '<p class="text-center text-muted p-3">Nenhum item encontrado para esta fatura.</p>';
    } else {
        itensFatura.sort((a, b) => {
            return (a.descricao || '').localeCompare(b.descricao || '', 'pt-BR', {
                sensitivity: 'base'
            });
        })

        let html = '<div class="table-responsive shadow-sm rounded mb-3"><table class="table table-sm table-bordered text-center">';
        html += '<thead class="table-light"><tr><th>Código</th><th>Descrição</th><th>Quantidade</th><th>Ações</th></tr></thead><tbody>';


        itensFatura.forEach(item => {
            const itemId = item['Codigo estoque'] || '';
            html += `
                    <tr data-item-id="${itemId}">
                        <td class="menor">${item.codigo || ''}</td>
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
            btn.addEventListener('click', function () {
                const tr = this.closest('tr');
                const itemId = tr.getAttribute('data-item-id');
                const novaQtd = tr.querySelector('.edit-qtd').value;

                fetch('./modulos/faturas/action/update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
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
            btn.addEventListener('click', function () {
                if (!confirm('Tem certeza que deseja excluir este item?')) return;

                const tr = this.closest('tr');
                const itemId = tr.getAttribute('data-item-id');

                fetch('./modulos/faturas/action/deleteEstoque.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: itemId
                    })
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
    const itensFatura = todosItensEstoque.filter(item => {
        const nome = (item.nome || '').trim();
        const cliente = (item.cliente || '').trim();
        const label = (cliente !== '' && nome.toLowerCase() !== 'sobra') ? `${nome} - ${cliente}` : nome;
        return label === fatura || nome === fatura;
    });

    if (itensFatura.length === 0) {
        alert('Nenhum item encontrado para esta fatura.');
        return;
    }

    if (!confirm(`Tem certeza que deseja excluir TODOS os ${itensFatura.length} itens da fatura "${fatura}"?`)) {
        return;
    }

    // Coleta todos os IDs para excluir
    const ids = itensFatura.map(item => item['Codigo estoque']).filter(Boolean);

    fetch('./modulos/faturas/action/deleteEstoque.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            ids: ids
        })
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
    btn.addEventListener('click', function () {
        const fatura = this.dataset.fatura;

        if (faturasSelecionadas.includes(fatura)) {
            faturasSelecionadas = faturasSelecionadas.filter(f => f !== fatura);
            this.classList.remove('ativo');
        } else {
            faturasSelecionadas.push(fatura);
            faturasSelecionadas.sort((a, b) => {
                return a.localeCompare(b, undefined, {
                    numeric: true,
                    sensitivity: 'base'
                });
            });
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
    thead.innerHTML = '<th class="sortable">Código</th><th class="sortable">Descrição</th>';
    faturasSelecionadas.forEach(fatura => {
        const th = document.createElement('th');
        th.textContent = fatura;
        thead.appendChild(th);
    });

    const thSobra = document.createElement('th');
    thSobra.textContent = 'Sobra';
    thead.appendChild(thSobra);

    const thTotal = document.createElement('th');
    thTotal.textContent = 'Estoque Total';
    thead.appendChild(thTotal);

    let encontrou = false;

    linhas.forEach(linha => {
        const dados = JSON.parse(linha.getAttribute('data-json'));
        const quantidades = dados.quantidades;

        const total = calcularTotal(quantidades);
        const possuiFaturaSelecionada = faturasSelecionadas.some(f => quantidades[f] !== undefined);

        let mostrarLinha = true;
        if (faturasSelecionadas.length === 0) {
            if (total === 0) {
                mostrarLinha = false;
            }

        } else {
            if (!possuiFaturaSelecionada) {
                mostrarLinha = false;
            }
        }

        if (!mostrarLinha) {
            linha.style.display = 'none';
            return;
        }

        linha.style.display = '';
        encontrou = true;

        // Mantem as duas primeiras celulas (descricao e codigo) e reconstroi o resto
        const descricaoTd = linha.children[0].outerHTML;
        const codigoTd = linha.children[1].outerHTML;
        linha.innerHTML = descricaoTd + codigoTd;

        faturasSelecionadas.forEach(fatura => {
            const td = document.createElement('td');
            const qtd = quantidades[fatura];
            td.textContent = qtd !== undefined ? qtd : '-';
            linha.appendChild(td);
        });

        const qtdSobra = quantidades['sobra'] ?? '-';
        const tdSobra = document.createElement('td');
        tdSobra.textContent = qtdSobra;
        linha.appendChild(tdSobra);

        const tdTotal = document.createElement('td');
        tdTotal.textContent = total;
        linha.appendChild(tdTotal);

    });

    semResultados.style.display = encontrou ? 'none' : 'block';
}

function calcularTotal(quantidades) {
    let total = 0;
    Object.values(quantidades).forEach(valor => {
        total += parseInt(valor) || 0;
    });
    return total;
}

renderizarTabela();
atualizarListaAcoes();

// Ordenação da tabela
document.getElementById('tr-thead').closest('thead').addEventListener('click', function (e) {
    const th = e.target.closest('th.sortable');
    if (!th) return;

    const table = th.closest('table');
    const tbody = table.querySelector('tbody');
    const headerRow = th.parentElement;
    const colIndex = Array.from(headerRow.children).indexOf(th);

    // Pegar somente linhas visíveis
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(
        row => row.style.display !== 'none'
    );

    // Determinar direção: none→asc, asc→desc, desc→asc
    const currentOrder = th.dataset.order || 'none';
    const newOrder = (currentOrder === 'asc') ? 'desc' : 'asc';

    // Limpar indicadores de todas as colunas da mesma tabela
    headerRow.querySelectorAll('th.sortable').forEach(otherTh => {
        otherTh.dataset.order = 'none';
        otherTh.textContent = otherTh.textContent.replace(/ [▲▼]$/, '');
    });

    // Definir nova direção e indicador visual
    th.dataset.order = newOrder;
    th.textContent += (newOrder === 'asc') ? ' ▲' : ' ▼';

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

        return newOrder === 'asc' ?
            cellA.localeCompare(cellB, 'pt-BR') :
            cellB.localeCompare(cellA, 'pt-BR');
    });

    // Reinserir as linhas ordenadas
    rows.forEach(row => tbody.appendChild(row));
});