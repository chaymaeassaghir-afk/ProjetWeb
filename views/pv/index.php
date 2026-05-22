<!-- views/pv/liste.php -->

<?php require_once ($_SERVER['DOCUMENT_ROOT'].'/projetweb/views/sidebar.html'); ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap"
        rel="stylesheet">

    <link href="/projetweb/views/style.css" rel="stylesheet">
    <link href="/projetweb/views/salle/styleSalle.css" rel="stylesheet">

    <title>Liste des PV</title>
</head>

<body>

<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="page-title">
            Liste des PV par <span>Encadrants</span>
        </div>

        <button class="btn-import"
                onclick="window.location.href='index.php?controller=pv&page=generer'"
                style="background-color:#98c7d3;color:white;">
            Générer tous les PV
        </button>
    </div>

    <?php
        // Regrouper les étudiants par encadrant
        $groupes = [];

        foreach($pvs as $pv){
            $groupes[$pv['encadrant']][] = $pv;
        }
    ?>

    <div class="row">

        <?php foreach($groupes as $encadrant => $etudiants): ?>

            <div class="col-lg-6 mb-4">

                <div class="card shadow border-0 rounded-4">

                    <div class="card-header text-white"
                         style="background-color:#98c7d3;font-weight:700;font-size:18px;">

                        <i class="bi bi-person-workspace"></i>
                        <?= $encadrant ?>

                    </div>

                    <div class="card-body">

                        <table class="table table-hover align-middle">

                            <thead class="table-light">

                                <tr>
                                    <th>Étudiant</th>
                                    <th>Filière</th>
                                    <th>PDF</th>
                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach($etudiants as $pv): ?>

                                    <tr>

                                        <td>
                                            <?= $pv['nom'] . ' ' . $pv['prenom'] ?>
                                        </td>

                                        <td>
                                            <?= $pv['filiere'] ?>
                                        </td>

                                        <td>

                                            <a href="index.php?controller=pv&page=telecharger&id=<?= $pv['soutenance_id'] ?>"
                                               class="btn btn-sm"
                                               style="background-color:#98c7d3;color:white;border-radius:8px;">

                                                <i class="bi bi-download"></i>

                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>

</body>
</html>