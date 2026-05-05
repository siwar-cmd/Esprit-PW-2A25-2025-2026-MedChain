<?php require BASE_PATH . '/views/templates/back/header.php'; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-family:'Syne',sans-serif;font-size:24px;font-weight:700;color:var(--navy);">
            <i class="bi bi-calendar3" style="color:var(--green);"></i> Calendrier des Prêts
        </h1>
        <p style="color:var(--gray-500);font-size:14px;margin-top:4px;">
            Visualisez les prêts en cours et en attente sur une vue calendrier interactive.
        </p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="<?php echo htmlspecialchars(routeUrl('pret', 'list', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
            <i class="bi bi-list-ul"></i> Liste des prêts
        </a>
        <a href="<?php echo htmlspecialchars(routeUrl('admin', 'dashboard', ['office' => 'back']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </div>
</div>

<!-- Legend -->
<div style="display:flex;gap:16px;margin-bottom:16px;flex-wrap:wrap;">
    <span style="display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:600;color:var(--navy);">
        <span style="width:14px;height:14px;border-radius:3px;background:#1D9E75;display:inline-block;"></span>
        En cours
    </span>
    <span style="display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:600;color:var(--navy);">
        <span style="width:14px;height:14px;border-radius:3px;background:#F59E0B;display:inline-block;"></span>
        En attente
    </span>
</div>

<!-- Calendar container -->
<div class="card" style="margin-bottom:0;">
    <div style="padding:20px;">
        <div id="pret-calendar"></div>
    </div>
</div>

<!-- Event detail modal -->
<div id="eventModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;
     display:none;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:var(--radius-xl);padding:28px;max-width:420px;width:90%;
                box-shadow:0 20px 60px rgba(0,0,0,.20);position:relative;">
        <button onclick="closeModal()"
                style="position:absolute;top:14px;right:16px;background:none;border:none;
                       font-size:20px;cursor:pointer;color:var(--gray-500);">✕</button>
        <div id="modalContent"></div>
    </div>
</div>

<!-- FullCalendar CDN -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/fr.global.min.js"></script>

<script>
(function () {
    'use strict';

    const eventsUrl = <?php echo json_encode(
        routeUrl('pret', 'calendarEvents', ['office' => 'back']),
        JSON_UNESCAPED_UNICODE
    ); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        const calEl = document.getElementById('pret-calendar');

        const calendar = new FullCalendar.Calendar(calEl, {
            locale: 'fr',
            initialView: 'dayGridMonth',
            headerToolbar: {
                left:   'prev,next today',
                center: 'title',
                right:  'dayGridMonth,timeGridWeek,listMonth'
            },
            buttonText: {
                today:     "Aujourd'hui",
                month:     'Mois',
                week:      'Semaine',
                list:      'Liste',
            },
            height: 'auto',
            events: eventsUrl,
            eventDisplay: 'block',
            eventBorderRadius: '6px',
            dayMaxEvents: 3,
            eventClick: function (info) {
                const p = info.event.extendedProps;
                const start = info.event.startStr;
                const end   = info.event.endStr || start;

                const statusLabel = p.statut === 'en_cours' ? 'En cours' : 'En attente';
                const statusColor = p.statut === 'en_cours' ? '#1D9E75' : '#F59E0B';

                document.getElementById('modalContent').innerHTML = `
                    <div style="margin-bottom:16px;">
                        <span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;
                                     font-weight:700;background:${statusColor}22;color:${statusColor};">
                            ${statusLabel}
                        </span>
                    </div>
                    <h3 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;
                               color:#1E3A52;margin-bottom:16px;">${escHtml(p.objet)}</h3>
                    <div style="display:flex;flex-direction:column;gap:10px;font-size:14px;color:#374151;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <i class="bi bi-person-fill" style="color:#1D9E75;font-size:16px;width:20px;"></i>
                            <span>${escHtml(p.patient)}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <i class="bi bi-calendar-event-fill" style="color:#1D9E75;font-size:16px;width:20px;"></i>
                            <span>Du <strong>${formatDate(start)}</strong> au <strong>${formatDate(end)}</strong></span>
                        </div>
                    </div>
                    <div style="margin-top:20px;display:flex;gap:10px;">
                        <a href="<?php echo htmlspecialchars(routeUrl('pret', 'timeline', ['office' => 'back', 'id' => 0]), ENT_QUOTES, 'UTF-8'); ?>".replace('/id=0', '/id=' + ${info.event.id})
                           style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:6px;
                                  padding:9px 16px;background:linear-gradient(135deg,#1D9E75,#0F6E56);
                                  color:#fff;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;">
                            <i class="bi bi-clock-history"></i> Historique
                        </a>
                        <button onclick="closeModal()"
                                style="flex:1;padding:9px 16px;background:#f1f5f9;color:#1E3A52;
                                       border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">
                            Fermer
                        </button>
                    </div>
                `;
                document.getElementById('eventModal').style.display = 'flex';
            },
            eventDidMount: function (info) {
                // Tooltip on hover
                info.el.title = info.event.extendedProps.objet
                    + ' — ' + info.event.extendedProps.patient;
            }
        });

        calendar.render();
    });

    function closeModal() {
        document.getElementById('eventModal').style.display = 'none';
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }

    function formatDate(iso) {
        if (!iso) return '—';
        const [y, m, d] = iso.split('-');
        return d + '/' + m + '/' + y;
    }

    // Close modal on backdrop click
    document.getElementById('eventModal').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });
})();
</script>

<style>
    /* FullCalendar overrides to match MedChain design */
    .fc { font-family: 'DM Sans', sans-serif; }
    .fc .fc-toolbar-title { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700; color: #1E3A52; }
    .fc .fc-button-primary { background: linear-gradient(135deg,#1D9E75,#0F6E56); border-color: #0F6E56; font-weight: 600; }
    .fc .fc-button-primary:hover { background: linear-gradient(135deg,#0F6E56,#094D3C); border-color: #094D3C; }
    .fc .fc-button-primary:not(:disabled).fc-button-active { background: #0F6E56; border-color: #094D3C; }
    .fc .fc-daygrid-day-number { color: #1E3A52; font-weight: 500; }
    .fc .fc-col-header-cell-cushion { color: #6B7280; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; }
    .fc .fc-event { cursor: pointer; font-size: 12px; font-weight: 600; padding: 2px 6px; }
    .fc .fc-daygrid-day.fc-day-today { background: rgba(29,158,117,.06); }
    .fc .fc-list-event:hover td { background: rgba(29,158,117,.05); }
</style>

<?php require BASE_PATH . '/views/templates/back/footer.php'; ?>
