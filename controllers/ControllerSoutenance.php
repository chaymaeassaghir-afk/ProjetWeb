<?php
//Importe le fichier du modèle Soutenance une seule fois. __DIR__ donne le chemin absolu du dossier courant, ce qui évite les problèmes de chemins relatifs.

require_once __DIR__ . '/Model/Soutenance.php';

class SoutenanceController {
    private $soutenanceModel;
//Instancie le modèle Soutenance et le stocke dans $this->soutenanceModel
    public function __construct() {
        $this->soutenanceModel = new Soutenance();
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
}
?>
