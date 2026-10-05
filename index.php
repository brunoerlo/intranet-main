<?php
session_start();

// Versão do Sistema
define('APP_VERSION', '2.4.0');

// Tempo limite de inatividade em segundos (ex: 1800 para 30 minutos)
$timeout_duration = 1800;

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: login.php?msg=sessao_expirada");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

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

    <script src="js/principal/scriptIndex.js"></script>
    <!-- Bootstrap 5 Bundle JS (inclui Popper para Collapse, Modais, Dropdowns, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Coloque antes de qualquer script que usa jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>

</html>