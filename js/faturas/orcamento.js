(function () {
    const form = document.getElementById('importForm');
    const resultDiv = document.getElementById('result');
    const btnSubmit = document.getElementById('btnSubmit');

    let dadosOrcamentoAtual = null;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const fileInput = document.getElementById('pdfFile');
        if (!fileInput.files.length) {
            alert('Selecione um arquivo PDF.');
            return;
        }

        const formData = new FormData(this);
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Processando...';
        resultDiv.innerHTML = '<div class="text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Lendo dados do orçamento...</div>';

        fetch('./modulos/faturas/action/transformarOrcamento.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Processar';

                if (data.status === 'success') {
                    dadosOrcamentoAtual = data;
                    preencherModal(data);

                    // Mensagem simples e botão para reabrir modal no resultDiv
                    resultDiv.innerHTML = `
                        <div class="alert alert-success d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0">
                            <div>
                                <i class="fa-solid fa-circle-check me-2"></i>
                                Orçamento <strong>${data.numeroOrcamento || ''}</strong> processado com sucesso! (${data.itens.length} itens)
                            </div>
                            <button class="btn btn-primary btn-sm" id="btnReabrirModal">
                                <i class="me-1"></i> Ver Produtos do Orçamento
                            </button>
                        </div>
                    `;

                    document.getElementById('btnReabrirModal').addEventListener('click', function () {
                        abrirModal();
                    });

                    // Abre o modal automaticamente no mesmo formato do faturas.php
                    abrirModal();
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">${data.error || 'Erro ao processar PDF.'}</div>`;
                }
            })
            .catch(err => {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Processar';
                resultDiv.innerHTML = `<div class="alert alert-danger">Erro na requisição: ${err.message}</div>`;
            });
    });

    function abrirModal() {
        const modalEl = document.getElementById('modalProdutosOrcamento');
        if (modalEl && window.bootstrap) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    function preencherModal(data) {
        const modalNome = document.getElementById('modalNomeOrcamento');
        const horario = new Date().toLocaleString('pt-BR');
        const dataFormatada = horario.slice(0, 10); //horario sem hora, apenas dia/mes/ano
        if (modalNome) {
            let titulo = dataFormatada;
            modalNome.textContent = titulo;
        }

        const tbody = document.getElementById('modalProdutosBody');
        tbody.innerHTML = '';

        if (!data.itens || data.itens.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nenhum produto encontrado.</td></tr>';
            return;
        }

        data.itens.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center col-codigo">${item.codigo}</td>
                <td class="col-descricao">${item.descricao}</td>
                <td class="text-center col-un">${item.unidade}</td>
                <td class="text-center fw-semibold col-qtd">${item.quantidade}</td>
                <td class="text-start col-unitario">US$ ${item.unitario}</td>
                <td class="text-start col-total">US$ ${item.total}</td>
            `;
            tbody.appendChild(tr);
        });

        const totalEl = document.getElementById('modalTotalOrcamento');
        if (totalEl) {
            totalEl.textContent = `US$ ${data.totalGeral || '0,00'}`;
        }
    }

    // Exportação para CSV (formatado direto pro cliente com descrições em ordem alfabética)
    const btnCsv = document.getElementById('btnExportarCsv');
    if (btnCsv) {
        btnCsv.addEventListener('click', function () {
            if (!dadosOrcamentoAtual || !dadosOrcamentoAtual.itens) return;
            const dataFormatada = new Date().toLocaleString('pt-BR').slice(0, 10);
            const itens = dadosOrcamentoAtual.itens;
            const separador = ';';

            let csvConteudo = '';
            if (dadosOrcamentoAtual.numeroOrcamento || dadosOrcamentoAtual.cliente) {
                csvConteudo += `Saldos dia: ${dataFormatada}\n`;
            }

            csvConteudo += ['Código', 'Descrição', 'UN', 'Quantidade', 'Preço Unitário', 'Preço Total'].join(separador) + '\n';

            itens.forEach(item => {
                const linha = [
                    item.codigo,
                    item.descricao,
                    item.unidade,
                    item.quantidade,
                    item.unitario,
                    item.total
                ];
                csvConteudo += linha.map(campo => `"${String(campo).replace(/"/g, '""')}"`).join(separador) + '\n';
            });

            csvConteudo += `;;;;Total:;"${dadosOrcamentoAtual.totalGeral}"\n`;

            const blob = new Blob(['\uFEFF' + csvConteudo], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            const nomeArq = `orcamento_${dataFormatada}.csv`;
            a.download = nomeArq;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    }

    // Botão de impressão (igual ao faturas.js)
    const printBtn = document.getElementById('btn-print');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            print();
        });
    }

    // Ordenação dinâmica ao clicar nas colunas da tabela
    function ordenacaoAlfabetica(tabela) {
        if (!tabela) return;
        const thead = tabela.querySelector('thead');
        if (!thead) return;

        thead.addEventListener('click', function (e) {
            const th = e.target.closest('th.sortable');
            if (!th) return;

            const tbody = tabela.querySelector('tbody');
            const headerRow = th.parentElement;
            const colIndex = Array.from(headerRow.children).indexOf(th);

            const rows = Array.from(tbody.querySelectorAll('tr')).filter(
                row => row.style.display !== 'none'
            );

            const currentOrder = th.dataset.order || 'none';
            const newOrder = (currentOrder === 'asc') ? 'desc' : 'asc';

            headerRow.querySelectorAll('th.sortable').forEach(otherTh => {
                otherTh.dataset.order = 'none';
                otherTh.querySelectorAll('.sort-arrow').forEach(el => el.remove());
                otherTh.textContent = otherTh.textContent.replace(/\s*[▲▼↕]\s*$/g, '').trim();
            });

            // Definir nova direção (indicador visual controlado via CSS)
            th.dataset.order = newOrder;

            rows.sort((a, b) => {
                const cellA = (a.children[colIndex]?.textContent || '').trim();
                const cellB = (b.children[colIndex]?.textContent || '').trim();

                // Remove prefixo monetário caso exista
                const limpoA = cellA.replace(/^[A-Z]{1,3}\$\s*/i, '').trim();
                const limpoB = cellB.replace(/^[A-Z]{1,3}\$\s*/i, '').trim();

                // Só considera numérico se NÃO contiver letras (evita que descrições com medidas tipo "6.1/2" ou "440mm" virem números)
                const isNumA = /^[\d\.\s]+(?:,\d+)?$/.test(limpoA) && !/[a-zA-Z]/.test(limpoA);
                const isNumB = /^[\d\.\s]+(?:,\d+)?$/.test(limpoB) && !/[a-zA-Z]/.test(limpoB);

                if (isNumA && isNumB) {
                    const numA = parseFloat(limpoA.replace(/\./g, '').replace(',', '.'));
                    const numB = parseFloat(limpoB.replace(/\./g, '').replace(',', '.'));
                    if (!isNaN(numA) && !isNaN(numB)) {
                        return newOrder === 'asc' ? numA - numB : numB - numA;
                    }
                }

                // Ordenação alfabética padrão em português
                return newOrder === 'asc' ?
                    cellA.localeCompare(cellB, 'pt-BR', { sensitivity: 'base' }) :
                    cellB.localeCompare(cellA, 'pt-BR', { sensitivity: 'base' });
            });

            rows.forEach(row => tbody.appendChild(row));
        });
    }

    const tabelaModal = document.getElementById('show-print');
    if (tabelaModal) {
        ordenacaoAlfabetica(tabelaModal);
    }
})();