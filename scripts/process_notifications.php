<?php
/**
 * Script de traitement des notifications automatisées.
 * Ce script est destiné à être lancé via une tâche Cron (ex: une fois par jour à 8h).
 */

require_once __DIR__ . '/../controllers/NotificationController.php';

echo "Démarrage du traitement des notifications [" . date('Y-m-d H:i:s') . "]\n";

$notificationController = new NotificationController();

// 1. Rappels de RDV (24h avant)
echo "Traitement des rappels de RDV...\n";
$remindersResult = $notificationController->sendAppointmentReminders();
if ($remindersResult['success']) {
    echo "Succès : " . $remindersResult['sent'] . " rappels envoyés.\n";
} else {
    echo "Erreur lors des rappels : " . $remindersResult['message'] . "\n";
}

// 2. Notifications de suivi (Prochain RDV)
echo "Traitement des suivis recommandés...\n";
$followUpResult = $notificationController->sendFollowUpNotifications();
if ($followUpResult['success']) {
    echo "Succès : " . $followUpResult['sent'] . " notifications de suivi envoyées.\n";
} else {
    echo "Erreur lors des suivis : " . $followUpResult['message'] . "\n";
}

echo "Traitement terminé [" . date('Y-m-d H:i:s') . "]\n";
