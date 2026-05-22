<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Affectation Salles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<h3>Test — Affectation des salles</h3>
<hr>

<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/soutenance.php';
require_once __DIR__ . '/../models/salle.php';
require_once __DIR__ . '/../models/configuration.php';
require_once __DIR__ . '/../controllers/ControllerSoutenance.php';

// ── afficher toutes les soutenances ──
$resultat=null;
$stmt = $pdo->query("SELECT * FROM soutenance ORDER BY date, heure_debut");
$soutenances = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(isset($_GET['affecter'])) {
    $ctrl = new SoutenanceController($pdo);
    $resultat=$ctrl->affecterSalles();
}
$stmt        = $pdo->query("SELECT * FROM soutenance ORDER BY date, heure_debut");
$soutenances = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Bouton pour déclencher l'affectation -->
<a href="test.php?affecter=1" class="btn btn-primary mb-3">
    Affecter les salles automatiquement
</a>

<!-- Tableau des soutenances -->
<table class="table table-bordered table-hover">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Titre PFE</th>
            <th>Date</th>
            <th>Heure début</th>
            <th>Heure fin</th>
            <th>Salle affectée</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($soutenances as $s): ?>
        <tr>
            <td><?= $s['id_stnc'] ?></td>
            <td><?= htmlspecialchars($s['titre_pfe']) ?></td>
            <td><?= $s['date'] ?></td>
            <td><?= $s['heure_debut'] ?></td>
            <td><?= $s['heure_fin'] ?></td>
            <td>
                <?php if($s['id_salle']): ?>
                    <span class="badge bg-success">Salle <?= $s['id_salle'] ?></span>
                <?php else: ?>
                    <span class="badge bg-danger">Non affectée</span>
                <?php endif; ?>
            </td>
            <td><?= $s['statut'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if($resultat): ?>
    <div class="alert alert-<?= $resultat['affectees'] > 0 ? 'success' : 'warning' ?>">
        ✅ <?= $resultat['affectees'] ?> soutenance(s) affectée(s)
        <?php if(!empty($resultat['conflits'])): ?>
            — ⚠️ <?= count($resultat['conflits']) ?> conflit(s) détecté(s)
        <?php endif; ?>
    </div>
<?php endif; ?>

</body>
</html>