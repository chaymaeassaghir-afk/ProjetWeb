<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PV.php';

class PVController {

    private PDO $pdo;
    private PV $pvModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pvModel = new PV($pdo);
    }

    public function index()
{

    $sql = "

    SELECT

        pv.*,

        e.nom,
        e.prenom,
        e.filiere,

        p1.nom AS president,
        p2.nom AS rapporteur,
        p3.nom AS encadrant

    FROM pv

    JOIN soutenance s
        ON pv.soutenance_id = s.id_stnc

    JOIN etudiant e
        ON s.etudiant_id = e.id_etudiant

    JOIN professeur p1
        ON pv.president_jury_id = p1.id

    JOIN professeur p2
        ON pv.rapporteur_id = p2.id

    JOIN professeur p3
        ON pv.encadrant_id = p3.id

    ORDER BY pv.created_at DESC

    ";

    $stmt = $this->pdo->query($sql);

    $pvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    require_once __DIR__ . '/../views/pv/index.php';
}

    public function genererPV($soutenance_id)
{
    // récupérer les membres du jury
    $sql = "
        SELECT 
            j.id_prof,
            j.role
        FROM jury j
        WHERE j.id_soutenance = ?
          AND j.role IN ('encadrant', 'rapporteur', 'president')
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$soutenance_id]);

    // récupérer les 3 lignes
    $jury = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$jury) {
        die('Jury introuvable');
    }

    // initialisation
    $encadrantId = null;
    $rapporteurId = null;
    $presidentId = null;

    // parcourir les membres du jury
    foreach ($jury as $membre) {

        if ($membre['role'] === 'Encadrant') {
            $encadrantId = $membre['id_prof'];
        }

        elseif ($membre['role'] === 'Rapporteur') {
            $rapporteurId = $membre['id_prof'];
        }

        elseif ($membre['role'] === 'Président') {
            $presidentId = $membre['id_prof'];
        }
    }

    // vérifier que les 3 existent
    if (!$encadrantId || !$rapporteurId || !$presidentId) {
        die('Jury incomplet');
    }

    // vérifier si PV existe déjà
    $check = $this->pdo->prepare("
        SELECT id
        FROM pv
        WHERE soutenance_id = ?
    ");

    $check->execute([$soutenance_id]);

    if ($check->fetch()) {
        die('PV déjà généré');
    }

    // insertion du PV
    $insert = $this->pdo->prepare("
        INSERT INTO pv(
            soutenance_id,
            president_jury_id,
            rapporteur_id,
            encadrant_id,
            statut
        )
        VALUES(?,?,?,?,?)
    ");

    try {

        $insert->execute([
            $soutenance_id,
            $presidentId,
            $rapporteurId,
            $encadrantId,
            'brouillon'
        ]);

        echo "PV généré avec succès";

    } catch(PDOException $e) {

        die("Erreur SQL : " . $e->getMessage());
    }
}
public function genererTousLesPV()
{
    $sql = "SELECT id_stnc FROM soutenance";

    $stmt = $this->pdo->query($sql);

    $soutenances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($soutenances as $s){

        $this->genererPV($s['id_stnc']);
    }

    header('Location:index.php?controller=pv&page=index');
    exit();
}

    
}