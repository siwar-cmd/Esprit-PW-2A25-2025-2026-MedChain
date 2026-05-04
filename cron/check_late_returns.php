<?php
/**
 * MedChain — Cron: Check Late Returns
 *
 * Run daily via cron/scheduler:
 *   php C:\xampp\htdocs\midchaine\cron\check_late_returns.php
 *
 * Or Windows Task Scheduler:
 *   Program: C:\xampp\php\php.exe
 *   Arguments: C:\xampp\htdocs\midchaine\cron\check_late_returns.php
 *
 * Logic:
 *   1. Find all loans WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE()
 *   2. Update their statut to 'en_retard'
 *   3. Send a reminder email to the patient
 */
declare(strict_types=1);

// ─── Bootstrap ──────────────────────────────────────────────
$ROOT = dirname(__DIR__);
require_once $ROOT . '/config.php';
require_once $ROOT . '/models/ObjetLoisir.php';
require_once $ROOT . '/models/Pret.php';
require_once $ROOT . '/services/EmailService.php';

echo "[" . date('Y-m-d H:i:s') . "] MedChain Cron — Checking late returns...\n";

// ─── Find overdue loans ─────────────────────────────────────
$overdueLoans = Pret::findOverdue();
$count = count($overdueLoans);

if ($count === 0) {
    echo "  → No overdue loans found. All good.\n";
    exit(0);
}

echo "  → Found {$count} overdue loan(s).\n";

$updated  = 0;
$emailed  = 0;
$errors   = 0;

foreach ($overdueLoans as $loan) {
    $loanId     = (int) $loan['id_pret'];
    $patientNom = $loan['patient_nom'] ?? 'Patient';
    $email      = $loan['patient_email'] ?? '';
    $objetNom   = $loan['objet_nom'] ?? 'Objet';
    $retourPrevu = $loan['date_retour_prevue'] ?? '—';

    // 1. Mark as overdue
    if (Pret::markOverdue($loanId)) {
        $updated++;
        echo "  ✓ Loan #{$loanId} ({$objetNom}) marked as 'en_retard'\n";

        // 2. Send reminder email
        if ($email !== '') {
            $result = EmailService::notifyLoanOverdue($email, $patientNom, $objetNom, $retourPrevu);
            if ($result['success']) {
                $emailed++;
                echo "    ✉ Email sent to {$email}\n";
            } else {
                $errors++;
                echo "    ✗ Email failed: " . ($result['message'] ?? 'unknown') . "\n";
            }
        }
    } else {
        $errors++;
        echo "  ✗ Failed to update loan #{$loanId}\n";
    }
}

echo "\n[SUMMARY] Updated: {$updated} | Emails: {$emailed} | Errors: {$errors}\n";
echo "[" . date('Y-m-d H:i:s') . "] Done.\n";

exit($errors > 0 ? 1 : 0);
