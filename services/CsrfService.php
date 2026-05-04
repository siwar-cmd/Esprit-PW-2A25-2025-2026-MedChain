<?php
declare(strict_types=1);

/**
 * CsrfService — Simple CSRF token protection.
 *
 * Usage:
 *   In form:  <input type="hidden" name="csrf_token" value="<?= CsrfService::token() ?>">
 *   On POST:  CsrfService::verify($_POST['csrf_token'] ?? '');
 */
class CsrfService
{
    /**
     * Generate or return the current CSRF token for this session.
     */
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Verify a submitted CSRF token against the session token.
     * Regenerates the token after verification to prevent replay.
     *
     * @throws \RuntimeException if verification fails
     */
    public static function verify(string $submittedToken): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $sessionToken = $_SESSION['csrf_token'] ?? '';

        if ($submittedToken === '' || !hash_equals($sessionToken, $submittedToken)) {
            throw new \RuntimeException('CSRF token invalide. Veuillez réessayer.');
        }

        // Regenerate to prevent replay attacks
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        return true;
    }

    /**
     * Output the hidden input field directly.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token()) . '">';
    }
}
