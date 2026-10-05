<?php
$logDir = __DIR__ . '/action/logs.json';
$logs = [];

if (file_exists($logDir)) {
    $content = file_get_contents($logDir);
    $logs = json_decode($content, true); // Retorna array associativo
    if (is_array($logs)) {
        $logs = array_reverse($logs);
    } else {
        $logs = [];
    }
}

$usuarioUnico = [];
$moduloUnico = [];
$acoesUnicas = [];

foreach ($logs as $l) {
    if (isset($l['usuario'])) $usuarioUnico[$l['usuario']] = true;
    if (isset($l['modulo']))  $moduloUnico[$l['modulo']] = true;
    if (isset($l['acao']))    $acoesUnicas[$l['acao']] = true;
}

$usuarioUnico = array_keys($usuarioUnico);
sort($usuarioUnico);
$moduloUnico = array_keys($moduloUnico);
sort($moduloUnico);
$acoesUnicas = array_keys($acoesUnicas);
sort($acoesUnicas);

// Captura parâmetros para gerar URL
$filtroDataInicio = $_GET['data_inicio'] ?? '';
$filtroDataFim    = $_GET['data_fim']    ?? '';
$filtroUsuario    = $_GET['usuario']     ?? '';
$filtroModulo     = $_GET['filtro_modulo'] ?? '';
$filtroAcao       = $_GET['acao']        ?? '';


// Aplica Filtros 
$logsFiltrados = array_filter($logs, function ($log) use ($filtroDataInicio, $filtroDataFim, $filtroUsuario, $filtroModulo, $filtroAcao) {
    // Filtro de Datas
    $datalog = DateTime::createFromFormat('d/m/Y H:i:s', $log['timestamp'] ?? '');
    if ($datalog) {
        if ($filtroDataInicio !== '') {
            $inicio = DateTime::createFromFormat('Y-m-d', $filtroDataInicio);
            if ($inicio && $datalog->format('Ymd') < $inicio->format('Ymd')) return false;
        }
        if ($filtroDataFim !== '') {
            $fim = DateTime::createFromFormat('Y-m-d', $filtroDataFim);
            if ($fim && $datalog->format('Ymd') > $fim->format('Ymd')) return false;
        }
    }

    // Filtro Strings
    if ($filtroUsuario !== '' && strtolower($log['usuario'] ?? '') !== strtolower($filtroUsuario)) return false;
    if ($filtroModulo !== '' && strtolower($log['modulo'] ?? '') !== strtolower($filtroModulo)) return false;
    if ($filtroAcao !== '' && strtolower($log['acao'] ?? '') !== strtolower($filtroAcao)) return false;

    return true;
});

// Paginação Server-side

// Usa 'p' em vez de 'page' para não conflitar com rotas principais do sistema
$p = max(1, intval($_GET['p'] ?? 1));
$limitePorPagina = 30;

$totalRegistros = count($logsFiltrados);
$totalPaginas = ceil($totalRegistros / $limitePorPagina);
if ($p > $totalPaginas && $totalPaginas > 0) $p = $totalPaginas;

$offset = ($p - 1) * $limitePorPagina;
$logsPaginados = array_slice($logsFiltrados, $offset, $limitePorPagina);

// Helper para gerar URL da paginação sem perder os filtros e rotas atuais
function gerarUrlPaginacao($novaPagina)
{
    $params = $_GET;
    $params['p'] = $novaPagina;
    unset($params['modulo']);
    unset($params['submodulo']);
    return '?' . http_build_query($params);
}

?>

<div class="container-fluid mt-4">
    <h2>Histórico de Atividades (Logs)</h2>
    <hr>
    <!-- Filtro -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body bg-light">
            <form id="formFiltroLogs" method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label small">Data Início</label>
                        <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?= htmlspecialchars($filtroDataInicio) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Data Fim</label>
                        <input type="date" name="data_fim" class="form-control form-control-sm" value="<?= htmlspecialchars($filtroDataFim) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Usuário</label>
                        <select name="usuario" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach ($usuarioUnico as $u): ?>
                                <option value="<?= htmlspecialchars($u) ?>" <?= $filtroUsuario === $u ? 'selected' : '' ?>> <?= htmlspecialchars($u) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Módulo</label>
                        <select name="filtro_modulo" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach ($moduloUnico as $m): ?>
                                <option value="<?= htmlspecialchars($m) ?>" <?= $filtroModulo === $m ? 'selected' : '' ?>> <?= ucfirst(htmlspecialchars($m)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Ação</label>
                        <select name="acao" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach ($acoesUnicas as $a): ?>
                                <option value="<?= htmlspecialchars($a) ?>" <?= $filtroAcao === $a ? 'selected' : '' ?>> <?= ucfirst(htmlspecialchars($a)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex flex-column justify-content-end gap-1">
                        <button type="button" id="btnLimparLogs" class="btn btn-secondary btn-sm w-100"><i class="fa-solid"></i> Limpar</button>
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa-solid"></i> Filtrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive shadow-sm rounded mb-3">
        <table class="table table-hover table-bordered align-middle mb-0 text-center">
            <thead class="table-dark">
                <tr>
                    <th style="width: 180px;">Data/Hora</th>
                    <th style="width: 100px;">Usuário</th>
                    <th style="width: 180px;">Módulo/Submódulo</th>
                    <th style="width: 90px;">Ação</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logsPaginados)): ?>
                    <div class="alert alert-info text-muted text-center">Nenhum registro de log encontrado com esses filtros</div>
                <?php else: ?>
                    <?php foreach ($logsPaginados as $log): ?>
                        <tr>
                            <td class="text-nowrap">
                                <i class="fa-regular text-muted"></i> <?= htmlspecialchars($log['timestamp'] ?? '-') ?>
                            </td>

                            <td>
                                <i class="fa-regular text-muted"></i> <?= htmlspecialchars($log['usuario'] ?? 'Desconhecido') ?>
                            </td>

                            <td>
                                <strong><?= htmlspecialchars(ucfirst($log['modulo'] ?? '-')) ?></strong>
                                <?php if (!empty($log['submodulo'])): ?>
                                    <small class="text-muted">/ <?= htmlspecialchars(ucfirst($log['submodulo'])) ?></small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php $acao = strtolower($log['acao'] ?? ''); ?>
                                <i class="fa-solid"></i> <?= htmlspecialchars(ucfirst($acao)) ?>
                            </td>

                            <td><?= htmlspecialchars($log['detalhes'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Navegação de Paginação -->
    <nav>
        <ul class="pagination justify-content-center mt-4">
            <!-- Botão Anterior -->
            <li class="page-item <?= ($p <= 1) ? 'disabled' : '' ?>">
                <a class="page-link link-paginacao" href="<?= gerarUrlPaginacao($p - 1) ?>">Anterior</a>
            </li>

            <?php
                    $adjacents = 2; // Define a "janela" de botões (ex: 2 pra esquerda, 2 pra direita)
                    $inicioLoop = max(1, $p - $adjacents);
                    $fimLoop = min($totalPaginas, $p + $adjacents);

                    // Ajuste fino para sempre mostrar o mesmo tamanho de bloco se estiver no comecinho ou finalzinho
                    if ($p <= $adjacents) {
                        $fimLoop = min($totalPaginas, 1 + ($adjacents * 2));
                    }
                    if ($p > $totalPaginas - $adjacents) {
                        $inicioLoop = max(1, $totalPaginas - ($adjacents * 2));
                    }

                    // Se a janela não começar no 1, mostra o 1 e os 3 pontinhos...
                    if ($inicioLoop > 1) {
                        echo '<li class="page-item"><a class="page-link link-paginacao" href="' . gerarUrlPaginacao(1) . '">' . 1 . '</a></li>';
                        if ($inicioLoop > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    // Laço de repetição só para a "janela" visível
                    for ($i = $inicioLoop; $i <= $fimLoop; $i++):
            ?>
                <li class="page-item <?= $i == $p ? 'active' : '' ?>">
                    <a class="page-link link-paginacao" href="<?= gerarUrlPaginacao($i) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>

            <?php
                    // Se a janela não terminar na última página, mostra os 3 pontinhos... e a última página
                    if ($fimLoop < $totalPaginas) {
                        if ($fimLoop < $totalPaginas - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link link-paginacao" href="' . gerarUrlPaginacao($totalPaginas) . '">' . $totalPaginas . '</a></li>';
                    }
            ?>

            <!-- Botão Próximo -->
            <li class="page-item <?= ($p >= $totalPaginas) ? 'disabled' : '' ?>">
                <a class="page-link link-paginacao" href="<?= gerarUrlPaginacao($p + 1) ?>">Próxima</a>
            </li>
        </ul>

        <div class="text-center text-muted small mb-4">
            Exibindo página <?= $p ?> de <?= $totalPaginas ?> (Total: <?= $totalRegistros ?> registros)
        </div>
    </nav>
<?php endif; ?>
</div>

<script src="js/configuracao/log.js"></script>