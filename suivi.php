<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Post-Opératoire - MedChain</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .page-hero {
            background: linear-gradient(135deg, #0F6E56 0%, #1D9E75 100%);
            color: #fff;
            padding: 60px 5%;
            text-align: center;
        }
        .page-hero h1 { font-size: 2rem; margin-bottom: 12px; }
        .page-hero p  { font-size: 1.05rem; opacity: 0.9; max-width: 580px; margin: 0 auto; }

        .suivi-wrapper {
            max-width: 900px;
            margin: 50px auto;
            padding: 0 20px 60px;
        }
        .suivi-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e8e8e8;
            padding-bottom: 0;
        }
        .suivi-tab {
            padding: 11px 22px;
            cursor: pointer;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            font-size: 0.95rem;
            color: #888;
            border: none;
            background: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
        }
        .suivi-tab.active {
            color: var(--secondary);
            border-bottom-color: var(--primary);
        }
        .suivi-tab:hover { color: var(--primary); }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .suivi-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
            padding: 32px 36px;
            margin-bottom: 20px;
        }
        .suivi-card h2 {
            color: var(--secondary);
            margin-top: 0;
            font-size: 1.2rem;
            margin-bottom: 22px;
        }
        .engagement-list { list-style: none; padding: 0; margin: 0; }
        .engagement-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 13px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.97rem;
        }
        .engagement-item:last-child { border-bottom: none; }
        .engagement-item input[type="checkbox"] {
            width: 18px; height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
            flex-shrink: 0;
        }
        .engagement-item label { cursor: pointer; color: #333; flex: 1; }
        .engagement-item .badge {
            font-size: 0.78rem;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 600;
            background: #e8f7f1;
            color: var(--secondary);
        }
        .badge.urgent { background: #fef2f2; color: #dc2626; }
        .badge.normal { background: #eff6ff; color: #2563eb; }

        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #444;
            margin-bottom: 7px;
            font-size: 0.95rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.97rem;
            font-family: inherit;
            color: #333;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
        }
        .form-group textarea { resize: vertical; min-height: 90px; }
        .form-row { display: flex; gap: 18px; }
        .form-row .form-group { flex: 1; }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-submit:hover { opacity: 0.88; }
        .progress-bar-wrap {
            background: #e8e8e8;
            border-radius: 99px;
            height: 10px;
            margin-top: 6px;
        }
        .progress-bar-fill {
            height: 10px;
            border-radius: 99px;
            background: linear-gradient(90deg, var(--primary), #9FE1CB);
            transition: width 0.4s;
        }
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #888;
            margin-top: 5px;
        }
        .success-banner {
            display: none;
            background: #d1fae5;
            border: 1.5px solid var(--primary);
            border-radius: 10px;
            padding: 14px 20px;
            color: #065f46;
            font-weight: 600;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo"><a href="index.php"><img src="logo.PNG" alt="MedChain Logo"></a></div>
            <ul class="nav-links">
                <li class="dropdown">
                    <a href="#" class="dropbtn">Flotte & Missions ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=ambulance">Gestion Ambulances</a>
                        <a href="index.php?page=mission">Registre Missions</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Bloc opératoire ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=intervention">Interventions</a>
                        <a href="index.php?page=materiel">Matériel Médical</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Traçabilité ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=lot">Lots Médicaments</a>
                        <a href="index.php?page=distribution">Distributions</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Rendez-vous ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=rdv">Agenda RDV</a>
                        <a href="index.php?page=ficherdv">Fiches de RDV</a>
                    </div>
                </li>
                <li><a href="loisir.php">Loisir</a></li>
            </ul>
        </nav>
    </header>

    <div class="page-hero">
        <h1>🩺 Suivi Post-Opératoire</h1>
        <p>Un engagement qualité pour votre récupération — suivez vos instructions médicales étape par étape.</p>
    </div>

    <div class="suivi-wrapper">
        <div class="suivi-tabs">
            <button class="suivi-tab active" onclick="showTab('engagements', this)">📋 Engagements</button>
            <button class="suivi-tab" onclick="showTab('nouveau', this)">➕ Nouvel engagement</button>
            <button class="suivi-tab" onclick="showTab('historique', this)">📅 Historique</button>
        </div>

        <!-- Tab 1: Engagements -->
        <div class="tab-panel active" id="tab-engagements">
            <div class="suivi-card">
                <h2>Mes engagements post-opératoires</h2>
                <div style="margin-bottom:18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <span style="font-weight:600;color:#555;font-size:0.92rem;">Progression globale</span>
                        <span id="progressText" style="font-weight:700;color:var(--secondary);">2 / 6 complétés</span>
                    </div>
                    <div class="progress-bar-wrap">
                        <div class="progress-bar-fill" id="progressFill" style="width:33%"></div>
                    </div>
                </div>
                <ul class="engagement-list" id="engagementList">
                    <li class="engagement-item">
                        <input type="checkbox" id="e1" checked onchange="updateProgress()">
                        <label for="e1">Prendre le médicament anti-inflammatoire matin et soir</label>
                        <span class="badge urgent">Urgent</span>
                    </li>
                    <li class="engagement-item">
                        <input type="checkbox" id="e2" checked onchange="updateProgress()">
                        <label for="e2">Changer le pansement toutes les 48h</label>
                        <span class="badge normal">Normal</span>
                    </li>
                    <li class="engagement-item">
                        <input type="checkbox" id="e3" onchange="updateProgress()">
                        <label for="e3">Consultation de contrôle dans 7 jours</label>
                        <span class="badge">Planifié</span>
                    </li>
                    <li class="engagement-item">
                        <input type="checkbox" id="e4" onchange="updateProgress()">
                        <label for="e4">Éviter tout effort physique intense pendant 3 semaines</label>
                        <span class="badge normal">Normal</span>
                    </li>
                    <li class="engagement-item">
                        <input type="checkbox" id="e5" onchange="updateProgress()">
                        <label for="e5">Séance de kinésithérapie — 3 fois par semaine</label>
                        <span class="badge normal">Normal</span>
                    </li>
                    <li class="engagement-item">
                        <input type="checkbox" id="e6" onchange="updateProgress()">
                        <label for="e6">Alimentation légère et hydratation suffisante</label>
                        <span class="badge">Conseil</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Tab 2: Nouvel engagement -->
        <div class="tab-panel" id="tab-nouveau">
            <div class="suivi-card">
                <h2>Créer un nouvel engagement</h2>
                <form onsubmit="handleNewEngagement(event)">
                    <div class="form-group">
                        <label>Description de l'engagement</label>
                        <input type="text" placeholder="Ex: Prendre le médicament X deux fois par jour" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Priorité</label>
                            <select>
                                <option>Normal</option>
                                <option>Urgent</option>
                                <option>Conseil</option>
                                <option>Planifié</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date limite</label>
                            <input type="date">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes supplémentaires</label>
                        <textarea placeholder="Instructions détaillées ou observations..."></textarea>
                    </div>
                    <button type="submit" class="btn-submit">➕ Ajouter l'engagement</button>
                    <div class="success-banner" id="engagementBanner">✅ Engagement ajouté avec succès !</div>
                </form>
            </div>
        </div>

        <!-- Tab 3: Historique -->
        <div class="tab-panel" id="tab-historique">
            <div class="suivi-card">
                <h2>Historique des suivis</h2>
                <table style="width:100%;border-collapse:collapse;font-size:0.94rem;">
                    <thead>
                        <tr style="background:#f5f5f5;">
                            <th style="padding:11px 14px;text-align:left;color:#555;font-weight:600;border-bottom:2px solid #e8e8e8;">Date</th>
                            <th style="padding:11px 14px;text-align:left;color:#555;font-weight:600;border-bottom:2px solid #e8e8e8;">Engagement</th>
                            <th style="padding:11px 14px;text-align:left;color:#555;font-weight:600;border-bottom:2px solid #e8e8e8;">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding:11px 14px;color:#666;">2024-01-15</td>
                            <td style="padding:11px 14px;">Prise du médicament anti-inflammatoire</td>
                            <td style="padding:11px 14px;"><span style="color:#059669;font-weight:600;">✓ Complété</span></td>
                        </tr>
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding:11px 14px;color:#666;">2024-01-14</td>
                            <td style="padding:11px 14px;">Changement de pansement</td>
                            <td style="padding:11px 14px;"><span style="color:#059669;font-weight:600;">✓ Complété</span></td>
                        </tr>
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding:11px 14px;color:#666;">2024-01-12</td>
                            <td style="padding:11px 14px;">Séance de kinésithérapie</td>
                            <td style="padding:11px 14px;"><span style="color:#059669;font-weight:600;">✓ Complété</span></td>
                        </tr>
                        <tr>
                            <td style="padding:11px 14px;color:#666;">2024-01-10</td>
                            <td style="padding:11px 14px;">Consultation de contrôle</td>
                            <td style="padding:11px 14px;"><span style="color:#d97706;font-weight:600;">⏳ En cours</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function showTab(id, btn) {
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.suivi-tab').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + id).classList.add('active');
            btn.classList.add('active');
        }

        function updateProgress() {
            const boxes = document.querySelectorAll('#engagementList input[type="checkbox"]');
            const checked = [...boxes].filter(b => b.checked).length;
            const total = boxes.length;
            const pct = Math.round((checked / total) * 100);
            document.getElementById('progressFill').style.width = pct + '%';
            document.getElementById('progressText').textContent = checked + ' / ' + total + ' complétés';
        }

        function handleNewEngagement(e) {
            e.preventDefault();
            const banner = document.getElementById('engagementBanner');
            banner.style.display = 'block';
            e.target.reset();
            setTimeout(() => banner.style.display = 'none', 4000);
        }
    </script>
</body>
</html>
