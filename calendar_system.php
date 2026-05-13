<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Système de Réservation et Calendrier</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            padding: 20px;
        }
        h1, h2 { color: #0f172a; }
        
        .nav-btn {
            background-color: #3b82f6; color: white; border: none; padding: 10px 20px; 
            border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 1rem;
            text-decoration: none; display: inline-block; margin-bottom: 20px;
        }
        .nav-btn:hover { background-color: #2563eb; }

        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .panel {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; }
        input, select { 
            width: 100%; padding: 10px; border: 1px solid #cbd5e1; 
            border-radius: 6px; box-sizing: border-box;
        }
        
        button {
            background-color: #10b981; color: white; border: none; 
            padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%;
        }
        button:hover { background-color: #059669; }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        .schedule-table th, .schedule-table td {
            border: 1px solid #e2e8f0;
            padding: 12px;
            text-align: left;
        }
        .schedule-table th { background-color: #f1f5f9; }
        .slot {
            background-color: #e0f2fe; color: #0369a1; padding: 5px; 
            border-radius: 4px; margin-bottom: 5px; font-weight: 600; font-size: 0.85rem;
        }

        /* Simple Modal */
        #modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 100;
        }
        .modal-content {
            background: white; padding: 30px; border-radius: 12px; width: 400px;
        }
        .close { float: right; cursor: pointer; font-size: 1.5rem; }

        .error { color: #ef4444; margin-top: 10px; font-weight: bold; }
        .success { color: #10b981; margin-top: 10px; font-weight: bold; }

    </style>
    <script src="calendar-component.js"></script>
</head>
<body>

    <a href="views/backoffice/admin-dashboard.php" class="nav-btn">&larr; Retour au Dashboard</a>
    <h1>Calendrier des Rendez-vous & Réservations</h1>

    <div class="container">
        <!-- DOCTOR PANEL -->
        <div class="panel">
            <h2>👨‍⚕️ Calendrier Médecin</h2>
            <div class="form-group">
                <label for="doctor-select">Sélectionner un Médecin (ID)</label>
                <input type="number" id="doctor-select" placeholder="Entrez l'ID du médecin (ex: 1)" value="1">
                <button onclick="loadDoctorSchedule()" style="margin-top: 10px; background-color: #3b82f6;">Charger l'agenda</button>
            </div>
            
            <button onclick="openModal('doctor')" style="margin-bottom: 20px;">+ Nouveau Rendez-vous</button>

            <div id="doctor-calendar-wrapper" style="margin-top:20px;">
                <div style="text-align:center; padding:50px; background:white; border:1px solid #e2e8f0; border-radius:8px;">Veuillez charger un agenda</div>
            </div>
        </div>

        <!-- MACHINE PANEL -->
        <div class="panel">
            <h2>🏥 Calendrier Matériel / Machines</h2>
            <div class="form-group">
                <label for="machine-select">Sélectionner une Machine</label>
                <select id="machine-select">
                    <option value="">Chargement...</option>
                </select>
                <button onclick="loadMachineSchedule()" style="margin-top: 10px; background-color: #3b82f6;">Voir les réservations</button>
            </div>

            <button onclick="openModal('machine')" style="margin-bottom: 20px;">+ Nouvelle Réservation</button>
            <!-- Machine info -->
            <div id=\"machine-info\" style=\"margin-bottom: 15px; padding: 15px; background: #e0f2fe; border-left: 4px solid #0284c7; border-radius: 4px; display: none;\">
                <strong>ℹ️ Informations du matériel :</strong>
                <div id=\"machine-info-content\" style=\"margin-top: 10px; font-size: 0.9rem;\"></div>
            </div>
            <div id="machine-calendar-wrapper" style="margin-top:20px;">
                <div style="text-align:center; padding:50px; background:white; border:1px solid #e2e8f0; border-radius:8px;">Veuillez sélectionner une machine</div>
            </div>
        </div>
    </div>

    <!-- BOOKING MODAL -->
    <div id="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modal-title">Nouvelle Réservation</h2>
            <div id="modal-msg"></div>

            <input type="hidden" id="modal-type">

            <div class="form-group" id="group-patient" style="display:none;">
                <label>Nom du Patient</label>
                <input type="text" id="input-patient" placeholder="Ex: Jean Dupont">
            </div>

            <div class="form-group" id="group-usedby" style="display:none;">
                <label>Médecin / Service (Utilisé par)</label>
                <input type="text" id="input-usedby" placeholder="Ex: Dr. Martin">
            </div>

            <div class="form-group">
                <label>Date</label>
                <input type="date" id="input-date">
            </div>
            
            <div class="form-group">
                <label>Heure de Début</label>
                <input type="time" id="input-start">
            </div>

            <div class="form-group">
                <label>Heure de Fin</label>
                <input type="time" id="input-end">
            </div>

            <button onclick="submitBooking()">Confirmer</button>
        </div>
    </div>

    <script>
        // API Base
        const API = 'api-calendar.php';

        // Load Machines into Select
        async function fetchMachines() {
            try {
                const res = await fetch('api-machines.php');
                const machines = await res.json();
                const select = document.getElementById('machine-select');
                select.innerHTML = '<option value="">Choisir une machine...</option>';
                machines.forEach(m => {
                    select.innerHTML += `<option value="${m.name}">${m.name} (${m.type})</option>`;
                });
            } catch (e) { console.error(e); }
        }
        fetchMachines();

        let doctorCal = null;
        let machineCal = null;

        // Load Doctor Schedule
        async function loadDoctorSchedule() {
            const docId = document.getElementById('doctor-select').value;
            if(!docId) return;
            
            const res = await fetch(`${API}?type=doctor&doctor_id=${docId}`);
            const json = await res.json();
            
            if(!doctorCal) {
                doctorCal = new HospitalCalendar('doctor-calendar-wrapper', {
                    startHour: 8, endHour: 20,
                    onSlotClick: (date, time) => {
                        document.getElementById('input-date').value = date;
                        document.getElementById('input-start').value = time;
                        document.getElementById('input-end').value = time; // default 0 mins duration? user will adjust
                        openModal('doctor');
                    }
                });
            }

            if(json.success) {
                const evs = json.data.map(item => ({
                    id: item.id, start_time: item.start_time, end_time: item.end_time, title: item.patient_name, status: 'reserved'
                }));
                doctorCal.setEvents(evs);
            }
        }

        // Load Machine Schedule
        async function loadMachineSchedule() {
            const machId = document.getElementById('machine-select').value;
            if(!machId) return;
            
            // Charger les réservations et les infos du matériel
            const res = await fetch(`${API}?type=machine&machine_id=${machId}`);
            const json = await res.json();
            
            if(!machineCal) {
                machineCal = new HospitalCalendar('machine-calendar-wrapper', {
                    startHour: 8, endHour: 20,
                    onSlotClick: (date, time) => {
                        document.getElementById('input-date').value = date;
                        document.getElementById('input-start').value = time;
                        document.getElementById('input-end').value = time;
                        openModal('machine');
                    }
                });
            }

            if(json.success) {
                // Mapper les événements
                const evs = json.data.map(item => ({
                    id: item.id, 
                    start_time: item.start_time, 
                    end_time: item.end_time, 
                    title: item.used_by || item.machine_id, 
                    status: item.type === 'reservation' ? 'reserved' : 'in_use'
                }));
                machineCal.setEvents(evs);
                
                // Afficher les informations du matériel s'il y a une réservation
                if(json.data.length > 0) {
                    const machineInfo = document.getElementById('machine-info');
                    const machineInfoContent = document.getElementById('machine-info-content');
                    
                    const item = json.data[0];
                    const startDate = new Date(item.start_time);
                    const endDate = new Date(item.end_time);
                    
                    machineInfoContent.innerHTML = `
                        <div><strong>Machine :</strong> ${item.machine_id}</div>
                        <div><strong>Type :</strong> ${item.type === 'reservation' ? 'Période de réservation' : 'Utilisation'}</div>
                        <div><strong>Début :</strong> ${startDate.toLocaleDateString('fr-FR')} à ${startDate.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})}</div>
                        <div><strong>Fin :</strong> ${endDate.toLocaleDateString('fr-FR')} à ${endDate.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})}</div>
                        <div><strong>Réservé par :</strong> ${item.used_by || 'N/A'}</div>
                    `;
                    machineInfo.style.display = 'block';
                } else {
                    document.getElementById('machine-info').style.display = 'none';
                }
            }
        }

        // Modal Logic
        function openModal(type) {
            document.getElementById('modal').style.display = 'flex';
            document.getElementById('modal-type').value = type;
            document.getElementById('modal-msg').innerHTML = '';
            
            if (type === 'doctor') {
                document.getElementById('modal-title').innerText = 'Nouveau RDV Médecin';
                document.getElementById('group-patient').style.display = 'block';
                document.getElementById('group-usedby').style.display = 'none';
            } else {
                document.getElementById('modal-title').innerText = 'Nouvelle Réservation Machine';
                document.getElementById('group-patient').style.display = 'none';
                document.getElementById('group-usedby').style.display = 'block';
            }
        }

        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }

        // Submit Booking (Checks constraints natively)
        async function submitBooking() {
            const type = document.getElementById('modal-type').value;
            const msgBox = document.getElementById('modal-msg');
            msgBox.innerHTML = 'Traitement...';

            let payload = {
                type: type,
                date: document.getElementById('input-date').value,
                start_time: document.getElementById('input-start').value,
                end_time: document.getElementById('input-end').value
            };

            if(type === 'doctor') {
                payload.doctor_id = document.getElementById('doctor-select').value;
                payload.patient_name = document.getElementById('input-patient').value;
                if(!payload.doctor_id) { msgBox.innerHTML = '<div class="error">Sélectionnez un médecin avant d\'ajouter.</div>'; return; }
            } else {
                payload.machine_id = document.getElementById('machine-select').value;
                payload.used_by = document.getElementById('input-usedby').value;
                if(!payload.machine_id) { msgBox.innerHTML = '<div class="error">Sélectionnez une machine avant d\'ajouter.</div>'; return; }
            }

            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();

                if(res.status === 409) {
                    msgBox.innerHTML = `<div class="error">❌ ${json.message} (Chevauchement de dates détecté)</div>`;
                } else if(json.success) {
                    msgBox.innerHTML = `<div class="success">✅ Réservé avec succès !</div>`;
                    setTimeout(() => {
                        closeModal();
                        if(type === 'doctor') loadDoctorSchedule();
                        else loadMachineSchedule();
                    }, 1000);
                } else {
                    msgBox.innerHTML = `<div class="error">❌ ${json.message}</div>`;
                }
            } catch (err) {
                msgBox.innerHTML = `<div class="error">Erreur serveur.</div>`;
            }
        }

        // Delete Booking
        async function deleteBooking(type, id) {
            if(!confirm("Supprimer cette réservation ?")) return;
            const res = await fetch(`${API}?type=${type}&id=${id}`, { method: 'DELETE' });
            if(res.ok) {
                if(type === 'doctor') loadDoctorSchedule();
                else loadMachineSchedule();
            }
        }
    </script>
</body>
</html>
