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

    function paginateProformaItems(allItems) {
        const list = Array.isArray(allItems) ? allItems.slice() : [];
        list.sort((a, b) => {
            const descA = String((a && a.descricao) || '').trim();
            const descB = String((b && b.descricao) || '').trim();
            return descA.localeCompare(descB, 'pt-BR', { sensitivity: 'base' });
        });
        list.forEach((it, idx) => {
            if (it && typeof it === 'object') {
                it.item = idx + 1;
            }
        });
        if (list.length <= 29) {
            return [list];
        }
        const pages = [];
        let remaining = list.slice();
        while (remaining.length > 29) {
            if (remaining.length > 40) {
                pages.push(remaining.slice(0, 40));
                remaining = remaining.slice(40);
            } else {
                pages.push(remaining.slice(0, 29));
                remaining = remaining.slice(29);
            }
        }
        pages.push(remaining);
        return pages;
    }

    function escapeXml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function atualizarPaginaProforma(data) {
        const container = document.getElementById('proforma-pages-container');
        if (!container) return;

        const cab = data.cabecalho || {};
        const cli = data.cliente || {};
        const detAd = data.detalhes_adicionais || {};
        const detEnv = data.detalhes_envio || {};
        const totais = data.totais || {};
        const ci = data.custos_internos || {};
        const resFin = data.resumo_financeiro || {};
        const rod = data.rodape || {};

        const [impEnd1, impEnd2] = splitImportadorEndereco(cli.importador_endereco || '');
        const tt = (detAd.tempo_transito || '').trim();
        const ttParts = tt.split(/\s+/);
        const tempoNum = ttParts[0] || '';
        const tempoUn = ttParts.slice(1).join(' ') || '';

        const custosLabels = [
            ['Taxas Bancárias e Diferença Cambial:', 825.44],
            ['Desembaraço Aduaneiro:', 836.64],
            ['Embalagens e Certificado de Origem:', 847.84],
            ['Custos de Armazenagem:', 859.04],
            ['Fretes Terrestres:', 870.40],
            ['Estufagem:', 881.60],
            ['Taxas Portuárias:', 892.80],
            ['Taxas Armador:', 904.00],
            ['Remessa de Documentos:', 915.20],
            ['Manuseio, Presença de Carga e Cia Aérea', 926.40]
        ];
        const ciItens = Array.isArray(ci.itens) ? ci.itens : [];
        const totCi = (ci.total || '').trim();

        const obsRaw = (ci.observacao || '').trim();
        const obsLines = obsRaw ? obsRaw.split(/\r\n|\r|\n/) : ['', ''];
        const obsLine1 = (obsLines[0] || '').trim();
        const obsLine2 = (obsLines[1] || '').trim();

        const resCustos = (resFin.custos_internos || '').trim();
        const resFrete = (resFin.frete || '').trim();
        const resSeguro = (resFin.seguro || '').trim();
        const resDesc = (resFin.desconto || '').trim();
        const resTotal = (resFin.total_geral || '').trim();

        const dots300 = '.'.repeat(310);
        const rowHeight = 17.12;
        const pageHeight = 1122.56;

        const pagesItems = paginateProformaItems(data.itens);
        const totalPages = pagesItems.length;

        let htmlPages = '';

        pagesItems.forEach((pageItems, pIdx) => {
            const pageNum = pIdx + 1;
            const isLastPage = (pageNum === totalPages);
            const pageRows = Math.max(1, pageItems.length);

            let deltaY = 0;
            let tableBgH, clipDescH, borderLeftH, borderRgtH, gridLineCnt;

            if (isLastPage) {
                deltaY = (pageRows - 29) * rowHeight;
                tableBgH = 530.88 + deltaY;
                clipDescH = 514.00 + deltaY;
                borderLeftH = 965.60 + deltaY;
                borderRgtH = 964.16 + deltaY;
                gridLineCnt = pageRows + 1;
            } else {
                tableBgH = 16.80 + (pageRows * rowHeight);
                clipDescH = (pageRows * rowHeight) + 2.00;
                borderLeftH = 240.80 + (pageRows * rowHeight);
                borderRgtH = 239.36 + (pageRows * rowHeight);
                gridLineCnt = pageRows;
            }

            let itemsSvg = '';
            pageItems.forEach((it, idx) => {
                const yMain = (288.80 + idx * rowHeight).toFixed(2);
                const ySub = (288.80 + idx * rowHeight - 0.48).toFixed(2);
                itemsSvg += `
                    <g class="pf-item-row">
                        <text x="45.80" y="${ySub}" text-anchor="middle" class="f-cr-76">${escapeXml(it.item)}</text>
                        <text x="94.15" y="${yMain}" text-anchor="middle" class="f-cr-76">${escapeXml(it.codigo)}</text>
                        <text x="146.00" y="${yMain}" text-anchor="middle" class="f-cr-76">${escapeXml(it.ncm)}</text>
                        <text x="173.76" y="${yMain}" class="f-cr-76" clip-path="url(#pf-clip-desc-p${pageNum})">${escapeXml(it.descricao)}</text>
                        <text x="474.67" y="${yMain}" text-anchor="middle" class="f-cr-76">${escapeXml(it.unidade)}</text>
                        <text x="512.00" y="${yMain}" text-anchor="middle" class="f-cr-76">${escapeXml(it.quantidade)}</text>
                        <text x="555.78" y="${yMain}" text-anchor="middle" class="f-cr-76">${escapeXml(it.peso_liq)}</text>
                        <text x="584.48" y="${yMain}" class="f-cr-76">USS</text>
                        <text x="636.44" y="${yMain}" text-anchor="end" class="f-cr-76">${escapeXml(it.preco_unitario)}</text>
                        <text x="669.25" y="${ySub}" text-anchor="middle" class="f-cr-76">${escapeXml(it.peso_liq_total)}</text>
                        <text x="702.67" y="${ySub}" class="f-cr-76">USS</text>
                        <text x="760.89" y="${ySub}" text-anchor="end" class="f-cr-76">${escapeXml(it.preco_total)}</text>
                    </g>`;
            });

            let gridLinesSvg = '';
            for (let i = 1; i <= gridLineCnt; i++) {
                const yLine = (273.92 + i * rowHeight).toFixed(2);
                gridLinesSvg += `<rect x="24.00" y="${yLine}" width="739.20" height="0.80" fill="#000000"/>`;
            }

            let bottomBlockSvg = '';
            if (isLastPage) {
                let custosRowsSvg = '';
                custosLabels.forEach((c, idx) => {
                    const val = ((ciItens[idx] && ciItens[idx].valor) || '').trim();
                    const yCusto = c[1].toFixed(2);
                    const isDash = (val === '-');
                    custosRowsSvg += `
                        <text x="170.85" y="${yCusto}" text-anchor="end" class="f-cr-76">${escapeXml(c[0])}</text>
                        <text x="176.80" y="${yCusto}" class="f-cr-69">USS</text>
                        ${val !== '' ? `<text x="${isDash ? '244.00' : '252.89'}" y="${yCusto}" text-anchor="${isDash ? 'start' : 'end'}" class="f-cr-69">${isDash ? '-' : escapeXml(val)}</text>` : ''}`;
                });

                const fmtResumo = (v, y) => {
                    if (!v) return '';
                    const isDash = (v === '-');
                    return `<text x="${isDash ? '748.32' : '759.56'}" y="${y}" text-anchor="${isDash ? 'start' : 'end'}" class="f-cr-85">${isDash ? '-' : escapeXml(v)}</text>`;
                };

                bottomBlockSvg = `
                <g class="pf-dynamic-bottom" transform="translate(0, ${deltaY.toFixed(2)})">
                    <g class="pf-bg-fills-bottom" shape-rendering="crispEdges">
                        <rect x="23.84" y="787.84" width="740.16" height="17.28" fill="#EDEDED"/>
                        <rect x="23.84" y="804.96" width="233.12" height="12.00" fill="#D9D9D9"/>
                        <rect x="256.80" y="804.96" width="507.20" height="12.00" fill="#FFFFFF"/>
                        <rect x="23.84" y="816.80" width="233.12" height="11.36" fill="#EDEDED"/>
                        <rect x="256.80" y="816.80" width="507.20" height="11.36" fill="#FFFFFF"/>
                        <rect x="23.84" y="828.00" width="233.12" height="34.40" fill="#EDEDED"/>
                        <rect x="256.80" y="828.00" width="507.20" height="34.40" fill="#FFFFFF"/>
                        <rect x="23.84" y="862.24" width="233.12" height="10.88" fill="#EDEDED"/>
                        <rect x="23.84" y="872.96" width="233.12" height="11.36" fill="#EDEDED"/>
                        <rect x="23.84" y="884.16" width="233.12" height="45.60" fill="#EDEDED"/>
                        <rect x="23.84" y="929.60" width="233.12" height="57.44" fill="#FFFFFF"/>
                    </g>

                    <g class="pf-totais-section">
                        <text x="615.68" y="799.04" class="f-cb-76">TOTAIS</text>
                        <text x="669.11" y="799.52" text-anchor="middle" class="f-cr-85">${escapeXml(totais.peso_liquido_total)}</text>
                        <text x="702.40" y="799.52" class="f-cr-85">USS</text>
                        <text x="760.89" y="799.52" text-anchor="end" class="f-cr-85">${escapeXml(totais.preco_total)}</text>
                    </g>

                    <g class="pf-bottom-section">
                        <text x="66.24" y="814.24" class="f-cb-93">INFORMAÇÃO DOS CUSTOS INTERNOS</text>
                        ${custosRowsSvg}
                        <text x="78.24" y="937.76" class="f-cb-76">TOTAL DE CUSTOS INTERNOS</text>
                        <text x="176.48" y="937.12" class="f-cr-69">USS</text>
                        ${totCi !== '' ? `<text x="${totCi === '-' ? '244.00' : '252.89'}" y="937.12" text-anchor="${totCi === '-' ? 'start' : 'end'}" class="f-cr-69">${totCi === '-' ? '-' : escapeXml(totCi)}</text>` : ''}

                        <text x="140.20" y="954.88" text-anchor="middle" class="f-cb-76">${escapeXml(obsLine1)}</text>
                        <text x="140.20" y="977.60" text-anchor="middle" class="f-cb-76">${escapeXml(obsLine2)}</text>

                        <g clip-path="url(#pf-clip-dots-p${pageNum})">
                            <text x="258.40" y="812.48" class="f-cr-69">CUSTOS INTERNOS: ${dots300}</text>
                            <text x="258.40" y="824.00" class="f-cr-69">FRETE: ${dots300}</text>
                            <text x="258.40" y="835.20" class="f-cr-69">SEGURO:${dots300}</text>
                            <text x="258.40" y="846.40" class="f-cr-69">DESCONTO: ${dots300}</text>
                            <text x="258.40" y="857.92" class="f-cb-69">TOTAL ${dots300}</text>
                        </g>

                        <text x="703.04" y="813.76" class="f-cr-85">USS</text>
                        ${fmtResumo(resCustos, '813.76')}
                        <text x="702.88" y="825.28" class="f-cr-85">USS</text>
                        ${fmtResumo(resFrete, '825.28')}
                        <text x="702.88" y="836.48" class="f-cr-85">USS</text>
                        ${fmtResumo(resSeguro, '836.48')}
                        <text x="702.88" y="847.68" class="f-cr-85">USS</text>
                        ${fmtResumo(resDesc, '847.68')}
                        <text x="703.36" y="859.20" class="f-cb-85">USS</text>
                        ${resTotal !== '' ? `<text x="759.56" y="859.20" text-anchor="end" class="f-cb-85">${escapeXml(resTotal)}</text>` : ''}

                        <text x="338.88" y="888.80" class="f-cr-62">INSTRUÇÕES PARA CRÉDITO</text>
                        <text x="262.40" y="905.44" class="f-cr-62">Beneficiario: BRAZMIX COMERCIAL EXPORTADORA DE BOMBAS PARA MINERAÇÃO LTDA</text>
                        <text x="340.16" y="913.76" class="f-cr-62">Numero da Conta: 45216-5</text>
                        <text x="320.16" y="922.08" class="f-cr-62">IBAN&gt;BR4700000000028710000452165C1</text>
                        <text x="322.72" y="930.40" class="f-cr-62">BANCO DO BRASIL S.A. (SAO PAULO - SP)</text>
                        <text x="347.36" y="938.72" class="f-cr-62">SWIFT: BRASBRRJSBO</text>
                        <text x="288.96" y="947.04" class="f-cr-62">Receiver correspondent : BANCO DO BRASIL - NEW YORK - NY - USA</text>
                        <text x="351.84" y="955.36" class="f-cr-62">SWIFT: BRASUS33 </text>
                        <text x="326.56" y="963.68" class="f-cr-62">FEDWIRE ABA NUMBER / FW0260000</text>

                        <text x="545.76" y="921.92" class="f-cr-69" clip-path="url(#pf-clip-sig-p${pageNum})">_________________________________________________                                                                               </text>
                        <text x="531.04" y="931.04" class="f-cr-69">BRAZMIX COMERCIAL EXPORTADORA DE BOMBAS PARA MINERAÇÃO</text>
                    </g>

                    <g class="pf-footer-section">
                        <text x="393.78" y="994.72" text-anchor="middle" class="f-cl-69">${escapeXml(rod.info_arquivo)}</text>
                    </g>

                    <g class="pf-bottom-grid-lines" shape-rendering="crispEdges">
                        <rect x="24.00" y="861.92" width="232.16" height="0.80" fill="#000000"/>
                        <rect x="256.16" y="805.76" width="1.44" height="181.92" fill="#000000"/>
                        <rect x="491.52" y="863.04" width="1.44" height="124.64" fill="#000000"/>
                        <rect x="24.00" y="804.32" width="740.64" height="1.44" fill="#000000"/>
                        <rect x="24.00" y="816.48" width="232.16" height="0.80" fill="#000000"/>
                        <rect x="24.00" y="827.68" width="232.16" height="0.80" fill="#000000"/>
                        <rect x="24.00" y="838.88" width="232.16" height="0.80" fill="#000000"/>
                        <rect x="24.00" y="850.08" width="232.16" height="0.80" fill="#000000"/>
                        <rect x="257.60" y="861.60" width="507.04" height="1.44" fill="#000000"/>
                        <rect x="24.00" y="872.64" width="232.16" height="0.80" fill="#000000"/>
                        <rect x="24.00" y="883.84" width="232.16" height="0.80" fill="#000000"/>
                        <rect x="24.00" y="895.04" width="232.16" height="0.80" fill="#000000"/>
                        <rect x="24.00" y="906.24" width="232.16" height="0.80" fill="#000000"/>
                        <rect x="24.00" y="917.44" width="232.16" height="0.80" fill="#000000"/>
                        <rect x="24.00" y="928.96" width="233.60" height="1.44" fill="#000000"/>
                        <rect x="24.00" y="940.80" width="233.60" height="1.44" fill="#000000"/>
                        <rect x="24.00" y="986.24" width="740.64" height="1.44" fill="#000000"/>
                        <rect x="24.00" y="998.08" width="740.64" height="1.44" fill="#000000"/>
                    </g>
                </g>`;
            }

            htmlPages += `
            <div class="proforma-sheet" ${pageNum === 1 ? 'id="proforma-sheet-document"' : ''}>
                <svg class="pf-svg-document"
                     viewBox="0 0 793.76 ${pageHeight.toFixed(2)}"
                     width="793.76"
                     height="${pageHeight.toFixed(2)}"
                     xmlns="http://www.w3.org/2000/svg"
                     xmlns:xlink="http://www.w3.org/1999/xlink">

                    <defs>
                        <clipPath id="pf-clip-dots-p${pageNum}">
                            <rect x="258.00" y="804.00" width="439.76" height="58.00"/>
                        </clipPath>
                        <clipPath id="pf-clip-sig-p${pageNum}">
                            <rect x="545.76" y="910.00" width="218.08" height="16.00"/>
                        </clipPath>
                        <clipPath id="pf-clip-desc-p${pageNum}">
                            <rect x="172.00" y="274.00" width="284.00" height="${clipDescH.toFixed(2)}"/>
                        </clipPath>
                    </defs>

                    <g class="pf-bg-fills-top" shape-rendering="crispEdges">
                        <rect x="0" y="0" width="793.76" height="${pageHeight.toFixed(2)}" fill="#FFFFFF"/>
                        <rect x="23.84" y="35.20" width="272.96" height="85.12" fill="#FFFFFF"/>
                        <rect x="456.00" y="35.20" width="308.00" height="85.12" fill="#FFFFFF"/>
                        <rect x="23.84" y="120.16" width="740.16" height="34.40" fill="#FFFFFF"/>
                        <rect x="23.84" y="154.40" width="312.80" height="34.40" fill="#FFFFFF"/>
                        <rect x="336.48" y="154.40" width="427.52" height="34.40" fill="#FFFFFF"/>
                        <rect x="23.84" y="188.64" width="740.16" height="51.52" fill="#FFFFFF"/>
                        <rect x="336.48" y="240.00" width="427.52" height="17.28" fill="#FFFFFF"/>
                        <rect x="23.84" y="257.12" width="740.16" height="${tableBgH.toFixed(2)}" fill="#FFFFFF"/>
                    </g>

                    <g class="pf-header-section">
                        <image href="css/fonts/logo-brazmix-pdf.png" x="52.42" y="50.13" width="161.74" height="18.86" preserveAspectRatio="none"/>
                        <text x="35.68" y="87.84" class="f-cb-62">BRAZMIX COMERCIAL EXPORTADORA DE BOMBAS PARA MINERAÇÃO LTDA </text>
                        <text x="233.60" y="87.84" class="f-cr-62">RUA </text>
                        <text x="26.88" y="96.16" class="f-cr-62">CAVALIERE AMBROGIO CIPOLLA, 562 - MARILAND, CEP 95057-000 - CAXIAS DO SUL - RS - </text>
                        <text x="70.08" y="104.48" class="f-cr-62">BRASIL -PHONE +55 54 3229 9365  - www.brazmix.com</text>
                        <text x="306.24" y="80.80" class="f-ab-12">FATURA PROFORMA </text>
                        <text x="376.22" y="95.04" text-anchor="middle" class="f-ab-76">${escapeXml(cab.numero)}</text>
                        <text x="577.60" y="66.24" class="f-cb-76">DATA DE EMISSÃO:</text>
                        <text x="642.24" y="66.24" class="f-cr-85">${escapeXml(cab.emissao)}</text>
                        <text x="560.48" y="83.36" class="f-cb-76">VALIDADE DA COTAÇÃO:</text>
                        <text x="642.24" y="83.36" class="f-cr-85">${escapeXml(cab.validade)}</text>
                    </g>

                    <g class="pf-meta-section">
                        <text x="131.84" y="134.72" class="f-cb-85">INFORMAÇÕES DO CLIENTE</text>
                        <text x="394.67" y="134.72" class="f-cb-85">DETALHES ADICIONAIS</text>
                        <text x="635.11" y="134.72" class="f-cb-85">DETALHES DO ENVIO</text>

                        <text x="28.48" y="144.48" class="f-cb-69">Importador:</text>
                        <text x="69.60" y="148.16" class="f-cr-76">${escapeXml(cli.importador)}</text>
                        <text x="69.60" y="168.80" class="f-cr-76">${escapeXml(impEnd1)}</text>
                        <text x="69.60" y="178.88" class="f-cr-76">${escapeXml(impEnd2)}</text>

                        <text x="26.88" y="195.84" class="f-cb-69">Cosignatário:</text>
                        <text x="69.60" y="216.64" class="f-cr-76">${escapeXml(cli.cosignatario)}</text>

                        <text x="29.76" y="251.20" class="f-cb-69">Notificado:</text>
                        <text x="69.60" y="250.88" class="f-cr-76">${escapeXml(cli.notificado)}</text>

                        <text x="338.08" y="151.84" class="f-cb-69">País de Origem:</text>
                        <text x="417.76" y="151.84" class="f-cr-76">${escapeXml(detAd.pais_origem)}</text>

                        <text x="338.08" y="168.96" class="f-cb-69">Equipamento: </text>
                        <text x="417.76" y="168.96" class="f-cr-76">${escapeXml(detAd.equipamento)}</text>

                        <text x="338.08" y="186.08" class="f-cb-69">Modalidade de Frete:</text>
                        <text x="417.76" y="186.08" class="f-cr-76">${escapeXml(detAd.modalidade_frete)}</text>

                        <text x="338.08" y="203.20" class="f-cb-69">Lugar de embarque: </text>
                        <text x="417.76" y="203.20" class="f-cr-76">${escapeXml(detAd.lugar_embarque)}</text>

                        <text x="338.08" y="220.32" class="f-cb-69">Lugar de transbordo: </text>
                        <text x="417.76" y="220.32" class="f-cr-76">${escapeXml(detAd.lugar_transbordo)}</text>

                        <text x="338.08" y="237.44" class="f-cb-69">Lugar de descarga: </text>
                        <text x="417.76" y="237.44" class="f-cr-76">${escapeXml(detAd.lugar_descarga)}</text>

                        <text x="338.08" y="254.56" class="f-cb-69">Tempo de Trânsito: </text>
                        <text x="447.04" y="254.08" class="f-cr-76">${escapeXml(tempoNum)}</text>
                        <text x="457.60" y="254.56" class="f-cr-76">${escapeXml(tempoUn)}</text>

                        <text x="580.96" y="151.20" class="f-cb-69">Embalagem:</text>
                        <text x="702.44" y="151.84" text-anchor="middle" class="f-cb-69">${escapeXml(detEnv.embalagem)}</text>

                        <text x="580.96" y="168.96" class="f-cb-69">Peso Líquido:</text>
                        <text x="762.67" y="168.48" text-anchor="end" class="f-cr-76">${escapeXml(detEnv.peso_liquido)}</text>

                        <text x="580.96" y="186.08" class="f-cb-69">Peso Bruto:</text>
                        <text x="762.67" y="185.60" text-anchor="end" class="f-cr-76">${escapeXml(detEnv.peso_bruto)}</text>

                        <text x="580.96" y="203.20" class="f-cb-69">Cubagem:</text>
                        <text x="762.67" y="202.72" text-anchor="end" class="f-cr-76">${escapeXml(detEnv.cubagem)}</text>

                        <text x="580.96" y="220.32" class="f-cb-69">Volumes:</text>
                        <text x="762.67" y="219.84" text-anchor="end" class="f-cr-76">${escapeXml(detEnv.volumes)}</text>

                        <text x="580.96" y="229.60" class="f-cb-69">Condições de </text>
                        <text x="580.96" y="238.72" class="f-cb-69">Venda: </text>
                        <text x="702.00" y="233.44" text-anchor="middle" class="f-cb-69">${escapeXml(detEnv.condicoes_venda)}</text>

                        <text x="580.96" y="251.20" class="f-cb-69">Pagamento:</text>
                        <text x="702.22" y="250.56" text-anchor="middle" class="f-cr-69">${escapeXml(detEnv.pagamento)}</text>
                    </g>

                    <g class="pf-table-header">
                        <text x="38.40" y="271.04" class="f-cb-69">ITEM</text>
                        <text x="81.78" y="271.04" class="f-cb-69">CÓDIGO</text>
                        <text x="138.22" y="271.04" class="f-cb-69">NCM</text>
                        <text x="178.22" y="271.04" class="f-cb-69">DESCRIÇÃO</text>
                        <text x="468.80" y="271.04" class="f-cb-69">UN.</text>
                        <text x="505.33" y="271.04" class="f-cb-69">QTD</text>
                        <text x="542.22" y="271.04" class="f-cb-69">PESO LIQ</text>
                        <text x="585.33" y="271.04" class="f-cb-69">PREÇO UNITÁRIO</text>
                        <text x="646.22" y="271.04" class="f-cb-69">PESO LIQ TOTAL</text>
                        <text x="711.56" y="271.04" class="f-cb-69">PREÇO TOTAL</text>
                    </g>

                    <g class="pf-itens-body">
                        ${itemsSvg}
                    </g>

                    <g class="pf-top-and-outer-lines" shape-rendering="crispEdges">
                        <rect x="22.56" y="33.92" width="1.44" height="${borderLeftH.toFixed(2)}" fill="#000000"/>
                        <rect x="763.20" y="35.36" width="1.44" height="${borderRgtH.toFixed(2)}" fill="#000000"/>
                        <rect x="336.16" y="137.76" width="0.80" height="119.84" fill="#000000"/>
                        <rect x="579.04" y="137.76" width="0.80" height="119.84" fill="#000000"/>
                        <rect x="24.00" y="33.92" width="740.64" height="1.44" fill="#000000"/>
                        <rect x="24.00" y="119.84" width="739.20" height="0.80" fill="#000000"/>
                        <rect x="24.00" y="136.96" width="739.20" height="0.80" fill="#000000"/>
                        <rect x="336.96" y="154.08" width="426.24" height="0.80" fill="#000000"/>
                        <rect x="336.96" y="171.20" width="426.24" height="0.80" fill="#000000"/>
                        <rect x="24.00" y="188.32" width="739.20" height="0.80" fill="#000000"/>
                        <rect x="336.96" y="205.44" width="426.24" height="0.80" fill="#000000"/>
                        <rect x="336.96" y="222.56" width="426.24" height="0.80" fill="#000000"/>
                        <rect x="24.00" y="239.68" width="739.20" height="0.80" fill="#000000"/>
                        <rect x="24.00" y="256.80" width="739.20" height="0.80" fill="#000000"/>
                        <rect x="24.00" y="273.92" width="739.20" height="0.80" fill="#000000"/>
                    </g>

                    <g class="pf-item-grid-lines" shape-rendering="crispEdges">
                        ${gridLinesSvg}
                    </g>

                    ${bottomBlockSvg}

                    <g class="pf-page-footer-fixed">
                        <text x="373.44" y="1087.68" class="f-cr-85">Página ${pageNum} de ${totalPages}</text>
                    </g>
                </svg>
            </div>`;
        });

        container.innerHTML = htmlPages;
    }
})();

