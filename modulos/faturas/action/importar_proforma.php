<?php
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'status' => 'error',
            'error'  => 'Erro interno: ' . $err['message'] . ' (linha ' . $err['line'] . ')',
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
});

require_once dirname(__DIR__, 3) . '/logger.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$usuario = (string)($_SESSION["usuario"]["nome"] ?? '');

if (!isset($_FILES['pdfFile']) || $_FILES['pdfFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'error' => 'Nenhum arquivo PDF foi enviado ou ocorreu um erro no envio.']);
    exit();
}

$tmpFile = $_FILES['pdfFile']['tmp_name'];
$nomeOriginal = $_FILES['pdfFile']['name'] ?? 'proforma.pdf';

function funcaoDisponivel(string $fn): bool {
    if (!function_exists($fn)) return false;
    $desativadas = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    return !in_array($fn, $desativadas, true);
}

function executarComandoShell(string $cmd): string {
    if (funcaoDisponivel('shell_exec')) {
        $out = @shell_exec($cmd);
        if (is_string($out) && $out !== '') return $out;
    }
    if (funcaoDisponivel('exec')) {
        $lines = [];
        $ret = 0;
        @exec($cmd, $lines, $ret);
        if (!empty($lines)) return implode("\n", $lines);
    }
    if (funcaoDisponivel('proc_open')) {
        $desc = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = @proc_open($cmd, $desc, $pipes);
        if (is_resource($proc)) {
            @fclose($pipes[0]);
            $out = (string)@stream_get_contents($pipes[1]);
            @fclose($pipes[1]);
            @fclose($pipes[2]);
            @proc_close($proc);
            if ($out !== '') return $out;
        }
    }
    if (funcaoDisponivel('passthru')) {
        ob_start();
        @passthru($cmd);
        $out = (string)ob_get_clean();
        if ($out !== '') return $out;
    }
    return '';
}

function escaparArgShell(string $arg): string {
    if (funcaoDisponivel('escapeshellarg')) {
        return escapeshellarg($arg);
    }
    return "'" . str_replace("'", "'\\''", $arg) . "'";
}

$textoLayout = '';
$bboxXml = '';
$pdfObj = null;

// Tenta pdftotext somente se ao menos uma função shell estiver disponível no servidor
$temShell = funcaoDisponivel('shell_exec') || funcaoDisponivel('exec') || funcaoDisponivel('proc_open') || funcaoDisponivel('passthru');
if ($temShell) {
    $argFile = escaparArgShell($tmpFile);
    foreach (['pdftotext', '/usr/bin/pdftotext', '/usr/local/bin/pdftotext'] as $bin) {
        $bboxXml = executarComandoShell($bin . ' -bbox-layout ' . $argFile . ' - 2>/dev/null');
        if (empty(trim($bboxXml)) || strpos($bboxXml, '<word') === false) {
            $bboxXml = executarComandoShell($bin . ' -bbox ' . $argFile . ' - 2>/dev/null');
        }
        if (!empty(trim($bboxXml)) && strpos($bboxXml, '<word') !== false) {
            $textoLayout = executarComandoShell($bin . ' -layout ' . $argFile . ' - 2>/dev/null');
            break;
        }
    }
}

// Carrega Smalot\PdfParser se pdftotext não gerou coordenadas ou texto
if (empty(trim($bboxXml)) || strpos($bboxXml, '<word') === false || empty(trim($textoLayout))) {
    $autoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        if (class_exists('\\Smalot\\PdfParser\\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdfObj = $parser->parseFile($tmpFile);
                if (empty(trim($textoLayout))) {
                    $textoLayout = $pdfObj->getText();
                }
            } catch (\Throwable $e) {
                error_log('importar_proforma PdfParser: ' . $e->getMessage());
            }
        }
    }
}

if (empty(trim($textoLayout)) && empty(trim($bboxXml)) && $pdfObj === null) {
    echo json_encode(['status' => 'error', 'error' => 'Não foi possível ler o conteúdo do PDF informado.']);
    exit();
}

$custosNomes = [
    "Taxas Bancárias e Diferença Cambial:",
    "Desembaraço Aduaneiro:",
    "Embalagens e Certificado de Origem:",
    "Custos de Armazenagem:",
    "Fretes Terrestres:",
    "Estufagem:",
    "Taxas Portuárias:",
    "Taxas Armador:",
    "Remessa de Documentos:",
    "Manuseio, Presença de Carga e Cia Aérea"
];

$custosItensDefault = [];
foreach ($custosNomes as $cnome) {
    $custosItensDefault[] = ["nome" => $cnome, "moeda" => "USS", "valor" => ""];
}

$data = [
    "cabecalho" => [
        "empresa_endereco" => "BRAZMIX COMERCIAL EXPORTADORA DE BOMBAS PARA MINERAÇÃO LTDA RUA\nCAVALIERE AMBROGIO CIPOLLA, 562 - MARILAND, CEP 95057-000 - CAXIAS DO SUL - RS -\nBRASIL -PHONE +55 54 3229 9365 - www.brazmix.com",
        "titulo" => "FATURA PROFORMA",
        "numero" => "",
        "emissao" => "",
        "validade" => "",
    ],
    "cliente" => [
        "importador" => "",
        "importador_endereco" => "",
        "cosignatario" => "",
        "notificado" => "",
    ],
    "detalhes_adicionais" => [
        "pais_origem" => "",
        "equipamento" => "",
        "modalidade_frete" => "",
        "lugar_embarque" => "",
        "lugar_transbordo" => "",
        "lugar_descarga" => "",
        "tempo_transito" => "",
    ],
    "detalhes_envio" => [
        "embalagem" => "",
        "peso_liquido" => "",
        "peso_bruto" => "",
        "cubagem" => "",
        "volumes" => "",
        "condicoes_venda" => "",
        "pagamento" => "",
    ],
    "itens" => [],
    "totais" => [
        "peso_liquido_total" => "",
        "preco_total" => "",
    ],
    "custos_internos" => [
        "itens" => $custosItensDefault,
        "total" => "",
        "observacao" => ""
    ],
    "resumo_financeiro" => [
        "custos_internos" => "",
        "frete" => "",
        "seguro" => "",
        "desconto" => "",
        "total_geral" => "",
    ],
    "instrucoes_credito" => [
        "beneficiario" => "BRAZMIX COMERCIAL EXPORTADORA DE BOMBAS PARA MINERAÇÃO LTDA",
        "conta" => "45216-5",
        "iban" => "BR4700000000028710000452165C1",
        "banco" => "BANCO DO BRASIL S.A. (SAO PAULO - SP)",
        "swift" => "BRASBRRJSBO",
        "correspondente" => "BANCO DO BRASIL - NEW YORK - NY - USA",
        "correspondente_swift" => "BRASUS33",
        "fedwire" => "FW0260000",
    ],
    "rodape" => [
        "info_arquivo" => "",
        "pagina" => ""
    ]
];

// Helpers de agrupamento de palavras por coordenadas
function joinWordsBbox(array $wlist): string {
    if (empty($wlist)) return '';
    usort($wlist, function ($a, $b) {
        $ya = round($a['yc'] / 3.0);
        $yb = round($b['yc'] / 3.0);
        if ($ya !== $yb) return $ya <=> $yb;
        return $a['xMin'] <=> $b['xMin'];
    });
    $parts = array_map(fn($w) => $w['text'], $wlist);
    return trim(implode(' ', $parts));
}

function joinLinesBbox(array $wlist, float $yTol = 4.0): string {
    if (empty($wlist)) return '';
    usort($wlist, fn($a, $b) => $a['yc'] <=> $b['yc']);
    $lines = [];
    $curr = [$wlist[0]];
    for ($i = 1, $n = count($wlist); $i < $n; $i++) {
        $w = $wlist[$i];
        if (abs($w['yc'] - $curr[count($curr) - 1]['yc']) <= $yTol) {
            $curr[] = $w;
        } else {
            usort($curr, fn($a, $b) => $a['xMin'] <=> $b['xMin']);
            $lines[] = implode(' ', array_map(fn($x) => $x['text'], $curr));
            $curr = [$w];
        }
    }
    usort($curr, fn($a, $b) => $a['xMin'] <=> $b['xMin']);
    $lines[] = implode(' ', array_map(fn($x) => $x['text'], $curr));
    return trim(implode("\n", $lines));
}

// Extração em PHP puro com coordenadas via Smalot\PdfParser quando pdftotext não estiver disponível
function extrairComandosBrutosPagina(\Smalot\PdfParser\Page $page): array {
    try {
        $cmds = $page->extractRawData();
        if (!empty($cmds)) return $cmds;
    } catch (\Throwable $e) {
    }
    $contents = $page->get('Contents');
    if (!$contents) return [];
    if ($contents instanceof \Smalot\PdfParser\Element\ElementXRef) {
        $obj = $page->getDocument()->getObjectById($contents->getId());
        if ($obj) {
            return $page->getDataCommands($obj->getContent() ?? '');
        }
    } elseif ($contents instanceof \Smalot\PdfParser\Element\ElementArray) {
        $cmds = [];
        foreach ($contents->getRawContent() as $child) {
            if ($child instanceof \Smalot\PdfParser\Element\ElementXRef) {
                $obj = $page->getDocument()->getObjectById($child->getId());
                if ($obj) {
                    $cmds = array_merge($cmds, $page->getDataCommands($obj->getContent() ?? ''));
                }
            } elseif (method_exists($child, 'getContent')) {
                $cmds = array_merge($cmds, $page->getDataCommands($child->getContent() ?? ''));
            }
        }
        return $cmds;
    }
    return [];
}

function decodificarTextoPdfCmd(array $subCmd, ?\Smalot\PdfParser\Font $font): string {
    if ($font !== null) {
        try {
            $txt = $font->decodeText([$subCmd]);
            if (is_string($txt) && $txt !== '') {
                return $txt;
            }
        } catch (\Throwable $e) {
        }
    }
    $t = $subCmd['t'] ?? '';
    $c = (string)($subCmd['c'] ?? '');
    if ($t === '<') {
        $hex = preg_replace('/\s+/', '', $c);
        if (strlen($hex) % 2 !== 0) $hex .= '0';
        $bin = @ pack('H*', $hex);
        if ($bin !== false) {
            return @mb_convert_encoding($bin, 'UTF-8', 'UTF-16BE,Windows-1252,ISO-8859-1');
        }
    }
    $str = preg_replace_callback('/\\\\([0-7]{1,3})/', fn($m) => chr(octdec($m[1])), $c);
    $str = strtr($str, [
        '\\n' => "\n", '\\r' => "\r", '\\t' => "\t",
        '\\(' => '(', '\\)' => ')', '\\\\' => '\\'
    ]);
    if (!mb_check_encoding($str, 'UTF-8')) {
        $str = @mb_convert_encoding($str, 'UTF-8', 'Windows-1252,ISO-8859-1');
    }
    return $str;
}

function extrairPagesWordsSmalot(\Smalot\PdfParser\Document $pdf): array {
    $pagesWords = [];
    foreach ($pdf->getPages() as $page) {
        $pageH = 841.89;
        $mb = $page->get('MediaBox');
        if ($mb instanceof \Smalot\PdfParser\Element\ElementArray) {
            $rawMb = $mb->getRawContent();
            if (isset($rawMb[3]) && method_exists($rawMb[3], 'getContent')) {
                $hVal = (float)$rawMb[3]->getContent();
                if ($hVal > 100) $pageH = $hVal;
            }
        }

        $cmds = extrairComandosBrutosPagina($page);
        if (empty($cmds)) continue;

        $ctm = [1.0, 0.0, 0.0, 1.0, 0.0, 0.0];
        $stack = [];
        $tm = [1.0, 0.0, 0.0, 1.0, 0.0, 0.0];
        $tlm = [1.0, 0.0, 0.0, 1.0, 0.0, 0.0];
        $tl = 0.0;
        $fontSize = 9.0;
        $currentFont = null;

        $mult = function (array $m1, array $m2): array {
            return [
                $m1[0] * $m2[0] + $m1[1] * $m2[2],
                $m1[0] * $m2[1] + $m1[1] * $m2[3],
                $m1[2] * $m2[0] + $m1[3] * $m2[2],
                $m1[2] * $m2[1] + $m1[3] * $m2[3],
                $m1[4] * $m2[0] + $m1[5] * $m2[2] + $m2[4],
                $m1[4] * $m2[1] + $m1[5] * $m2[3] + $m2[5],
            ];
        };

        $chunks = [];
        foreach ($cmds as $cmd) {
            $o = $cmd['o'] ?? '';
            $c = $cmd['c'] ?? '';
            switch ($o) {
                case 'q':
                    $stack[] = [$ctm, $currentFont, $fontSize];
                    break;
                case 'Q':
                    if (!empty($stack)) {
                        [$ctm, $currentFont, $fontSize] = array_pop($stack);
                    }
                    break;
                case 'cm':
                    $p = preg_split('/\s+/', trim((string)$c));
                    if (count($p) >= 6) {
                        $m = array_map('floatval', array_slice($p, -6));
                        $ctm = $mult($m, $ctm);
                    }
                    break;
                case 'BT':
                    $tm = [1.0, 0.0, 0.0, 1.0, 0.0, 0.0];
                    $tlm = [1.0, 0.0, 0.0, 1.0, 0.0, 0.0];
                    break;
                case 'Tf':
                    $p = preg_split('/\s+/', trim((string)$c));
                    if (count($p) >= 2) {
                        $fontId = ltrim($p[0], '/');
                        $fontSize = (float)$p[count($p) - 1];
                        try {
                            $f = $page->getFont($fontId);
                            if ($f !== null) $currentFont = $f;
                        } catch (\Throwable $e) {
                        }
                    }
                    break;
                case 'TL':
                    $tl = (float)trim((string)$c);
                    break;
                case 'Tm':
                    $p = preg_split('/\s+/', trim((string)$c));
                    if (count($p) >= 6) {
                        $tm = array_map('floatval', array_slice($p, -6));
                        $tlm = $tm;
                    }
                    break;
                case 'Td':
                case 'TD':
                    $p = preg_split('/\s+/', trim((string)$c));
                    if (count($p) >= 2) {
                        $tx = (float)$p[count($p) - 2];
                        $ty = (float)$p[count($p) - 1];
                        if ($o === 'TD') $tl = -$ty;
                        $tlm = $mult([1.0, 0.0, 0.0, 1.0, $tx, $ty], $tlm);
                        $tm = $tlm;
                    }
                    break;
                case 'T*':
                    $tlm = $mult([1.0, 0.0, 0.0, 1.0, 0.0, -$tl], $tlm);
                    $tm = $tlm;
                    break;
                case "'":
                case '"':
                    $tlm = $mult([1.0, 0.0, 0.0, 1.0, 0.0, -$tl], $tlm);
                    $tm = $tlm;
                    // prossegue para Tj
                case 'Tj':
                case 'TJ':
                    $trm = $mult($tm, $ctm);
                    $x = $trm[4];
                    $yPdf = $trm[5];
                    $scaleX = sqrt($trm[0] * $trm[0] + $trm[1] * $trm[1]) * ($fontSize > 0 ? $fontSize : 1.0);
                    $scaleY = sqrt($trm[2] * $trm[2] + $trm[3] * $trm[3]) * ($fontSize > 0 ? $fontSize : 1.0);
                    if ($scaleX <= 0) $scaleX = 7.0;
                    if ($scaleY <= 0) $scaleY = 7.0;

                    $yc = ($pageH - $yPdf) - ($scaleY * 0.35);
                    $charW = $scaleX * 0.50;

                    if (is_array($c)) {
                        $curStr = '';
                        $curX = $x;
                        $chunkStartX = $x;
                        foreach ($c as $sub) {
                            $st = $sub['t'] ?? '';
                            if ($st === 'n') {
                                $n = (float)$sub['c'];
                                $shift = -($n / 1000.0) * $scaleX;
                                if (abs($n) > 120) {
                                    if (trim($curStr) !== '') {
                                        $chunks[] = ['xMin' => $chunkStartX, 'yc' => $yc, 'text' => trim($curStr)];
                                    }
                                    $curStr = '';
                                    $curX += $shift;
                                    $chunkStartX = $curX;
                                } else {
                                    $curX += $shift;
                                }
                            } elseif ($st === '(' || $st === '<') {
                                $piece = decodificarTextoPdfCmd($sub, $currentFont);
                                if ($curStr === '') $chunkStartX = $curX;
                                $curStr .= $piece;
                                $curX += mb_strlen($piece, 'UTF-8') * $charW;
                            }
                        }
                        if (trim($curStr) !== '') {
                            $chunks[] = ['xMin' => $chunkStartX, 'yc' => $yc, 'text' => trim($curStr)];
                        }
                    } else {
                        $str = decodificarTextoPdfCmd($cmd, $currentFont);
                        if (trim($str) !== '') {
                            $chunks[] = ['xMin' => $x, 'yc' => $yc, 'text' => trim($str)];
                        }
                    }
                    break;
            }
        }

        if (empty($chunks)) continue;

        // Calibra offset vertical yc usando âncoras conhecidas da página
        $yShift = 0.0;
        foreach ($chunks as $ch) {
            if (strpos($ch['text'], 'DATA DE EMISS') !== false && $ch['yc'] > 25 && $ch['yc'] < 65) {
                $yShift = 44.8 - $ch['yc'];
                break;
            }
            if ($ch['text'] === 'NCM' && $ch['yc'] > 180 && $ch['yc'] < 225) {
                $yShift = 200.2 - $ch['yc'];
                break;
            }
        }

        $words = [];
        foreach ($chunks as $ch) {
            $yc = $ch['yc'] + $yShift;
            $parts = preg_split('/\s+/u', trim($ch['text']), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($parts as $idx => $part) {
                $wx = $ch['xMin'] + ($idx * 0.5);
                $words[] = [
                    'xMin' => $wx,
                    'yMin' => $yc - 3.5,
                    'xMax' => $wx + 4.0,
                    'yMax' => $yc + 3.5,
                    'yc'   => $yc,
                    'text' => $part,
                ];
            }
        }
        if (!empty($words)) {
            $pagesWords[] = $words;
        }
    }
    return $pagesWords;
}

// Extração principal baseada em coordenadas exatas (pdftotext -bbox-layout ou Smalot\PdfParser)
$pagesWords = [];
if (!empty($bboxXml) && preg_match_all('/<page[^>]*>(.*?)<\/page>/s', $bboxXml, $pm)) {
    foreach ($pm[1] as $pageContent) {
        $words = [];
        if (preg_match_all('/<word xMin="([0-9.]+)" yMin="([0-9.]+)" xMax="([0-9.]+)" yMax="([0-9.]+)">(.*?)<\/word>/s', $pageContent, $wm, PREG_SET_ORDER)) {
            foreach ($wm as $m) {
                $yMin = (float)$m[2];
                $yMax = (float)$m[4];
                $words[] = [
                    'xMin' => (float)$m[1],
                    'yMin' => $yMin,
                    'xMax' => (float)$m[3],
                    'yMax' => $yMax,
                    'yc'   => ($yMin + $yMax) / 2.0,
                    'text' => html_entity_decode($m[5], ENT_QUOTES | ENT_XML1, 'UTF-8'),
                ];
            }
        }
        if (!empty($words)) {
            $pagesWords[] = $words;
        }
    }
}

if (empty($pagesWords) && $pdfObj !== null) {
    try {
        $pagesWords = extrairPagesWordsSmalot($pdfObj);
    } catch (\Throwable $e) {
        error_log('importar_proforma extrairPagesWordsSmalot: ' . $e->getMessage());
    }
}

$regexLinhaItem = '/^(\d+)\s+(.+?)\s*(\d{4}\.\d{2}\.\d{2})\s*(.+?)\s+([A-Z]{1,4})\s+([\d\.]+)\s+([\d\.,-]+)\s*(?:US[S\$]\s*)?([\d\.,-]+)\s+([\d\.,-]+)\s*(?:US[S\$]\s*)?([\d\.,-]+)$/u';

if (!empty($pagesWords)) {
    $p1 = $pagesWords[0];

    // 1. Cabeçalho
    $numW = array_filter($p1, fn($w) => $w['xMin'] >= 220 && $w['xMin'] < 350 && $w['yc'] >= 58 && $w['yc'] < 80 && !in_array($w['text'], ['FATURA', 'PROFORMA'], true));
    $emissaoW = array_filter($p1, fn($w) => $w['xMin'] >= 468 && $w['yc'] >= 38 && $w['yc'] < 52 && strpos($w['text'], ':') === false);
    $validadeW = array_filter($p1, fn($w) => $w['xMin'] >= 468 && $w['yc'] >= 52 && $w['yc'] < 68 && strpos($w['text'], ':') === false);

    $data['cabecalho']['numero']   = joinWordsBbox($numW);
    $data['cabecalho']['emissao']  = joinWordsBbox($emissaoW);
    $data['cabecalho']['validade'] = joinWordsBbox($validadeW);

    // 2. Cliente (Coluna 1: 50 <= xMin < 252)
    $impW    = array_filter($p1, fn($w) => $w['xMin'] >= 50 && $w['xMin'] < 252 && $w['yc'] >= 103 && $w['yc'] < 116);
    $impEndW = array_filter($p1, fn($w) => $w['xMin'] >= 50 && $w['xMin'] < 252 && $w['yc'] >= 116 && $w['yc'] < 141);
    $cosigW  = array_filter($p1, fn($w) => $w['xMin'] >= 50 && $w['xMin'] < 252 && $w['yc'] >= 142 && $w['yc'] < 180);
    $notifW  = array_filter($p1, fn($w) => $w['xMin'] >= 50 && $w['xMin'] < 252 && $w['yc'] >= 180 && $w['yc'] < 193);

    $data['cliente']['importador']          = joinWordsBbox($impW);
    $data['cliente']['importador_endereco'] = preg_replace('/\s+/', ' ', joinLinesBbox($impEndW));
    $data['cliente']['cosignatario']       = joinWordsBbox($cosigW);
    $data['cliente']['notificado']          = joinWordsBbox($notifW);

    // 3. Detalhes Adicionais (310 <= xMin < 434) e Detalhes do Envio (468 <= xMin < 573)
    $rowBounds = [
        [103.3, 116.2],
        [116.2, 129.0],
        [129.0, 141.8],
        [141.8, 154.6],
        [154.6, 167.5],
        [167.5, 180.3],
        [180.3, 193.1],
    ];
    $adKeys = ['pais_origem', 'equipamento', 'modalidade_frete', 'lugar_embarque', 'lugar_transbordo', 'lugar_descarga', 'tempo_transito'];
    $enKeys = ['embalagem', 'peso_liquido', 'peso_bruto', 'cubagem', 'volumes', 'condicoes_venda', 'pagamento'];

    foreach ($rowBounds as $idx => [$y0, $y1]) {
        $adW = array_filter($p1, fn($w) => $w['xMin'] >= 310 && $w['xMin'] < 434 && $w['yc'] >= $y0 && $w['yc'] < $y1 && strpos($w['text'], ':') === false);
        $enW = array_filter($p1, fn($w) => $w['xMin'] >= 468 && $w['xMin'] < 573 && $w['yc'] >= $y0 && $w['yc'] < $y1 && strpos($w['text'], ':') === false);
        $data['detalhes_adicionais'][$adKeys[$idx]] = joinWordsBbox($adW);
        $data['detalhes_envio'][$enKeys[$idx]]      = joinWordsBbox($enW);
    }

    // 4. Itens da Tabela (em todas as páginas do PDF)
    foreach ($pagesWords as $pWords) {
        $totWords = array_values(array_filter($pWords, fn($w) => $w['text'] === 'TOTAIS' && $w['xMin'] > 400));
        $yLimit = !empty($totWords) ? ($totWords[0]['yc'] - 5.0) : 760.0;

        $anchors = array_values(array_filter($pWords, fn($w) => $w['xMin'] < 48 && $w['yc'] > 205 && $w['yc'] < $yLimit && preg_match('/^\d+$/', $w['text'])));
        usort($anchors, fn($a, $b) => $a['yc'] <=> $b['yc']);

        $processedYc = [];
        foreach ($anchors as $anc) {
            $yc = $anc['yc'];
            $rw = array_values(array_filter($pWords, fn($w) => abs($w['yc'] - $yc) < 4.5));
            usort($rw, fn($a, $b) => $a['xMin'] <=> $b['xMin']);

            $ncmW = array_values(array_filter($rw, fn($w) => $w['xMin'] >= 85 && $w['xMin'] < 135 && preg_match('/^\d{4}\.\d{2}\.\d{2}$/', $w['text'])));
            if (empty($ncmW)) continue;

            $processedYc[] = $yc;
            $ncmX = $ncmW[0]['xMin'];

            $codigoW   = array_filter($rw, fn($w) => $w['xMin'] > $anc['xMin'] && $w['xMin'] < $ncmX);
            $descW     = array_filter($rw, fn($w) => $w['xMin'] > $ncmX && $w['xMin'] < 342);
            $unW       = array_filter($rw, fn($w) => $w['xMin'] >= 342 && $w['xMin'] < 369);
            $qtdW      = array_filter($rw, fn($w) => $w['xMin'] >= 369 && $w['xMin'] < 398);
            $pliqW     = array_filter($rw, fn($w) => $w['xMin'] >= 398 && $w['xMin'] < 434 && $w['text'] !== 'USS');
            $punitW    = array_filter($rw, fn($w) => $w['xMin'] >= 450 && $w['xMin'] < 481 && $w['text'] !== 'USS');
            $ptotLiqW  = array_filter($rw, fn($w) => $w['xMin'] >= 481 && $w['xMin'] < 522 && $w['text'] !== 'USS');
            $ptotW     = array_filter($rw, fn($w) => $w['xMin'] >= 535 && $w['xMin'] < 573 && $w['text'] !== 'USS');

            $qtdStr = preg_replace('/\D/', '', joinWordsBbox($qtdW));

            $itemRow = [
                'item'           => (int)$anc['text'],
                'codigo'         => joinWordsBbox($codigoW),
                'ncm'            => $ncmW[0]['text'],
                'descricao'      => joinWordsBbox($descW),
                'unidade'        => joinWordsBbox($unW),
                'quantidade'     => (int)($qtdStr !== '' ? $qtdStr : 0),
                'peso_liq'       => preg_replace('/\s*US[S\$]\s*$/iu', '', joinWordsBbox($pliqW)),
                'preco_unitario' => joinWordsBbox($punitW),
                'peso_liq_total' => preg_replace('/\s*US[S\$]\s*$/iu', '', joinWordsBbox($ptotLiqW)),
                'preco_total'    => joinWordsBbox($ptotW),
            ];

            // Fallback por regex na linha caso alguma coluna tenha ficado deslocada no PDF
            if ($itemRow['descricao'] === '' || $itemRow['preco_total'] === '' || $itemRow['quantidade'] === 0) {
                $rowStr = implode(' ', array_map(fn($w) => $w['text'], $rw));
                if (preg_match($regexLinhaItem, $rowStr, $rm)) {
                    $qStr = preg_replace('/\D/', '', $rm[6]);
                    $itemRow = [
                        'item'           => (int)$rm[1],
                        'codigo'         => trim($rm[2]),
                        'ncm'            => trim($rm[3]),
                        'descricao'      => trim($rm[4]),
                        'unidade'        => trim($rm[5]),
                        'quantidade'     => (int)($qStr !== '' ? $qStr : 0),
                        'peso_liq'       => trim($rm[7]),
                        'preco_unitario' => trim($rm[8]),
                        'peso_liq_total' => trim($rm[9]),
                        'preco_total'    => trim($rm[10]),
                    ];
                }
            }

            $data['itens'][] = $itemRow;
        }

        // Verifica linhas com NCM que eventualmente não foram pegas por âncora xMin < 48
        $allNcms = array_values(array_filter($pWords, fn($w) => $w['yc'] > 205 && $w['yc'] < $yLimit && preg_match('/^\d{4}\.\d{2}\.\d{2}$/', $w['text'])));
        foreach ($allNcms as $nw) {
            $nyc = $nw['yc'];
            $already = false;
            foreach ($processedYc as $pyc) {
                if (abs($pyc - $nyc) < 4.5) {
                    $already = true;
                    break;
                }
            }
            if ($already) continue;

            $rw = array_values(array_filter($pWords, fn($w) => abs($w['yc'] - $nyc) < 4.5));
            usort($rw, fn($a, $b) => $a['xMin'] <=> $b['xMin']);
            $rowStr = implode(' ', array_map(fn($w) => $w['text'], $rw));
            if (preg_match($regexLinhaItem, $rowStr, $rm)) {
                $processedYc[] = $nyc;
                $qStr = preg_replace('/\D/', '', $rm[6]);
                $data['itens'][] = [
                    'item'           => (int)$rm[1],
                    'codigo'         => trim($rm[2]),
                    'ncm'            => trim($rm[3]),
                    'descricao'      => trim($rm[4]),
                    'unidade'        => trim($rm[5]),
                    'quantidade'     => (int)($qStr !== '' ? $qStr : 0),
                    'peso_liq'       => trim($rm[7]),
                    'preco_unitario' => trim($rm[8]),
                    'peso_liq_total' => trim($rm[9]),
                    'preco_total'    => trim($rm[10]),
                ];
            }
        }
    }

    // 5. Seção Inferior (na última página onde fica TOTAIS)
    $pLast = $pagesWords[count($pagesWords) - 1];
    $totWords = array_values(array_filter($pLast, fn($w) => $w['text'] === 'TOTAIS' && $w['xMin'] > 400));
    if (!empty($totWords)) {
        $yTot = $totWords[0]['yc'];
        $totRow = array_filter($pLast, fn($w) => abs($w['yc'] - $yTot) < 4.5);
        $data['totais']['peso_liquido_total'] = preg_replace('/\s*US[S\$]\s*$/iu', '', joinWordsBbox(array_filter($totRow, fn($w) => $w['xMin'] >= 481 && $w['xMin'] < 522 && $w['text'] !== 'USS')));
        $data['totais']['preco_total']        = joinWordsBbox(array_filter($totRow, fn($w) => $w['xMin'] >= 535 && $w['xMin'] < 573 && $w['text'] !== 'USS'));

        // Custos Internos (10 linhas à esquerda)
        $costAnchors = [
            ["Taxas Bancárias e Diferença Cambial:", "Cambial:"],
            ["Desembaraço Aduaneiro:", "Aduaneiro:"],
            ["Embalagens e Certificado de Origem:", "Origem:"],
            ["Custos de Armazenagem:", "Armazenagem:"],
            ["Fretes Terrestres:", "Terrestres:"],
            ["Estufagem:", "Estufagem:"],
            ["Taxas Portuárias:", "Portuárias:"],
            ["Taxas Armador:", "Armador:"],
            ["Remessa de Documentos:", "Documentos:"],
            ["Manuseio, Presença de Carga e Cia Aérea", "Aérea"],
        ];
        $custosExtraidos = [];
        foreach ($costAnchors as [$fullLbl, $keyWord]) {
            $lw = array_values(array_filter($pLast, fn($w) => $w['xMin'] < 145 && $w['yc'] > ($yTot + 10) && $w['text'] === $keyWord));
            $val = '';
            if (!empty($lw)) {
                $lyc = $lw[0]['yc'];
                $vw = array_values(array_filter($pLast, fn($w) => $w['xMin'] >= 150 && $w['xMin'] < 195 && abs($w['yc'] - $lyc) < 3.5 && $w['text'] !== 'USS'));
                if (!empty($vw)) {
                    $val = $vw[count($vw) - 1]['text'];
                }
            }
            $custosExtraidos[] = ["nome" => $fullLbl, "moeda" => "USS", "valor" => $val];
        }
        $data['custos_internos']['itens'] = $custosExtraidos;

        // Total de Custos Internos
        $totCiLw = array_values(array_filter($pLast, fn($w) => $w['xMin'] < 145 && $w['yc'] > ($yTot + 85) && $w['text'] === 'INTERNOS'));
        $yTotCi = $yTot + 104.0;
        if (!empty($totCiLw)) {
            $yTotCi = $totCiLw[0]['yc'];
            $vw = array_values(array_filter($pLast, fn($w) => $w['xMin'] >= 150 && $w['xMin'] < 195 && abs($w['yc'] - $yTotCi) < 3.5 && $w['text'] !== 'USS'));
            if (!empty($vw)) {
                $data['custos_internos']['total'] = $vw[count($vw) - 1]['text'];
            }
        }

        // Observação abaixo de TOTAL DE CUSTOS INTERNOS (ex: "SEM CARGAS CONSOLIDADAS" ou "MWM - SP\n474")
        $obsW = array_values(array_filter($pLast, fn($w) => $w['xMin'] >= 18 && $w['xMin'] < 192 && $w['yc'] > ($yTotCi + 6.0) && $w['yc'] < ($yTotCi + 38.0)));
        if (!empty($obsW)) {
            $data['custos_internos']['observacao'] = joinLinesBbox($obsW);
        }

        // Resumo Financeiro (5 linhas à direita)
        $resMap = [
            'custos_internos' => 'CUSTOS',
            'frete'           => 'FRETE:',
            'seguro'          => 'SEGURO:',
            'desconto'        => 'DESCONTO:',
            'total_geral'     => 'TOTAL',
        ];
        foreach ($resMap as $field => $prefix) {
            $rw = array_values(array_filter($pLast, fn($w) => $w['xMin'] >= 192 && $w['xMin'] < 235 && $w['yc'] > ($yTot + 5) && $w['yc'] < ($yTot + 55) && strpos($w['text'], $prefix) === 0));
            if (!empty($rw)) {
                $ryc = $rw[0]['yc'];
                $vw = array_values(array_filter($pLast, fn($w) => $w['xMin'] >= 530 && $w['xMin'] < 575 && abs($w['yc'] - $ryc) < 3.5 && $w['text'] !== 'USS'));
                if (!empty($vw)) {
                    $data['resumo_financeiro'][$field] = $vw[count($vw) - 1]['text'];
                }
            }
        }

        // Rodapé interno (Atualizado em ...)
        $rodapeW = array_values(array_filter($pLast, fn($w) => $w['yc'] > ($yTot + 130) && $w['yc'] < ($yTot + 158) && $w['xMin'] >= 180 && $w['xMin'] < 420));
        if (!empty($rodapeW)) {
            $data['rodape']['info_arquivo'] = joinWordsBbox($rodapeW);
        }
    }
}

// Fallback textual caso apenas $textoLayout esteja disponível
if (empty($data['itens']) && !empty($textoLayout)) {
    foreach (preg_split('/\R/u', $textoLayout) as $line) {
        $line = trim(preg_replace('/\s+/u', ' ', $line));
        if ($line !== '' && preg_match($regexLinhaItem, $line, $rm)) {
            $qStr = preg_replace('/\D/', '', $rm[6]);
            $data['itens'][] = [
                'item'           => (int)$rm[1],
                'codigo'         => trim($rm[2]),
                'ncm'            => trim($rm[3]),
                'descricao'      => trim($rm[4]),
                'unidade'        => trim($rm[5]),
                'quantidade'     => (int)($qStr !== '' ? $qStr : 0),
                'peso_liq'       => trim($rm[7]),
                'preco_unitario' => trim($rm[8]),
                'peso_liq_total' => trim($rm[9]),
                'preco_total'    => trim($rm[10]),
            ];
        }
    }
}

// Ordena os itens alfabeticamente pela descrição e renumera a coluna ITEM
if (!empty($data['itens'])) {
    $normalizarTexto = function (string $str): string {
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

    usort($data['itens'], function ($a, $b) use ($normalizarTexto) {
        $normA = $normalizarTexto($a['descricao'] ?? '');
        $normB = $normalizarTexto($b['descricao'] ?? '');
        $cmp = strcmp($normA, $normB);
        if ($cmp !== 0) {
            return $cmp;
        }
        return strcasecmp($a['descricao'] ?? '', $b['descricao'] ?? '');
    });

    foreach ($data['itens'] as $idx => &$itemRef) {
        $itemRef['item'] = $idx + 1;
    }
    unset($itemRef);
}

// Fallback para cabeçalho e rodapé a partir de $textoLayout se necessário
if (empty($data['rodape']['info_arquivo']) && !empty($textoLayout) && preg_match('/(Atualizado\s+em\s+.+?FATURA\s+\d+-\d+)/iu', $textoLayout, $m)) {
    $data['rodape']['info_arquivo'] = trim(preg_replace('/\s+/', ' ', $m[1]));
}
if (empty($data['cabecalho']['numero']) && preg_match('/FATURA\s+(\d+-\d+)/iu', $data['rodape']['info_arquivo'] . ' ' . $textoLayout, $m)) {
    $data['cabecalho']['numero'] = $m[1];
}
if (empty($data['cabecalho']['emissao']) && !empty($textoLayout) && preg_match('/EMISS[ÃA]O:\s*(\d{2}\/\d{2}\/\d{4})/iu', $textoLayout, $m)) {
    $data['cabecalho']['emissao'] = $m[1];
}
if (empty($data['cabecalho']['validade']) && !empty($textoLayout) && preg_match('/VALIDADE:\s*(\d{2}\/\d{2}\/\d{4})/iu', $textoLayout, $m)) {
    $data['cabecalho']['validade'] = $m[1];
}
if (empty($data['resumo_financeiro']['total_geral']) && !empty($textoLayout) && preg_match('/TOTAL\s+GERAL:\s*US[S\$]\s*([\d\.,-]+)/iu', $textoLayout, $m)) {
    $data['resumo_financeiro']['total_geral'] = $m[1];
}

// Grava no arquivo proforma.json
$jsonPath = __DIR__ . '/proforma.json';
$salvo = file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if ($salvo === false) {
    echo json_encode(['status' => 'error', 'error' => 'Falha ao gravar arquivo proforma.json no servidor.']);
    exit();
}

try {
    registrarLog($usuario, 'faturas', 'proforma', 'importar', "Importou Fatura Proforma {$data['cabecalho']['numero']} (" . count($data['itens']) . " itens, Total USS {$data['resumo_financeiro']['total_geral']})");
} catch (\Throwable $e) {
    error_log('importar_proforma log: ' . $e->getMessage());
}

ob_end_clean();
echo json_encode([
    'status'  => 'success',
    'message' => 'Proforma ' . ($data['cabecalho']['numero'] ?? '') . ' importada com sucesso! (' . count($data['itens']) . ' itens)',
    'data'    => $data,
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

