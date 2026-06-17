<?php
class Soutenance {
    private PDO $pdo;
    private array $jours = [];
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->jours = [];
    }
    public function insert(int $id_etudiant): void {
        $stmt = $this->pdo->prepare("INSERT INTO soutenance (etudiant_id) VALUES (?)");
        $stmt->execute([$id_etudiant]);
    }
    public function getAllSoutenances(array $filters = []): array {
        $sql = "SELECT * FROM soutenance";
        $params = [];
        $clauses = [];

        if (!empty($filters['statut'])) {
            $clauses[] = 'statut = ?';
            $params[] = $filters['statut'];
        }
        if (!empty($filters['id_salle'])) {
            $clauses[] = 'id_salle = ?';
            $params[] = $filters['id_salle'];
        }

        if (!empty($clauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSoutenanceById(int $id): ?array {
        $sql = "SELECT * FROM soutenance WHERE id_soutenance = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createSoutenance(array $data): array {
        $sql = "INSERT INTO soutenance (date_soutenance, id_salle, id_etudiant, id_jury, statut)
                VALUES (?, ?, ?, ?, 'planifiée')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['date_soutenance'], 
            $data['id_salle'], 
            $data['id_etudiant'], 
            $data['id_jury']
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->getSoutenanceById($id);
    }

    public function updateSoutenance(int $id, array $data): bool {
        $sql = "UPDATE soutenance SET date_soutenance = ?, id_salle = ?, id_etudiant = ?, id_jury = ? WHERE id_soutenance = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['date_soutenance'], 
            $data['id_salle'], 
            $data['id_etudiant'], 
            $data['id_jury'], 
            $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function deleteSoutenance(int $id): bool {
        $sql = "DELETE FROM soutenance WHERE id_soutenance = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // CHAYMAE: debut

    public function soutenancesSansAffectation(): array {
        $stmt = $this->pdo->query(
            "SELECT * FROM soutenance 
            WHERE id_salle IS NULL 
            ORDER BY date, heure_debut"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function affecterSalles(int $id_stc, int $id_salle): void {
        $stmt = $this->pdo->prepare(
            "UPDATE soutenance SET id_salle = ? WHERE id_stnc = ?"
        );
        $stmt->execute([$id_salle, $id_stc]);
    }
    //CHAYMAE: fin 

    private function getSoutenancesSansDate(): array {
        $stmt = $this->pdo->query(
            "SELECT s.id_stnc, e.nom, e.prenom, e.filiere
             FROM soutenance s
             JOIN etudiant e ON s.etudiant_id = e.id_etudiant
             WHERE s.date IS NULL
             ORDER BY e.filiere, e.nom"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
        // Grouper par filière
        $parFiliere = [];
        foreach ($rows as $row) {
            $parFiliere[$row['filiere']][] = $row;
        }
        return $parFiliere;
    }
    private function calculerRepartition(array $parFiliere): array {
        $repartition = [];
 
        foreach ($parFiliere as $filiere => $soutenances) {
            $total   = count($soutenances);
            $nbJours = count($this->jours);
            $base    = intdiv($total, $nbJours);
            $reste   = $total % $nbJours;
 
            $repartition[$filiere] = [];
            for ($i = 0; $i < $nbJours; $i++) {
                $jour = $this->jours[$i];
                if($reste>=1){
                    $repartition[$filiere][$jour] = $base + 1;
                    $reste--;
                }
                else{
                    $repartition[$filiere][$jour] = $base;
                }
            }
        }
        return $repartition;
    }
    private function updateSoutenanceDate(
        int    $id_stnc,
        string $date,
        string $heure_debut,
        string $heure_fin,
        
    ): void {
        $stmt = $this->pdo->prepare(
            "UPDATE soutenance
             SET date        = ?,
                 heure_debut = ?,
                 heure_fin   = ?
             WHERE id_stnc   = ?"
        );
        $stmt->execute([$date, $heure_debut, $heure_fin,  $id_stnc]);
    }

    public function affecterDatesEtHoraires($nbr_jours, $dateDebut): void
    {
        // =========================
        // RÉCUPÉRATION CONFIG
        // =========================

        $config = [];

        $stmt = $this->pdo->query("
            SELECT cle, valeur
            FROM configuration
        ");

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $config[$row['cle']] = $row['valeur'];
        }

        // =========================
        // GÉNÉRATION DES JOURS
        // =========================

        $this->jours = [];

        for ($i = 0; $i < $nbr_jours; $i++) {
            $this->jours[] = date(
                'Y-m-d',
                strtotime("$dateDebut +$i day")
            );
        }

        // =========================
        // RÉCUPÉRATION DES SOUTENANCES
        // =========================

        $parFiliere = $this->getSoutenancesSansDate();

        // Répartition équilibrée sur les jours
        $repartition = $this->calculerRepartition($parFiliere);

        // =========================
        // GÉNÉRATION DES CRÉNEAUX
        // =========================

        $creneaux = [];

        // Matin
        $heure = $config['heure_debut_matin'];

        while ($heure < $config['heure_fin_matin']) {

            $fin = date(
                'H:i',
                strtotime($heure . ' +60 minutes')
            );

            if ($fin > $config['heure_fin_matin']) {
                break;
            }

            $creneaux[] = [
                'debut' => $heure,
                'fin'   => $fin
            ];

            $heure = $fin;
        }

        // Après-midi
        $heure = $config['heure_debut_aprem'];

        while ($heure < $config['heure_fin_aprem']) {

            $fin = date(
                'H:i',
                strtotime($heure . ' +60 minutes')
            );

            if ($fin > $config['heure_fin_aprem']) {
                break;
            }

            $creneaux[] = [
                'debut' => $heure,
                'fin'   => $fin
            ];

            $heure = $fin;
        }

        // =========================
        // CONSTRUIRE LES SOUTENANCES PAR JOUR
        // =========================

        $soutenancesParJour = [];

        foreach ($this->jours as $jour) {
            $soutenancesParJour[$jour] = [];
        }

        foreach ($parFiliere as $filiere => $soutenances) {

            $index = 0;

            foreach ($this->jours as $jour) {

                $nbPourJour = $repartition[$filiere][$jour];

                for ($i = 0; $i < $nbPourJour; $i++) {

                    if (!isset($soutenances[$index])) {
                        break;
                    }

                    $soutenancesParJour[$jour][] =
                        $soutenances[$index];

                    $index++;
                }
            }
        }

        // =========================
        // AFFECTATION DES HORAIRES
        // =========================

        foreach ($soutenancesParJour as $jour => $listeJour) {

            // Mélange des filières
            shuffle($listeJour);

            $nbCreneaux = count($creneaux);

            foreach ($listeJour as $i => $soutenance) {

                $creneau = $creneaux[$i % $nbCreneaux];

                $idEncadrant = $this->getEncadrantBySoutenance(
                    $soutenance['id_stnc']
                );

                // Vérifier disponibilité encadrant
                if (
                    $this->encadrantOccupe(
                        $idEncadrant,
                        $jour,
                        $creneau['debut']
                    )
                ) {

                    $trouve = false;

                    foreach ($creneaux as $autreCreneau) {

                        if (
                            !$this->encadrantOccupe(
                                $idEncadrant,
                                $jour,
                                $autreCreneau['debut']
                            )
                        ) {

                            $creneau = $autreCreneau;
                            $trouve = true;
                            break;
                        }
                    }

                    if (!$trouve) {
                        continue;
                    }
                }

                $this->updateSoutenanceDate(
                    $soutenance['id_stnc'],
                    $jour,
                    $creneau['debut'],
                    $creneau['fin']
                );
            }
        }
    }
    
    public function encadrantOccupe($id_prof, $date, $heure_debut): bool {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM jury j
            JOIN soutenance s ON j.id_soutenance = s.id_stnc
            WHERE j.id_prof = ?
            AND j.role = 'Encadrant'
            AND s.date = ?
            AND s.heure_debut = ?
        ");
        $stmt->execute([$id_prof, $date, $heure_debut]);
        return $stmt->fetchColumn() > 0;
    }
    public function getEncadrantBySoutenance($id_stnc): int {
        $stmt = $this->pdo->prepare("
            SELECT j.id_prof 
            FROM jury j
            WHERE j.id_soutenance = ?
            AND j.role = 'Encadrant'
        ");
        $stmt->execute([$id_stnc]);
        return $stmt->fetchColumn();
    }
    public function capaciteSuffisante(int $nbJours): bool
    {
        // Nombre de salles
        $stmt = $this->pdo->query("
            SELECT COUNT(*) AS nb_salles
            FROM salle
        ");

        $nbSalles = (int) $stmt->fetch(PDO::FETCH_ASSOC)['nb_salles'];

        // Nombre de soutenances à planifier
        $stmt = $this->pdo->query("
            SELECT COUNT(*) AS nb_soutenances
            FROM soutenance
            WHERE date IS NULL
        ");

        $nbSoutenances = (int) $stmt->fetch(PDO::FETCH_ASSOC)['nb_soutenances'];

        // Capacité totale
        $capacite = $nbSalles * 7 * $nbJours;

        return $capacite >= $nbSoutenances;
    }

}