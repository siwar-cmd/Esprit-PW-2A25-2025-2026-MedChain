<?php
declare(strict_types=1);

/**
 * MedChain — Unified Database Connection
 *
 * Singleton PDO wrapper.  Every model, controller and cron script must use:
 *     Database::getInstance()
 *
 * The legacy `config::getConnexion()` helper is kept as an alias so that the
 * old user/projet files continue to work without modification during the
 * migration period.
 */
final class Database
{
    private static ?PDO $instance = null;

    // ── Connection constants — change only here ──────────────────
    private const DB_HOST    = 'localhost';
    private const DB_NAME    = 'medchain';    // unified database name
    private const DB_USER    = 'root';
    private const DB_PASS    = '';
    private const DB_CHARSET = 'utf8mb4';

    /** Prevent direct instantiation */
    private function __construct() {}
    private function __clone()      {}

    /**
     * Returns the shared PDO instance, creating it on first call.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::DB_HOST,
                self::DB_NAME,
                self::DB_CHARSET
            );

            self::$instance = new MedChainPDO($dsn, self::DB_USER, self::DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        }

        return self::$instance;
    }
}

final class MedChainPDO extends PDO
{
    public function getConnection(): PDO
    {
        return $this;
    }
}

// ────────────────────────────────────────────────────────────────────────────
//  Backward-compatibility shim
//  Any file that still calls  config::getConnexion()  will work transparently.
// ────────────────────────────────────────────────────────────────────────────
if (!class_exists('config')) {
    class config
    {
        public static function getConnexion(): PDO
        {
            return Database::getInstance();
        }
    }
}
