// CADASTRAR QUADRO
const checkConsolidado = document.getElementById('consolidado');
const divNomeConsolidado = document.getElementById('divNomeConsolidado');
const inputNomeConsolidado = document.getElementById('nomeConsolidado');

checkConsolidado.addEventListener('change', function () {
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


document.getElementById('formularioQuadro').addEventListener('submit', function (event) {
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

// FUNÇÕES QUADRO
// EDITAR
document.querySelectorAll('.edit-cad-btn').forEach(button => {
    button.addEventListener('click', function () {
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
    button.addEventListener('click', function () {
        const editRow = this.closest('.edit-row');
        if (editRow) {
            editRow.style.display = 'none';
        }
    });
});

// SALVAR EDIÇÃO
document.querySelectorAll('.edit-form-cad').forEach(form => {
    form.addEventListener('submit', function (event) {
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
    button.addEventListener('click', function () {
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
        thead.addEventListener('click', function (e) {
            const th = e.target.closest('th.sortable');
            if (!th) return;

            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
            const headerRow = th.parentElement;
            const colIndex = Array.from(headerRow.children).indexOf(th);

            // Pegar somente linhas de dados principais (não as edit-row)
            const rows = Array.from(tbody.querySelectorAll('tr:not(.edit-row)'));

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

                        const anoAtual = new Date().getFullYear();
                        // +40 é para priorizar datas que já estão definidas
                        return anoAtual * 10000 + (num * 100) + 40 + semana;
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
                if (dateA !== null || dateB !== null) {
                    const valA = dateA || 0;
                    const valB = dateB || 0;

                    // Joga as faturas sem data de estufagem sempre para o final da lista
                    if (valA === 0 && valB !== 0) return 1;
                    if (valB === 0 && valA !== 0) return -1;

                    return newOrder === 'asc' ? valA - valB : valB - valA;
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

// Ordenar automaticamente a coluna com a classe 'ordenaPadrao' logo após carregar o componente
setTimeout(() => {
    const colunaPadrao = document.querySelector('.ordenaPadrao');
    if (colunaPadrao) {
        colunaPadrao.click();
    }
}, 0);

// HISTORICO
const btnCadastro = document.getElementById('botaoCadastro');
if (btnCadastro) {
    btnCadastro.addEventListener('click', function () {
        // Verifica se a cor atual é cinza. Se for, tira a cor (voltando para o verde padrão do Bootstrap).
        if (this.style.backgroundColor === 'grey') {
            this.style.backgroundColor = '';
            this.style.borderColor = '';
        } else {
            this.style.backgroundColor = 'grey';
            this.style.borderColor = 'grey';
        }
    });
}

const btnHistorico = document.getElementById('botaoHistorico');
if (btnHistorico) {
    btnHistorico.addEventListener('click', function () {
        if (this.style.backgroundColor === 'grey') {
            this.style.backgroundColor = '';
            this.style.borderColor = '';
        } else {
            this.style.backgroundColor = 'grey';
            this.style.borderColor = 'grey';
        }
    });
}

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

// Ordenação por coluna — somente para tabelas do histórico
// (a tabela do quadro é tratada pelo sorter específico acima via #tr-thead)
document.querySelectorAll('th.sortable').forEach(th => {
    // Ignora th que pertencem à tabela do quadro
    if (th.closest('table')?.querySelector('#tr-thead')) return;

    th.dataset.order = 'none';

    th.addEventListener('click', function () {
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
printBtn.addEventListener('click', function () {
    print();
})

var btnExportarFaturas = document.getElementById('btn-exportar-faturas');
if (btnExportarFaturas) {
    btnExportarFaturas.addEventListener('click', () => {
        const faturasData = window.historicoFaturas;
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