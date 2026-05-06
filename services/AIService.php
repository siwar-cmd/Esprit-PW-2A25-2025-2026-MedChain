<?php
declare(strict_types=1);

class AIService
{
    // ─── PUT YOUR OPENAI API KEY HERE ───────────────────────────────────────
    private const API_KEY = ;
    // ────────────────────────────────────────────────────────────────────────

    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    private const MODEL    = 'gpt-3.5-turbo';

    /**
     * Analyse free-text patient input and return structured JSON.
     * Falls back to keyword-based mock if API key is not set.
     *
     * @return array{emotion:string, physical_condition:string, intent:string, recommended_category:string, empathy_message:string}
     */
    public function analyzePatientInput(string $userMessage): array
    {
        if (self::API_KEY === 'sk-YOUR_OPENAI_API_KEY_HERE') {
            return $this->mockAnalysis($userMessage);
        }

        $systemPrompt = <<<'PROMPT'
You are a hospital leisure assistant helping patients find activities.
Analyze the patient message and return ONLY a valid JSON object — no markdown, no explanation, no extra keys.

Required JSON structure:
{
  "emotion": "<one of: joy, stress, fatigue, boredom, anxiety, loneliness, neutral>",
  "condition": "<brief physical state, e.g. mobile, resting, recovering — inferred only from explicit words>",
  "intent": "<what the patient wants, e.g. move, relax, be entertained, think, listen>",
  "recommended_category": "<exactly one of: Sport, Musique, Livre, Film, Jeu de societe, Electronique, Casse-tete>",
  "empathy_message": "<one upbeat, helpful sentence in French — no apologies unless patient explicitly mentions pain>"
}

STRICT MAPPING RULES — follow these exactly, they override your own judgment:
1. Patient wants to move / exercise / is energetic
   Keywords: bouger, sport, actif, active, exercice, marcher, courir, énergie, forme
   → recommended_category = "Sport", emotion = "joy"

2. Patient is stressed / anxious / nervous
   Keywords: stress, stressé, anxieux, angoisse, nerveux, pression, inquiet
   → recommended_category = "Musique", emotion = "stress"

3. Patient is tired / wants to rest
   Keywords: fatigué, épuisé, las, dormir, repos, reposer
   → recommended_category = "Livre", emotion = "fatigue"

4. Patient is bored / wants distraction
   Keywords: ennui, ennuyé, rien à faire, occuper, passer le temps
   → recommended_category = "Jeu de societe", emotion = "boredom"

5. Patient explicitly mentions pain or immobility
   Keywords: j'ai mal, douleur, souffre, immobile, ne peux pas bouger
   → recommended_category = "Film", emotion = "pain"

6. Patient wants mental challenge / puzzle
   Keywords: réfléchir, logique, puzzle, défi, cerveau, mental
   → recommended_category = "Casse-tete", emotion = "neutral"

7. Patient wants screen / gaming / electronics
   Keywords: console, tablette, jeu vidéo, écran, jouer en ligne
   → recommended_category = "Electronique", emotion = "boredom"

8. Patient wants music / to listen
   Keywords: musique, écouter, chanson, instrument
   → recommended_category = "Musique", emotion = "neutral"

9. Patient wants to watch a film / series
   Keywords: film, série, regarder, cinéma, DVD
   → recommended_category = "Film", emotion = "neutral"

CRITICAL GUARDRAILS:
- NEVER apologize or assume suffering unless the patient explicitly uses "j'ai mal" or "douleur".
- NEVER map "bouger", "sport", or "exercice" to Film or any non-Sport category.
- If no rule matches, default to recommended_category = "Livre" and emotion = "neutral".
- The empathy_message must be upbeat and encouraging, not pitying.
PROMPT;

        $payload = json_encode([
            'model'       => self::MODEL,
            'temperature' => 0.1,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userMessage],
            ],
        ]);

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . self::API_KEY,
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return $this->mockAnalysis($userMessage);
        }

        $decoded = json_decode($response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? '';
        $result  = json_decode(trim($content), true);

        if (!is_array($result) || !isset($result['recommended_category'])) {
            return $this->mockAnalysis($userMessage);
        }

        // Normalise: ensure legacy callers that expect 'empathy_message' still work
        if (empty($result['empathy_message']) && !empty($result['intent'])) {
            $result['empathy_message'] = $result['empathy_message'] ?? '';
        }

        return $result;
    }

    /**
     * Keyword-based fallback — no API key required.
     */
    private function mockAnalysis(string $message): array
    {
        $msg = mb_strtolower($message);

        $map = [
            'stress|anxieux|anxieuse|angoisse|nerveux|nerveuse|pression' => [
                'emotion' => 'stress', 'recommended_category' => 'Musique',
                'empathy_message' => "Je comprends que vous traversez une période stressante. La musique peut vraiment vous aider à vous détendre.",
            ],
            'fatigué|fatiguée|fatigue|épuisé|épuisée|las|lasse|dormir' => [
                'emotion' => 'fatigue', 'recommended_category' => 'Livre',
                'empathy_message' => "Je vois que vous êtes fatigué(e). Un bon livre peut être un compagnon doux et reposant.",
            ],
            'ennui|ennuyé|ennuyée|rien à faire|occuper' => [
                'emotion' => 'boredom', 'recommended_category' => 'Jeu de societe',
                'empathy_message' => "L'ennui peut être difficile. Un jeu de société pourrait vous divertir agréablement.",
            ],
            'douleur|j\'ai mal|souffre|souffrir|immobile' => [
                'emotion' => 'pain', 'recommended_category' => 'Film',
                'empathy_message' => "Un bon film peut vous aider à vous évader et oublier l'inconfort un moment.",
            ],
            'seul|seule|solitude|isolé|isolée' => [
                'emotion' => 'loneliness', 'recommended_category' => 'Jeu de societe',
                'empathy_message' => "La solitude est difficile. Un jeu de société peut créer de beaux moments de partage.",
            ],
            'réfléchir|cerveau|mental|logique|puzzle|défi' => [
                'emotion' => 'neutral', 'recommended_category' => 'Casse-tete',
                'empathy_message' => "Stimuler votre esprit est une excellente idée ! Voici quelques défis pour vous.",
            ],
            'bouger|sport|actif|active|exercice|énergie' => [
                'emotion' => 'joy', 'recommended_category' => 'Sport',
                'empathy_message' => "Votre envie de bouger est formidable ! Voici ce que nous avons pour vous.",
            ],
            'jeu|jouer|console|tablette|écran|vidéo' => [
                'emotion' => 'boredom', 'recommended_category' => 'Electronique',
                'empathy_message' => "Bonne idée ! Un peu de divertissement électronique peut vraiment égayer votre journée.",
            ],
        ];

        foreach ($map as $pattern => $data) {
            if (preg_match('/(' . $pattern . ')/ui', $msg)) {
                return array_merge([
                    'physical_condition' => 'non précisé',
                    'intent'             => 'distraction',
                ], $data);
            }
        }

        return [
            'emotion'              => 'neutral',
            'physical_condition'   => 'non précisé',
            'intent'               => 'distraction',
            'recommended_category' => 'Livre',
            'empathy_message'      => "Je suis là pour vous aider à trouver quelque chose d'agréable. Voici quelques suggestions.",
        ];
    }
}
