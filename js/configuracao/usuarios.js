// CADASTRAR

function inicializarCadastroUsuario() {
    const roleSelect = document.getElementById("role");
    const modulosContainer = document.getElementById("modulosContainer");
    const listaModulos = document.getElementById("listaModulos");
    const mensagem = document.getElementById("mensagem");

    function carregarModulos() {
        const xhr = new XMLHttpRequest();
        xhr.open("GET", "./modulos/configuracao/action/listar_modulos.php", true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const modulos = JSON.parse(xhr.responseText);
                listaModulos.innerHTML = "";

                // Iterando sobre as chaves do objeto
                Object.keys(modulos).forEach(modulo => {
                    const div = document.createElement("div");
                    div.classList.add("form-check");
                    div.innerHTML = `<input class="form-check-input" type="checkbox" name="modulos[]" value="${modulo}"> 
                                 <label class="form-check-label">${modulo}</label>`;
                    listaModulos.appendChild(div);
                });
            }
        };
        xhr.send();
    }

    roleSelect.addEventListener("change", function () {
        if (this.value === "user") {
            modulosContainer.classList.remove("d-none");
            carregarModulos();
        } else {
            modulosContainer.classList.add("d-none");
        }
    });

    document.getElementById("cadastroUsuario").addEventListener("submit", function (event) {
        event.preventDefault();
        const nome = document.getElementById("nome").value.trim();
        const email = document.getElementById("email").value.trim();
        const senha = document.getElementById("senha").value;
        const confirmarSenha = document.getElementById("confirmarSenha").value;
        const role = roleSelect.value;
        const modulos = Array.from(document.querySelectorAll('input[name="modulos[]"]:checked')).map(el => el.value);

        const senhaValida = /^(?=.*\d)(?=.*[\W_]).{8,}$/;
        let valido = true;

        if (!senhaValida.test(senha)) {
            document.getElementById("senha").classList.add("is-invalid");
            valido = false;
        } else {
            document.getElementById("senha").classList.remove("is-invalid");
        }

        if (senha !== confirmarSenha) {
            document.getElementById("confirmarSenha").classList.add("is-invalid");
            valido = false;
        } else {
            document.getElementById("confirmarSenha").classList.remove("is-invalid");
        }

        if (!valido) {
            mensagem.innerHTML = '<div class="alert alert-danger">Corrija os erros antes de enviar.</div>';
            return;
        }

        const formData = new FormData();
        formData.append("nome", nome);
        formData.append("email", email);
        formData.append("senha", senha);
        formData.append("role", role);
        modulos.forEach(modulo => formData.append("modulos[]", modulo));

        const xhr = new XMLHttpRequest();
        xhr.open("POST", "./modulos/configuracao/action/create_user.php", true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                const result = JSON.parse(xhr.responseText);
                if (xhr.status === 200 && result.success) {
                    mensagem.innerHTML = '<div class="alert alert-success">Usuário cadastrado com sucesso!</div>';
                    document.getElementById("cadastroUsuario").reset();
                    modulosContainer.classList.add("d-none");
                } else {
                    mensagem.innerHTML = `<div class="alert alert-danger">${result.message || "Erro ao cadastrar."}</div>`;
                }
            }
        };
        xhr.send(formData);
    });
}

// Certifique-se de chamar essa função depois de inserir o HTML dinamicamente:
inicializarCadastroUsuario();

// LISTAGEM

document.body.addEventListener("submit", function (event) {
    if (event.target.classList.contains("edit-form")) {
        event.preventDefault();

        const form = event.target;
        
        const senhaInput = form.querySelector('[name="senha"]');
        const confirmarSenhaInput = form.querySelector('[name="confirmarSenha"]');
        const senha = senhaInput ? senhaInput.value : "";
        const confirmarSenha = confirmarSenhaInput ? confirmarSenhaInput.value : "";
        
        let valido = true;
        if (senha !== "") {
            const senhaValida = /^(?=.*\d)(?=.*[\W_]).{8,}$/;
            if (!senhaValida.test(senha)) {
                senhaInput.classList.add("is-invalid");
                valido = false;
            } else {
                senhaInput.classList.remove("is-invalid");
            }

            if (senha !== confirmarSenha) {
                confirmarSenhaInput.classList.add("is-invalid");
                valido = false;
            } else {
                confirmarSenhaInput.classList.remove("is-invalid");
            }
        }
        
        if (!valido) {
            return;
        }

        const formData = new FormData(form);
        const data = {};

        formData.forEach((value, key) => {
            if (key === "modulos[]") {
                if (!Array.isArray(data.modulos)) {
                    data.modulos = [];
                }
                data.modulos.push(value);
            } else {
                data[key] = value;
            }
        });

        console.log("Dados a serem enviados:", JSON.stringify(data));

        fetch('./modulos/configuracao/action/update_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    location.reload();
                    const collapseRow = form.closest('.collapse-row');
                    collapseRow.style.display = "none";
                } else {
                    alert("Erro ao atualizar usuário: " + (data.erro || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error("Erro na requisição AJAX:", error);
                alert("Ocorreu um erro ao tentar salvar as alterações.");
            });
    }
});

function carregarModulosExistentes(callback) {
    fetch('./modulos/configuracao/action/listar_modulos.php')
        .then(response => response.json())
        .then(data => callback(Object.keys(data)))
        .catch(error => {
            console.error("Erro ao carregar módulos existentes:", error);
            callback([]);
        });
}


document.body.addEventListener("click", function (event) {
    if (event.target.classList.contains("edit-btn")) {
        const btn = event.target;
        const user = JSON.parse(btn.getAttribute("data-user"));
        const collapseRow = document.getElementById(`collapse-${user.id}`);
        const editForm = collapseRow.querySelector(".edit-form");

        collapseRow.style.display = collapseRow.style.display === "none" ? "" : "none";

        editForm.querySelector(".edit-name").value = user.nome;
        editForm.querySelector(".edit-email").value = user.email;
        editForm.querySelector(".edit-role").value = user.role || "";

        const senhaInput = editForm.querySelector('[name="senha"]');
        if (senhaInput) {
            senhaInput.value = "";
            senhaInput.classList.remove("is-invalid");
        }
        const confirmarSenhaInput = editForm.querySelector('[name="confirmarSenha"]');
        if (confirmarSenhaInput) {
            confirmarSenhaInput.value = "";
            confirmarSenhaInput.classList.remove("is-invalid");
        }

        carregarModulosExistentes(modulosDisponiveis => {
            const modulesContainer = editForm.querySelector(".edit-modules");
            modulesContainer.innerHTML = "";

            modulosDisponiveis.forEach(module => {
                const isChecked = user.modulos && user.modulos.includes(module) ? "checked" : "";
                modulesContainer.innerHTML += `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="modulos[]" value="${module}" id="mod_${module}" ${isChecked}>
                        <label class="form-check-label" for="mod_${module}">${module}</label>
                    </div>
                `;
            });

            const roleSelect = editForm.querySelector(".edit-role");
            const modulesDiv = editForm.querySelector(".modules-container");

            modulesDiv.style.display = roleSelect.value === "user" ? "block" : "none";

            roleSelect.addEventListener("change", () => {
                modulesDiv.style.display = roleSelect.value === "user" ? "block" : "none";
            });
        });
    }
});

//Deletar
document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function () {
        const usuarioId = this.closest('tr').getAttribute('data-id');
        if (confirm("Tem certeza que deseja excluir este Usuario?")) {
            const formData = new FormData();
            formData.append('id', usuarioId);

            fetch('./modulos/configuracao/action/delete_user.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Usuario excluído com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao excluir o Usuario: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro ao tentar excluir o Usuario.');
                });
        }
    });
});
