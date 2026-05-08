<?php
require_once ($_SERVER['DOCUMENT_ROOT'].'/projetPFE/views/sidebar.html');
require '../config/database.php';

// Récupération des professeurs
$sql = "SELECT * FROM prof ORDER BY nom ASC";

$stmt = $pdo->query($sql);

$profs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="/projetPFE/views/css/bootstrap.min.css" rel="stylesheet">
    <link href="/projetPFE/views/css/style.css" rel="stylesheet">

    <script src="/projetPFE/views/js/bootstrap.bundle.min.js"></script>

    <title>Liste des Professeurs</title>
</head>

<body class="bg-light">

    <div class="main-content">

        <h2 class="form-title">
            Liste Des Professeurs
        </h2>

        <?php if (empty($profs)): ?>

            <div class="alert alert-warning">
                Aucun professeur trouvé.
            </div>

        <?php else: ?>

            <table class="table table-striped table-bordered">

                <thead class="table-dark">

                    <tr>
                        <th>id</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Spécialité</th>
                        <th>action</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($profs as $prof): ?>

                        <tr>

                            <td>
                                <?= $prof['id'] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($prof['nom']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($prof['prenom']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($prof['specialite']) ?>
                            </td>

                            <td class="text-center">

                                <a href="modifier_prof.php?id=<?= $prof['id']; ?>"
                                   class="btn btn-warning btn-sm">

                                    <i class="bi bi-pencil"></i>

                                </a>

                                <a href="supprimer_prof.php?id=<?= $prof['id']; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Voulez-vous vraiment supprimer ce professeur ?');">

                                    <i class="bi bi-trash"></i>

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>

</body>
</html>
