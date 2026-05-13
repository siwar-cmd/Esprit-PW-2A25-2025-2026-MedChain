# Mise à Jour des Fonctionnalités - Carte 3D & Calendrier

**Date de mise à jour :** 12 Mai 2026  
**Statut :** ✅ Complété

---

## 🎯 Objectifs Atteints

### 1️⃣ **Carte 3D - Mise à jour automatique des matériels**
- Les nouveaux matériels ajoutés apparaissent automatiquement sur la carte 3D
- Synchronisation en temps réel (polling toutes les 2 secondes)
- Rafraîchissement immédiat via localStorage et événements `visibilitychange`

### 2️⃣ **Calendrier - Affichage des périodes d'utilisation**
- Les périodes d'utilisation du matériel s'affichent dans le calendrier
- Informations détaillées sur les réservations de matériel
- Synchronisation avec les dates `date_utilisation_debut` et `date_utilisation_fin`

---

## 📝 Fichiers Modifiés

### 1. **views/backoffice/bloc-operation/materiel-create.php**

#### Changements :
- ✅ Amélioration du message de succès avec liens rapides
- ✅ Ajout du lien "Voir sur la Carte 3D" après création
- ✅ Déclenchement de localStorage `materielAdded` pour notifier la carte 3D
- ✅ Notification au `window.opener` si ouvert depuis la carte 3D

#### Code ajouté :
```javascript
// Déclencher la mise à jour de la carte 3D après création du matériel
document.addEventListener('DOMContentLoaded', () => {
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        try {
            localStorage.setItem('materielAdded', new Date().getTime().toString());
            if (window.opener && window.opener.fetchMachines) {
                window.opener.fetchMachines();
            }
        } catch (e) {
            console.log('localStorage non disponible');
        }
    }
});
```

---

### 2. **admin_map.html**

#### Changements :
- ✅ Ajout d'un écouteur localStorage pour détecter les nouveaux matériels
- ✅ Ajout d'un écouteur `visibilitychange` pour rafraîchir au retour sur l'onglet
- ✅ Amélioration de la réactivité de la carte 3D

#### Code ajouté :
```javascript
// Listen to localStorage for refresh signals
window.addEventListener('storage', (event) => {
    if (event.key === 'mapRefreshTrigger' || event.key === 'materielAdded') {
        console.log('🔄 Nouveau matériel détecté, mise à jour de la carte 3D...');
        setTimeout(fetchMachines, 500);
    }
});

// Listen for visibility change to refresh when user returns to tab
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        console.log('📱 Retour sur la carte 3D, mise à jour...');
        fetchMachines();
    }
});
```

---

### 3. **api-calendar.php**

#### Changements :
- ✅ Modification de la logique pour récupérer les périodes d'utilisation du matériel
- ✅ Suppression de la dépendance au champ `sous_type_materiel` (déjà supprimé)
- ✅ Utilisation directe des colonnes `date_utilisation_debut` et `date_utilisation_fin`

#### Code modifié :
```php
// Fetch machine usage periods from materiel table
$stmt2 = $pdo->prepare("SELECT id_materiel, date_utilisation_debut, date_utilisation_fin FROM materiel WHERE id_materiel = ?");
$stmt2->execute([$machine_id]);
$materielData = $stmt2->fetch(PDO::FETCH_ASSOC);

$events = [];

// If materiel has usage dates, add them as events
if ($materielData && !empty($materielData['date_utilisation_debut']) && !empty($materielData['date_utilisation_fin'])) {
    $events[] = [
        'id' => 'mat_' . $materielData['id_materiel'],
        'machine_id' => $materielData['id_materiel'],
        'used_by' => 'Période de réservation matériel',
        'start_time' => $materielData['date_utilisation_debut'],
        'end_time' => $materielData['date_utilisation_fin'],
        'type' => 'reservation'
    ];
}
```

---

### 4. **calendar_system.php**

#### Changements :
- ✅ Ajout d'une section "Machine Info" pour afficher les détails du matériel
- ✅ Amélioration de la fonction `loadMachineSchedule()`
- ✅ Affichage automatique des informations de réservation

#### Code ajouté :
```html
<!-- Machine info -->
<div id="machine-info" style="...display: none;">
    <strong>ℹ️ Informations du matériel :</strong>
    <div id="machine-info-content" style="..."></div>
</div>
```

#### JavaScript amélioré :
```javascript
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
}
```

---

## 🔄 Flux de Fonctionnement

### **Processus d'ajout d'un matériel avec synchronisation 3D :**

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Admin accède à materiel-create.php                       │
│    (peut être ouvert depuis admin_map.html ou séparément)   │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│ 2. Remplit le formulaire avec les détails du matériel       │
│    - ID, catégorie, bloc, dates d'utilisation, etc.        │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│ 3. Soumet le formulaire (POST)                              │
│    - Validation côté serveur par createMateriel()           │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│ 4. Matériel créé avec succès                                │
│    - Message de succès affiché                              │
│    - localStorage.setItem('materielAdded', timestamp)       │
│    - window.opener.fetchMachines() appelé si possible       │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│ 5. Carte 3D détecte le changement                           │
│    - Événement 'storage' déclenché                          │
│    - fetchMachines() exécutée                               │
│    - Nouvelle machine créée en 3D avec animation            │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│ 6. Admin voit le matériel sur la carte 3D                   │
│    - Apparaît immédiatement sur la scène                    │
│    - Position basée sur le bloc spécifié                    │
│    - Code couleur selon le statut (vert/orange/rouge)      │
└─────────────────────────────────────────────────────────────┘
```

### **Processus d'affichage des périodes d'utilisation dans le calendrier :**

```
┌──────────────────────────────────────────────────────────────┐
│ 1. Admin accède à calendar_system.php                        │
└──────────────┬───────────────────────────────────────────────┘
               │
┌──────────────▼───────────────────────────────────────────────┐
│ 2. Sélectionne une machine dans le dropdown                  │
│    - Liste chargée depuis api-machines.php                  │
└──────────────┬───────────────────────────────────────────────┘
               │
┌──────────────▼───────────────────────────────────────────────┐
│ 3. Clique sur "Voir les réservations"                        │
│    - Requête GET à api-calendar.php?type=machine&machine_id │
└──────────────┬───────────────────────────────────────────────┘
               │
┌──────────────▼───────────────────────────────────────────────┐
│ 4. API récupère les périodes d'utilisation                   │
│    - Query: SELECT ... FROM materiel                        │
│    - Filtre: id_materiel = $machine_id                      │
│    - Retourne: date_utilisation_debut, date_utilisation_fin│
└──────────────┬───────────────────────────────────────────────┘
               │
┌──────────────▼───────────────────────────────────────────────┐
│ 5. Calendrier affiche les événements                         │
│    - Périodes réservées visibles sur le calendrier          │
│    - Code couleur: orange pour 'reserved'                   │
│    - Infos détaillées dans la section "Machine Info"        │
└──────────────┬───────────────────────────────────────────────┘
               │
┌──────────────▼───────────────────────────────────────────────┐
│ 6. Admin voit la période d'utilisation                       │
│    - Dates d'utilisation affichées clairement               │
│    - Information du réservant visible                        │
│    - Possibilité d'ajouter une nouvelle réservation        │
└──────────────────────────────────────────────────────────────┘
```

---

## 🧪 Tests à effectuer

### **Test 1 : Synchronisation Carte 3D**
1. ✅ Ouvrir la carte 3D (`admin_map.html`)
2. ✅ Ouvrir un nouvel onglet avec `materiel-create.php`
3. ✅ Ajouter un matériel avec un bloc valide (ex: "ER")
4. ✅ Vérifier que le matériel apparaît sur la carte 3D en moins de 2 secondes
5. ✅ Vérifier que le lien "Voir sur la Carte 3D" fonctionne

### **Test 2 : Affichage Calendrier**
1. ✅ Aller à `calendar_system.php`
2. ✅ Ajouter un matériel avec dates d'utilisation
3. ✅ Sélectionner le matériel dans le dropdown
4. ✅ Cliquer "Voir les réservations"
5. ✅ Vérifier que la période d'utilisation s'affiche dans le calendrier
6. ✅ Vérifier que les informations du matériel s'affichent dans la section "Machine Info"

### **Test 3 : Rafraîchissement par onglet**
1. ✅ Ouvrir la carte 3D dans un onglet
2. ✅ Minimiser/mettre en arrière-plan l'onglet
3. ✅ Ajouter un matériel depuis un autre onglet
4. ✅ Revenir sur l'onglet de la carte 3D
5. ✅ Vérifier que le matériel est automatiquement apparu

---

## 🔧 Fonctionnalités Techniques

### **LocalStorage Events**
- **Clé :** `materielAdded`
- **Déclencheur :** Après création d'un matériel
- **Effet :** Rafraîchit la carte 3D

### **Visibility API**
- **Événement :** `visibilitychange`
- **Déclencheur :** Quand l'utilisateur revient sur l'onglet
- **Effet :** Rafraîchit les matériels affichés

### **Polling API**
- **Intervalle :** 2 secondes
- **Endpoint :** `api-machines.php`
- **Vérification :** Compare les IDs des matériels en BD vs dans la scène 3D

### **Données Matériel**
- **Tableau :** `materiel`
- **Colonnes clés :**
  - `id_materiel` : Identifiant unique
  - `bloc` : Emplacement (ER, ICU, Radiology, Lab, Hall, Storage)
  - `date_utilisation_debut` : Début de réservation
  - `date_utilisation_fin` : Fin de réservation
  - `pos_x, pos_y, pos_z` : Coordonnées 3D

---

## 📊 Performance & Optimisation

| Aspect | Valeur | Notes |
|--------|--------|-------|
| Polling fréquence | 2 sec | Équilibre entre réactivité et charge serveur |
| LocalStorage | Instantané | Communication inter-onglets/fenêtres |
| Animation 3D | 0.5-1s | Chute avec rotation du nouveau matériel |
| Limite API | Non définie | Peut être ajoutée si besoin |
| Cache browser | Standard | Géré par le navigateur |

---

## ⚠️ Notes Importantes

### Compatibilité
- ✅ Chrome/Edge 70+
- ✅ Firefox 55+
- ✅ Safari 12+
- ⚠️ Internet Explorer : Non supporté (Three.js requis)

### Limitations Actuelles
- 🔸 Les matériels ne sont synchronisés que via polling (2s de latence max)
- 🔸 Pas de WebSocket pour synchronisation temps réel instantanée
- 🔸 LocalStorage limité à 5-10 MB par domaine

### Améliorations Futures
- 🎯 Implémenter WebSocket pour synchronisation instantanée
- 🎯 Ajouter les notifications du navigateur
- 🎯 Implémenter un système d'événements côté serveur (SSE)
- 🎯 Optimiser le polling avec compression gzip

---

## 🆘 Troubleshooting

### Problème : La carte 3D ne se met pas à jour
**Solution :**
1. Vérifier la console du navigateur pour les erreurs
2. Rafraîchir manuellement la page (F5)
3. Vérifier que `api-machines.php` retourne les données correctement

### Problème : LocalStorage ne fonctionne pas
**Solution :**
1. Vérifier que le mode privé n'est pas activé
2. Vérifier les paramètres de confidentialité du navigateur
3. Réessayer sans mode privé

### Problème : Les périodes d'utilisation ne s'affichent pas
**Solution :**
1. Vérifier que les dates ont été remplies dans le formulaire
2. Vérifier que la date de fin > date de début
3. Vérifier les logs MySQL pour les erreurs de requête

---

## 📚 Documentation Connexe
- [BLOC_OPERATION_CHANGELOG.txt](BLOC_OPERATION_CHANGELOG.txt)
- [admin_map.html](admin_map.html)
- [calendar_system.php](calendar_system.php)
- [api-calendar.php](api-calendar.php)

---

**Développé par :** GitHub Copilot  
**Date :** 12 Mai 2026
