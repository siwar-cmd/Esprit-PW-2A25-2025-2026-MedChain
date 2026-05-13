<?php

class ChatbotController {
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $message = mb_strtolower($input['message'] ?? '', 'UTF-8');

        if (empty($message)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Message is empty']);
            return;
        }

        $response = $this->generateLocalResponse($message);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'response' => $response['text'],
            'fields' => $response['fields'] ?? []
        ]);
    }

    private function generateLocalResponse($message) {
        $data = [
            'text' => "Je peux vous aider pour : Angine, Grippe, Hypertension, Diabète, Allergies, Gastro, Cystite, Migraine, Anémie, Insomnie ou Covid.",
            'fields' => []
        ];

        // --- ANGINE ---
        if (strpos($message, 'angine') !== false) {
            $data['text'] = "Protocole pour Angine :";
            $data['fields'] = [
                'prescription' => "1. Paracétamol 1g (3x/j)\n2. Spray buccal antiseptique\n3. Si bactérienne (TDR+) : Amoxicilline 1g (2x/j pendant 6j)",
                'examensComplementaires' => "Test de Diagnostic Rapide (TDR) Angine.",
                'observations' => "Odynophagie, amygdales érythémateuses, pas de toux."
            ];
        } 
        // --- GRIPPE / FIEVRE ---
        elseif (strpos($message, 'grippe') !== false || strpos($message, 'fievre') !== false) {
            $data['text'] = "Gestion d'un état grippal :";
            $data['fields'] = [
                'prescription' => "1. Paracétamol 1g (max 4g/j)\n2. Vitamine C 1000mg\n3. Hydratation abondante\n4. Repos strict 48h",
                'examensComplementaires' => "Surveillance clinique (température).",
                'observations' => "Syndrome fébrile, courbatures, fatigue intense."
            ];
        } 
        // --- ALLERGIE ---
        elseif (strpos($message, 'allergie') !== false || strpos($message, 'rhinite') !== false) {
            $data['text'] = "Traitement pour Rhinite Allergique :";
            $data['fields'] = [
                'prescription' => "1. Antihistaminique (ex: Cetirizine 10mg le soir)\n2. Corticoïde nasal (ex: Fluticasone 2 pulv/j)\n3. Lavage nasal eau de mer",
                'examensComplementaires' => "Bilan allergologique (Prick-tests) si persistance.",
                'observations' => "Éternuements, prurit nasal, rhinorrhée claire."
            ];
        }
        // --- GASTRO ---
        elseif (strpos($message, 'gastro') !== false || strpos($message, 'diarrhée') !== false) {
            $data['text'] = "Prise en charge Gastro-entérite :";
            $data['fields'] = [
                'prescription' => "1. Diosmectite (Smecta) 3 sach/j\n2. Racécadotril (Tiorfan) 1 gél x3/j\n3. Probiotiques\n4. Soluté de réhydratation si besoin",
                'examensComplementaires' => "Coproculture si fièvre > 39°C ou sang dans les selles.",
                'observations' => "Douleurs abdominales, nausées, transit accéléré."
            ];
        }
        // --- INFECTION URINAIRE / CYSTITE ---
        elseif (strpos($message, 'cystite') !== false || strpos($message, 'urinaire') !== false) {
            $data['text'] = "Protocole pour Cystite non compliquée :";
            $data['fields'] = [
                'prescription' => "1. Fosfomycine Trométamol 3g (dose unique)\n2. Antispasmodique (Spasfon) si douleur\n3. Hydratation > 2L/j",
                'examensComplementaires' => "Bandelette urinaire (BU). ECBU si récidive.",
                'observations' => "Brûlures mictionnelles, pollakiurie, urines troubles."
            ];
        }
        // --- MIGRAINE ---
        elseif (strpos($message, 'migraine') !== false || strpos($message, 'céphalée') !== false) {
            $data['text'] = "Traitement de la crise migraineuse :";
            $data['fields'] = [
                'prescription' => "1. Ibuprofène 400mg ou Kétoprofène\n2. Triptan (si échec AINS)\n3. Repos dans le noir et calme",
                'examensComplementaires' => "Scanner cérébral uniquement si signe d'atypie.",
                'observations' => "Céphalée unilatérale pulsatite, photophobie, nausées."
            ];
        }
        // --- HYPERTENSION ---
        elseif (strpos($message, 'tension') !== false || strpos($message, 'hypertension') !== false) {
            $data['text'] = "Suivi HTA :";
            $data['fields'] = [
                'prescription' => "Poursuite du traitement actuel. Réduction du sel. Exercice physique.",
                'examensComplementaires' => "Bilan biologique (Kaliémie, Créatinine, Glycémie).",
                'observations' => "Tension artérielle stable. Bonne observance."
            ];
        }
        // --- DIABETE ---
        elseif (strpos($message, 'diabète') !== false || strpos($message, 'sucre') !== false) {
            $data['text'] = "Suivi Diabète type 2 :";
            $data['fields'] = [
                'prescription' => "Metformine 850mg (1 à 2 comp/j). Éducation alimentaire.",
                'examensComplementaires' => "Hémoglobine glyquée (HbA1c tous les 3 mois).",
                'observations' => "Surveillance de la glycémie capillaire. Examen des pieds."
            ];
        }
        // --- ANÉMIE ---
        elseif (strpos($message, 'anémie') !== false || strpos($message, 'fer') !== false) {
            $data['text'] = "Prise en charge de l'anémie ferriprive :";
            $data['fields'] = [
                'prescription' => "1. Supplémentation martiale (Fer) pendant 3 mois\n2. Vitamine C (aide à l'absorption)\n3. Aliments riches en fer",
                'examensComplementaires' => "NFS, Ferritinémie, CRP.",
                'observations' => "Pâleur, asthénie, dyspnée d'effort."
            ];
        }
        // --- INSOMNIE ---
        elseif (strpos($message, 'insomnie') !== false || strpos($message, 'sommeil') !== false) {
            $data['text'] = "Conseils pour troubles du sommeil :";
            $data['fields'] = [
                'prescription' => "1. Mélatonine si besoin\n2. Phytothérapie (Valériane/Passiflore)\n3. Hygiène du sommeil (pas d'écran)",
                'examensComplementaires' => "Agenda du sommeil sur 15 jours.",
                'observations' => "Difficultés d'endormissement, réveils nocturnes."
            ];
        }
        // --- COVID ---
        elseif (strpos($message, 'covid') !== false || strpos($message, 'coronavirus') !== false) {
            $data['text'] = "Protocole Covid-19 (Symptomatique) :";
            $data['fields'] = [
                'prescription' => "1. Paracétamol 1g si fièvre\n2. Isolement selon recommandations\n3. Surveillance saturation O2",
                'examensComplementaires' => "Test antigénique ou PCR.",
                'observations' => "Toux, anosmie, agueusie, asthénie."
            ];
        }

        return $data;
    }
}

if (basename($_SERVER['PHP_SELF']) === 'ChatbotController.php') {
    $controller = new ChatbotController();
    $controller->handleRequest();
}
