<?php
declare(strict_types=1);

/**
 * MedChain — Abstract Base Controller
 *
 * All controllers extend this class to inherit:
 *  - requireAuth()    → redirects to login if user is not authenticated
 *  - requireAdmin()   → additionally checks for 'admin' role
 *  - requireRole()    → checks for any specific role
 *  - currentUserId()  → returns the session user ID
 *  - currentUserRole()→ returns the session role
 *  - redirect()       → wrapper around header('Location:')
 *  - view()           → includes a view file with extracted variables
 */
abstract class BaseController
{
    // ── Canonical login page (relative to web root) ──────────────
    protected const LOGIN_URL = '/midchaine/index.php?controller=auth&action=login';

    // ──────────────────────────────────────────────────────────────
    //  AUTH GUARDS
    // ──────────────────────────────────────────────────────────────

    /**
     * Ensure user is logged in; redirect to login otherwise.
     */
    protected function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect(self::LOGIN_URL);
        }
    }

    /**
     * Ensure current user is an admin; redirect to login otherwise.
     */
    protected function requireAdmin(): void
    {
        if (
            empty($_SESSION['user_id'])
            || ($_SESSION['user_role'] ?? '') !== 'admin'
        ) {
            $this->redirect(self::LOGIN_URL);
        }
    }

    /**
     * Ensure current user has the given role (or is an admin).
     *
     * @param string|string[] $roles
     */
    protected function requireRole(string|array $roles): void
    {
        $roles = (array) $roles;
        if (
            empty($_SESSION['user_id'])
            || !in_array($_SESSION['user_role'] ?? '', $roles, true)
        ) {
            $this->redirect(self::LOGIN_URL);
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  SESSION ACCESSORS
    // ──────────────────────────────────────────────────────────────

    protected function currentUserId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    protected function currentUserRole(): string
    {
        return (string) ($_SESSION['user_role'] ?? '');
    }

    protected function currentUserName(): string
    {
        $prenom = $_SESSION['user_prenom'] ?? '';
        $nom    = $_SESSION['user_nom']    ?? '';
        return trim("$prenom $nom") ?: 'Utilisateur';
    }

    protected function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    protected function isAdmin(): bool
    {
        return $this->isLoggedIn() && $this->currentUserRole() === 'admin';
    }

    // ──────────────────────────────────────────────────────────────
    //  VIEW & ROUTING HELPERS
    // ──────────────────────────────────────────────────────────────

    /**
     * Include a view file, exposing all passed variables.
     *
     * @param string $viewPath Absolute path to the view file.
     * @param array  $data     Variables to extract into the view scope.
     */
    protected function view(string $viewPath, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require $viewPath;
    }

    /**
     * Redirect to a URL and stop execution.
     */
    protected function redirect(string $urlOrController, ?string $action = null, array $params = []): never
    {
        if ($action !== null) {
            header('Location: ' . routeUrl($urlOrController, $action, $params));
            exit;
        }

        header('Location: ' . $urlOrController);
        exit;
    }

    /**
     * Redirect using the application's front-controller routing.
     */
    protected function redirectToRoute(
        string $controller,
        string $action,
        array  $params = []
    ): never {
        $this->redirect($controller, $action, $params);
    }
}
