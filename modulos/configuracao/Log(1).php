<div class="container-fluid mt-4">
    <h2><i class="fa-solid fa-list-check"></i> Histórico de Atividades (Logs)</h2>
    <hr>
    
    <?php
    $logFile = __DIR__ . '/action/logs.json';
    $logs = [];

    // Carrega e inverte o JSON (mais novos primeiro)
    if (file_exists($logFile)) {
        $conteudo = file_get_contents($logFile);
        $logs = json_decode($conteudo, true) ?? [];
        $logs = array_reverse($logs);
    }

    // --- Levanta os valores únicos para os <select> dos filtros ---
    $usuariosUnicos = [];
    $modulosUnicos = [];
    $acoesUnicas = [];
    foreach ($logs as $log) {
        if (!empty($log['usuario'])) $usuariosUnicos[$log['usuario']] = true;
        if (!empty($log['modulo'])) $modulosUnicos[$log['modulo']] = true;
        if (!empty($log['acao'])) $acoesUnicas[$log['acao']] = true;
    }
    $usuariosUnicos = array_keys($usuariosUnicos); sort($usuariosUnicos);
    $modulosUnicos = array_keys($modulosUnicos); sort($modulosUnicos);
    $acoesUnicas = array_keys($acoesUnicas); sort($acoesUnicas);

    // --- Captura os parâmetros de filtro da URL ---
    $filtroDataInicio = $_GET['data_inicio'] ?? '';
    $filtroDataFim    = $_GET['data_fim'] ?? '';
    $filtroUsuario    = $_GET['usuario_log'] ?? '';
    $filtroModulo     = $_GET['modulo_log'] ?? '';
    $filtroAcao       = $_GET['acao_log'] ?? '';
    
    // Usa 'p' em vez de 'page' para não conflitar com rotas principais do sistema
    $p = max(1, intval($_GET['p'] ?? 1));
    $limitePorPagina = 30;

    // --- Aplica os Filtros ---
    $logsFiltrados = array_filter($logs, function($log) use ($filtroDataInicio, $filtroDataFim, $filtroUsuario, $filtroModulo, $filtroAcao) {
        // Filtro de Data
        $dataLog = DateTime::createFromFormat('d/m/Y H:i:s', $log['timestamp']);
        if ($dataLog) {
            if ($filtroDataInicio !== '') {
                $inicio = DateTime::createFromFormat('Y-m-d', $filtroDataInicio);
                if ($inicio && $dataLog->format('Ymd') < $inicio->format('Ymd')) return false;
            }
            if ($filtroDataFim !== '') {
                $fim = DateTime::createFromFormat('Y-m-d', $filtroDataFim);
                if ($fim && $dataLog->format('Ymd') > $fim->format('Ymd')) return false;
            }
        }

        // Filtro de Textos exatos
        if ($filtroUsuario !== '' && strtolower($log['usuario'] ?? '') !== strtolower($filtroUsuario)) return false;
        if ($filtroModulo !== '' && strtolower($log['modulo'] ?? '') !== strtolower($filtroModulo)) return false;
        if ($filtroAcao !== '' && strtolower($log['acao'] ?? '') !== strtolower($filtroAcao)) return false;

        return true;
    });

    // --- Paginação Server-side ---
    $totalRegistros = count($logsFiltrados);
    $totalPaginas = ceil($totalRegistros / $limitePorPagina);
    if ($p > $totalPaginas && $totalPaginas > 0) $p = $totalPaginas;
    
    $offset = ($p - 1) * $limitePorPagina;
    $logsPaginados = array_slice($logsFiltrados, $offset, $limitePorPagina);
    /*
    // Helper para gerar URL da paginação sem perder os filtros e rotas atuais
    function gerarUrlPaginacao($novaPagina) {
        $params = $_GET;
        $params['p'] = $novaPagina;
        return '?' . http_build_query($params);
    }
        */
    ?>

    <!-- Painel de Filtros -->
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
                        <select name="usuario_log" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach($usuariosUnicos as $u): ?>
                                <option value="<?= htmlspecialchars($u) ?>" <?= $filtroUsuario === $u ? 'selected' : '' ?>><?= htmlspecialchars($u) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Módulo</label>
                        <select name="modulo_log" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach($modulosUnicos as $m): ?>
                                <option value="<?= htmlspecialchars($m) ?>" <?= $filtroModulo === $m ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($m)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Ação</label>
                        <select name="acao_log" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach($acoesUnicas as $a): ?>
                                <option value="<?= htmlspecialchars($a) ?>" <?= $filtroAcao === $a ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($a)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-filter"></i> Filtrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela -->
    <?php if (empty($logsPaginados)): ?>
        <div class="alert alert-info">
            <i class="fa-solid fa-circle-info"></i> Nenhum registro de log encontrado com esses filtros.
        </div>
    <?php else: ?>
        <div class="table-responsive shadow-sm rounded mb-3">
            <table class="table table-striped table-hover table-bordered align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" style="width: 180px;">Data/Hora</th>
                        <th scope="col">Usuário</th>
                        <th scope="col">Módulo (Local)</th>
                        <th scope="col">Ação</th>
                        <th scope="col">Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logsPaginados as $log): ?>
                        <tr>
                            <td class="text-nowrap">
                                <i class="fa-regular fa-clock text-muted"></i> <?= htmlspecialchars($log['timestamp'] ?? '-') ?>
                            </td>
                            
                            <td>
                                <span class="badge bg-secondary">
                                    <i class="fa-solid fa-user"></i> <?= htmlspecialchars($log['usuario'] ?? 'Desconhecido') ?>
                                </span>
                            </td>
                            
                            <td>
                                <strong><?= htmlspecialchars(ucfirst($log['modulo'] ?? '-')) ?></strong>
                                <?php if (!empty($log['submodulo'])): ?>
                                    <small class="text-muted">/ <?= htmlspecialchars(ucfirst($log['submodulo'])) ?></small>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php 
                                    $acao = strtolower($log['acao'] ?? '');
                                    $badgeClass = 'bg-primary'; 
                                    $icone = 'fa-arrow-right';

                                    if (strpos($acao, 'excluir') !== false || strpos($acao, 'deletar') !== false) {
                                        $badgeClass = 'bg-danger';
                                        $icone = 'fa-trash';
                                    } elseif (strpos($acao, 'cadastrar') !== false || strpos($acao, 'criar') !== false) {
                                        $badgeClass = 'bg-success';
                                        $icone = 'fa-plus';
                                    } elseif (strpos($acao, 'editar') !== false || strpos($acao, 'atualizar') !== false) {
                                        $badgeClass = 'bg-warning text-dark';
                                        $icone = 'fa-pen';
                                    }
                                ?>
                                <span class="badge <?= $badgeClass ?>">
                                    <i class="fa-solid <?= $icone ?>"></i> <?= htmlspecialchars(ucfirst($acao)) ?>
                                </span>
                            </td>
                            
                            <td><?= htmlspecialchars($log['detalhes'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Navegação de Paginação -->

    <?php endif; ?>
</div>

<script>
// A intranet carrega as telas via AJAX, então um <form> comum recarregaria 
// a página inteira e perderia o contexto do SPA. Interceptamos via JS.
function atualizarLogs(queryString) {
    const contentDiv = document.getElementById("modulo-content");
    if (!contentDiv) return;

    // A rota correta do script de backend
    const url = `carregar_modulo.php?modulo=configuracao&submodulo=Log&${queryString}`;

    fetch(url)
        .then(response => response.text())
        .then(html => {
            const tempDiv = document.createElement("div");
            tempDiv.innerHTML = html;
            
            // Remove scripts para executar manualmente depois
            const scripts = tempDiv.querySelectorAll("script");
            scripts.forEach(s => s.remove());
            
            contentDiv.innerHTML = tempDiv.innerHTML;
            
            scripts.forEach(oldScript => {
                const newScript = document.createElement("script");
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.textContent = oldScript.textContent;
                document.body.appendChild(newScript);
            });
        })
        .catch(error => console.error("Erro ao carregar logs:", error));
}

// Intercepta o Formulário de Filtro
const formLogs = document.getElementById('formFiltroLogs');
if (formLogs) {
    formLogs.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const params = new URLSearchParams(formData).toString();
        atualizarLogs(params);
    });
}

// Intercepta os links de Paginação
document.querySelectorAll('.log-pagination-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        if (href && href.includes('?')) {
            const params = href.split('?')[1];
            atualizarLogs(params);
        }
    });
});
</script>

