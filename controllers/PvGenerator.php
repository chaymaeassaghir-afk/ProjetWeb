<?php

require_once 'libs/fpdf186/fpdf.php';
require_once __DIR__ . '/../config/database.php';

class PVGenerator {

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    private function enc($text){
        return iconv('UTF-8', 'windows-1252//TRANSLIT', $text);
    }

    public function genererPDF($soutenance_id)
    {

        $sql = "
        SELECT 
            e.nom,
            e.prenom,
            e.filiere,

            pr1.nom AS nom_president,
            pr2.nom AS nom_rapporteur,
            pr3.nom AS nom_encadrant,
            pr1.prenom AS prenom_president,
            pr2.prenom AS prenom_rapporteur,
            pr3.prenom AS prenom_encadrant

        FROM pv p

        JOIN soutenance s 
            ON p.soutenance_id = s.id_stnc

        JOIN etudiant e 
            ON s.etudiant_id = e.id_etudiant

        JOIN professeur pr1
            ON p.president_jury_id = pr1.id

        JOIN professeur pr2
            ON p.rapporteur_id = pr2.id

        JOIN professeur pr3
            ON p.encadrant_id = pr3.id

        WHERE s.id_stnc = ?
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$soutenance_id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$data){
            die("PV introuvable");
        }

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->Image($_SERVER['DOCUMENT_ROOT'] . '/projetweb/logoUAE.png',10,8,25,25);
        $pdf->Image($_SERVER['DOCUMENT_ROOT'] . '/projetweb/ensah.png',170,8,25,25);

        
        $pdf->SetFont('Arial','',10);

        $pdf->Cell(0,5,'UNIVERSITE ABDELMALEK ESSAADI',0,1,'C');

        
        $pdf->SetFont('Arial','',8);

        $pdf->Cell(0,5,$this->enc("École Nationale des Sciences Appliquées d Al-Hoceima - Maroc"),0,1,'C');

        $pdf->Ln(2);
        $pdf->SetFont('Arial','B',12);

        $pdf->Cell(0,5,$this->enc("Département de Mathématiques et Informatique"),0,1,'C');

        
        $pdf->SetFont('Arial','B',10);

        $pdf->Cell(0,5,$this->enc("Fiche d'évaluation du Projet de Fin d'Études"),0,1,'C');

        
        $pdf->SetFont('Arial','B',10);

        $pdf->Cell(0,5,$this->enc("Année Universitaire : 2025-2026"),0,1,'C');

        $pdf->Ln(10);

        $pdf->SetFont('Arial','BU',12);



        $pdf->Cell(0,10,$this->enc("Nom - Prénom de l'élève ingénieur : "),0,1);
        $pdf->SetFont('Arial','',12);
        $pdf->Ln(3);

        $pdf->Cell(0,0," -       ".$data['nom']." ".$data['prenom'],0,1);
        $pdf->Ln(5);

        
        $pdf->SetFont('Arial','bu',12);

        $pdf->Cell(0,10,$this->enc("Filière : "),0,1);
        $pdf->Ln(5);
        $pdf->SetFont('Arial','',12);
        
        $filiere=$data['filiere'];
        $pdf->Rect(15,80,5,5);
        if($filiere == 'TDIA'){
            $pdf->Text(16,84,'X');
        }
        
        $pdf->Text(
            25,
            84,
            'Transformation Digitale & Intelligence Artificielle'
        );
        $pdf->Ln(3);

        $pdf->Rect(15,90,5,5);
        if($filiere == 'DATA'){
            $pdf->Text(16,94,'X');
        }

        $pdf->Text(
            25,
            94,
            $this->enc("Ingénierie des Données")
        );

        $pdf->Ln(3);

        $pdf->Rect(15,100,5,5);
        if($filiere == 'GI'){
            $pdf->Text(16,104,'X');
        }

        $pdf->Text(
            25,
            104,
            $this->enc("Génie Informatique")
        );
        
        $pdf->SetFont('Arial','BU',12);
        $pdf->Text(10,111," Intitule du rapport :");
        $pdf->SetFont('Arial','',12);
        $pdf->Text(10,118,"-  ____________________________________________________________");
        
        $pdf->SetFont('Arial','BU',12);
        $pdf->Text(10,125," Membre de jury :");

        $pdf->SetFont('Arial','',12);
        $pdf->Text(10,132,"-Rapporteur : ".$data['nom_president']." ".$data['prenom_president'] );

        $pdf->Text(10,139,"-Rapporteur : ".$data['nom_rapporteur']." ".$data['prenom_rapporteur']);

        $pdf->Text(10,146,"-Encadrant : ".$data['nom_encadrant']." ".$data['prenom_encadrant']);

        $pdf->Ln(10);
        $pdf->SetFont('Arial','BU',12);
        $pdf->Text(10,153,"Note contenu : ");
        $pdf->SetFont('Arial','',12);
        $pdf->Text(10,160,"     C = _________________ ");

        $pdf->SetFont('Arial','BU',12);
        $pdf->Text(10,167,$this->enc("Note mémoire :"),0,1);
        $pdf->SetFont('Arial','',12);
        $pdf->Text(10,174,"     M = _________________ ");

        $pdf->SetFont('Arial','BU',12);
        $pdf->Text(10,181,"Note soutenance : ",0,1);
        $pdf->SetFont('Arial','',12);
        $pdf->Text(10,188,"     S = _________________ ");

        $pdf->Rect(20,195,170,10);

        $pdf->Text(70,202,'Moyenne');

        $pdf->Rect(20,205,170,10);

        $pdf->Text(25,210,'Moyenne = C * 0,5 + M * 0,2 + S * 0,3 = ___________________');

        $pdf->Text(10,225,'Le : .....................');
        $pdf->Text(10,232,'Signature des membres du jury :');
        $pdf->Text(10,242,"Pr.________________________ Pr.________________________ Pr.________________________");
        
        

        $pdf->Output(
            'D',
            'pv-' . $data['nom'] . '_' . $data['prenom'] . '.pdf'
        );
    }
}