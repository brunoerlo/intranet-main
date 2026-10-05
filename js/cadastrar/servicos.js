document.getElementById("formServico").addEventListener("submit", function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('./modulos/cadastrar/action/servicos/salvar_servico.php', {
        method: 'POST',
        body: formData
    })
    .then(resp => resp.json())
    .then(data => {
        const resposta = document.getElementById("resposta");
        if (data.success) {
            resposta.innerHTML = '<div class="alert alert-success">Serviço salvo com sucesso!</div>';
            document.getElementById("formServico").reset();
            setTimeout(() => location.reload(), 500); // recarrega após pequena pausa
        } else {
            resposta.innerHTML = '<div class="alert alert-danger">Erro ao salvar: ' + data.message + '</div>';
        }
    })
    .catch(error => {
        document.getElementById("resposta").innerHTML = '<div class="alert alert-danger">Erro: ' + error.message + '</div>';
    });
});

function excluirServico(id) {
    if (!confirm("Tem certeza que deseja excluir este serviço?")) return;

    fetch('./modulos/cadastrar/action/servicos/excluir_servico.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
    .then(resp => resp.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert("Erro ao excluir: " + data.message);
        }
    })
    .catch(error => {
        alert("Erro: " + error.message);
    });
}