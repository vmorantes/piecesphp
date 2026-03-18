<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Carga de Archivos Complejos</title>
    <base href="<?= baseurl(); ?>">
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f4f7f6;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
    }

    .container {
        background-color: #fff;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 600px;
    }

    h2 {
        color: #333;
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .form-group {
        margin-bottom: 1.2rem;
    }

    label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: bold;
        color: #555;
    }

    input[type="file"] {
        display: block;
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: #fafafa;
    }

    input[type="text"] {
        display: block;
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: #fafafa;
    }

    .btn {
        display: block;
        width: 100%;
        padding: 0.75rem;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        cursor: pointer;
        transition: background-color 0.2s;
        margin-top: 1.5rem;
    }

    .btn:hover {
        background-color: #0056b3;
    }

    .helper-text {
        font-size: 0.85rem;
        color: #888;
        margin-top: 0.25rem;
    }
    </style>
</head>

<body>

    <div class="container">
        <h2>Formulario de Prueba (Nombres Complejos)</h2>
        <form action="./pcsphp-testing/queue-request/handle" method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label for="name">Nombre</label>
                <input type="text" id="name" name="name">
                <div class="helper-text">name="name"</div>
            </div>

            <div class="form-group">
                <label for="file1">1. Archivo Simple</label>
                <input type="file" id="file1" name="single_file">
                <div class="helper-text">name="single_file"</div>
            </div>

            <div class="form-group">
                <label for="file2">2. Colección de Archivos (Múltiple)</label>
                <input type="file" id="file2" name="collection[]" multiple>
                <div class="helper-text">name="collection[]" (permite seleccionar varios)</div>
            </div>

            <div class="form-group">
                <label for="file3">3. Colección de Archivos (Múltiple)</label>
                <input type="file" id="file3" name="collection[]" multiple>
                <div class="helper-text">name="collection[]" (permite seleccionar varios)</div>
            </div>

            <div class="form-group">
                <label for="file4">4. Estructura Anidada</label>
                <input type="file" id="file4" name="nested[structure][attachment]">
                <div class="helper-text">name="nested[structure][attachment]"</div>
            </div>

            <div class="form-group">
                <label for="file5">5. Estructura Profunda con Índices</label>
                <input type="file" id="file5" name="portfolio[work][2024][images][0][file]">
                <div class="helper-text">name="portfolio[work][2024][images][0][file]"</div>
            </div>

            <div class="form-group">
                <label for="file6">6. Estructura Profunda con Índices</label>
                <input type="file" id="file6" name="portfolio[work][2024][images][1][file]">
                <div class="helper-text">name="portfolio[work][2024][images][1][file]"</div>
            </div>

            <div class="form-group">
                <label for="file7">7. Colección de Archivos (Múltiple)</label>
                <input type="file" id="file7" name="collection2[]" multiple>
                <div class="helper-text">name="collection2[]" (permite seleccionar varios)</div>
            </div>

            <button type="submit" class="btn">Enviar Formulario</button>
        </form>
    </div>

    <script>
    window.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action);
            xhr.send(formData);
            xhr.onload = function() {
                console.log(xhr.responseText);
            };
        });
    });
    </script>

</body>

</html>
