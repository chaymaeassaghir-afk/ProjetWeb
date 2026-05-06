<?php
require_once '../../config/database.php';
require_once '../../models/salle.php';
require_once '../sidebar.html';


$salleObj = new salle($pdo);
$salle_edit   = null;
$message      = '';
$type_message = '';

if(isset($_GET['supprimer'])){
    $s = $salleObj->trouversalleParId((int)$_GET['supprimer']);
    if ($s) {
        try {
            $s->supprimersalle();
            $message      = "Salle supprimée avec succès.";
            $type_message = "success";
        } catch (RuntimeException $e) {
            $message      = $e->getMessage();
            $type_message = "danger";
        }
    }
}
$motCle = trim($_GET['q'] ?? '');
if ($motCle !== '') {
    $salles = $salleObj->rechercher($motCle);
} else {
    $salles = $salleObj->listersalles();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>liste_salle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="/projetweb/views/salle/styleSalle.css" rel="stylesheet">
</head>
<body>
    <div class="main">
        <div class="topbar">
            <div class="page-title">Gestion des <span> Salles </span></div>
            <div style="font-size:.85rem;color:var(--muted)">
                <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y') ?>
            </div>
        </div>
        <div class="card-body-custom" style="padding-bottom:8px">

            <!-- Recherche -->
            <form method="GET" action="">
                <div class="search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text"
                        name="q"
                        class="search-input"
                        placeholder="Rechercher par numéro ou bâtiment..."
                        value="<?= htmlspecialchars($motCle) ?>"
                        oninput="this.form.submit()">
                </div>
            </form>

            <!-- Tableau -->
            <?php if (empty($salles)): ?>
                <div class="empty-state">
                    <i class="bi bi-building-slash"></i>
                    <p><?= $motCle ? "Aucun résultat pour \"$motCle\"" : "Aucune salle enregistrée." ?></p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Numero</th>
                                <th>Bâtiment</th>
                                <th>ID Salle</th>
                                <th style="text-align:center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salles as $i => $s): ?>
                                <tr>
                                    <td style="color:var(--muted);font-size:.82rem"><?= $i + 1 ?></td>
                                    <td>
                                        <span class="badge-salle">
                                            <i class="bi bi-door-open"></i>
                                            <?= htmlspecialchars($s->getNumero_salle()) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($s->getBatiment()): ?>
                                            <span class="badge-batiment">
                                            <i class="bi bi-building"></i>
                                            <?= htmlspecialchars($s->getBatiment()) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:var(--muted)">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:var(--muted);font-size:.85rem"><?= $s->getId_salle() ?></td>
                                    <td style="text-align:center">
                                        
                                        <a href="?supprimer=<?= $s->getId_salle() ?>"
                                            class="btn-icon del ms-1" title="Supprimer"
                                            onclick="return confirm('Supprimer cette salle ?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>   
                    
     
    
</body>
</html>
