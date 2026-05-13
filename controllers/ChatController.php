<?php
declare(strict_types=1);

require_once BASE_PATH . '/services/AIService.php';

class ChatController
{
    /**
     * POST /index1.php?controller=chat&action=message
     * Expects JSON body: { "message": "..." }
     * Returns JSON: { "reply": "...", "objects": [...] }
     */
    public function handleMessage(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Only logged-in users
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Vous devez être connecté.']);
            exit;
        }

        $raw     = file_get_contents('php://input');
        $body    = json_decode($raw, true);
        $message = trim($body['message'] ?? '');

        if ($message === '') {
            echo json_encode(['error' => 'Message vide.']);
            exit;
        }

        // 1. AI analysis
        $ai       = new AIService();
        $analysis = $ai->analyzePatientInput($message);

        // 2. DB lookup
        $category = $analysis['recommended_category'] ?? 'Livre';
        $objects  = ObjetLoisir::getRecommendedObjects($category);

        // 3. Build natural-language reply
        $reply = $analysis['empathy_message'] ?? '';

        if (empty($objects)) {
            $reply .= " Malheureusement, aucun objet de la catégorie « {$category} » n'est disponible en ce moment.";
        } else {
            $names  = array_column($objects, 'nom_objet');
            $list   = implode(', ', array_map(fn($n) => "« {$n} »", $names));
            $reply .= " Je vous recommande : {$list}.";
        }

        echo json_encode([
            'reply'    => $reply,
            'objects'  => $objects,
            'analysis' => [
                'emotion'   => $analysis['emotion']   ?? '',
                'category'  => $category,
            ],
        ]);
        exit;
    }

    /**
     * GET — renders the chat page
     */
    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /midchaine/views/frontoffice/auth/login.php');
            exit;
        }
        require_once BASE_PATH . '/views/front/chat.php';
    }
}
