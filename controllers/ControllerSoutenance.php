<?php
//Importe le fichier du modèle Soutenance une seule fois. __DIR__ donne le chemin absolu du dossier courant, ce qui évite les problèmes de chemins relatifs.

require_once __DIR__ . '/../models/soutenance.php';
require_once __DIR__ . '/../models/salle.php';           
require_once __DIR__ . '/../models/configuration.php';
require_once __DIR__ . '/../config/database.php';

class SoutenanceController {
    private $soutenanceModel;
    private $salleModel;        
    private $configModel;
    private PDO  $pdo;
//Instancie le modèle Soutenance et le stocke dans $this->soutenanceModel
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->soutenanceModel = new Soutenance($pdo);
        $this->salleModel      = new salle($pdo);       
        $this->configModel     = new configuration($pdo); 
    }

    private function sendJson($data, int $status = 200): void {
        //fixe le code HTTP 
        http_response_code($status);
        //déclare le Content-Type: application/json
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
//Récupère tous les paramètres $_GET comme filtres et les passe directement au modèle.
    public function index(): void {
           $filters = $_GET;
        $soutenances = $this->soutenanceModel->getAllSoutenances($filters);
        $this->sendJson($soutenances);
    }

//Cherche une soutenance par ID . Si elle n'existe pas, renvoie une erreur 404.
    public function show($id): void {
        //(casté en int pour éviter les injections)
        $soutenance = $this->soutenanceModel->getSoutenanceById((int) $id);
        if ($soutenance) {
            $this->sendJson($soutenance);
        } else {
            $this->sendJson(["error" => "Soutenance introuvable"], 404);
        }
    }


    public function store(): void {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!is_array($data)) {
            $this->sendJson(["error" => "Données JSON invalides"], 400);
            return;
        }

        $requiredFields = ['date_soutenance', 'id_salle', 'id_etudiant', 'id_jury'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->sendJson(["error" => "Le champ $field est requis"], 400);
                return;
            }
        }

        $apiUrl = "https://api.chaymae.com/checkSalle";
        $query = http_build_query([
            'salle_id' => $data['id_salle'], 
            'date' => $data['date_soutenance'], 
            'heure_debut' => $data['heure_debut'] ?? ''
        ]);
        $response = @file_get_contents($apiUrl . "?" . $query);
        if ($response === false) {
            $this->sendJson(["error" => "Impossible de vérifier la disponibilité de la salle"], 503);
            return;
        }

        $result = json_decode($response, true);
        if (!is_array($result) || !array_key_exists('conflict', $result)) {
            $this->sendJson(["error" => "Réponse de l'API de disponibilité invalide"], 502);
            return;
        }

        if ($result['conflict'] === true) {
            $this->sendJson(["error" => "Salle déjà réservée"], 409);
            return;
        }

        $newSoutenance = $this->soutenanceModel->createSoutenance($data);
        $this->sendJson($newSoutenance, 201);
    }

    // Appelle updateSoutenance() sur le modèle. Retourne une erreur 400 si aucune ligne n'a été modifiée
    public function update($id): void {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!is_array($data)) {
            $this->sendJson(["error" => "Données JSON invalides"], 400);
            return;
        }

        $requiredFields = ['date_soutenance', 'id_salle', 'id_etudiant', 'id_jury'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->sendJson(["error" => "Le champ $field est requis"], 400);
                return;
            }
        }

        $updated = $this->soutenanceModel->updateSoutenance((int) $id, $data);
        if ($updated) {
            $this->sendJson(["success" => true]);
        } else {
            $this->sendJson(["error" => "Échec de la mise à jour ou aucun changement effectué"], 400);
        }
    }
//Supprime la soutenance par ID. Simple délégation au modèle avec gestion de l'échec.
    public function destroy($id): void {
        $deleted = $this->soutenanceModel->deleteSoutenance((int) $id);
        if ($deleted) {
            $this->sendJson(["success" => true]);
        } else {
            $this->sendJson(["error" => "Échec de la suppression"], 400);
        }
    }


    //CHAYMAE : Partie affectation des salles aux soutenances 
    //fonction pour verefier que le creneau est valide 
    private function estDansCreneauValide(string $heure_debut,string $heure_fin):bool{
        $debut_matin=$this->configModel->getValeurByCle('heure_debut_matin');
        $fin_matin=$this->configModel->getValeurByCle('heure_fin_matin');
        $debut_apres_midi = $this->configModel->getValeurByCle('heure_debut_aprem');
        $fin_apres_midi   = $this->configModel->getValeurByCle('heure_fin_aprem');
        
        $dans_matin=($heure_debut>=$debut_matin && $heure_fin <= $fin_matin);
        $dans_apres_midi=($heure_debut>=$debut_apres_midi && $heure_fin <= $fin_apres_midi);

        return $dans_matin || $dans_apres_midi;
    }
    //fonction retourne si une salle dispo 
    private function salleDisponible(int $id_salle,string $date,string $heure_debut,string $heure_fin , int $id_sout_exclure=0):bool{
        $stmt=$this->pdo->prepare("
            SELECT COUNT(*) FROM soutenance
            WHERE id_salle=:id_salle
            AND date=:date
            AND heure_debut < :heure_fin
            AND heure_fin > :heure_debut
            AND id_stnc != :id_exclure
        ");
        $stmt->execute([
            ':id_salle'    => $id_salle,
            ':date'        => $date,
            ':heure_debut' => $heure_debut,
            ':heure_fin'   => $heure_fin,
            ':id_exclure'  => $id_sout_exclure
        ]);
        return $stmt->fetchColumn()==0;
    }
    //la fct principale qui va affecter les salles aux soutenances 
    public function affecterSalles():array{
        //recuperer les soutenances 
        $soutenances=$this->soutenanceModel->soutenancesSansAffectation();
        //recuperer les salles
        $salles=$this->salleModel->listersalles();
        $conflits=[];


        foreach($soutenances as $sout){
            if(!$this->estDansCreneauValide($sout['heure_debut'],$sout['heure_fin'])){
                $conflits[]=[
                    'soutenance'=>$sout,
                    'raison'=>'heure hors creneau autorise'
                ];
                continue;
            }
            $salle_trouvee=null;
            foreach($salles as $s){
                if($this->salleDisponible($s->getId_salle(),$sout['date'],$sout['heure_debut'],$sout['heure_fin'],$sout['id_stnc'])){
                    $salle_trouvee=$s->getId_salle();
                    break;
                }
            }
            if($salle_trouvee){
                $this->soutenanceModel->affecterSalles($sout['id_stnc'],$salle_trouvee);

            }else{
                $conflits[]=[
                    'soutenance'=>$sout,
                    'raison'=>'aucune salle disponible a ce creneau'
                ];
            }
        }
        return[
            'success'=>true,
            'affectees'=>count($soutenances)-count($conflits),
            'conflits'=>$conflits
        ];
    }
    //CHAYMAE : fin

}
?>