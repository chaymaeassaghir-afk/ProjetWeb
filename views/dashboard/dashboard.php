<?php require_once ($_SERVER['DOCUMENT_ROOT'] . '/projetweb/views/sidebar.html');?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

    <link href="/projetweb/views/dashboard/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="page-title">
            Dashboard <span>Gestion PFE</span>
        </div>

        <div style="font-size:.85rem;color:var(--muted)">
            <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y') ?>
        </div>
    </div>

    <!-- CARD -->
    <div class="row">
        <div class="col-6">
            <div class="card-body-custom" style="background-color:¨#f0fdf4">
                <div class="card-head">
                    <div class="card-head-title">
                        <i class="bi bi-speedometer2"></i>
                        Tableau de bord
                    </div>
                </div>

                <!-- ALERT IMPORTANT -->
                <div class="alert alert-warning mt-3 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Veuillez importer tous les étudiants et les professeurs avant de lancer l’affectation.
                </div>

                <!-- ACTION BUTTON -->
                <div class="mt-3 d-flex justify-content-end">

                    <a href="index.php?controller=etudiant&page=affecter_pdf"
                            class="btn btn-primary d-flex align-items-center gap-2"
                              style="border-radius:10px;padding:10px 16px;font-weight:600;">

                             <i class="bi bi-magic"></i> Affecter les encadrants</a>
                </div>

                <!-- INFO -->
                <div class="mt-4" style="color:var(--muted)">
                    Cliquez sur le bouton pour générer automatiquement l’affectation des professeurs aux étudiants.
                </div>

            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center p-3">
                <p class="text-muted">Total profs jury</p>
                <h3><?= count($statistiques) ?></h3>
            </div>
        </div>
    </div>

    <!-- Graphique -->
    <div style="position: relative; width: 100%; height: 400px;">
        <canvas id="juryChart" role="img" 
                aria-label="Nombre de soutenances par professeur jury">
        </canvas>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
    const noms = <?= $noms_json ?>;
    const totaux = <?= $totaux_json ?>;

    new Chart(document.getElementById('juryChart'), {
        type: 'bar',
        data: {
            labels: noms,
            datasets: [{
                label: 'Nombre de soutenances comme jury',
                data: totaux,
                backgroundColor: '#378ADD',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
</script>


</body>
</html>
