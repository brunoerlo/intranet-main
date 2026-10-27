(function () {
    const form = document.getElementById('importFormProforma');
    const resultDiv = document.getElementById('resultProforma');
    const btnSubmit = document.getElementById('btnSubmitProforma');
    const btnPrint = document.getElementById('btn-print-proforma');

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const fileInput = document.getElementById('pdfFileProforma');
            if (!fileInput.files.length) {
                alert('Selecione um arquivo PDF de Fatura Proforma.');
                return;
            }

            const formData = new FormData(this);
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Processando...';
            resultDiv.innerHTML = '<div class="text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Lendo dados da fatura proforma...</div>';

            fetch('./modulos/faturas/action/importar_proforma.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(res => {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i class="fa-solid fa-upload me-1"></i> Processar Importação';

                    if (res.status === 'success') {
                        resultDiv.innerHTML = `
                            <div class="alert alert-success d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0">
                                <div>
                                    <i class="fa-solid fa-circle-check me-2"></i>
                                    ${res.message || 'Fatura Proforma importada com sucesso!'}
                                </div>
                                <button class="btn btn-outline-success btn-sm" id="btnRolarParaFatura">
                                    <i class="fa-solid fa-arrow-down me-1"></i> Ver Fatura Abaixo
                                </button>
                            </div>
                        `;

                        const btnRolar = document.getElementById('btnRolarParaFatura');
                        if (btnRolar) {
                            btnRolar.addEventListener('click', () => {
                                const docEl = document.getElementById('proforma-sheet-document');
                                if (docEl) docEl.scrollIntoView({ behavior: 'smooth' });
                            });
                        }

                        if (res.data) {
                            atualizarPaginaProforma(res.data);
                        }
                    } else {
                        resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>${res.error || 'Erro ao processar PDF.'}</div>`;
                    }
                })
                .catch(err => {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i class="fa-solid fa-upload me-1"></i> Processar Importação';
                    resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fa-solid fa-circle-xmark me-2"></i>Erro na requisição: ${err.message}</div>`;
                });
        });
    }

    if (btnPrint) {
        btnPrint.addEventListener('click', function () {
            window.print();
        });
    }

    function splitImportadorEndereco(end) {
        const clean = (end || '').replace(/\s+/g, ' ').trim();
        if (clean.length <= 70) return [clean, ''];
        const sub = clean.substring(0, 70);
        const lastSpace = sub.lastIndexOf(' ');
        if (lastSpace > 30) {
            return [
                clean.substring(0, lastSpace).trim(),
                clean.substring(lastSpace + 1).trim()
            ];
        }
        return [clean.substring(0, 70).trim(), clean.substring(70).trim()];
    }

    function atualizarPaginaProforma(data) {
        // Cabeçalho
        if (data.cabecalho) {
            setText('pf-num-fatura', data.cabecalho.numero || '');
            setText('pf-emissao', data.cabecalho.emissao || '');
            setText('pf-validade', data.cabecalho.validade || '');
        }

        // Cliente
        if (data.cliente) {
            setText('pf-importador', data.cliente.importador || '');
            const [end1, end2] = splitImportadorEndereco(data.cliente.importador_endereco || '');
            setText('pf-importador-end-1', end1);
            setText('pf-importador-end-2', end2);
            setText('pf-cosignatario', data.cliente.cosignatario || '');
            setText('pf-notificado', data.cliente.notificado || '');
        }

        // Detalhes Adicionais
        if (data.detalhes_adicionais) {
            setText('pf-pais-origem', data.detalhes_adicionais.pais_origem || '');
            setText('pf-equipamento', data.detalhes_adicionais.equipamento || '');
            setText('pf-modalidade-frete', data.detalhes_adicionais.modalidade_frete || '');
            setText('pf-lugar-embarque', data.detalhes_adicionais.lugar_embarque || '');
            setText('pf-lugar-transbordo', data.detalhes_adicionais.lugar_transbordo || '');
            setText('pf-lugar-descarga', data.detalhes_adicionais.lugar_descarga || '');

            const tt = (data.detalhes_adicionais.tempo_transito || '').trim();
            const ttParts = tt.split(/\s+/);
            setText('pf-tempo-transito-num', ttParts[0] || '');
            setText('pf-tempo-transito-un', ttParts.slice(1).join(' ') || '');
        }

        // Detalhes Envio
        if (data.detalhes_envio) {
            setText('pf-embalagem', data.detalhes_envio.embalagem || '');
            setText('pf-peso-liquido', data.detalhes_envio.peso_liquido || '');
            setText('pf-peso-bruto', data.detalhes_envio.peso_bruto || '');
            setText('pf-cubagem', data.detalhes_envio.cubagem || '');
            setText('pf-volumes', data.detalhes_envio.volumes || '');
            setText('pf-condicoes-venda', data.detalhes_envio.condicoes_venda || '');
            setText('pf-pagamento', data.detalhes_envio.pagamento || '');
        }

        // Itens na camada SVG
        const tbody = document.getElementById('pf-itens-body');
        if (tbody && Array.isArray(data.itens)) {
            tbody.innerHTML = '';
            const svgNS = 'http://www.w3.org/2000/svg';

            data.itens.slice(0, 30).forEach((item, idx) => {
                const yMain = (288.80 + idx * 17.12).toFixed(2);
                const ySub = (288.80 + idx * 17.12 - 0.48).toFixed(2);
                const g = document.createElementNS(svgNS, 'g');
                g.setAttribute('class', 'pf-item-row');

                const addSvgText = (x, y, text, anchor, cls, clip) => {
                    const t = document.createElementNS(svgNS, 'text');
                    t.setAttribute('x', x);
                    t.setAttribute('y', y);
                    if (anchor) t.setAttribute('text-anchor', anchor);
                    t.setAttribute('class', cls || 'f-cr-76');
                    if (clip) t.setAttribute('clip-path', clip);
                    t.textContent = text != null ? String(text) : '';
                    g.appendChild(t);
                };

                addSvgText('45.80', ySub, item.item, 'middle', 'f-cr-76');
                addSvgText('75.20', yMain, item.codigo, null, 'f-cr-76');
                addSvgText('128.64', yMain, item.ncm, null, 'f-cr-76');
                addSvgText('173.76', yMain, item.descricao, null, 'f-cr-76', 'url(#pf-clip-desc)');
                addSvgText('474.67', yMain, item.unidade, 'middle', 'f-cr-76');
                addSvgText('512.00', yMain, item.quantidade, 'middle', 'f-cr-76');
                addSvgText('555.78', yMain, item.peso_liq, 'middle', 'f-cr-76');
                addSvgText('584.48', yMain, 'USS', null, 'f-cr-76');
                addSvgText('636.44', yMain, item.preco_unitario, 'end', 'f-cr-76');
                addSvgText('669.25', ySub, item.peso_liq_total, 'middle', 'f-cr-76');
                addSvgText('702.67', ySub, 'USS', null, 'f-cr-76');
                addSvgText('760.89', ySub, item.preco_total, 'end', 'f-cr-76');

                tbody.appendChild(g);
            });
        }

        // Totais da tabela
        if (data.totais) {
            setText('pf-tot-peso-liq', data.totais.peso_liquido_total || '');
            setText('pf-tot-preco', data.totais.preco_total || '');
        }

        // Custos Internos
        if (data.custos_internos) {
            if (Array.isArray(data.custos_internos.itens)) {
                data.custos_internos.itens.forEach((ci, idx) => {
                    const val = (ci.valor || '-').trim();
                    const el = document.getElementById(`pf-custo-val-${idx}`);
                    if (el) {
                        const isDash = (val === '-' || val === '');
                        el.setAttribute('x', isDash ? '244.00' : '252.89');
                        el.setAttribute('text-anchor', isDash ? 'start' : 'end');
                        el.textContent = isDash ? '-' : val;
                    }
                });
            }
            setText('pf-custos-internos-tot', data.custos_internos.total || '');
        }

        // Resumo Financeiro
        if (data.resumo_financeiro) {
            setResumoVal('pf-resumo-custos-internos', data.resumo_financeiro.custos_internos || '-');
            setResumoVal('pf-resumo-frete', data.resumo_financeiro.frete || '-');
            setResumoVal('pf-resumo-seguro', data.resumo_financeiro.seguro || '-');
            setResumoVal('pf-resumo-desconto', data.resumo_financeiro.desconto || '-');
            setText('pf-resumo-total-geral', data.resumo_financeiro.total_geral || '');
        }

        // Rodapé
        if (data.rodape) {
            setText('pf-info-arquivo', data.rodape.info_arquivo || '');
        }
    }

    function setResumoVal(id, rawVal) {
        const el = document.getElementById(id);
        if (!el) return;
        const val = (rawVal || '-').trim();
        const isDash = (val === '-' || val === '');
        el.setAttribute('x', isDash ? '748.32' : '759.56');
        el.setAttribute('text-anchor', isDash ? 'start' : 'end');
        el.textContent = isDash ? '-' : val;
    }

    function setText(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    }
})();
