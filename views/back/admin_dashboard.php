<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<?php
// Prepare Chart.js data
$barLabels   = array_column($topObjects,   'nom_objet');
$barData     = array_column($topObjects,   'total');
$lineLabels  = [];
$lineData    = [];
foreach ($loansByMonth as $row) {
    $dt = DateTimeImmutable::createFromFormat('Y-m', $row['mois']);
    $lineLabels[] = $dt ? $dt->format('M Y') : $row['mois'];
    $lineData[]   = (int) $row['total'];
}
$onTime   = (int) ($returnRate['on_time']   ?? 0);
$enRetard = (int) ($returnRate['en_retard'] ?? 0);
?>

<!-- Page header + PDF button -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:14px;">
    <div>
        <h1 style="font-family:'Syne',sans-serif;font-size:26px;font-weight:700;color:var(--navy);">Tableau de bord</h1>
        <p style="color:var(--gray-500);font-size:14px;margin-top:4px;">Bienvenue sur votre espace d'administration MedChain.</p>
    </div>
    <a href="<?php echo htmlspecialchars(routeUrl('report', 'download', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>"
       style="display:inline-flex;align-items:center;gap:8px;padding:11px 22px;
              background:linear-gradient(135deg,#EF4444,#DC2626);color:#fff;border-radius:var(--radius-md);
              font-size:14px;font-weight:600;text-decoration:none;
              box-shadow:0 3px 12px rgba(239,68,68,.30);transition:all .25s;"
       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(239,68,68,.40)'"
       onmouseout="this.style.transform='';this.style.boxShadow='0 3px 12px rgba(239,68,68,.30)'">
        📥 Télécharger le Rapport (PDF)
    </a>
</div>

<!-- KPI cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="bi bi-box-seam-fill"></i></div>
        <div><div class="stat-number"><?php echo (int) $totalObjets; ?></div><div class="stat-label">Objets enregistrés</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
        <div><div class="stat-number"><?php echo (int) $pendingCount; ?></div><div class="stat-label">Demandes en attente</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="bi bi-arrow-repeat"></i></div>
        <div><div class="stat-number"><?php echo (int) $confirmedCount; ?></div><div class="stat-label">Prêts en cours</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="bi bi-check-circle-fill"></i></div>
        <div><div class="stat-number"><?php echo (int) $returnedCount; ?></div><div class="stat-label">Prêts terminés</div></div>
    </div>
</div>

<!-- Charts row: Bar + Pie -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

    <!-- Bar: Top 5 objects -->
    <div class="card" style="margin-bottom:0;">
        <div class="card-header">
            <span class="card-title"><i class="bi bi-bar-chart-fill"></i> Top 5 Objets Empruntés</span>
        </div>
        <div style="padding:20px;">
            <?php if (empty($topObjects)): ?>
                <p style="color:var(--gray-500);text-align:center;padding:20px;">Aucune donnée.</p>
            <?php else: ?>
                <canvas id="chartTopObjects" height="220"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pie: Return rate -->
    <div class="card" style="margin-bottom:0;">
        <div class="card-header">
            <span class="card-title"><i class="bi bi-pie-chart-fill"></i> Taux de Retour</span>
        </div>
        <div style="padding:20px;display:flex;flex-direction:column;align-items:center;">
            <?php if ($onTime === 0 && $enRetard === 0): ?>
                <p style="color:var(--gray-500);text-align:center;padding:20px;">Aucune donnée.</p>
            <?php else: ?>
                <canvas id="chartReturnRate" height="220" style="max-width:260px;"></canvas>
                <div style="display:flex;gap:20px;margin-top:14px;font-size:13px;">
                    <span style="display:flex;align-items:center;gap:6px;">
                        <span style="width:12px;height:12px;border-radius:50%;background:#1D9E75;display:inline-block;"></span>
                        À temps : <strong><?php echo $onTime; ?></strong>
                    </span>
                    <span style="display:flex;align-items:center;gap:6px;">
                        <span style="width:12px;height:12px;border-radius:50%;background:#EF4444;display:inline-block;"></span>
                        En retard : <strong><?php echo $enRetard; ?></strong>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Line chart: Loans by month (full width) -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-graph-up-arrow"></i> Activité des Prêts — 12 derniers mois</span>
    </div>
    <div style="padding:20px;">
        <?php if (empty($loansByMonth)): ?>
            <p style="color:var(--gray-500);text-align:center;padding:20px;">Aucune donnée.</p>
        <?php else: ?>
            <canvas id="chartLoansByMonth" height="100"></canvas>
        <?php endif; ?>
    </div>
</div>

<!-- Recent pending loans -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-clock-history"></i> Dernières demandes en attente</span>
        <a href="<?php echo htmlspecialchars(routeUrl('pret', 'pending', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn">
            <i class="bi bi-arrow-right"></i> Gérer les demandes
        </a>
    </div>
    <?php if (empty($recentPrets)): ?>
        <p style="color:var(--gray-500);padding:20px;">Aucune demande en attente pour le moment.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr><th>ID</th><th>Patient</th><th>Objet</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recentPrets as $pret): ?>
                    <tr>
                        <td>#<?php echo (int) $pret['id_pret']; ?></td>
                        <td><?php echo htmlspecialchars($pret['nom_patient'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($pret['nom_objet']  ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($pret['date_pret'])), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars(routeUrl('pret', 'confirm', ['office' => 'back', 'id' => (int) $pret['id_pret']]), ENT_QUOTES, 'UTF-8'); ?>"
                               class="btn btn-success" style="padding:6px 14px;font-size:12.5px;"
                               onclick="return confirm('Confirmer ce prêt ?');">
                                <i class="bi bi-check-lg"></i> Confirmer
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Quick actions -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;">
    <?php
    $quickActions = [
        ['url' => routeUrl('objet', 'list',     ['office' => 'back']), 'icon' => 'bi-box-seam-fill',   'label' => 'Gérer les objets'],
        ['url' => routeUrl('pret',  'list',     ['office' => 'back']), 'icon' => 'bi-list-ul',          'label' => 'Tous les prêts'],
        ['url' => routeUrl('pret',  'calendar', ['office' => 'back']), 'icon' => 'bi-calendar3',        'label' => 'Calendrier'],
        ['url' => routeUrl('objet', 'add',      ['office' => 'back']), 'icon' => 'bi-plus-circle-fill', 'label' => 'Ajouter un objet'],
    ];
    foreach ($quickActions as $qa):
    ?>
        <a href="<?php echo htmlspecialchars($qa['url'], ENT_QUOTES, 'UTF-8'); ?>"
           style="background:var(--white);border:2px dashed var(--gray-200);border-radius:var(--radius-lg);
                  padding:22px;text-align:center;display:flex;flex-direction:column;align-items:center;
                  gap:10px;transition:all .3s;color:var(--navy);text-decoration:none;"
           onmouseover="this.style.borderColor='var(--green)';this.style.transform='translateY(-2px)'"
           onmouseout="this.style.borderColor='var(--gray-200)';this.style.transform=''">
            <div style="width:52px;height:52px;border-radius:var(--radius-md);background:var(--green);
                        color:white;display:flex;align-items:center;justify-content:center;font-size:20px;">
                <i class="bi <?php echo $qa['icon']; ?>"></i>
            </div>
            <span style="font-weight:600;font-size:13.5px;"><?php echo htmlspecialchars($qa['label'], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
    <?php endforeach; ?>
</div>

<!-- Chart.js CDN + initialization -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const green     = '#1D9E75';
    const greenDark = '#0F6E56';
    const red       = '#EF4444';

    Chart.defaults.font.family = "'DM Sans', sans-serif";
    Chart.defaults.color       = '#6B7280';

    // ── Bar: Top 5 objects ────────────────────────────────────────────────────
    const ctxBar = document.getElementById('chartTopObjects');
    if (ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_values($barLabels), JSON_UNESCAPED_UNICODE); ?>,
                datasets: [{
                    label: "Emprunts",
                    data: <?php echo json_encode(array_map('intval', array_values($barData))); ?>,
                    backgroundColor: [
                        'rgba(29,158,117,.85)', 'rgba(29,158,117,.70)',
                        'rgba(29,158,117,.55)', 'rgba(29,158,117,.40)',
                        'rgba(29,158,117,.25)'
                    ],
                    borderColor: greenDark,
                    borderWidth: 1.5,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: c => ' ' + c.parsed.y + ' emprunt' + (c.parsed.y > 1 ? 's' : '') } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ── Line: Loans by month ──────────────────────────────────────────────────
    const ctxLine = document.getElementById('chartLoansByMonth');
    if (ctxLine) {
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_values($lineLabels), JSON_UNESCAPED_UNICODE); ?>,
                datasets: [{
                    label: "Prêts",
                    data: <?php echo json_encode(array_map('intval', array_values($lineData))); ?>,
                    borderColor: green,
                    backgroundColor: 'rgba(29,158,117,.08)',
                    borderWidth: 2.5,
                    pointBackgroundColor: green,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.35,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: c => ' ' + c.parsed.y + ' prêt' + (c.parsed.y > 1 ? 's' : '') } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ── Doughnut: Return rate ─────────────────────────────────────────────────
    const ctxPie = document.getElementById('chartReturnRate');
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['À temps', 'En retard'],
                datasets: [{
                    data: [<?php echo $onTime; ?>, <?php echo $enRetard; ?>],
                    backgroundColor: [green, red],
                    borderColor: ['#fff', '#fff'],
                    borderWidth: 3,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: c => ' ' + c.label + ' : ' + c.parsed + ' prêt' + (c.parsed > 1 ? 's' : '') } }
                }
            }
        });
    }
})();
</script>

<?php require BASE_PATH . '/views/templates/back/footer.php'; ?>
