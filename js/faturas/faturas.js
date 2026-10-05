// IMPORTAR

document.getElementById('importForm').addEventListener('submit', function (event) {
    event.preventDefault();

    const formData = new FormData(this);

    fetch('./modulos/faturas/action/importar_faturas.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('result');
            if (data.status === 'success') {
                resultDiv.innerHTML = '<div class="alert alert-success">Fatura importada com sucesso!</div>';
                location.reload();
            } else if (data.error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">Erro ao importar fatura: ${data.error}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('result').innerHTML = `<div class="alert alert-danger">Erro ao importar fatura: ${error.message}</div>`;
        });
});

// LISTAGEM
// Todos os itens de faturas já vêm do PHP, prontos pra filtrar no JS
const todosOsItensFaturas = window.listagemFaturas;

document.querySelectorAll('.linha-fatura-unica').forEach(function (linhaFatura) {
    linhaFatura.addEventListener('click', function () {
        const nomeClicado = this.querySelector('.clicavel').textContent.trim();

        const faturasAgrupadas = {};

        todosOsItensFaturas.forEach(item => {
            if (!faturasAgrupadas[item.nomeFatura]) {
                faturasAgrupadas[item.nomeFatura] = [];
            }

            faturasAgrupadas[item.nomeFatura].push(item);
        });

        const itensDaFatura = faturasAgrupadas[nomeClicado] || [];

        // Monta as linhas do modal
        const tbody = document.getElementById('modalProdutosBody');
        tbody.innerHTML = '';

        if (itensDaFatura.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Nenhum produto encontrado.</td></tr>';
        } else {
            itensDaFatura.sort((a, b) => {
                return a.descricao.localeCompare(b.descricao, 'pt-BR', {
                    sensitivity: 'base'
                });
            })
            itensDaFatura.forEach(function (item) {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                        <td class="sortable">${item.codigo ?? ''}</td>
                        <td class="sortable">${item.descricao ?? ''}</td>
                        <td>${item.unidadeMedida ?? ''}</td>
                        <td>${item.quantidade ?? ''}</td>
                        <td>${item.preco ?? ''}</td>
                    `;
                tbody.appendChild(tr);
            });
        }

        document.getElementById('modalNomeFatura').textContent = nomeClicado;

        // Abre o modal (Bootstrap)
        const modal = new bootstrap.Modal(document.getElementById('modalProdutosFatura'));
        modal.show();
    });
});

//Deletar
document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function (e) {
        e.stopPropagation();
        const faturaId = this.getAttribute('data-id');
        const linha = this.closest('tr');

        if (confirm("Tem certeza que deseja excluir esta fatura?")) {
            fetch('./modulos/faturas/action/deleteFaturas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    nomeFatura: faturaId
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Fatura excluída com sucesso!');
                        if (linha) linha.remove();
                        location.reload();
                    } else {
                        alert('Erro ao excluir a fatura: ' + data.message);
                    }
                })
                .catch(() => alert('Ocorreu um erro ao excluir a fatura.'));
        }
    });
});

function ordenacaoAlfabetica(tabela) {
    // Ordenação
    tabela.querySelector('thead').closest('thead').addEventListener('click', function (e) {
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
}

ordenacaoAlfabetica(document.getElementById('tabela-faturas-unicas'));
ordenacaoAlfabetica(document.getElementById('modalProdutosFatura').querySelector('table'));

const printBtn = document.getElementById('btn-print');

//impressao
printBtn.addEventListener('click', function () {
    print();
});