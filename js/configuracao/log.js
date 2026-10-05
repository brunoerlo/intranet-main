(function () {
    // Função de carregamento — guardada em window para ser sobrescrita a cada reload
    // sem acumular múltiplas definições conflitantes
    window.atualizarLogs = function (queryString) {
        const contentDiv = document.getElementById("modulo-content");
        if (!contentDiv) return;

        const url = `carregar_modulo.php?modulo=configuracao&submodulo=log&${queryString}`;

        fetch(url)
            .then(response => response.text())
            .then(html => {
                const tempDiv = document.createElement("div");
                tempDiv.innerHTML = html;

                const scripts = [...tempDiv.querySelectorAll("script")];
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
    };

    // Formulário de filtro — sempre resetando p=1 ao filtrar
    const _formLogs = document.getElementById('formFiltroLogs');
    if (_formLogs) {
        _formLogs.addEventListener('submit', function (e) {
            e.preventDefault();
            const params = new URLSearchParams(new FormData(this));
            params.set('p', '1'); // volta pra página 1 ao aplicar filtro
            window.atualizarLogs(params.toString());
        });
    }

    const _btnLimparLogs = document.getElementById('btnLimparLogs');
    if (_btnLimparLogs) {
        _btnLimparLogs.addEventListener('click', function () {
            if (_formLogs) _formLogs.reset();
            window.atualizarLogs('p=1');
        });
    }

    // Event delegation no document — remove o handler anterior antes de adicionar
    // para não acumular listeners a cada reload do módulo via AJAX
    if (window._logPaginacaoHandler) {
        document.removeEventListener('click', window._logPaginacaoHandler);
    }
    window._logPaginacaoHandler = function (e) {
        const link = e.target.closest('.link-paginacao');
        if (!link) return;
        e.preventDefault();
        const href = link.getAttribute('href');
        if (href && href.includes('?')) {
            window.atualizarLogs(href.split('?')[1]);
        }
    };
    document.addEventListener('click', window._logPaginacaoHandler);
})();
