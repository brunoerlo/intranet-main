// CADASTRAR
document.getElementById('tipo').addEventListener('change', function () {
    const tipo = this.value;

    // Oculta todos os grupos inicialmente
    document.querySelectorAll('.tipo-grupo').forEach(div => div.classList.add('d-none'));
    document.getElementById('enderecoCampos').classList.add('d-none');
    document.getElementById('camposContato').classList.add('d-none');

    // Desativa todos os campos
    document.querySelectorAll('#formCadastroCliente input, #formCadastroCliente textarea, #formCadastroCliente select').forEach(el => {
        if (el.id !== 'tipo') el.required = false;
    });

    if (tipo === 'cpf') {
        document.getElementById('grupo-cpf').classList.remove('d-none');
        document.getElementById('enderecoCampos').classList.remove('d-none');
        document.getElementById('camposContato').classList.remove('d-none');
        document.getElementById('cpf').required = true;
        document.getElementById('nomeCompleto').required = true;
    } else if (tipo === 'cnpj') {
        document.getElementById('grupo-cnpj').classList.remove('d-none');
        document.getElementById('enderecoCampos').classList.remove('d-none');
        document.getElementById('camposContato').classList.remove('d-none');
        ['cnpj', 'razaoSocial', 'inscricaoEstadual', 'pessoaContato'].forEach(id => {
            document.getElementById(id).required = true;
        });
    } else if (tipo === 'exterior') {
        document.getElementById('grupo-exterior').classList.remove('d-none');
        document.getElementById('camposContato').classList.remove('d-none');
        ['nomeImportador', 'docImportador', 'enderecoExterior', 'pais'].forEach(id => {
            document.getElementById(id).required = true;
        });
    }

    // Campos comuns
    if (tipo) {
        document.getElementById('telefone').required = true;
        document.getElementById('email').required = true;
    }
});

document.getElementById('formCadastroCliente').addEventListener('submit', function (event) {
    event.preventDefault(); // Evita o envio tradicional do formulário

    // Coleta os dados do formulário
    const formData = new FormData(this);

    // Converte os dados do FormData para um objeto simples
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });

    // Envia os dados via AJAX
    fetch('./modulos/cadastrar/action/clientes/cadastrarCliente.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cliente cadastrado com sucesso!');
                document.getElementById('formCadastroCliente').reset();
                location.reload();
            } else {
                alert('Erro ao cadastrar cliente: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao cadastrar o cliente.');
        });
});

// IMPORTAR

document.getElementById('importFormClientes').addEventListener('submit', function (event) {
    event.preventDefault();

    const formData = new FormData(this);

    fetch('./modulos/cadastrar/action/clientes/importar_clientes.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('result');
            if (data.status === 'success') {
                resultDiv.innerHTML = '<div class="alert alert-success">Clientes importados com sucesso!</div>';
            } else if (data.error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">Erro ao importar clientes: ${data.error}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('result').innerHTML = `<div class="alert alert-danger">Erro ao importar clientes: ${error.message}</div>`;
        });
});

// LISTAGEM DA TABELA

(function () {
    window.recarregarTabelaClientes = function (queryString) {
        const contentDiv = document.getElementById("modulo-content");
        if (!contentDiv) return;

        const url = `carregar_modulo.php?modulo=cadastrar&submodulo=clientes&${queryString}`;

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
    if (window._clientesPaginacaoHandler) {
        document.removeEventListener('click', window._clientesPaginacaoHandler);
    }
    window._clientesPaginacaoHandler = function (e) {
        const link = e.target.closest('.link-paginacao');
        if (!link) return;
        e.preventDefault();
        const href = link.getAttribute('href');
        if (href && href.includes('?')) {
            window.recarregarTabelaClientes(href.split('?')[1]);
        }
    };
    document.addEventListener('click', window._clientesPaginacaoHandler);

    // Formulário de filtro — sempre resetando p=1 ao filtrar
    const _formClientes = document.getElementById('formFiltroClientes');
    if (_formClientes) {
        // Ouvir o evento de 'change' em qualquer select dentro do form
        const selects = _formClientes.querySelectorAll('select');
        selects.forEach(select => {
            select.addEventListener('change', function (e) {
                e.preventDefault();
                const params = new URLSearchParams(new FormData(_formClientes));
                params.set('p', '1'); // volta pra página 1 ao aplicar filtro
                window.recarregarTabelaClientes(params.toString());
            });
        });
    }

    // PESQUISA DE CLIENTES
    let timerBusca; // Variável para controlar o debounce

    document.getElementById('filtro-clientes').addEventListener('input', function () {
        // Cancela o timer anterior se o usuário continuar digitando
        clearTimeout(timerBusca);

        timerBusca = setTimeout(() => {
            const _formClientes = document.getElementById('formFiltroClientes');

            // Pega todos os dados do formulário (incluindo o input de busca, que agora deve ter name="termo_busca")
            const params = new URLSearchParams(new FormData(_formClientes));

            // Volta para a página 1 sempre que houver uma nova pesquisa
            params.set('p', '1');

            // Recarrega a tabela enviando os parâmetros para o PHP
            window.recarregarTabelaClientes(params.toString());

        }, 1000);
    });
})();

// DELETE
document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function () {
        const clienteId = this.getAttribute('data-id');
        if (confirm("Tem certeza que deseja excluir este cliente?")) {
            fetch('./modulos/cadastrar/action/clientes/deleteCliente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: clienteId
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Cliente excluído com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao excluir o cliente: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro ao tentar excluir o cliente.');
                });
        }
    });
});

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
document.querySelectorAll('.edit-form-cad-clientes').forEach(form => {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        fetch('./modulos/cadastrar/action/clientes/updateCliente.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Atualizado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao atualizar cliente: ' + (data.message || data.error || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Ocorreu um erro ao tentar atualizar o cliente.');
            });
    });
});

document.addEventListener("click", function (e) {
    const td = e.target.closest("td.copy-container");
    if (td && td.closest("#tabela-clientes")) {
        const text = td.innerText.trim();

        // Copia para a área de transferência
        const tempInput = document.createElement("input");
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand("copy");
        document.body.removeChild(tempInput);

        // Cria o tooltip
        const tooltip = document.createElement("div");
        tooltip.innerText = "Copiado!";
        tooltip.style.position = "absolute";
        tooltip.style.background = "#000";
        tooltip.style.color = "#fff";
        tooltip.style.padding = "4px 8px";
        tooltip.style.borderRadius = "4px";
        tooltip.style.fontSize = "12px";
        tooltip.style.zIndex = "9999";
        tooltip.style.pointerEvents = "none";
        tooltip.style.opacity = "0";
        tooltip.style.transition = "opacity 0.3s ease";

        document.body.appendChild(tooltip);

        // Posição do tooltip (acima do td clicado)
        const rect = td.getBoundingClientRect();
        tooltip.style.left = `${rect.left + window.scrollX + rect.width / 2 - tooltip.offsetWidth / 2}px`;
        tooltip.style.top = `${rect.top + window.scrollY - 30}px`;

        // Mostra e depois remove
        requestAnimationFrame(() => {
            tooltip.style.opacity = "1";
        });

        setTimeout(() => {
            tooltip.style.opacity = "0";
            setTimeout(() => {
                tooltip.remove();
            }, 300);
        }, 1200);
    }
});

document.getElementById('th-thead-clientes').closest('thead').addEventListener('click', function (e) {
    const th = e.target.closest('th.sortable');
    if (!th) return;

    const table = th.closest('table');
    const tbody = table.querySelector('tbody');
    const headerRow = th.parentElement;
    const colIndex = Array.from(headerRow.children).indexOf(th);

    // Pegar as linhas principais e pareá-las com suas respectivas linhas de edição
    const rowPairs = Array.from(tbody.querySelectorAll('tr:not(.edit-row)')).map(row => {
        return {
            mainRow: row,
            editRow: row.nextElementSibling && row.nextElementSibling.classList.contains('edit-row') ? row.nextElementSibling : null
        };
    });

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

    // Ordenar os pares de linhas pela coluna clicada
    rowPairs.sort((a, b) => {
        let cellA = (a.mainRow.children[colIndex]?.textContent || '').trim().toLowerCase();
        let cellB = (b.mainRow.children[colIndex]?.textContent || '').trim().toLowerCase();

        // Se for a coluna de CPF ou CNPJ (índice 1), remove a pontuação para comparar apenas os números
        if (colIndex === 1) {
            cellA = cellA.replace(/\D/g, '');
            cellB = cellB.replace(/\D/g, '');
        }

        // Usar localeCompare com numeric: true resolve a ordenação natural de números e letras juntos
        return newOrder === 'asc' ?
            cellA.localeCompare(cellB, 'pt-BR', {
                numeric: true
            }) :
            cellB.localeCompare(cellA, 'pt-BR', {
                numeric: true
            });
    });

    // Reinserir as linhas ordenadas mantendo o par junto
    rowPairs.forEach(pair => {
        tbody.appendChild(pair.mainRow);
        if (pair.editRow) {
            tbody.appendChild(pair.editRow);
        }
    });
});

// EXPORTAR
var btnExportarClientes = document.getElementById('btn-exportar-clientes');
if (btnExportarClientes) {
    btnExportarClientes.addEventListener('click', () => {
        const clientesData = window.planilhaClientes;
        if (!Array.isArray(clientesData) || clientesData.length === 0) {
            alert('Nenhum cliente disponível para exportar.');
            return;
        }
        const headers = Object.keys(clientesData[0]);
        const main = clientesData.map((item) => {
            return Object.values(item).map(val => `"${String(val ?? '').replace(/"/g, '""')}"`).join(',');
        });
        const csv = [headers.join(','), ...main].join('\n');

        const blob = new Blob([csv], {
            type: 'text/csv;charset=utf-8;'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.download = 'clientes.csv';
        a.href = url;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    });
}