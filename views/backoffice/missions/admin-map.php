<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../frontoffice/auth/login.php'); exit;
}
require_once __DIR__ . '/../../../controllers/AmbulanceMissionController.php';
require_once __DIR__ . '/../../../controllers/AuthController.php';

$auth = new AuthController();
$user = $auth->getCurrentUser();
$userName = $user ? $user->getPrenom().' '.$user->getNom() : 'Administrateur';
$userId   = $user ? $user->getId() : null;

$ctrl = new AmbulanceMissionController();

// AJAX HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    if ($_POST['ajax_action'] === 'save_demande') {
        $res = $ctrl->createDemandeFromMap([
            'idUtilisateur' => $userId,
            'lieu_depart' => $_POST['lieu_depart'],
            'lieu_destination' => $_POST['lieu_destination'],
            'distance' => $_POST['distance'],
            'temps_estime' => $_POST['temps_estime']
        ]);
        echo json_encode($res);
        exit;
    }
}

$ambulances = $ctrl->getAmbulancesForMap();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte Interactive – Admin – MedChain</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../components/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- LEAFLET CSS & JS (FREE) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- LEAFLET ROUTING MACHINE (FREE) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

    <!-- LEAFLET GEOCODER (SEARCH BAR - FREE) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

    <style>
        :root {
            --teal: #1D9E75; --teal-dark: #0F6E56; --navy: #0F172A;
            --white: #fff; --bg: #f8fafc; --border: #e2e8f0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); display: flex; height: 100vh; overflow: hidden; }
        
        .main-container { flex: 1; display: flex; flex-direction: column; position: relative; }
        #map { flex: 1; width: 100%; height: 100%; z-index: 1; }
        
        .control-panel {
            position: absolute; top: 20px; left: 20px; width: 320px;
            background: var(--white); border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 24px; z-index: 1000; border: 1px solid var(--border);
        }
        .control-panel h2 { font-family: 'Syne', sans-serif; font-size: 18px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; color: var(--navy); }
        .control-panel h2 i { color: var(--teal); }
        
        .info-box { background: #f1f5f9; border-radius: 12px; padding: 15px; margin-bottom: 15px; font-size: 13px; }
        .info-box p { margin-bottom: 8px; display: flex; justify-content: space-between; }
        .info-box strong { color: var(--navy); }
        
        .btn-request {
            width: 100%; padding: 14px; background: linear-gradient(135deg, var(--teal), var(--teal-dark));
            color: #fff; border: none; border-radius: 12px; font-weight: 700; cursor: pointer;
            transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-request:disabled { background: #cbd5e1; cursor: not-allowed; }
        .btn-request:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(29,158,117,0.3); }

        .status-badge {
            position: absolute; top: 20px; right: 20px; background: rgba(15, 23, 42, 0.9);
            color: #fff; padding: 10px 20px; border-radius: 50px; font-size: 13px; z-index: 1000;
            display: flex; align-items: center; gap: 10px;
        }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); } 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); } }
        
        /* Hide Leaflet Routing Machine default panel */
        .leaflet-routing-container { display: none; }
    </style>
</head>
<body>

    <?php include '../components/sidebar-admin.php'; ?>

    <div class="main-container">
        <div class="status-badge">
            <div class="dot"></div>
            Leaflet OpenSource Mode
        </div>

        <div class="control-panel">
            <h2><i class="bi bi-lightning-fill"></i> Dispatch Intelligent</h2>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Double-cliquez pour définir le départ, puis la destination.</p>
            
            <div class="info-box">
                <p><span>Distance :</span> <strong id="val-distance">—</strong></p>
                <p><span>Temps estimé :</span> <strong id="val-time">—</strong></p>
                <p><span>Ambulance suggérée :</span> <strong id="val-amb">—</strong></p>
            </div>

            <button class="btn-request" id="btnSend" disabled>
                <i class="bi bi-send-check-fill"></i> Envoyer la demande
            </button>
        </div>

        <div id="map"></div>
    </div>

    <script>
        let map, routingControl;
        let startMarker, endMarker;
        let startPos = null, endPos = null;
        const ambulances = <?= json_encode($ambulances) ?>;
        const ambMarkers = [];

        function initMap() {
            // Center of Tunisia
            map = L.map('map').setView([36.8065, 10.1815], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // ADD SEARCH BAR (GEOCODER)
            L.Control.geocoder({
                defaultMarkGeocode: false,
                placeholder: "🔍 Rechercher un lieu...",
                errorMessage: "Lieu non trouvé."
            })
            .on('markgeocode', function(e) {
                const center = e.geocode.center;
                map.setView(center, 16);
                
                Swal.fire({
                    title: 'Lieu trouvé',
                    text: `Voulez-vous définir "${e.geocode.name}" comme point de départ ?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, Départ',
                    cancelButtonText: 'Non, juste voir',
                    confirmButtonColor: '#1D9E75'
                }).then(r => {
                    if(r.isConfirmed) {
                        setStartPoint(center);
                    }
                });
            })
            .addTo(map);

            // Create custom icons
            const ambIconFree = L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/3063/3063174.png', // Small ambulance icon
                iconSize: [32, 32], iconAnchor: [16, 32], popupAnchor: [0, -32]
            });
            const ambIconBusy = L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/3063/3063174.png',
                iconSize: [32, 32], iconAnchor: [16, 32], popupAnchor: [0, -32],
                className: 'grayscale' // We can add a CSS filter for busy status
            });

            // Load Ambulances
            ambulances.forEach(amb => {
                if (amb.lat && amb.lng) {
                    const m = L.marker([amb.lat, amb.lng], { icon: ambIconFree }).addTo(map);
                    m.bindPopup(`<strong>${amb.immatriculation}</strong><br>${amb.modele}<br>${amb.statut}`);
                    ambMarkers.push({ ...amb, marker: m });
                }
            });

            // Click to select points
            map.on('dblclick', (e) => {
                if (!startPos) {
                    startPos = e.latlng;
                    startMarker = L.marker(startPos, { draggable: true }).addTo(map).bindTooltip("Départ (A)", {permanent: true});
                } else if (!endPos) {
                    endPos = e.latlng;
                    endMarker = L.marker(endPos, { draggable: true }).addTo(map).bindTooltip("Arrivée (B)", {permanent: true});
                    calculateRoute();
                } else {
                    // Reset
                    if(routingControl) map.removeControl(routingControl);
                    if(startMarker) map.removeLayer(startMarker);
                    if(endMarker) map.removeLayer(endMarker);
                    startPos = e.latlng;
                    endPos = null;
                    startMarker = L.marker(startPos, { draggable: true }).addTo(map).bindTooltip("Départ (A)", {permanent: true});
                    document.getElementById('btnSend').disabled = true;
                    document.getElementById('val-distance').innerText = "—";
                    document.getElementById('val-time').innerText = "—";
                    document.getElementById('val-amb').innerText = "—";
                }
            });
            
            // Disable double click zoom to allow point selection
            map.doubleClickZoom.disable();
        }

        function setStartPoint(latlng) {
            if(startMarker) map.removeLayer(startMarker);
            if(endMarker) map.removeLayer(endMarker);
            if(routingControl) map.removeControl(routingControl);
            
            startPos = latlng;
            endPos = null;
            startMarker = L.marker(startPos, { draggable: true }).addTo(map).bindTooltip("Départ (A)", {permanent: true});
            
            document.getElementById('btnSend').disabled = true;
            document.getElementById('val-distance').innerText = "—";
            document.getElementById('val-time').innerText = "—";
            document.getElementById('val-amb').innerText = "—";
        }

        function calculateRoute() {
            if(routingControl) map.removeControl(routingControl);

            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(startPos.lat, startPos.lng),
                    L.latLng(endPos.lat, endPos.lng)
                ],
                routeWhileDragging: false,
                createMarker: function() { return null; }, // Hide default markers
                lineOptions: {
                    styles: [{ color: '#1D9E75', weight: 6, opacity: 0.8 }]
                }
            }).on('routesfound', function(e) {
                const routes = e.routes;
                const summary = routes[0].summary;
                document.getElementById('val-distance').innerText = (summary.totalDistance / 1000).toFixed(2) + ' km';
                document.getElementById('val-time').innerText = Math.round(summary.totalTime / 60) + ' min';
                findNearestAmbulance(startPos);
                document.getElementById('btnSend').disabled = false;
            }).addTo(map);
        }

        function findNearestAmbulance(location) {
            let nearest = null;
            let minDist = Infinity;

            ambMarkers.forEach(amb => {
                if (amb.estDisponible == 1) {
                    const dist = location.distanceTo(amb.marker.getLatLng());
                    if (dist < minDist) {
                        minDist = dist;
                        nearest = amb;
                    }
                }
            });

            if (nearest) {
                document.getElementById('val-amb').innerText = nearest.immatriculation + ' (' + (minDist/1000).toFixed(1) + ' km)';
            } else {
                document.getElementById('val-amb').innerText = "Aucune disponible";
            }
        }

        document.getElementById('btnSend').addEventListener('click', () => {
            Swal.fire({
                title: 'Confirmer la demande',
                text: "Une demande d'urgence sera envoyée pour l'itinéraire sélectionné.",
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#1D9E75',
                confirmButtonText: 'Confirmer'
            }).then(r => {
                if (r.isConfirmed) {
                    const formData = new FormData();
                    formData.append('ajax_action', 'save_demande');
                    formData.append('lieu_depart', 'Position A (Carte)');
                    formData.append('lieu_destination', 'Position B (Carte)');
                    formData.append('distance', document.getElementById('val-distance').innerText);
                    formData.append('temps_estime', document.getElementById('val-time').innerText);

                    fetch('admin-map.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            Swal.fire('Succès', 'Demande enregistrée et envoyée à l\'administration !', 'success').then(() => {
                                window.location.href = 'admin-demandes.php';
                            });
                        } else {
                            Swal.fire('Erreur', data.message, 'error');
                        }
                    });
                }
            });
        });

// Toggle Accordions
document.addEventListener('DOMContentLoaded', function() {
    function setupToggle(toggleId, submenuId, chevronId) {
        const toggle = document.getElementById(toggleId);
        const submenu = document.getElementById(submenuId);
        const chevron = document.getElementById(chevronId);
        
        if (toggle && submenu && chevron) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const isHidden = submenu.style.display === 'none' || submenu.style.display === '';
                submenu.style.display = isHidden ? 'flex' : 'none';
                chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            });
        }
    }
    
    setupToggle('blocOpToggle', 'blocOpSubmenu', 'blocOpChevron');
    setupToggle('flotteToggle', 'flotteSubmenu', 'flotteChevron');
});

window.onload = initMap;
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>
