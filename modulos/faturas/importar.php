<div class="container mt-5">
    <h2>Importar Faturas</h2>
    <form id="importForm" class="mt-4" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="csvFile" class="form-label">Escolha o arquivo CSV:</label>
            <input class="form-control" type="file" name="csvFile" id="csvFile" accept=".csv" required>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-auto">
                <label for="taxaDolar" class="form-label">Taxa do Dólar</label>
                <input type="text" class="form-control" style="width: 150px;" name="taxaDolar" id="taxaDolar" placeholder="Ex: 5.87" required>
            </div>
            <div class="col-auto">
                <label for="notaFiscal" class="form-label">Nº da Nota Fiscal</label>
                <input type="text" class="form-control" style="width: 150px;" name="notaFiscal" id="notaFiscal" placeholder="Ex: 12345" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Importar</button>
    </form>
    <div id="result" class="mt-3"></div>
</div>

<script>
    document.getElementById('importForm').addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(this);

        fetch('./modulos/faturas/action/importar_faturas.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('result');
            if (data.status === 'success') {
                resultDiv.innerHTML = '<div class="alert alert-success">Fatura importada com sucesso!</div>';
            } else if (data.error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">Erro ao importar fatura: ${data.error}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('result').innerHTML = `<div class="alert alert-danger">Erro ao importar fatura: ${error.message}</div>`;
        });
    });
</script>

<!-- 
<div class="row g-3 mb-3">
    <div class="col-auto">
        <label for="taxaDolar" class="form-label">Taxa do Dólar</label>
        <input type="text" class="form-control" style="width: 150px;" name="taxaDolar" id="taxaDolar" placeholder="Ex: 5.87" required>
    </div>
    <div class="col-auto">
        <label for="notaFiscal" class="form-label">Nº da Nota Fiscal</label>
        <input type="text" class="form-control" style="width: 150px;" name="notaFiscal" id="notaFiscal" placeholder="Ex: 12345" required>
    </div>
</div>