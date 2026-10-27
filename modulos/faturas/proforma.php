<?php
// Carrega os dados persistidos em proforma.json se existirem
$jsonPath = __DIR__ . '/action/proforma.json';
$proforma = null;
if (file_exists($jsonPath)) {
    $proforma = json_decode(file_get_contents($jsonPath), true);
}

// Helper para quebrar o endereço do importador nas 2 linhas exatas do PDF (70 caracteres por linha)
function splitImportadorEndereco(string $end): array {
    $end = trim(preg_replace('/\s+/', ' ', $end));
    if (mb_strlen($end, 'UTF-8') <= 70) {
        return [$end, ''];
    }
    $sub = mb_substr($end, 0, 70, 'UTF-8');
    $lastSpace = mb_strrpos($sub, ' ', 0, 'UTF-8');
    if ($lastSpace !== false && $lastSpace > 30) {
        return [
            trim(mb_substr($end, 0, $lastSpace, 'UTF-8')),
            trim(mb_substr($end, $lastSpace + 1, null, 'UTF-8'))
        ];
    }
    return [
        trim(mb_substr($end, 0, 70, 'UTF-8')),
        trim(mb_substr($end, 70, null, 'UTF-8'))
    ];
}

$impEndRaw = $proforma['cliente']['importador_endereco'] ?? '';
[$impEndLine1, $impEndLine2] = splitImportadorEndereco($impEndRaw);

$tempoTransitoRaw = trim($proforma['detalhes_adicionais']['tempo_transito'] ?? '');
$tempoParts = $tempoTransitoRaw !== '' ? preg_split('/\s+/', $tempoTransitoRaw, 2) : ['', ''];
$tempoNum = $tempoParts[0] ?? '';
$tempoUn = $tempoParts[1] ?? '';

$itens = (!empty($proforma['itens']) && is_array($proforma['itens'])) ? $proforma['itens'] : [];

if (!empty($itens)) {
    $normalizarTextoProforma = function (string $str): string {
        $str = function_exists('mb_strtolower') ? mb_strtolower(trim($str), 'UTF-8') : strtolower(trim($str));
        $mapa = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n'
        ];
        return strtr($str, $mapa);
    };

    usort($itens, function ($a, $b) use ($normalizarTextoProforma) {
        $normA = $normalizarTextoProforma($a['descricao'] ?? '');
        $normB = $normalizarTextoProforma($b['descricao'] ?? '');
        $cmp = strcmp($normA, $normB);
        if ($cmp !== 0) {
            return $cmp;
        }
        return strcasecmp($a['descricao'] ?? '', $b['descricao'] ?? '');
    });

    foreach ($itens as $idx => &$itemRef) {
        $itemRef['item'] = $idx + 1;
    }
    unset($itemRef);
}

// Paginação fiel ao modelo A4 do PDF:
// - Páginas intermediárias (sem bloco de Totais/Custos/Assinatura) comportam até 40 itens
// - A última página (com bloco de Totais/Custos/Assinatura) comporta até 29 itens
function paginateProformaItems(array $allItems): array {
    $total = count($allItems);
    if ($total <= 29) {
        return [$allItems];
    }
    $pages = [];
    $remaining = $allItems;
    while (count($remaining) > 29) {
        if (count($remaining) > 40) {
            $pages[] = array_slice($remaining, 0, 40);
            $remaining = array_slice($remaining, 40);
        } else {
            $pages[] = array_slice($remaining, 0, 29);
            $remaining = array_slice($remaining, 29);
        }
    }
    $pages[] = $remaining;
    return $pages;
}

$pagesItems = paginateProformaItems($itens);
$totalPages = count($pagesItems);
$rowHeight  = 17.12;
$pageHeight = 1122.56;

$custosLabels = [
    ["Taxas Bancárias e Diferença Cambial:", 825.44],
    ["Desembaraço Aduaneiro:", 836.64],
    ["Embalagens e Certificado de Origem:", 847.84],
    ["Custos de Armazenagem:", 859.04],
    ["Fretes Terrestres:", 870.40],
    ["Estufagem:", 881.60],
    ["Taxas Portuárias:", 892.80],
    ["Taxas Armador:", 904.00],
    ["Remessa de Documentos:", 915.20],
    ["Manuseio, Presença de Carga e Cia Aérea", 926.40],
];
$custosArr = $proforma['custos_internos']['itens'] ?? [];
$totCustosInternos = trim((string)($proforma['custos_internos']['total'] ?? ''));

$obsRaw = trim((string)($proforma['custos_internos']['observacao'] ?? ''));
$obsLines = $obsRaw !== '' ? preg_split('/\r\n|\r|\n/', $obsRaw) : ['', ''];
$obsLine1 = trim($obsLines[0] ?? '');
$obsLine2 = trim($obsLines[1] ?? '');

$dots300 = str_repeat('.', 310);
?>

<div class="container-fluid mt-3 mb-5" id="proforma-module-container">

    <!-- BARRA DE FERRAMENTAS / AÇÕES DO SISTEMA (NÃO IMPRIME) -->
    <div class="no-print mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pb-3 border-bottom">
            <div>
                <h2>Fatura Proforma</h2>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#collapseImportarProforma" aria-expanded="false" aria-controls="collapseImportarProforma" id="btnToggleImportProforma">
                    <i class="fa-solid fa-file-arrow-up me-1"></i> Nova Importação
                </button>
                <button class="btn btn-outline-secondary" id="btn-print-proforma" title="Imprimir documento no formato A4 idêntico ao PDF">
                    <i class="fa-solid fa-print me-1"></i> Imprimir
                </button>
            </div>
        </div>

        <div class="collapse mt-3" id="collapseImportarProforma">
            <div class="card card-body shadow-sm" style="max-width: 850px; background-color: #fdfdfd;">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-upload me-2 text-primary"></i>Importar Fatura Proforma (PDF)</h5>
                <form id="importFormProforma" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="pdfFileProforma" class="form-label fw-semibold">Selecione o arquivo PDF da Proforma:</label>
                        <input class="form-control" type="file" name="pdfFile" id="pdfFileProforma" accept=".pdf" required>
                    </div>
                    <button type="submit" class="btn btn-primary" id="btnSubmitProforma">
                        <i class="fa-solid fa-upload me-1"></i> Processar Importação
                    </button>
                </form>
                <div id="resultProforma" class="mt-3"></div>
            </div>
        </div>
    </div>

    <!-- FOLHAS DO DOCUMENTO PROFORMA (MÚLTIPLAS PÁGINAS A4 COM CABEÇALHO EM TODAS E RODAPÉ FIXO NA FOLHA) -->
    <div class="proforma-desk-wrapper" id="proforma-pages-container">
        <?php foreach ($pagesItems as $pIdx => $pageItems):
            $pageNum    = $pIdx + 1;
            $isLastPage = ($pageNum === $totalPages);
            $pageRows   = max(1, count($pageItems));

            if ($isLastPage) {
                $deltaY      = ($pageRows - 29) * $rowHeight;
                $tableBgH    = 530.88 + $deltaY;
                $clipDescH   = 514.00 + $deltaY;
                $borderLeftH = 965.60 + $deltaY;
                $borderRgtH  = 964.16 + $deltaY;
                $gridLineCnt = $pageRows + 1; // linhas dos produtos + 1 linha espaçadora antes de TOTAIS
            } else {
                $deltaY      = 0;
                $tableBgH    = 16.80 + ($pageRows * $rowHeight);
                $clipDescH   = ($pageRows * $rowHeight) + 2.00;
                $borderLeftH = 240.80 + ($pageRows * $rowHeight);
                $borderRgtH  = 239.36 + ($pageRows * $rowHeight);
                $gridLineCnt = $pageRows; // fecha a tabela exatamente no último item da página
            }
        ?>
        <div class="proforma-sheet" <?= $pageNum === 1 ? 'id="proforma-sheet-document"' : '' ?>>
            <svg class="pf-svg-document"
                 viewBox="0 0 793.76 <?= number_format($pageHeight, 2, '.', '') ?>"
                 width="793.76"
                 height="<?= number_format($pageHeight, 2, '.', '') ?>"
                 xmlns="http://www.w3.org/2000/svg"
                 xmlns:xlink="http://www.w3.org/1999/xlink">

                <defs>
                    <clipPath id="pf-clip-dots-p<?= $pageNum ?>">
                        <rect x="258.00" y="804.00" width="439.76" height="58.00"/>
                    </clipPath>
                    <clipPath id="pf-clip-sig-p<?= $pageNum ?>">
                        <rect x="545.76" y="910.00" width="218.08" height="16.00"/>
                    </clipPath>
                    <clipPath id="pf-clip-desc-p<?= $pageNum ?>">
                        <rect x="172.00" y="274.00" width="284.00" height="<?= number_format($clipDescH, 2, '.', '') ?>"/>
                    </clipPath>
                </defs>

                <!-- 0. PREENCHIMENTOS DE FUNDO DA FOLHA A4 E DA TABELA DINÂMICA -->
                <g class="pf-bg-fills-top" shape-rendering="crispEdges">
                    <rect x="0" y="0" width="793.76" height="<?= number_format($pageHeight, 2, '.', '') ?>" fill="#FFFFFF"/>
                    <rect x="23.84" y="35.20" width="272.96" height="85.12" fill="#FFFFFF"/>
                    <rect x="456.00" y="35.20" width="308.00" height="85.12" fill="#FFFFFF"/>
                    <rect x="23.84" y="120.16" width="740.16" height="34.40" fill="#FFFFFF"/>
                    <rect x="23.84" y="154.40" width="312.80" height="34.40" fill="#FFFFFF"/>
                    <rect x="336.48" y="154.40" width="427.52" height="34.40" fill="#FFFFFF"/>
                    <rect x="23.84" y="188.64" width="740.16" height="51.52" fill="#FFFFFF"/>
                    <rect x="336.48" y="240.00" width="427.52" height="17.28" fill="#FFFFFF"/>
                    <rect x="23.84" y="257.12" width="740.16" height="<?= number_format($tableBgH, 2, '.', '') ?>" fill="#FFFFFF"/>
                </g>

                <!-- 1. CABEÇALHO REPETIDO EM TODAS AS PÁGINAS -->
                <g class="pf-header-section">
                    <image href="css/fonts/logo-brazmix-pdf.png" x="52.42" y="50.13" width="161.74" height="18.86" preserveAspectRatio="none"/>

                    <text x="35.68" y="87.84" class="f-cb-62">BRAZMIX COMERCIAL EXPORTADORA DE BOMBAS PARA MINERAÇÃO LTDA </text>
                    <text x="233.60" y="87.84" class="f-cr-62">RUA </text>
                    <text x="26.88" y="96.16" class="f-cr-62">CAVALIERE AMBROGIO CIPOLLA, 562 - MARILAND, CEP 95057-000 - CAXIAS DO SUL - RS - </text>
                    <text x="70.08" y="104.48" class="f-cr-62">BRASIL -PHONE +55 54 3229 9365  - www.brazmix.com</text>

                    <text x="306.24" y="80.80" class="f-ab-12">FATURA PROFORMA </text>
                    <text x="376.22" y="95.04" text-anchor="middle" class="f-ab-76"><?= htmlspecialchars($proforma['cabecalho']['numero'] ?? '') ?></text>

                    <text x="577.60" y="66.24" class="f-cb-76">DATA DE EMISSÃO:</text>
                    <text x="642.24" y="66.24" class="f-cr-85"><?= htmlspecialchars($proforma['cabecalho']['emissao'] ?? '') ?></text>

                    <text x="560.48" y="83.36" class="f-cb-76">VALIDADE DA COTAÇÃO:</text>
                    <text x="642.24" y="83.36" class="f-cr-85"><?= htmlspecialchars($proforma['cabecalho']['validade'] ?? '') ?></text>
                </g>

                <!-- 2. METADADOS REPETIDOS EM TODAS AS PÁGINAS -->
                <g class="pf-meta-section">
                    <text x="131.84" y="134.72" class="f-cb-85">INFORMAÇÕES DO CLIENTE</text>
                    <text x="394.67" y="134.72" class="f-cb-85">DETALHES ADICIONAIS</text>
                    <text x="635.11" y="134.72" class="f-cb-85">DETALHES DO ENVIO</text>

                    <!-- Coluna 1: Informações do Cliente -->
                    <text x="28.48" y="144.48" class="f-cb-69">Importador:</text>
                    <text x="69.60" y="148.16" class="f-cr-76"><?= htmlspecialchars($proforma['cliente']['importador'] ?? '') ?></text>
                    <text x="69.60" y="168.80" class="f-cr-76"><?= htmlspecialchars($impEndLine1) ?></text>
                    <text x="69.60" y="178.88" class="f-cr-76"><?= htmlspecialchars($impEndLine2) ?></text>

                    <text x="26.88" y="195.84" class="f-cb-69">Cosignatário:</text>
                    <text x="69.60" y="216.64" class="f-cr-76"><?= htmlspecialchars($proforma['cliente']['cosignatario'] ?? '') ?></text>

                    <text x="29.76" y="251.20" class="f-cb-69">Notificado:</text>
                    <text x="69.60" y="250.88" class="f-cr-76"><?= htmlspecialchars($proforma['cliente']['notificado'] ?? '') ?></text>

                    <!-- Coluna 2: Detalhes Adicionais -->
                    <text x="338.08" y="151.84" class="f-cb-69">País de Origem:</text>
                    <text x="417.76" y="151.84" class="f-cr-76"><?= htmlspecialchars($proforma['detalhes_adicionais']['pais_origem'] ?? '') ?></text>

                    <text x="338.08" y="168.96" class="f-cb-69">Equipamento: </text>
                    <text x="417.76" y="168.96" class="f-cr-76"><?= htmlspecialchars($proforma['detalhes_adicionais']['equipamento'] ?? '') ?></text>

                    <text x="338.08" y="186.08" class="f-cb-69">Modalidade de Frete:</text>
                    <text x="417.76" y="186.08" class="f-cr-76"><?= htmlspecialchars($proforma['detalhes_adicionais']['modalidade_frete'] ?? '') ?></text>

                    <text x="338.08" y="203.20" class="f-cb-69">Lugar de embarque: </text>
                    <text x="417.76" y="203.20" class="f-cr-76"><?= htmlspecialchars($proforma['detalhes_adicionais']['lugar_embarque'] ?? '') ?></text>

                    <text x="338.08" y="220.32" class="f-cb-69">Lugar de transbordo: </text>
                    <text x="417.76" y="220.32" class="f-cr-76"><?= htmlspecialchars($proforma['detalhes_adicionais']['lugar_transbordo'] ?? '') ?></text>

                    <text x="338.08" y="237.44" class="f-cb-69">Lugar de descarga: </text>
                    <text x="417.76" y="237.44" class="f-cr-76"><?= htmlspecialchars($proforma['detalhes_adicionais']['lugar_descarga'] ?? '') ?></text>

                    <text x="338.08" y="254.56" class="f-cb-69">Tempo de Trânsito: </text>
                    <text x="447.04" y="254.08" class="f-cr-76"><?= htmlspecialchars($tempoNum) ?></text>
                    <text x="457.60" y="254.56" class="f-cr-76"><?= htmlspecialchars($tempoUn) ?></text>

                    <!-- Coluna 3: Detalhes do Envio -->
                    <text x="580.96" y="151.20" class="f-cb-69">Embalagem:</text>
                    <text x="702.44" y="151.84" text-anchor="middle" class="f-cb-69"><?= htmlspecialchars($proforma['detalhes_envio']['embalagem'] ?? '') ?></text>

                    <text x="580.96" y="168.96" class="f-cb-69">Peso Líquido:</text>
                    <text x="762.67" y="168.48" text-anchor="end" class="f-cr-76"><?= htmlspecialchars($proforma['detalhes_envio']['peso_liquido'] ?? '') ?></text>

                    <text x="580.96" y="186.08" class="f-cb-69">Peso Bruto:</text>
                    <text x="762.67" y="185.60" text-anchor="end" class="f-cr-76"><?= htmlspecialchars($proforma['detalhes_envio']['peso_bruto'] ?? '') ?></text>

                    <text x="580.96" y="203.20" class="f-cb-69">Cubagem:</text>
                    <text x="762.67" y="202.72" text-anchor="end" class="f-cr-76"><?= htmlspecialchars($proforma['detalhes_envio']['cubagem'] ?? '') ?></text>

                    <text x="580.96" y="220.32" class="f-cb-69">Volumes:</text>
                    <text x="762.67" y="219.84" text-anchor="end" class="f-cr-76"><?= htmlspecialchars($proforma['detalhes_envio']['volumes'] ?? '') ?></text>

                    <text x="580.96" y="229.60" class="f-cb-69">Condições de </text>
                    <text x="580.96" y="238.72" class="f-cb-69">Venda: </text>
                    <text x="702.00" y="233.44" text-anchor="middle" class="f-cb-69"><?= htmlspecialchars($proforma['detalhes_envio']['condicoes_venda'] ?? '') ?></text>

                    <text x="580.96" y="251.20" class="f-cb-69">Pagamento:</text>
                    <text x="702.22" y="250.56" text-anchor="middle" class="f-cr-69"><?= htmlspecialchars($proforma['detalhes_envio']['pagamento'] ?? '') ?></text>
                </g>

                <!-- 3. CABEÇALHO DA TABELA DE ITENS REPETIDO EM TODAS AS PÁGINAS -->
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

                <!-- 4. CORPO DINÂMICO DA TABELA DE ITENS DA PÁGINA -->
                <g class="pf-itens-body">
                    <?php foreach ($pageItems as $idx => $it):
                        $yMain = 288.80 + ($idx * $rowHeight);
                        $ySub  = $yMain - 0.48;
                    ?>
                        <g class="pf-item-row">
                            <text x="45.80" y="<?= number_format($ySub, 2, '.', '') ?>" text-anchor="middle" class="f-cr-76"><?= htmlspecialchars((string)$it['item']) ?></text>
                            <text x="94.15" y="<?= number_format($yMain, 2, '.', '') ?>" text-anchor="middle" class="f-cr-76"><?= htmlspecialchars((string)$it['codigo']) ?></text>
                            <text x="146.00" y="<?= number_format($yMain, 2, '.', '') ?>" text-anchor="middle" class="f-cr-76"><?= htmlspecialchars((string)$it['ncm']) ?></text>
                            <text x="173.76" y="<?= number_format($yMain, 2, '.', '') ?>" class="f-cr-76" clip-path="url(#pf-clip-desc-p<?= $pageNum ?>)"><?= htmlspecialchars((string)$it['descricao']) ?></text>
                            <text x="474.67" y="<?= number_format($yMain, 2, '.', '') ?>" text-anchor="middle" class="f-cr-76"><?= htmlspecialchars((string)$it['unidade']) ?></text>
                            <text x="512.00" y="<?= number_format($yMain, 2, '.', '') ?>" text-anchor="middle" class="f-cr-76"><?= htmlspecialchars((string)$it['quantidade']) ?></text>
                            <text x="555.78" y="<?= number_format($yMain, 2, '.', '') ?>" text-anchor="middle" class="f-cr-76"><?= htmlspecialchars((string)$it['peso_liq']) ?></text>
                            <text x="584.48" y="<?= number_format($yMain, 2, '.', '') ?>" class="f-cr-76">USS</text>
                            <text x="636.44" y="<?= number_format($yMain, 2, '.', '') ?>" text-anchor="end" class="f-cr-76"><?= htmlspecialchars((string)$it['preco_unitario']) ?></text>
                            <text x="669.25" y="<?= number_format($ySub, 2, '.', '') ?>" text-anchor="middle" class="f-cr-76"><?= htmlspecialchars((string)$it['peso_liq_total']) ?></text>
                            <text x="702.67" y="<?= number_format($ySub, 2, '.', '') ?>" class="f-cr-76">USS</text>
                            <text x="760.89" y="<?= number_format($ySub, 2, '.', '') ?>" text-anchor="end" class="f-cr-76"><?= htmlspecialchars((string)$it['preco_total']) ?></text>
                        </g>
                    <?php endforeach; ?>
                </g>

                <!-- 5. BORDAS DO TOPO + BORDAS LATERAIS EXTERNAS DINÂMICAS + LINHAS DE ITENS -->
                <g class="pf-top-and-outer-lines" shape-rendering="crispEdges">
                    <rect x="22.56" y="33.92" width="1.44" height="<?= number_format($borderLeftH, 2, '.', '') ?>" fill="#000000"/>
                    <rect x="763.20" y="35.36" width="1.44" height="<?= number_format($borderRgtH, 2, '.', '') ?>" fill="#000000"/>
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
                    <?php for ($i = 1; $i <= $gridLineCnt; $i++):
                        $yLine = 273.92 + ($i * $rowHeight);
                    ?>
                        <rect x="24.00" y="<?= number_format($yLine, 2, '.', '') ?>" width="739.20" height="0.80" fill="#000000"/>
                    <?php endfor; ?>
                </g>

                <?php if ($isLastPage): ?>
                <!-- 6. BLOCO INFERIOR DINÂMICO (APENAS NA ÚLTIMA PÁGINA, DESLOCADO CONFORME OS ITENS DESTA PÁGINA) -->
                <g class="pf-dynamic-bottom" transform="translate(0, <?= number_format($deltaY, 2, '.', '') ?>)">

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
                        <text x="669.11" y="799.52" text-anchor="middle" class="f-cr-85"><?= htmlspecialchars($proforma['totais']['peso_liquido_total'] ?? '') ?></text>
                        <text x="702.40" y="799.52" class="f-cr-85">USS</text>
                        <text x="760.89" y="799.52" text-anchor="end" class="f-cr-85"><?= htmlspecialchars($proforma['totais']['preco_total'] ?? '') ?></text>
                    </g>

                    <g class="pf-bottom-section">
                        <text x="66.24" y="814.24" class="f-cb-93">INFORMAÇÃO DOS CUSTOS INTERNOS</text>

                        <?php foreach ($custosLabels as $idx => $c):
                            $val = trim((string)($custosArr[$idx]['valor'] ?? ''));
                            $yCusto = number_format($c[1], 2, '.', '');
                            $isDash = ($val === '-');
                        ?>
                            <text x="170.85" y="<?= $yCusto ?>" text-anchor="end" class="f-cr-76"><?= htmlspecialchars($c[0]) ?></text>
                            <text x="176.80" y="<?= $yCusto ?>" class="f-cr-69">USS</text>
                            <?php if ($isDash): ?>
                                <text x="244.00" y="<?= $yCusto ?>" text-anchor="start" class="f-cr-69">-</text>
                            <?php elseif ($val !== ''): ?>
                                <text x="252.89" y="<?= $yCusto ?>" text-anchor="end" class="f-cr-69"><?= htmlspecialchars($val) ?></text>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <text x="78.24" y="937.76" class="f-cb-76">TOTAL DE CUSTOS INTERNOS</text>
                        <text x="176.48" y="937.12" class="f-cr-69">USS</text>
                        <?php if ($totCustosInternos === '-'): ?>
                            <text x="244.00" y="937.12" text-anchor="start" class="f-cr-69">-</text>
                        <?php elseif ($totCustosInternos !== ''): ?>
                            <text x="252.89" y="937.12" text-anchor="end" class="f-cr-69"><?= htmlspecialchars($totCustosInternos) ?></text>
                        <?php endif; ?>

                        <text x="140.20" y="954.88" text-anchor="middle" class="f-cb-76"><?= htmlspecialchars($obsLine1) ?></text>
                        <text x="140.20" y="977.60" text-anchor="middle" class="f-cb-76"><?= htmlspecialchars($obsLine2) ?></text>

                        <g clip-path="url(#pf-clip-dots-p<?= $pageNum ?>)">
                            <text x="258.40" y="812.48" class="f-cr-69">CUSTOS INTERNOS: <?= $dots300 ?></text>
                            <text x="258.40" y="824.00" class="f-cr-69">FRETE: <?= $dots300 ?></text>
                            <text x="258.40" y="835.20" class="f-cr-69">SEGURO:<?= $dots300 ?></text>
                            <text x="258.40" y="846.40" class="f-cr-69">DESCONTO: <?= $dots300 ?></text>
                            <text x="258.40" y="857.92" class="f-cb-69">TOTAL <?= $dots300 ?></text>
                        </g>

                        <?php
                        $resCustos = trim((string)($proforma['resumo_financeiro']['custos_internos'] ?? ''));
                        $resFrete  = trim((string)($proforma['resumo_financeiro']['frete'] ?? ''));
                        $resSeguro = trim((string)($proforma['resumo_financeiro']['seguro'] ?? ''));
                        $resDesc   = trim((string)($proforma['resumo_financeiro']['desconto'] ?? ''));
                        $resTotal  = trim((string)($proforma['resumo_financeiro']['total_geral'] ?? ''));
                        ?>
                        <text x="703.04" y="813.76" class="f-cr-85">USS</text>
                        <?php if ($resCustos !== ''): ?>
                        <text x="<?= ($resCustos === '-') ? '748.32' : '759.56' ?>" y="813.76" text-anchor="<?= ($resCustos === '-') ? 'start' : 'end' ?>" class="f-cr-85"><?= htmlspecialchars($resCustos) ?></text>
                        <?php endif; ?>

                        <text x="702.88" y="825.28" class="f-cr-85">USS</text>
                        <?php if ($resFrete !== ''): ?>
                        <text x="<?= ($resFrete === '-') ? '748.32' : '759.56' ?>" y="825.28" text-anchor="<?= ($resFrete === '-') ? 'start' : 'end' ?>" class="f-cr-85"><?= htmlspecialchars($resFrete) ?></text>
                        <?php endif; ?>

                        <text x="702.88" y="836.48" class="f-cr-85">USS</text>
                        <?php if ($resSeguro !== ''): ?>
                        <text x="<?= ($resSeguro === '-') ? '748.32' : '759.56' ?>" y="836.48" text-anchor="<?= ($resSeguro === '-') ? 'start' : 'end' ?>" class="f-cr-85"><?= htmlspecialchars($resSeguro) ?></text>
                        <?php endif; ?>

                        <text x="702.88" y="847.68" class="f-cr-85">USS</text>
                        <?php if ($resDesc !== ''): ?>
                        <text x="<?= ($resDesc === '-') ? '748.32' : '759.56' ?>" y="847.68" text-anchor="<?= ($resDesc === '-') ? 'start' : 'end' ?>" class="f-cr-85"><?= htmlspecialchars($resDesc) ?></text>
                        <?php endif; ?>

                        <text x="703.36" y="859.20" class="f-cb-85">USS</text>
                        <?php if ($resTotal !== ''): ?>
                        <text x="759.56" y="859.20" text-anchor="end" class="f-cb-85"><?= htmlspecialchars($resTotal) ?></text>
                        <?php endif; ?>

                        <text x="338.88" y="888.80" class="f-cr-62">INSTRUÇÕES PARA CRÉDITO</text>
                        <text x="262.40" y="905.44" class="f-cr-62">Beneficiario: BRAZMIX COMERCIAL EXPORTADORA DE BOMBAS PARA MINERAÇÃO LTDA</text>
                        <text x="340.16" y="913.76" class="f-cr-62">Numero da Conta: 45216-5</text>
                        <text x="320.16" y="922.08" class="f-cr-62">IBAN&gt;BR4700000000028710000452165C1</text>
                        <text x="322.72" y="930.40" class="f-cr-62">BANCO DO BRASIL S.A. (SAO PAULO - SP)</text>
                        <text x="347.36" y="938.72" class="f-cr-62">SWIFT: BRASBRRJSBO</text>
                        <text x="288.96" y="947.04" class="f-cr-62">Receiver correspondent : BANCO DO BRASIL - NEW YORK - NY - USA</text>
                        <text x="351.84" y="955.36" class="f-cr-62">SWIFT: BRASUS33 </text>
                        <text x="326.56" y="963.68" class="f-cr-62">FEDWIRE ABA NUMBER / FW0260000</text>

                        <text x="545.76" y="921.92" class="f-cr-69" clip-path="url(#pf-clip-sig-p<?= $pageNum ?>)">_________________________________________________                                                                               </text>
                        <text x="531.04" y="931.04" class="f-cr-69">BRAZMIX COMERCIAL EXPORTADORA DE BOMBAS PARA MINERAÇÃO</text>
                    </g>

                    <g class="pf-footer-section">
                        <text x="393.78" y="994.72" text-anchor="middle" class="f-cl-69"><?= htmlspecialchars($proforma['rodape']['info_arquivo'] ?? '') ?></text>
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

                </g> <!-- /pf-dynamic-bottom -->
                <?php endif; ?>

                <!-- 7. NÚMERO DE PÁGINAS SEMPRE FIXO NO RODAPÉ DA FOLHA A4 -->
                <g class="pf-page-footer-fixed">
                    <text x="373.44" y="1087.68" class="f-cr-85">Página <?= $pageNum ?> de <?= $totalPages ?></text>
                </g>

            </svg>
        </div> <!-- /proforma-sheet -->
        <?php endforeach; ?>
    </div> <!-- /proforma-desk-wrapper -->

</div> <!-- /proforma-module-container -->

<style>
/* ========================================================
   FONTES EXTRAÍDAS DO PDF OFICIAL COM UNICODE-RANGE EXATO
   ======================================================== */
@font-face {
    font-family: 'PDFCalibriBold';
    src: url('css/fonts/calibri-bold.ttf') format('truetype');
    font-weight: 100 900;
    font-style: normal;
    unicode-range: U+0020, U+0022, U+002C, U+002E, U+0030-003A, U+0041-0056, U+0058, U+005A, U+0061-0067, U+0069, U+006C-0075, U+00A0, U+00B2-00B4, U+00B9, U+00C1, U+00C3, U+00C7, U+00CD, U+00D3, U+00D5, U+00E1-00E2, U+00E7, U+00ED, U+00F5;
}
@font-face {
    font-family: 'PDFCalibriRegular';
    src: url('css/fonts/calibri-regular.ttf') format('truetype');
    font-weight: 100 900;
    font-style: normal;
    unicode-range: U+0020, U+0022, U+0027-0029, U+002B-003A, U+003E, U+0040-005A, U+005F, U+0061-006A, U+006C-0070, U+0072-0078, U+007A, U+00A0, U+00AD, U+00B2-00B4, U+00B9, U+00C3, U+00C7, U+00C9, U+00CD, U+00D5, U+00E1, U+00E7, U+00E9;
}
@font-face {
    font-family: 'PDFArialBlack';
    src: url('css/fonts/arial-black.ttf') format('truetype');
    font-weight: 100 900;
    font-style: normal;
    unicode-range: U+0020, U+0022, U+0027, U+002D, U+0031-0032, U+0034-0036, U+0041, U+0044, U+0046, U+004D, U+004F-0050, U+0052, U+0054-0055, U+0064, U+0072, U+00A0;
}
@font-face {
    font-family: 'PDFCalibriLight';
    src: url('css/fonts/calibri-light.ttf') format('truetype');
    font-weight: 100 900;
    font-style: normal;
    unicode-range: U+0020, U+0022, U+002D-0032, U+0034-0036, U+0039-003A, U+0041, U+0046, U+0048-004A, U+004C-004D, U+004F, U+0052, U+0054-0055, U+0061-0062, U+0064-0065, U+0069, U+006C-006D, U+006F, U+0071-0076, U+0078, U+007A, U+00A0;
}

/* ========================================================
   CONTAINER DE VISUALIZAÇÃO E FOLHA COM ALTURA DINÂMICA
   ======================================================== */
.proforma-desk-wrapper {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 28px;
    background-color: #525659;
    padding: 24px 10px;
    border-radius: 6px;
    overflow-x: auto;
}

.proforma-sheet {
    background: #ffffff;
    width: 793.76px;
    min-width: 793.76px;
    height: 1122.56px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.40);
    margin: 0 auto;
    padding: 0;
    box-sizing: border-box;
    position: relative;
}

.pf-svg-document {
    display: block;
    width: 793.76px;
    height: 1122.56px;
}

.pf-svg-document text {
    fill: #000000;
    dominant-baseline: alphabetic;
    white-space: pre;
    text-rendering: geometricPrecision;
}

/* Classes tipográficas exatas (tamanhos de matriz de fonte do PDF em 96dpi px) */
.f-cb-62 {
    font-family: 'PDFCalibriBold', 'PDFCalibriRegular', Calibri, Arial, sans-serif;
    font-size: 6.24024px;
    font-weight: bold;
}
.f-cr-62 {
    font-family: 'PDFCalibriRegular', Calibri, Arial, sans-serif;
    font-size: 6.24024px;
    font-weight: normal;
}
.f-cb-69 {
    font-family: 'PDFCalibriBold', 'PDFCalibriRegular', Calibri, Arial, sans-serif;
    font-size: 6.88027px;
    font-weight: bold;
}
.f-cr-69 {
    font-family: 'PDFCalibriRegular', Calibri, Arial, sans-serif;
    font-size: 6.88027px;
    font-weight: normal;
}
.f-cl-69 {
    font-family: 'PDFCalibriLight', 'PDFCalibriRegular', Calibri, Arial, sans-serif;
    font-size: 6.88027px;
    font-weight: 300;
}
.f-cb-76 {
    font-family: 'PDFCalibriBold', 'PDFCalibriRegular', Calibri, Arial, sans-serif;
    font-size: 7.68030px;
    font-weight: bold;
}
.f-cr-76 {
    font-family: 'PDFCalibriRegular', Calibri, Arial, sans-serif;
    font-size: 7.68030px;
    font-weight: normal;
}
.f-ab-76 {
    font-family: 'PDFArialBlack', 'Arial Black', 'PDFCalibriBold', Arial, sans-serif;
    font-size: 7.68030px;
    font-weight: 900;
}
.f-cb-85 {
    font-family: 'PDFCalibriBold', 'PDFCalibriRegular', Calibri, Arial, sans-serif;
    font-size: 8.48033px;
    font-weight: bold;
}
.f-cr-85 {
    font-family: 'PDFCalibriRegular', Calibri, Arial, sans-serif;
    font-size: 8.48033px;
    font-weight: normal;
}
.f-cb-93 {
    font-family: 'PDFCalibriBold', 'PDFCalibriRegular', Calibri, Arial, sans-serif;
    font-size: 9.28036px;
    font-weight: bold;
}
.f-ab-12 {
    font-family: 'PDFArialBlack', 'Arial Black', 'PDFCalibriBold', Arial, sans-serif;
    font-size: 12.48050px;
    font-weight: 900;
}

/* ========================================================
   ESTILOS DE IMPRESSÃO
   ======================================================== */
@page {
    size: A4 portrait;
    margin: 0;
}

@media print {
    #sidebar,
    nav.navbar,
    #loading,
    .no-print,
    #proforma-module-container > .no-print {
        display: none !important;
    }

    html, body {
        width: 210mm !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        background: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .content,
    #modulo-content,
    .container-fluid,
    #proforma-module-container {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    .proforma-desk-wrapper {
        background: transparent !important;
        padding: 0 !important;
        margin: 0 !important;
        gap: 0 !important;
        display: block !important;
        overflow: visible !important;
    }

    .proforma-sheet {
        width: 210mm !important;
        height: 297mm !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 !important;
        page-break-after: always !important;
        break-after: page !important;
    }

    .proforma-sheet:last-child {
        page-break-after: auto !important;
        break-after: auto !important;
    }

    .pf-svg-document {
        width: 210mm !important;
        height: 297mm !important;
    }
}
</style>

<script src="js/faturas/proforma.js?v=<?= htmlspecialchars($versaoAtual ?? '2.5.2') ?>"></script>