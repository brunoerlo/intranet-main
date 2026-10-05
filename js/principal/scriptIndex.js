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

selectModulos.addEventListener("change", function () {
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

window.addEventListener("hashchange", function () {
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

document.addEventListener("DOMContentLoaded", function () {
    const hashAtual = location.hash;
    if (hashAtual) {
        hashesVisitados.push(hashAtual);
    }
    carregarPorHash();
});

// RECARREGAR COM VERSÃO DISTINTA
// Script de verificação de versão para dar reload automático
(function () {
    const VERSION_URL = '/version.json';
    const CHECK_INTERVAL = 5 * 60 * 1000; // 5 minutos

    let currentVersion = null;
    let pollTimer = null;

    async function fetchVersion() {
        try {
            const res = await fetch(VERSION_URL, {
                cache: 'no-store'
            });
            if (!res.ok) return null;
            const data = await res.json();
            return data.versao;
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
        // PODERIA SER UM locaton.reload()
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

// DESLOGAR USUÁRIO POR INATIVIDADE
// Timeout de inatividade (Frontend)
(function () {
    const INACTIVITY_TIMEOUT = 30 * 60 * 1000;
    let timeoutTimer;

    function updateActivity() {
        localStorage.setItem('last_activity', Date.now());
    }

    function checkTimer() {
        clearTimeout(timeoutTimer);

        const lastActivity = parseInt(localStorage.getItem('last_activity') || Date.now());
        const timePassed = Date.now() - lastActivity;
        const timeLeft = INACTIVITY_TIMEOUT - timePassed;

        if (timeLeft <= 0) {
            window.location.href = 'logout.php?motivo=inatividade';
        } else {
            timeoutTimer = setTimeout(checkTimer, timeLeft);
        }
    }

    const events = ['mousemove', 'keydown', 'wheel', 'mousedown', 'touchstart', 'touchmove'];
    events.forEach(event => {
        // Atualiza o tempo no localStorage quando o usuário mexe no mouse
        document.addEventListener(event, () => {
            updateActivity();
            checkTimer();
        }, false);
    });

    // Sincroniza abas (se você mexer em outra aba, não desloga essa)
    window.addEventListener('storage', (e) => {
        if (e.key === 'last_activity') {
            checkTimer();
        }
    });

    updateActivity();
    checkTimer();
})();