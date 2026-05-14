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

<div class="main" style="background-color:var(--surface)">

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
            <div class="card-body-custom" style="background-color:#92e4e9">
                <div class="card-head">
                    <div class="card-head-title">
                        <i class="bi bi-speedometer2"></i>
                        Affectation des encadrants
                    </div>
                </div>

                <!-- ALERT IMPORTANT -->
                <div class="alert alert-warning mt-3 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Veuillez importer tous les étudiants et les professeurs avant de lancer l’affectation.
                </div>

                <!-- ACTION BUTTON -->
                <div class="mt-3 d-flex justify-content-end">
                    <a href="index.php?controller=soutenance&page=afficherFormulairePlanification"
                            class="btn btn-primary "
                              style="border-radius:10px;padding:10px 16px;font-weight:600;background-color: #f4e28b;color:gray;">

                             <i class="bi bi-magic"></i> Generer le planning
                    </a>
                </div>

                

            </div>
            
        </div>
        <div class="col-6">
            <div class="card-body-custom" style="background-color:#92e4e9">
                <div class="card-head">
                    <div class="card-head-title">
                        <i class="bi bi-speedometer2"></i>
                        Recuperation des documents
                    </div>
                </div>

                
                

                <!-- ACTION BUTTON -->
                <div class="mt-3 d-flex justify-content-end">

                    <a href="index.php?controller=genererPDF"
                            class="btn btn-primary "
                            style="border-radius:10px;padding:10px 16px;font-weight:600;background-color: #f4e28b;color:gray;">


                             <i class="bi bi-magic"></i> Télécharger le planning
                    </a>
                </div>
                <div class="mt-3 d-flex justify-content-end">
                    <a href="index.php?controller=etudiant&page=affecter_pdf"
                            class="btn btn-primary "
                              style="border-radius:10px;padding:10px 16px;font-weight:600;background-color: #f4e28b;color:gray;">

                             <i class="bi bi-magic"></i> Télécharger le PDF des encadrants
                    </a>
                </div>

                <div>
                    <br></br>
                </div>
                
            </div>
        </div>
    </div>
    <!-- statistiques -->
    <h4 class="page-title">Soutenances par prof jury</h4>
    <div style="position: relative; width: 100%; height: 350px;">
        <canvas id="juryChart" role="img" aria-label="Soutenances par prof jury"></canvas>
    </div>

    <h4 class="page-title">Étudiants encadrés par prof</h4>
    <div style="position: relative; width: 100%; height: 350px;">
        <canvas id="encadrantChart" role="img" aria-label="Étudiants encadrés par prof"></canvas>
    </div>

    <h4 class="page-title">Soutenances par filière</h4>
    <div style="position: relative; width: 100%; height: 350px;">
        <canvas id="filiereChart" role="img" aria-label="Soutenances par filière"></canvas>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
// ── Graphique 1 — Nombre de soutenances par prof jury ──
const noms = <?= $noms_json ?>;
const totaux = <?= $totaux_json ?>;
new Chart(document.getElementById('juryChart'), {
    type: 'bar',
    data: {
        labels: noms,
        datasets: [{
            label: 'Soutenances comme jury',
            data: totaux,
            backgroundColor: '#eeb18c',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { autoSkip: false, maxRotation: 30, font: { size: 11 } }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// ── Graphique 2 — Nombre d'étudiants encadrés par prof ──
const noms_enc = <?= $noms_enc_json ?>;
const totaux_enc = <?= $totaux_enc_json ?>;
new Chart(document.getElementById('encadrantChart'), {
    type: 'bar',
    data: {
        labels: noms_enc,
        datasets: [{
            label: "Étudiants encadrés",
            data: totaux_enc,
            backgroundColor: '#92e4e9',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { autoSkip: false, maxRotation: 30, font: { size: 11 } }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// ── Graphique 3 — Soutenances par filière (camembert) ──
const filieres = <?= $filieres_json ?>;
const totaux_fil = <?= $totaux_fil_json ?>;
new Chart(document.getElementById('filiereChart'), {
    type: 'pie',
    data: {
        labels: filieres,
        datasets: [{
            data: totaux_fil,
            backgroundColor: ['#92e4e9', '#eeb18c', '#D85A30'],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

</body>
</html>