<div class="container mt-5">
    <h2>Importar Clientes</h2>
    <form id="importForm" class="mt-4" enctype="multipart/form-data">
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

        fetch('./modulos/clientes/action/importar_clientes.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('result');
            if (data.status === 'success') {
                resultDiv.innerHTML = '<div class="alert alert-success">Clientes importados com sucesso!</div>';
            } else if (data.error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">Erro ao importar clientes: ${data.error}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('result').innerHTML = `<div class="alert alert-danger">Erro ao importar clientes: ${error.message}</div>`;
        });
    });
</script>
