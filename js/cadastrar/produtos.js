// CADASTRAR

document.getElementById('formProduto').addEventListener('submit', function (e) {
    e.preventDefault(); // Evita o envio padrão

    const form = e.target;
    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
        .then(response => response.text())
        .then(result => {
            alert(result); // Você pode substituir por um modal ou mensagem no DOM
            form.reset(); // Limpa o formulário
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao cadastrar o produto.');
        });
});

// IMPORTAR 

document.getElementById('importFormProdutos').addEventListener('submit', function (event) {
    event.preventDefault();

    const formData = new FormData(this);

    fetch('./modulos/cadastrar/action/produtos/importar_produtos.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('result');
            if (data.status === 'success') {
                resultDiv.innerHTML = '<div class="alert alert-success">Produtos importados com sucesso!</div>';
            } else if (data.error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">Erro ao importar Produtos: ${data.error}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('result').innerHTML = `<div class="alert alert-danger">Erro ao importar Produtos: ${error.message}</div>`;
        });
});

// LISTAGEM DA TABELA

(function () {
    window.recarregarTabelaProdutos = function (queryString) {
        const contentDiv = document.getElementById("modulo-content");
        if (!contentDiv) return;

        const url = `carregar_modulo.php?modulo=cadastrar&submodulo=produtos&${queryString}`;

        fetch(url)
            .then(response => response.text())
            .then(html => {
                const tempDiv = document.createElement("div");
                tempDiv.innerHTML = html;

                const scripts = [...tempDiv.querySelectorAll("script")];
                scripts.forEach(s => s.remove());

                contentDiv.innerHTML = tempDiv.innerHTML;

                scripts.forEach(oldScript => {
                    const newScript = document.createElement("script");
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.textContent = oldScript.textContent;
                    document.body.appendChild(newScript);
                });
            })
            .catch(error => console.error("Erro ao carregar paginação:", error));
    };

    // Event delegation no document para os links de paginação
    if (window._produtoPaginacaoHandler) {
        document.removeEventListener('click', window._produtoPaginacaoHandler);
    }
    window._produtoPaginacaoHandler = function (e) {
        const link = e.target.closest('.link-paginacao');
        if (!link) return;
        e.preventDefault();
        const href = link.getAttribute('href');
        if (href && href.includes('?')) {
            window.recarregarTabelaProdutos(href.split('?')[1]);
        }
    };
    document.addEventListener('click', window._produtoPaginacaoHandler);

    // Formulário de filtro — sempre resetando p=1 ao filtrar
    const _formProdutos = document.getElementById('formFiltroProdutos');
    if (_formProdutos) {
        // Ouvir o evento de 'change' em qualquer select dentro do form
        const selects = _formProdutos.querySelectorAll('select');
        selects.forEach(select => {
            select.addEventListener('change', function (e) {
                e.preventDefault();
                const params = new URLSearchParams(new FormData(_formProdutos));
                params.set('p', '1'); // volta pra página 1 ao aplicar filtro
                window.recarregarTabelaProdutos(params.toString());
            });
        });
    }

    // PESQUISA DE PRODUTOS
    let timerBusca; // Variável para controlar o debounce

    document.getElementById('filtro-produtos').addEventListener('input', function () {
        // Cancela o timer anterior se o usuário continuar digitando
        clearTimeout(timerBusca);

        timerBusca = setTimeout(() => {
            const _formProdutos = document.getElementById('formFiltroProdutos');

            // Pega todos os dados do formulário (incluindo o input de busca, que agora deve ter name="termo_busca")
            const params = new URLSearchParams(new FormData(_formProdutos));

            // Volta para a página 1 sempre que houver uma nova pesquisa
            params.set('p', '1');

            // Recarrega a tabela enviando os parâmetros para o PHP
            window.recarregarTabelaProdutos(params.toString());

        }, 1000);
    });
})();

// EDITAR
document.querySelectorAll('.edit-cad-btn').forEach(button => {
    button.addEventListener('click', function () {
        const row = this.closest('tr');
        const editRow = row.nextElementSibling;

        if (editRow && editRow.classList.contains('edit-row')) {
            // Alterna a exibição da linha de edição
            if (editRow.style.display === 'none' || editRow.style.display === '') {
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
document.querySelectorAll('.edit-form-cad-produtos').forEach(form => {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        fetch('./modulos/cadastrar/action/produtos/updateProduto.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Atualizado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao atualizar produto: ' + (data.message || data.error || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Ocorreu um erro ao tentar atualizar o produto.');
            });
    });
});

document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function () {
        const faturaId = this.getAttribute('data-id');
        if (confirm("Tem certeza que deseja excluir este produto?")) {
            fetch('./modulos/cadastrar/action/produtos/deleteProduto.php', {
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
                        alert('Produto excluído com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao excluir produto: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro ao tentar excluir o produto.');
                });
        }
    });
})

document.querySelectorAll('th.sortable').forEach(th => {
    // Ignora th que pertencem à tabela do quadro
    if (th.closest('table')?.querySelector('#tr-thead')) return;

    th.dataset.order = 'none';

    th.addEventListener('click', function () {
        const table = this.closest('table');
        const tbody = table.querySelector('tbody');
        const headerRow = this.parentElement;
        const colIndex = Array.from(headerRow.children).indexOf(this);

        // PEGA AS LINHAS EM PARES (Linha do Produto + Linha de Edição)
        const rowPairs = Array.from(tbody.querySelectorAll('tr:not(.edit-row)')).map(row => {
            return {
                mainRow: row,
                editRow: row.nextElementSibling && row.nextElementSibling.classList.contains('edit-row') ? row.nextElementSibling : null
            };
        });

        const currentOrder = this.dataset.order;
        const newOrder = (currentOrder === 'asc') ? 'desc' : 'asc';

        // Limpar indicadores
        headerRow.querySelectorAll('th.sortable').forEach(otherTh => {
            otherTh.dataset.order = 'none';
            otherTh.textContent = otherTh.textContent.replace(/ [▲▼]$/, '');
        });

        this.dataset.order = newOrder;
        this.textContent += (newOrder === 'asc') ? ' ▲' : ' ▼';

        // ORDENA OS PARES
        rowPairs.sort((a, b) => {
            const cellA = (a.mainRow.children[colIndex]?.textContent || '').trim().toLowerCase();
            const cellB = (b.mainRow.children[colIndex]?.textContent || '').trim().toLowerCase();

            const numA = parseFloat(cellA.replace(',', '.'));
            const numB = parseFloat(cellB.replace(',', '.'));

            if (!isNaN(numA) && !isNaN(numB)) {
                return newOrder === 'asc' ? numA - numB : numB - numA;
            }

            return newOrder === 'asc' ?
                cellA.localeCompare(cellB, 'pt-BR') :
                cellB.localeCompare(cellA, 'pt-BR');
        });

        // REINSERE OS PARES JUNTOS NO HTML
        rowPairs.forEach(pair => {
            tbody.appendChild(pair.mainRow);
            if (pair.editRow) {
                tbody.appendChild(pair.editRow);
            }
        });
    });
});

// EXPORTAR
var btnExportarProdutos = document.getElementById('btn-exportar-produtos');
if (btnExportarProdutos) {
    btnExportarProdutos.addEventListener('click', () => {
        const produtosData = window.planilhaProdutos;
        if (!Array.isArray(produtosData) || produtosData.length === 0) {
            alert('Nenhum produto disponível para exportar.');
            return;
        }
        const headers = Object.keys(produtosData[0]);
        const main = produtosData.map((item) => {
            return Object.values(item).map(val => `"${String(val ?? '').replace(/"/g, '""')}"`).join(',');
        });
        const csv = [headers.join(','), ...main].join('\n');

        const blob = new Blob([csv], {
            type: 'text/csv;charset=utf-8;'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.download = 'produtos.csv';
        a.href = url;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    });
}