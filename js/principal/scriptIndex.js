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

            // Remove scripts de submódulos injetados anteriormente
            document.querySelectorAll("script[data-submodulo-script]").forEach(s => s.remove());

            scripts.forEach(oldScript => {
                const newScript = document.createElement("script");
                Array.from(oldScript.attributes).forEach(attr => {
                    let val = attr.value;
                    if (attr.name === "src" && val && !/^(https?:)?\/\//i.test(val) && !/[?&]v=/.test(val)) {
                        const ver = window.APP_VERSION || Date.now();
                        val += (val.includes("?") ? "&" : "?") + "v=" + encodeURIComponent(ver);
                    }
                    newScript.setAttribute(attr.name, val);
                });
                newScript.setAttribute("data-submodulo-script", "1");
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

// RECARREGAR E DESLOGAR COM VERSÃO DISTINTA
// Script de verificação de versão para notificar e deslogar após deploy
(function () {
    const VERSION_URL = 'index.php?check_version=1';
    const CHECK_INTERVAL = 5 * 1000; // 5 segundos
    const COUNTDOWN_SECONDS = 15;

    let currentVersion = window.APP_VERSION ? String(window.APP_VERSION).trim() : null;
    let pollTimer = null;
    let noticeShown = false;

    function forceUpdateLogout() {
        window.location.href = 'logout.php?motivo=atualizacao';
    }

    async function fetchVersion() {
        try {
            const res = await fetch(`${VERSION_URL}&t=${Date.now()}`, {
                cache: 'no-store',
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache'
                }
            });
            if (!res.ok) return null;
            const data = await res.json();
            return data.versao ? String(data.versao).trim() : null;
        } catch (e) {
            console.warn('Falha ao checar versão:', e);
            return null;
        }
    }

    async function checkForUpdate() {
        if (noticeShown) return;

        const latest = await fetchVersion();
        if (!latest) return;

        if (currentVersion === null) {
            currentVersion = latest;
            return;
        }

        if (latest !== String(currentVersion).trim()) {
            noticeShown = true;
            stopPolling();
            showUpdateNotice(latest);
        }
    }

    function showUpdateNotice(newVersion) {
        let remaining = COUNTDOWN_SECONDS;

        const toast = document.createElement('div');
        toast.id = 'update-version-toast';
        toast.style.cssText = `
            position: fixed; bottom: 24px; right: 24px; z-index: 99999;
            background: #212529; color: #fff; padding: 16px 22px;
            border-radius: 10px; box-shadow: 0 6px 20px rgba(0,0,0,.45);
            border-left: 5px solid #0dcaf0;
            font-family: sans-serif; font-size: 14px; cursor: pointer;
            max-width: 360px; line-height: 1.45;
        `;

        const renderContent = () => {
            toast.innerHTML = `
                <div style="font-weight: 600; margin-bottom: 4px; color: #0dcaf0;">
                    Nova versão disponível (v${newVersion})
                </div>
                <div>
                    Você será desconectado em <strong>${remaining}s</strong> para aplicar a atualização.
                </div>
                <div style="margin-top: 8px; font-size: 12px; text-decoration: underline; opacity: 0.9;">
                    Clique aqui para atualizar e fazer login agora
                </div>
            `;
        };

        renderContent();
        toast.onclick = () => forceUpdateLogout();
        document.body.appendChild(toast);

        const countdownTimer = setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(countdownTimer);
                renderContent();
                forceUpdateLogout();
            } else {
                renderContent();
            }
        }, 1000);
    }

    function startPolling() {
        if (noticeShown) return;
        checkForUpdate(); // checa imediatamente ao iniciar/retomar
        if (!pollTimer) {
            pollTimer = setInterval(checkForUpdate, CHECK_INTERVAL);
        }
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

    // Checa imediatamente quando a janela do navegador ganha foco (ex: Alt+Tab)
    window.addEventListener('focus', () => {
        if (!noticeShown) {
            checkForUpdate();
        }
    });

    // Expõe globalmente caso outro ponto do sistema queira acionar a notificação
    window.triggerVersionUpdateNotice = function (newVersion) {
        if (!noticeShown) {
            noticeShown = true;
            stopPolling();
            showUpdateNotice(newVersion || 'nova');
        }
    };

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