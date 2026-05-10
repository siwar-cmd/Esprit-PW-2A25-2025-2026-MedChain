<?php

/**
 * UnsplashService — Fetches a relevant image URL for a given keyword.
 *
 * ── HOW TO GET A FREE API KEY ─────────────────────────────────────────────────
 *  1. Go to https://unsplash.com/developers
 *  2. Click "Register as a developer" → create a free account
 *  3. Create a new application (Demo access = 50 requests/hour, free forever)
 *  4. Copy your "Access Key" and paste it into UNSPLASH_ACCESS_KEY below
 * ─────────────────────────────────────────────────────────────────────────────
 */
class UnsplashService
{
    // ── Replace with your real Unsplash Access Key ────────────────────────────
    private const UNSPLASH_ACCESS_KEY = '.';
    // ─────────────────────────────────────────────────────────────────────────

    /** Local fallback image served when the API returns nothing */
    private const FALLBACK_IMAGE = '/midchaine/public/assets/images/default-object.jpg';

    /**
     * Fetch the most relevant image URL for a given keyword.
     *
     * @param  string $keyword  Object name used as search term (e.g. "football", "livre")
     * @return string           Absolute image URL (Unsplash CDN) or fallback path
     */
    public static function fetchImageForObject(string $keyword): string
    {
        $keyword = trim($keyword);

        if ($keyword === '' || self::UNSPLASH_ACCESS_KEY === 'YOUR_UNSPLASH_ACCESS_KEY_HERE') {
            return self::FALLBACK_IMAGE;
        }

        // Translate common French object names to English for better results
        $keyword = self::translateKeyword($keyword);

        $url = 'https://api.unsplash.com/search/photos?'
             . http_build_query([
                 'query'       => $keyword,
                 'per_page'    => 1,
                 'orientation' => 'landscape',
                 'content_filter' => 'high',
             ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Client-ID ' . self::UNSPLASH_ACCESS_KEY,
                'Accept-Version: v1',
            ],
            CURLOPT_SSL_VERIFYPEER => false, // Safe for localhost/XAMPP
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Log errors silently — never crash the main flow
        if ($curlError || $httpCode !== 200 || $response === false) {
            error_log('[UnsplashService] API error: HTTP ' . $httpCode . ' | ' . $curlError);
            return self::FALLBACK_IMAGE;
        }

        $data = json_decode($response, true);

        // Extract the "regular" size URL from the first result
        $imageUrl = $data['results'][0]['urls']['regular'] ?? null;

        if (empty($imageUrl)) {
            error_log('[UnsplashService] No results for keyword: ' . $keyword);
            return self::FALLBACK_IMAGE;
        }

        return $imageUrl;
    }

    /**
     * Translate common French leisure object names to English
     * so Unsplash (English-indexed) returns better results.
     */
    private static function translateKeyword(string $keyword): string
    {
        $map = [
            'livre'          => 'book',
            'roman'          => 'novel book',
            'jeu de societe' => 'board game',
            'jeu'            => 'game',
            'sport'          => 'sport',
            'musique'        => 'music instrument',
            'electronique'   => 'electronics',
            'casse-tete'     => 'puzzle',
            'puzzle'         => 'puzzle',
            'film'           => 'movie cinema',
            'ballon'         => 'ball',
            'guitare'        => 'guitar',
            'tablette'       => 'tablet device',
            'echecs'         => 'chess',
            'dames'          => 'checkers game',
        ];

        $lower = mb_strtolower($keyword);

        foreach ($map as $fr => $en) {
            if (str_contains($lower, $fr)) {
                return $en;
            }
        }

        // Return original keyword — Unsplash handles many languages
        return $keyword;
    }
}
