<?php
session_start();

// Versão do Sistema
define('APP_VERSION', '2.0.0');

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

// Obtém os módulos permitidos ao usuário
$usuario = $_SESSION["usuario"];
$modulosPermitidos = ($usuario["role"] === "admin") ? "todos" : $usuario["modulos"];
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Intranet | Brazmix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .sidebar {
            width: 250px;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed {
            width: 0;
            overflow: hidden;
        }

        .content {
            flex-grow: 1;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed * {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .submodulo-link {
            text-decoration: none;
            display: block;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            color: white;
            transition: background-color 0.3s, opacity 0.3s;
        }

        .submodulo-link:hover {
            background-color: rgb(59, 59, 59);
            opacity: 0.9;
        }

        .submodulo-link.active {
            background-color: rgb(59, 59, 59);
            opacity: 1;
        }

        body {
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .sidebar {
            width: 250px;
            transition: all 0.3s ease;
            position: fixed;
            /* Fixar o sidebar na tela */
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            /* Permitir rolagem interna caso o conteúdo do menu seja muito grande */
            z-index: 10;
            /* Certificar que o sidebar fica acima do conteúdo principal */
        }

        .content {
            flex-grow: 1;
            padding: 20px;
            margin-left: 250px;
            /* Espaço padrão para o sidebar */
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .content.expanded {
            margin-left: 0;
            padding-left: 100px;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div id="sidebar" class="sidebar bg-dark text-light p-3 d-flex flex-column justify-content-between" style="height: 100vh;">
        <div>
            <h5 class="text-center">Menus</h5>
            <div class="select-container">
                <select id="modulos" class="form-select">
                    <option value="" disabled selected>Escolha um módulo</option>
                    <?php
                    $baseDir = __DIR__ . '/modulos';
                    $submodulosData = [];

                    if (is_dir($baseDir)) {
                        foreach (scandir($baseDir) as $modulo) {
                            if ($modulo !== '.' && $modulo !== '..' && is_dir("$baseDir/$modulo")) {
                                if ($modulosPermitidos === "todos" || in_array($modulo, $modulosPermitidos)) {
                                    echo "<option value='$modulo'>" . ucfirst($modulo) . "</option>";
                                    $submodulosData[$modulo] = [];

                                    foreach (scandir("$baseDir/$modulo") as $submodulo) {
                                        if ($submodulo !== '.' && $submodulo !== '..' && pathinfo($submodulo, PATHINFO_EXTENSION) === 'php') {
                                            $submodulosData[$modulo][] = pathinfo($submodulo, PATHINFO_FILENAME);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    echo "<script>const modulosData = " . json_encode($submodulosData) . ";</script>";
                    ?>
                </select>
                <br />
                <div id="sub-modulos"></div>
            </div>
        </div>

        <div class="mt-auto pt-2 border-top border-secondary">
            <a href="./logout.php" class="text-light d-flex align-items-center p-2 text-decoration-none">
                <i class="fas fa-sign-out-alt me-2"></i> Sair
            </a>
            <div class="text-center pb-1">
                <span class="badge bg-secondary bg-opacity-25 text-light border border-secondary border-opacity-50 font-monospace" style="font-size: 0.75rem; margin-bottom: 3px;">
                    v<?= APP_VERSION ?>
                </span>
                <div style="font-size: 10px; ">
                    &copy; <?php echo date("Y"); ?> <strong>Brazmix</strong> &bull; Todos os direitos reservados.
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <nav class="navbar navbar-expand-lg navbar-dark bg-light">
            <button id="toggleSidebar" class="btn btn-outline-dark">☰</button>
            <img id="image" src="logo-black.png" style="margin-left:20px;width:200px" />
        </nav>
        <div id="loading" class="text-center mt-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>

        <div class="mt-4 flex-grow-1" id="modulo-content">
            <h2>Bem-vindo, <?php echo htmlspecialchars($usuario["nome"]); ?></h2>
            <p>Selecione um módulo e um submódulo para visualizar o conteúdo.</p>
        </div>
    </div>

    <script>
        const selectModulos = document.getElementById("modulos");
        const divSubmodulos = document.getElementById("sub-modulos");
        const contentDiv = document.getElementById("modulo-content");
        const sidebar = document.getElementById("sidebar");
        const toggleSidebar = document.getElementById("toggleSidebar");

        toggleSidebar.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
            document.querySelector('.content').classList.toggle('expanded');
        });

        function carregarPorHash() {
            const hash = location.hash.slice(2);
            if (!hash) return;

            const partes = hash.split('/');
            const modulo = partes[0];
            const submodulo = partes[1] || null;

            if (modulo) {
                selectModulos.value = modulo;
                mostrarSubmodulos(modulo);

                if (submodulo) {
                    carregarSubmodulo(modulo, submodulo);
                }
            }
        }

        function mostrarSubmodulos(modulo) {
            divSubmodulos.innerHTML = '';

            if (modulo && modulosData[modulo]) {
                modulosData[modulo].forEach(sub => {
                    const aLink = document.createElement("a");
                    aLink.textContent = sub.charAt(0).toUpperCase() + sub.slice(1);
                    aLink.href = `/#/${modulo}/${sub}`;
                    aLink.dataset.modulo = modulo;
                    aLink.dataset.submodulo = sub;
                    aLink.classList.add("submodulo-link");

                    if (location.hash === `/#/${modulo}/${sub}`) {
                        aLink.classList.add("active");
                    }

                    divSubmodulos.appendChild(aLink);
                });
            }
        }

        selectModulos.addEventListener("change", function() {
            const modulo = this.value;

            mostrarSubmodulos(modulo);
            /*
            if (modulo && modulosData[modulo].length > 0) {

                // pega o primeiro submódulo
                const primeiroSubmodulo = modulosData[modulo][0];

                // carrega automaticamente
                carregarSubmodulo(modulo, primeiroSubmodulo);
            }
            */
        });


        function carregarSubmodulo(modulo, submodulo) {
            const loading = document.getElementById("loading");

            contentDiv.style.display = "none";
            loading.style.display = "block";

            fetch(`carregar_modulo.php?modulo=${modulo}&submodulo=${submodulo}`)
                .then(response => {
                    if (!response.ok) throw new Error(`Erro HTTP: ${response.status}`);
                    return response.text();
                })
                .then(html => {
                    // Cria um contêiner temporário para manipular o HTML
                    const tempDiv = document.createElement("div");
                    tempDiv.innerHTML = html;

                    // Extrai e executa scripts
                    const scripts = tempDiv.querySelectorAll("script");
                    scripts.forEach(script => script.remove()); // Remove antes de setar HTML

                    contentDiv.innerHTML = tempDiv.innerHTML;

                    scripts.forEach(oldScript => {
                        const newScript = document.createElement("script");
                        Array.from(oldScript.attributes).forEach(attr =>
                            newScript.setAttribute(attr.name, attr.value)
                        );
                        newScript.textContent = oldScript.textContent;
                        document.body.appendChild(newScript); // Executa fora do contentDiv
                    });

                    // Atualiza hash e submódulos
                    location.hash = `/${modulo}/${submodulo}`;
                    mostrarSubmodulos(modulo);
                })
                .catch(error => {
                    contentDiv.innerHTML = `<div class="alert alert-danger">Erro ao carregar o submódulo: ${error}</div>`;
                })
                .finally(() => {
                    loading.style.display = "none";
                    contentDiv.style.display = "block";
                });
        }

        let hashesVisitados = [];

        window.addEventListener("hashchange", function() {
            const currentHash = location.hash;

            if (hashesVisitados.includes(currentHash)) {
                // Se o hash já foi acessado antes, força um reload
                location.reload();
            } else {
                // Caso contrário, armazena e carrega normalmente
                hashesVisitados.push(currentHash);
                carregarPorHash(); // sua função já existente
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            const hashAtual = location.hash;
            if (hashAtual) {
                hashesVisitados.push(hashAtual);
            }
            carregarPorHash();
        });
    </script>

    <script> // Script de verificação de versão para dar reload automático
        (function() {
            const VERSION_URL = '/version.json';
            const CHECK_INTERVAL = 5 * 60 * 1000; // 5 minutos, ajusta como quiser

            let currentVersion = null;
            let pollTimer = null;

            async function fetchVersion() {
                try {
                    const res = await fetch(VERSION_URL, {
                        cache: 'no-store'
                    });
                    if (!res.ok) return null;
                    const data = await res.json();
                    return data.versao; // ← trocado de data.v pra data.versao
                } catch (e) {
                    console.warn('Falha ao checar versão:', e);
                    return null;
                }
            }

            async function checkForUpdate() {
                const latest = await fetchVersion();
                if (!latest) return;

                if (currentVersion === null) {
                    // primeira checagem, só define a baseline
                    currentVersion = latest;
                    return;
                }

                if (latest !== currentVersion) {
                    showUpdateNotice();
                    stopPolling(); // não precisa continuar checando depois de detectar
                }
            }

            function showUpdateNotice() {
                // opção simples: reload direto
                // location.reload();

                // opção mais educada: avisa o usuário e deixa ele decidir
                const toast = document.createElement('div');
                toast.style.cssText = `
      position: fixed; bottom: 20px; right: 20px; z-index: 9999;
      background: #212529; color: #fff; padding: 12px 20px;
      border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,.3);
      font-family: sans-serif; font-size: 14px; cursor: pointer;
    `;
                toast.innerHTML = '🔄 Nova versão disponível. Clique para atualizar.';
                toast.onclick = () => location.reload();
                document.body.appendChild(toast);
            }

            function startPolling() {
                if (pollTimer) return;
                checkForUpdate(); // checa imediatamente ao iniciar/retomar
                pollTimer = setInterval(checkForUpdate, CHECK_INTERVAL);
            }

            function stopPolling() {
                if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
            }

            // pausa quando a aba fica em background, retoma quando volta
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {  
                    stopPolling();
                } else {
                    startPolling();
                }
            });

            // inicia ao carregar a página
            startPolling();
        })();
    </script>

    <!-- Bootstrap 5 Bundle JS (inclui Popper para Collapse, Modais, Dropdowns, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Coloque antes de qualquer script que usa jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>

</html>