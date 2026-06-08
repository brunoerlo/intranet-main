<?php
$empresasPath = __DIR__ . '/../configuracao/action/empresas.json';
$empresasMoeda = [];
$empresasRazao = [];

if (file_exists($empresasPath)) {
    $empresas = json_decode(file_get_contents($empresasPath), true);
    foreach ($empresas as $empresa) {
        $empresasRazao[$empresa['id']] = $empresa['razaoSocial'];
        $empresasMoeda[$empresa['id']] = $empresa['moeda'] ?? 'R$'; // padrão R$ se não tiver definido
    }
}

?>
<style>
    .form-control, .form-select, textarea {
        border: 1px solid #ced4da !important;
        box-shadow: none !important;
    }

    .form-control:focus, .form-select:focus, textarea:focus {
        border-color: #80bdff !important;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
</style>

<div class="container mt-5">
    <h2>Cadastrar Serviço</h2>
    <form id="formServico">
        <div class="mb-3">
            <label for="empresa_id" class="form-label">Empresa</label>
            <select class="form-select" id="empresa_id" name="empresa_id" required>
                <option value="">Selecione a empresa</option>
                <?php
                $empresasPath = __DIR__ . '/../configuracao/action/empresas.json'; // ajuste depois se necessário
                if (file_exists($empresasPath)) {
                    $empresas = json_decode(file_get_contents($empresasPath), true);
                    foreach ($empresas as $empresa) {
                        echo '<option value="' . $empresa['id'] . '">' . htmlspecialchars($empresa['razaoSocial']) . '</option>';
                    }
                }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="codigo" class="form-label">Código (ERP - BM xxx.xxx)</label>
            <input type="text" class="form-control" id="codigo" name="codigo" required>
        </div>

        <div class="mb-3">
            <label for="nome" class="form-label">Descrição (Nome do Serviço)</label>
            <input type="text" class="form-control" id="nome" name="nome" required>
        </div>

        <div class="mb-3">
            <label for="descricao_en" class="form-label">Descrição em Inglês</label>
            <textarea class="form-control" id="descricao_en" name="descricao_en" rows="2"></textarea>
        </div>

        <div class="mb-3">
            <label for="descricao_es" class="form-label">Descrição em Espanhol</label>
            <textarea class="form-control" id="descricao_es" name="descricao_es" rows="2"></textarea>
        </div>

        <div class="mb-3">
            <label for="unidade" class="form-label">Unidade de Comercialização</label>
            <input type="text" class="form-control" id="unidade" name="unidade" required>
        </div>

        <div class="mb-3">
            <label for="descricao" class="form-label">Descrição Geral</label>
            <textarea class="form-control" id="descricao" name="descricao" rows="3" required></textarea>
        </div>

        <div class="mb-3">
            <label for="preco" class="form-label">Preço de Venda</label>
            <input type="number" step="0.01" class="form-control" id="preco" name="preco" required>
        </div>

        <button type="submit" class="btn btn-primary">Salvar</button>
        <div id="resposta" class="mt-3"></div>
    </form>
</div>

<!-- Listagem dos serviços -->
<div class="container mt-5">
    <h3>Serviços Cadastrados</h3>
    <?php
    $arquivo = __DIR__ . '/action/servicos.json';
    if (file_exists($arquivo)) {
        $servicos = json_decode(file_get_contents($arquivo), true);
        if (!empty($servicos)) {
            echo '<table class="table table-striped mt-3">';
            echo '<thead><tr><th>Empresa ID</th><th>Nome</th><th>Descrição</th><th>Preço</th><th>Ações</th></tr></thead><tbody>';
            foreach ($servicos as $servico) {
                $moeda = $empresasMoeda[$servico['empresa_id']] ?? 'R$';
                $razao = $empresasRazao[$servico['empresa_id']] ?? 'Empresa não encontrada';
                echo '<tr>';
                echo '<td>' . htmlspecialchars($razao) . '</td>';
                echo '<td>' . htmlspecialchars($servico['nome']) . '</td>';
                echo '<td>' . htmlspecialchars($servico['descricao']) . '</td>';
                echo '<td>' . $moeda . ' ' . number_format($servico['preco'], 2, ',', '.') . '</td>';
                echo '<td><button class="btn btn-danger btn-sm" onclick="excluirServico(\'' . $servico['id'] . '\')">Excluir</button></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p class="text-muted mt-3">Nenhum serviço cadastrado ainda.</p>';
        }
    } else {
        echo '<p class="text-muted mt-3">Arquivo de serviços não encontrado.</p>';
    }
    ?>
</div>

<script>
document.getElementById("formServico").addEventListener("submit", function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('./modulos/servicos/action/salvar_servico.php', {
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
</script>

<script>
function excluirServico(id) {
    if (!confirm("Tem certeza que deseja excluir este serviço?")) return;

    fetch('./modulos/servicos/action/excluir_servico.php', {
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
</script>
