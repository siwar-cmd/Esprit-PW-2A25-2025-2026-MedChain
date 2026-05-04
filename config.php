<?php
/**
 * MedChain — Root Configuration Shim
 *
 * Single entry point for the database connection used by ALL modules.
 * Delegates to the canonical Database singleton in /core/Database.php.
 *
 * This file exists for backward-compatibility with scripts that
 * require_once the root config.php directly.
 */
require_once __DIR__ . '/core/Database.php';
