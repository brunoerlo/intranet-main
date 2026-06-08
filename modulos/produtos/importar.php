<div class="container mt-5">
    <h2>Importar Produtos</h2>
    <form id="importForm" class="mt-4" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="empresa_id" class="form-label">Selecione a Empresa:</label>
            <select class="form-select" name="empresa_id" id="empresa_id" required>
                <option value="">-- Selecione --</option>
                <?php
                $empresasPath = __DIR__ . '/../configuracao/action/empresas.json';
                if (file_exists($empresasPath)) {
                    $empresas = json_decode(file_get_contents($empresasPath), true);
                    foreach ($empresas as $empresa) {
                        echo "<option value=\"{$empresa['id']}\">{$empresa['razaoSocial']}</option>";
                    }
                }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="csvFile" class="form-label">Escolha o arquivo CSV:</label>
            <input class="form-control" type="file" name="csvFile" id="csvFile" accept=".csv" required>
        </div>

        <button type="submit" class="btn btn-primary">Importar</button>
    </form>
    <div id="result" class="mt-3"></div>
</div>

<script>
    document.getElementById('importForm').addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(this);

        fetch('./modulos/produtos/action/importar_produtos.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('result');
            if (data.status === 'success') {
                resultDiv.innerHTML = '<div class="alert alert-success">Produtos importados com sucesso!</div>';
            } else if (data.error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">Erro ao importar Produtos: ${data.error}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('result').innerHTML = `<div class="alert alert-danger">Erro ao importar Produtos: ${error.message}</div>`;
        });
    });
</script>
