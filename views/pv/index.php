<?php 
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PV par encadrant - GestPFE</title>
    <link href="/projetweb/views/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
</head>

<body class="bg-light">

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/projetweb/views/sidebar.html'; ?>

<div class="main-content" style="margin-left:250px; padding:2rem;">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 style="font-family:'Syne',sans-serif; font-weight:700;">
                <i class="bi bi-diagram-3-fill me-2" style="color:#375e69;"></i>
                Procès-verbaux par encadrant
            </h2>
            <p class="text-muted mb-0">Liste des étudiants groupée par encadrant</p>
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                • PDF : tous les PVs fusionnés en un seul document
            </small>
        </div>

        <!-- Télécharger tous les PVs -->
        <div class="btn-group">
            <a href="/projetweb/index.php?controller=pv&page=telecharger&scope=tous&format=pdf"
               class="btn btn-outline-dark"
               title="Télécharger tous les PVs (PDF fusionné)">
                <i class="bi bi-file-earmark-pdf me-2"></i>PDF fusionné
            </a>
        </div>
    </div>

    <?php if (empty($encadrants)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Aucun encadrant ou étudiant trouvé.
        </div>

    <?php else: ?>

        <?php foreach ($encadrants as $enc): ?>

            <div class="card shadow-sm mb-4">

                <!-- EN-TÊTE ENCADRANT -->
                <div class="card-header d-flex justify-content-between align-items-center"
                     style="background-color:#23242c; color:#fff; border-radius:8px 8px 0 0;">

                    <div>
                        <i class="bi bi-person-badge-fill me-2"></i>
                        <strong style="font-family:'Syne',sans-serif; font-size:1.05rem;">
                            <?= htmlspecialchars($enc['nom'] . ' ' . $enc['prenom']) ?>
                        </strong>
                        <span class="badge ms-2" style="background-color:#375e69;">
                            <?= count($enc['etudiants']) ?> étudiant(s)
                        </span>
                    </div>

                    <!-- Télécharger PVs de cet encadrant -->
                    <div class="btn-group btn-group-sm">
                        <a href="/projetweb/index.php?controller=pv&page=telecharger&scope=encadrant&encadrant_id=<?= $enc['id'] ?>&format=pdf"
                           class="btn btn-outline-light"
                           title="Télécharger tous les PVs de cet encadrant (PDF fusionné)">
                            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                        </a>
                    </div>
                </div>

                <!-- TABLEAU ÉTUDIANTS -->
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background-color:#f0f4f5;">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Étudiant</th>
                                
                                <th>Filière</th>
                                <th>Titre PFE</th>
                                
                                <th>Horaire</th>
                                <th>PV</th>
                                <th class="text-center">pages</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($enc['etudiants'] as $i => $etd): ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $i + 1 ?></td>

                                <td>
                                    <strong><?= htmlspecialchars($etd['etudiant_nom'] . ' ' . $etd['etudiant_prenom']) ?></strong>
                                </td>

                                

                                <td>
                                    <span class="badge" style="background-color:#375e69;">
                                        <?= htmlspecialchars($etd['filiere'] ?? '-') ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($etd['titre_pfe'])): ?>
                                        <span title="<?= htmlspecialchars($etd['titre_pfe']) ?>">
                                            <?= htmlspecialchars(mb_strimwidth($etd['titre_pfe'], 0, 40, '…')) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Non renseigné</span>
                                    <?php endif; ?>
                                </td>


                                <td>
                                    <?php if (!empty($etd['heure_debut']) && !empty($etd['heure_fin'])): ?>
                                        <small>
                                            <?= substr($etd['heure_debut'], 0, 5) ?>
                                            → <?= substr($etd['heure_fin'], 0, 5) ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Badge statut PV -->
                                <td>
                                    <?php if ($etd['pv_existe']): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Généré
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-clock me-1"></i>En attente
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- ✅ pageS CORRIGÉES -->
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">

                                        <!-- Générer / Régénérer le PV -->
                                        <?php if (!empty($etd['date'])): ?>
                                            <a href="/projetweb/index.php?controller=pv&page=generer&etudiant_id=<?= $etd['etudiant_id'] ?>"
                                               class="btn btn-sm btn-outline-<?= $etd['pv_existe'] ? 'warning' : 'primary' ?>"
                                               title="<?= $etd['pv_existe'] ? 'Régénérer' : 'Générer' ?> le PV"
                                               onclick="return confirm('<?= $etd['pv_existe'] ? 'Régénérer' : 'Générer' ?> le PV pour <?= htmlspecialchars($etd['etudiant_nom'] . ' ' . $etd['etudiant_prenom']) ?> ?')">
                                                <i class="bi bi-<?= $etd['pv_existe'] ? 'arrow-clockwise' : 'file-earmark-plus' ?>"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary"
                                                    disabled
                                                    title="Soutenance non planifiée">
                                                <i class="bi bi-file-earmark-plus"></i>
                                            </button>
                                        <?php endif; ?>

                                        <!-- ✅ Télécharger — toujours visible sans condition pv_existe -->
                                        <a href="/projetweb/index.php?controller=pv&page=telecharger&scope=etudiant&etudiant_id=<?= $etd['etudiant_id'] ?>"
                                           class="btn btn-sm btn-outline-success"
                                           title="Télécharger le PV">
                                            <i class="bi bi-download"></i>
                                        </a>

                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

</body>
</html>
