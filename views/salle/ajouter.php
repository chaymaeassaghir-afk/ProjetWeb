<?php
require_once '../../config/database.php';
require_once '../../models/salle.php';
require_once '../sidebar.html';


$salleObj = new salle($pdo);
$salle_edit   = null;
$message      = '';
$type_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int)($_POST['id_salle'] ?? 0);
    $batiment = trim($_POST['batiment'] ?? '');
    $numero_salle = trim($_POST['numero_salle'] ?? '');

    if ($id === 0) {
        $s = new salle($pdo);
        $s->setNumero_salle($numero_salle); 
        $s->setBatiment($batiment);
        $s->ajouterSalle();
        $message      = "Salle ajoutée avec succès.";
        $type_message = "success";
    } else {
        $s = $salleObj->trouversalleParId($id);
        if ($s) {
            $s->setNumero_salle($numero_salle);
            $s->setBatiment($batiment);
            $s->modifiersalle();
            $message      = "Salle modifiée avec succès.";
            $type_message = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
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
        <div class="card-body-custom">
            <div class="card-head">
                <div class="card-head-title">
                    <i class="bi bi-calendar-check"></i>Nouvelle Salle                                
                </div>
            </div>
           <form method="POST" action="">
                <?php if ($salle_edit): ?>
                <input type="hidden" name="id_salle" value="<?= $salle_edit->getId_salle() ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label-custom">Numéro de salle</label>
                    <input 
                        type="text"
                        name="numero_salle"
                        class="form-control-custom"
                        placeholder="Ex : A101"
                        title="Exemple valide : A101, B202..."
                        value="<?= htmlspecialchars($salle_edit ? $salle_edit->getNumero_salle() : '') ?>"
                        required
                    >
                </div>
                <div class="mb-3">
                    <label class="form-label-custom">Bâtiment</label>
                    <input type="text"
                        name="batiment"
                        class="form-control-custom"
                        placeholder="Ex : Bloc A, Bâtiment principal..."
                        value="<?= htmlspecialchars($salle_edit ? $salle_edit->getBatiment() : '') ?>"
                        required>
                </div>

                <button type="submit" class="btn-primary-custom">
                    <i class="bi bi-<?= $salle_edit ? 'check-lg' : 'plus-lg' ?> me-1"></i>
                    <?= $salle_edit ? 'Enregistrer' : 'Ajouter la salle' ?>
                </button>
                
            </form>
        </div>
    </div>
        
    
</body>
</html>
