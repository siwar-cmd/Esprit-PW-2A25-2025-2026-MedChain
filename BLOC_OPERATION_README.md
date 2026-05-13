# 🏥 Bloc Opération - Documentation Complète

## 📋 Table des Matières
1. [Aperçu](#aperçu)
2. [Installation](#installation)
3. [Architecture](#architecture)
4. [Fonctionnalités](#fonctionnalités)
5. [Permissions](#permissions)
6. [API](#api)
7. [Guide d'Utilisation](#guide-dutilisation)

---

## 🎯 Aperçu

Le **Bloc Opération** est un module complet de gestion hospitalière avec deux entités principales :
- **Matériel** : Gestion des équipements médicaux
- **Intervention** : Gestion des opérations chirurgicales/médicales

### Caractéristiques Principales
✅ CRUD complet pour les deux entités
✅ Recherche dynamique en temps réel
✅ Statistiques et analytics
✅ Export PDF détaillé
✅ Système de permissions (Admin/Médecin)
✅ Interface responsive et moderne
✅ API REST JSON pour intégration

---

## 🚀 Installation

### 1. Importer la Base de Données

```bash
# Ouvrir phpMyAdmin et exécuter :
mysql -u root -p user < /xampp/htdocs/projet/projet/bloc_operation.sql

# OU copier-coller le contenu de bloc_operation.sql dans l'onglet SQL
```

### 2. Vérifier la Configuration

```php
// Dans config.php, s'assurer que la BD est correcte :
'mysql:host=localhost;dbname=user'
```

### 3. Installer TCPDF (Optionnel pour PDF)

```bash
cd /xampp/htdocs/projet/projet
composer require tecnickcom/tcpdf
```

---

## 🏗️ Architecture

### Structure des Dossiers

```
projet/
├── models/
│   ├── Materiel.php           (Modèle Matériel)
│   ├── Intervention.php       (Modèle Intervention)
│   └── Utilisateur.php        (Existant)
├── controllers/
│   ├── BlocOperationController.php (Logique principale)
│   └── ...autres
└── views/backoffice/
    ├── bloc-operation/
    │   ├── materiel-*.php     (5 fichiers)
    │   ├── intervention-*.php (5 fichiers)
    │   ├── api-*.php          (2 fichiers API)
    │   └── *-export-pdf.php   (2 fichiers)
    └── admin-dashboard.php    (Modifié)
```

### Modèle de Données

**Matériel**
```
id_materiel (PK) → Auto increment
├── nom (255) → Required
├── description (TEXT)
├── quantite (INT) → Default: 0
├── prix_unitaire (DECIMAL)
├── fournisseur (255)
├── date_acquisition (DATE)
├── statut (ENUM) → disponible|en_maintenance|hors_service
├── created_by (FK) → utilisateur
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)
```

**Intervention**
```
id_intervention (PK) → Auto increment
├── titre (255) → Required
├── description (TEXT)
├── date_intervention (DATETIME) → Required
├── medecin_id (FK) → utilisateur → Required
├── patient_id (FK) → utilisateur → Nullable
├── statut (ENUM) → planifiee|en_cours|terminee|annulee
├── resultat_intervention (TEXT)
├── notes_medecin (TEXT)
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)
```

**Liaison Intervention ↔ Matériel**
```
intervention_materiel
├── id (PK)
├── intervention_id (FK) → intervention
├── materiel_id (FK) → materiel
├── quantite_utilisee (INT) → Default: 1
└── UNIQUE (intervention_id, materiel_id)
```

---

## ✨ Fonctionnalités

### Matériel

| Fonction | Admin | Médecin |
|----------|-------|---------|
| **CREATE** | ✅ | ❌ |
| **READ** | ✅ | ✅ |
| **UPDATE** | ✅ | ✅ |
| **DELETE** | ✅ | ❌ |
| **SEARCH** | ✅ | ✅ |
| **STATS** | ✅ | ❌ |
| **EXPORT PDF** | ✅ | ❌ |

### Intervention

| Fonction | Admin | Médecin |
|----------|-------|---------|
| **CREATE** | ✅ | ✅ |
| **READ** | ✅ | ✅ |
| **UPDATE** | ✅ | ✅* |
| **DELETE** | ✅ | ❌ |
| **SEARCH** | ✅ | ✅ |
| **STATS** | ✅ | ❌ |
| **EXPORT PDF** | ✅ | ❌ |

*Médecin peut modifier seulement ses propres interventions

---

## 🔐 Permissions Détaillées

### Contrôle d'Accès

```php
// Dans BlocOperationController.php

// Matériel
if ($this->user_role !== 'admin') return error; // CREATE
if (!in_array($this->user_role, ['admin', 'medecin'])) return error; // READ/UPDATE

// Intervention
if (!in_array($this->user_role, ['admin', 'medecin'])) return error; // All

// Médecin : Vérification supplémentaire
if ($this->user_role === 'medecin' && $data['medecin_id'] !== $this->user_id)
    return error; // Ne peut pas modifier les autres
```

---

## 📡 API Endpoints

### Materiel API

```
GET  /api-materiel.php?action=list&page=1
     → Retourne liste paginnée

GET  /api-materiel.php?action=stats
     → Retourne statistiques

GET  /api-materiel.php?action=search&keyword=xyz
     → Retourne résultats recherche

POST /api-materiel.php
     body: {action: 'create', nom: '...', ...}
     → Crée matériel

POST /api-materiel.php
     body: {action: 'update', id: 1, nom: '...', ...}
     → Modifie matériel

POST /api-materiel.php
     body: {action: 'delete', id: 1}
     → Supprime matériel
```

### Intervention API

Mêmes endpoints pour `/api-intervention.php`

---

## 👥 Guide d'Utilisation

### Pour l'Admin

1. **Accéder au Bloc Opération**
   - Dashboard → Bloc Opération (nouvelle section)
   - Ou lien direct : `admin-dashboard.php`

2. **Gérer Matériel**
   - Cliquer "Matériel" dans le menu
   - Ajouter : Bouton "+ Ajouter Matériel"
   - Modifier : Icône ✏️ dans le tableau
   - Supprimer : Icône 🗑️ dans le tableau
   - Rechercher : Champ de recherche dynamique
   - Exporter : Bouton "Exporter en PDF" (nécessite TCPDF)
   - Stats : Cartes statistiques automatiquement chargées

3. **Gérer Interventions**
   - Même interface que Matériel
   - Sélectionner médecin lors de la création
   - Voir statut (Planifiée/En cours/Terminée/Annulée)

### Pour le Médecin

1. **Accéder au Bloc Opération**
   - Via lien direct ou menu si présent
   - `materiel-index.php` pour matériel
   - `intervention-index.php` pour interventions

2. **Matériel**
   - Lire liste complète ✅
   - Modifier quantité/statut ✅
   - Créer : ❌ (Admin only)
   - Supprimer : ❌ (Admin only)
   - Stats : ❌ (Admin only)

3. **Interventions**
   - Voir uniquement ses interventions ✅
   - Créer nouvelles ✅
   - Modifier les siennes ✅
   - Modifier autres : ❌ (Accès refusé)
   - Supprimer : ❌ (Admin only)
   - Stats : ❌ (Admin only)

---

## 🔄 Flux de Travail

### Création d'un Matériel

```
Admin → Matériel → + Ajouter
    ↓
Remplir formulaire (nom*, quantité*, prix*)
    ↓
Soumettre
    ↓
INSERT in materiel
    ↓
Redirection vers liste
```

### Modification d'une Intervention

```
Médecin → Intervention → ✏️ Modifier
    ↓
Vérifier proprietaire (medecin_id == user_id)
    ↓
Remplir formulaire
    ↓
Soumettre
    ↓
UPDATE intervention
    ↓
Redirection vers détails
```

---

## 📊 Statistiques

### Matériel Stats

- **Total Matériels** : COUNT(*)
- **Quantité Totale** : SUM(quantite)
- **Valeur du Stock** : SUM(quantite * prix_unitaire)
- **Par Statut** : GROUP BY statut

### Intervention Stats

- **Total Interventions** : COUNT(*)
- **Par Statut** : GROUP BY statut
  - Planifiée
  - En cours
  - Terminée
  - Annulée

---

## 🎨 Interface

### Thème Couleurs
- **Primaire** : #1D9E75 (Vert)
- **Secondaire** : #0F6E56 (Vert foncé)
- **Fond** : Gradient léger vert
- **Texte** : #1f2937 (Gris foncé)

### Composants
- Cards responsive
- Tableaux avec pagination
- Formulaires avec validation
- Badges de statut
- Modales confirmation (SweetAlert2)

---

## 🛠️ Dépannage

### PDF ne télécharge pas
**Solution** : Installer TCPDF
```bash
composer require tecnickcom/tcpdf
```

### Erreur 404
**Solution** : Vérifier chemins relatifs des fichiers

### Permissions refusées
**Solution** : Vérifier `$_SESSION['user_role']` dans le navigateur

### Recherche ne fonctionne pas
**Solution** : Vérifier connexion BD et tables importées

---

## 📝 Fichiers Modifiés

- ✏️ `/views/backoffice/admin-dashboard.php`
  - Ajout navigation Bloc Opération
  - Ajout quick actions
  - Ajout buttons d'accès

---

## 📚 Ressources

- [TCPDF Documentation](https://tcpdf.org/)
- [Bootstrap Classes](https://getbootstrap.com/docs/)
- [SweetAlert2](https://sweetalert2.github.io/)
- [Font Awesome Icons](https://fontawesome.com/)

---

## 📞 Support

Pour tout problème :
1. Vérifier les logs dans `error_log`
2. Consulter la console navigateur (F12)
3. Vérifier permissions DB et tables

---

**Création** : Mai 2026
**Version** : 1.0
**Statut** : Production Ready ✅
