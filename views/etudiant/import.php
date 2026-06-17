<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/projetweb/views/sidebar.html'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/projetweb/views/css/bootstrap.min.css" rel="stylesheet"></link>
    <link href="/projetweb/views/style.css" rel="stylesheet"></link>
    <title>Import</title>
</head>
<body class="bg-light">
    <div class="main-content">
        <div class="col-md-10 ">
            <div class="form-card">
                <form action="/projetweb/index.php?controller=import" method="POST" enctype="multipart/form-data">
                    <h2 class="form-title">Importation d'un fichier Excel</h2>
                  
                    <div class="mb-3">
                        <label for="fichier" class="form-label">Importer un Fichier Excel :</label>
                        <input type="file" name="fichier" id="fichier" class="form-control" accept=".xls,.xlsx" required />
                    </div>
                    <div class="text-center">
                        <button type="submit" name="submit"class="btn btn-import text-white">Importer</button>
                    </div>
                </form>

            </div>

        </div>

    </div>
</body>
</html>