<?php
// controllers/ChatbotController.php

class ChatbotController {
    private $apiKey;
    private $apiUrl;
    private $model;

    public function __construct() {
        // ⚙️ Configuration Groq (gratuit, fiable)
        $this->apiKey = 'votre_clé_groq_ici';  // À remplacer
        $this->model  = 'llama-3.3-70b-versatile';
        $this->apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
    }

    /**
     * Point d’entrée public : reçoit les messages du widget
     * (appelé via chatbot-api.php)
     */
    public function publicChat($messages) {
        $systemPrompt = $this->getPublicSystemPrompt();
        $groqMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $this->convertMessages($messages)
        );

        $reply = $this->callGroq($groqMessages);
        return $reply;
    }

    /**
     * Version admin (réutilise la même logique mais avec prompt différent)
     */
    public function adminChat($messages) {
        $systemPrompt = $this->getAdminSystemPrompt();
        $groqMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $this->convertMessages($messages)
        );
        return $this->callGroq($groqMessages);
    }

    private function getPublicSystemPrompt() {
        return "Tu es MedChain AI, l'assistant médical de la plateforme MedChain.

Tu aides les visiteurs et les patients avec :
- Les explications sur le fonctionnement de MedChain (blockchain, sécurité, partage)
- Les avantages du dossier médical numérique
- Les réponses sur la plateforme, les tarifs, l'inscription
- Des conseils généraux de santé (jamais de diagnostic médical)
- Les démarches administratives

Règles :
1. Réponds toujours en français, clair et bienveillant
2. Utilise du markdown (titres ##, listes -, gras **)
3. Ne donne jamais de diagnostic – oriente vers un médecin
4. Reste professionnel et rassurant";
    }

    private function getAdminSystemPrompt() {
        return "Tu es MedChain AI, assistant administratif et médical de la plateforme MedChain.

Tu aides exclusivement les administrateurs avec :
- Gestion des utilisateurs, validation RPPS, dossiers patients
- Statistiques, exports, KPI
- Sécurité RGPD, conformité CNIL
- Bonnes pratiques d'administration

Règles :
1. Réponds toujours en français, professionnel
2. Utilise du markdown
3. Sois précis sur les obligations légales";
    }

    private function convertMessages($messages) {
        $converted = [];
        foreach ($messages as $msg) {
            $role = ($msg['role'] === 'assistant') ? 'assistant' : 'user';
            $converted[] = ['role' => $role, 'content' => $msg['content']];
        }
        return $converted;
    }

    private function callGroq($messages) {
        $payload = [
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => 0.7,
            'max_tokens'  => 1024,
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $error = json_decode($result, true);
            throw new Exception($error['error']['message'] ?? 'Erreur API Groq');
        }

        $data = json_decode($result, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }
}
?>