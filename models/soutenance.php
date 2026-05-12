<?php
class Soutenance {
    private PDO $pdo;
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
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
            foreach ($this->jours as $i => $jour) {
                // Les premiers $reste jours ont 1 soutenance de plus
                $repartition[$filiere][$jour] = $base + ($i < $reste ? 1 : 0);
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

    public function affecterDatesEtHoraires($dateDebut): void{
        $config = [];
        $stmt = $this->pdo->query("
            SELECT cle, valeur
            FROM configuration
        ");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $config[$row['cle']] = $row['valeur'];
        }

        
        

        // RÉCUPÉRATION
 
        $parFiliere = $this->getSoutenancesSansDate();
        $repartition = $this->calculerRepartition($parFiliere);
        // GÉNÉRATION DES CRÉNEAUX
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
        // AFFECTATION
        // =========================
        foreach ($parFiliere as $filiere => $soutenances) {

            $indexSoutenance = 0;

            foreach ($this->jours as $jour) {

                $nbPourJour = $repartition[$filiere][$jour];

                for ($i = 0; $i < $nbPourJour; $i++) {

                    if (!isset($soutenances[$indexSoutenance])) {
                        break;
                    }

                    $soutenance = $soutenances[$indexSoutenance];

                    // Créneau correspondant
                    $creneau = $creneaux[$i % count($creneaux)];

                    // Mise à jour
                    $this->updateSoutenanceDate(
                        $soutenance['id_stnc'],
                        $jour,
                        $creneau['debut'],
                        $creneau['fin']
                    );

                    $indexSoutenance++;
                }
            }
        }
    }

}