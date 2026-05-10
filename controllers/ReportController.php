<?php

class ReportController
{
    public function downloadPdf(): void
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            redirectToRoute('objet', 'list', ['office' => 'front']);
        }

        $stats       = new StatistiqueController();
        $globalStats = $stats->getGlobalStats();
        $overdueLoans = $stats->getOverdueLoans();   // JOIN utilisateur already done
        $activeLoans  = $stats->getActiveLoans();    // new: en_cours + en_attente with patient names
        $topObjects   = $stats->getTopObjects(5);

        $tcpdfPath = BASE_PATH . '/vendor/tcpdf/tcpdf.php';

        if (file_exists($tcpdfPath)) {
            $this->generateWithTcpdf($globalStats, $overdueLoans, $activeLoans, $topObjects, $tcpdfPath);
        } else {
            $this->generateHtmlFallback($globalStats, $overdueLoans, $activeLoans, $topObjects);
        }
    }

    // ── TCPDF ─────────────────────────────────────────────────────────────────

    private function generateWithTcpdf(
        array $global, array $overdue, array $active, array $top, string $path
    ): void {
        require_once $path;

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('MedChain');
        $pdf->SetAuthor('Administration MedChain');
        $pdf->SetTitle('Rapport des Prêts — MedChain');
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $html = $this->buildHtmlContent($global, $overdue, $active, $top);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('rapport_prets_' . date('Y-m-d') . '.pdf', 'D');
        exit;
    }

    // ── HTML fallback (browser print-to-PDF) ──────────────────────────────────

    private function generateHtmlFallback(
        array $global, array $overdue, array $active, array $top
    ): void {
        if (ob_get_level()) ob_end_clean();

        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: inline; filename="rapport_prets_' . date('Y-m-d') . '.html"');

        $css = '
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11.5px; color: #1a202c; padding: 24px; }

        /* ── Header ── */
        .rpt-header { text-align: center; padding-bottom: 18px; margin-bottom: 26px;
                      border-bottom: 3px solid #1D9E75; }
        .rpt-header h1 { font-size: 20px; color: #1D9E75; letter-spacing: -.3px; }
        .rpt-header p  { color: #6B7280; font-size: 10.5px; margin-top: 5px; }

        /* ── Section titles ── */
        .rpt-section { font-size: 13px; font-weight: bold; color: #1E3A52;
                       margin: 24px 0 10px; padding: 6px 12px;
                       border-left: 4px solid #1D9E75;
                       background: #f0fdf4; border-radius: 0 6px 6px 0; }

        /* ── KPI grid ── */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr);
                    gap: 10px; margin-bottom: 6px; }
        .kpi-box  { background: #f0fdf4; border: 1px solid #bbf7d0;
                    border-radius: 8px; padding: 12px; text-align: center; }
        .kpi-num  { font-size: 22px; font-weight: bold; color: #1D9E75; line-height: 1; }
        .kpi-lbl  { font-size: 10px; color: #6B7280; margin-top: 4px; }

        /* ── Tables ── */
        table   { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        thead th { background: #1E3A52; color: #fff; padding: 8px 10px;
                   text-align: left; font-size: 10.5px; font-weight: 600;
                   letter-spacing: .03em; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #E5E7EB;
                   font-size: 10.5px; vertical-align: middle; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        tbody tr:hover td { background: #f0fdf4; }

        /* ── Badges ── */
        .badge { display: inline-block; padding: 2px 9px; border-radius: 10px;
                 font-size: 10px; font-weight: 700; }
        .badge-retard  { background: #fef2f2; color: #dc2626; }
        .badge-cours   { background: #eff6ff; color: #1d4ed8; }
        .badge-attente { background: #fff7ed; color: #c2410c; }

        /* ── Patient cell ── */
        .patient-cell { display: flex; flex-direction: column; }
        .patient-name { font-weight: 600; color: #1E3A52; }
        .patient-email{ font-size: 9.5px; color: #6B7280; margin-top: 1px; }

        /* ── Footer ── */
        .rpt-footer { margin-top: 32px; text-align: center; font-size: 10px;
                      color: #9CA3AF; border-top: 1px solid #E5E7EB; padding-top: 12px; }

        /* ── Empty state ── */
        .empty-state { color: #16A34A; font-weight: bold; padding: 10px 0; font-size: 12px; }

        @media print {
            body { padding: 0; }
            .rpt-section { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }';

        echo '<!DOCTYPE html><html lang="fr"><head>';
        echo '<meta charset="UTF-8">';
        echo '<title>Rapport MedChain — ' . date('d/m/Y') . '</title>';
        echo '<style>' . $css . '</style>';
        echo '<script>window.onload = function(){ window.print(); }</script>';
        echo '</head><body>';
        echo $this->buildHtmlContent($global, $overdue, $active, $top);
        echo '</body></html>';
        exit;
    }

    // ── Shared HTML builder ───────────────────────────────────────────────────

    private function buildHtmlContent(
        array $global, array $overdue, array $active, array $top
    ): string {
        $date = date('d/m/Y à H:i');
        $h    = '';

        // ── Report header ─────────────────────────────────────────────────────
        $h .= '<div class="rpt-header">';
        $h .= '<h1>🏥 MedChain — Rapport des Prêts d\'Objets Loisir</h1>';
        $h .= '<p>Généré le ' . htmlspecialchars($date, ENT_QUOTES, 'UTF-8')
            . ' &nbsp;·&nbsp; Administration MedChain</p>';
        $h .= '</div>';

        // ── Global KPIs ───────────────────────────────────────────────────────
        $h .= '<div class="rpt-section">📊 Statistiques Globales</div>';
        $h .= '<div class="kpi-grid">';
        $kpis = [
            ['Total prêts',   $global['total_prets']],
            ['En attente',    $global['en_attente']],
            ['En cours',      $global['en_cours']],
            ['Terminés',      $global['termine']],
            ['Annulés',       $global['annule']],
            ['En retard',     $global['en_retard']],
            ['Objets loisir', $global['total_objets']],
            ['Patients',      $global['total_patients']],
        ];
        foreach ($kpis as [$label, $value]) {
            $h .= '<div class="kpi-box">';
            $h .= '<div class="kpi-num">' . (int) $value . '</div>';
            $h .= '<div class="kpi-lbl">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</div>';
            $h .= '</div>';
        }
        $h .= '</div>';

        // ── Top 5 objects ─────────────────────────────────────────────────────
        $h .= '<div class="rpt-section">🏆 Top 5 Objets les Plus Empruntés</div>';
        if (empty($top)) {
            $h .= '<p class="empty-state">Aucune donnée disponible.</p>';
        } else {
            $h .= '<table><thead><tr>'
                . '<th style="width:40px;">#</th>'
                . '<th>Objet</th>'
                . '<th style="width:140px;">Nombre d\'emprunts</th>'
                . '</tr></thead><tbody>';
            foreach ($top as $i => $row) {
                $h .= '<tr>';
                $h .= '<td>' . ($i + 1) . '</td>';
                $h .= '<td>' . htmlspecialchars($row['nom_objet'], ENT_QUOTES, 'UTF-8') . '</td>';
                $h .= '<td>' . (int) $row['total'] . '</td>';
                $h .= '</tr>';
            }
            $h .= '</tbody></table>';
        }

        // ── Overdue loans — NOW WITH "Nom du Patient" column ─────────────────
        $h .= '<div class="rpt-section">⚠️ Prêts en Retard ('
            . count($overdue) . ')</div>';

        if (empty($overdue)) {
            $h .= '<p class="empty-state">✅ Aucun prêt en retard actuellement.</p>';
        } else {
            $h .= '<table><thead><tr>'
                . '<th style="width:40px;">ID</th>'
                . '<th>Nom du Patient</th>'
                . '<th>Email</th>'
                . '<th>Objet</th>'
                . '<th style="width:90px;">Retour prévu</th>'
                . '<th style="width:90px;">Retard</th>'
                . '</tr></thead><tbody>';

            foreach ($overdue as $row) {
                $patientName  = htmlspecialchars($row['nom_patient']    ?? '—', ENT_QUOTES, 'UTF-8');
                $patientEmail = htmlspecialchars($row['patient_email']  ?? '—', ENT_QUOTES, 'UTF-8');
                $nomObjet     = htmlspecialchars($row['nom_objet']       ?? '—', ENT_QUOTES, 'UTF-8');
                $retourPrevu  = !empty($row['date_retour_prevue'])
                    ? date('d/m/Y', strtotime($row['date_retour_prevue']))
                    : '—';
                $joursRetard  = max(0, (int) ($row['jours_retard'] ?? 0));

                $h .= '<tr>';
                $h .= '<td>#' . (int) $row['id_pret'] . '</td>';
                $h .= '<td>'
                    . '<div class="patient-cell">'
                    . '<span class="patient-name">' . $patientName . '</span>'
                    . '<span class="patient-email">' . $patientEmail . '</span>'
                    . '</div>'
                    . '</td>';
                $h .= '<td>' . $patientEmail . '</td>';
                $h .= '<td>' . $nomObjet . '</td>';
                $h .= '<td>' . $retourPrevu . '</td>';
                $h .= '<td><span class="badge badge-retard">+' . $joursRetard . ' j</span></td>';
                $h .= '</tr>';
            }
            $h .= '</tbody></table>';
        }

        // ── NEW SECTION: Active loans detail ─────────────────────────────────
        $enCoursCount  = count(array_filter($active, fn($r) => $r['statut'] === 'en_cours'));
        $enAttenteCount = count(array_filter($active, fn($r) => $r['statut'] === 'en_attente'));

        $h .= '<div class="rpt-section">📋 Détails des Prêts Actifs'
            . ' <span style="font-weight:400;font-size:11px;color:#6B7280;">'
            . '(' . $enCoursCount . ' en cours &nbsp;·&nbsp; ' . $enAttenteCount . ' en attente)'
            . '</span></div>';

        if (empty($active)) {
            $h .= '<p class="empty-state">✅ Aucun prêt actif pour le moment.</p>';
        } else {
            $h .= '<table><thead><tr>'
                . '<th style="width:50px;"># ID Prêt</th>'
                . '<th>Nom du Patient</th>'
                . '<th>Objet</th>'
                . '<th style="width:90px;">Date de prêt</th>'
                . '<th style="width:90px;">Retour prévu</th>'
                . '<th style="width:80px;">Statut</th>'
                . '</tr></thead><tbody>';

            foreach ($active as $row) {
                $patientName = htmlspecialchars(
                    trim(($row['patient_prenom'] ?? '') . ' ' . ($row['patient_nom'] ?? '')),
                    ENT_QUOTES, 'UTF-8'
                );
                $patientEmail = htmlspecialchars($row['patient_email'] ?? '—', ENT_QUOTES, 'UTF-8');
                $nomObjet     = htmlspecialchars($row['nom_objet']      ?? '—', ENT_QUOTES, 'UTF-8');
                $datePret     = !empty($row['date_pret'])
                    ? date('d/m/Y', strtotime($row['date_pret']))
                    : '—';
                $retourPrevu  = !empty($row['date_retour_prevue'])
                    ? date('d/m/Y', strtotime($row['date_retour_prevue']))
                    : '—';

                $isEnCours    = $row['statut'] === 'en_cours';
                $badgeClass   = $isEnCours ? 'badge-cours' : 'badge-attente';
                $badgeLabel   = $isEnCours ? 'En cours'   : 'En attente';

                $h .= '<tr>';
                $h .= '<td>#' . (int) $row['id_pret'] . '</td>';
                $h .= '<td>'
                    . '<div class="patient-cell">'
                    . '<span class="patient-name">' . $patientName . '</span>'
                    . '<span class="patient-email">' . $patientEmail . '</span>'
                    . '</div>'
                    . '</td>';
                $h .= '<td>' . $nomObjet . '</td>';
                $h .= '<td>' . $datePret . '</td>';
                $h .= '<td>' . $retourPrevu . '</td>';
                $h .= '<td><span class="badge ' . $badgeClass . '">' . $badgeLabel . '</span></td>';
                $h .= '</tr>';
            }
            $h .= '</tbody></table>';
        }

        // ── Footer ────────────────────────────────────────────────────────────
        $h .= '<div class="rpt-footer">'
            . 'MedChain &nbsp;·&nbsp; Rapport confidentiel &nbsp;·&nbsp; '
            . htmlspecialchars($date, ENT_QUOTES, 'UTF-8')
            . '</div>';

        return $h;
    }
}
