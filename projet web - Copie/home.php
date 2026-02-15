<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedChain | Bienvenue</title>
    <meta name="description" content="MedChain - Plateforme médicale intelligente de gestion hospitalière">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="home.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Animated background particles -->
    <div class="bg-particles">
        <span></span><span></span><span></span>
        <span></span><span></span><span></span>
    </div>

    <!-- Navbar -->
    <header class="home-navbar">
        <div class="home-logo">
            <a href="home.php">
                <img src="logo.PNG" alt="MedChain Logo">
            </a>
        </div>
        <nav class="home-nav-links">
            <a href="home.php?role=admin" class="home-btn home-btn-admin" id="btn-admin-nav">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Admin
            </a>
            <a href="home.php?role=client" class="home-btn home-btn-client" id="btn-client-nav">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Client
            </a>
        </nav>
    </header>

    <!-- Hero Section -->
    <main class="home-main">
        <section class="home-hero">
            <div class="home-hero-text">
                <div class="home-badge">🏥 Système de Santé Intelligent</div>
                <h1 class="home-title">
                    Bienvenue sur <span class="home-gradient-text">MedChain</span>
                </h1>
                <p class="home-subtitle">
                    Accédez à la plateforme de gestion médicale avancée. Choisissez votre espace pour continuer.
                </p>
                <div class="home-cta-group">
                    <a href="login.php?role=admin" class="home-cta home-cta-admin" id="btn-admin-hero">
                        <div class="home-cta-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div class="home-cta-text">
                            <span class="home-cta-label">Espace Admin</span>
                            <span class="home-cta-desc">Accès complet au système</span>
                        </div>
                        <svg class="home-cta-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <a href="login.php?role=client" class="home-cta home-cta-client" id="btn-client-hero">
                        <div class="home-cta-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div class="home-cta-text">
                            <span class="home-cta-label">Espace Client</span>
                            <span class="home-cta-desc">Vos rendez-vous & dossiers</span>
                        </div>
                        <svg class="home-cta-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
            <div class="home-hero-image">
                <div class="home-image-wrapper">
                    <img src="image acc.PNG" alt="Équipe Médicale MedChain">
                    <div class="home-image-badge home-badge-top">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        Système Actif 24/7
                    </div>
                    <div class="home-image-badge home-badge-bottom">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Données Sécurisées
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature Cards -->
        <section class="home-features">
            <div class="home-feature-card">
                <div class="home-feature-icon" style="background: linear-gradient(135deg,#1D9E75,#0F6E56)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                </div>
                <h3>Gestion Complète</h3>
                <p>Ambulances, missions, interventions et matériel médical centralisés.</p>
            </div>
            <div class="home-feature-card">
                <div class="home-feature-icon" style="background: linear-gradient(135deg,#3b82f6,#1d4ed8)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                </div>
                <h3>Rendez-vous</h3>
                <p>Agenda intelligent avec fiches de suivi et notifications automatiques.</p>
            </div>
            <div class="home-feature-card">
                <div class="home-feature-icon" style="background: linear-gradient(135deg,#f59e0b,#d97706)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3>Traçabilité</h3>
                <p>Suivi complet des lots de médicaments et distributions contrôlées.</p>
            </div>
        </section>
    </main>
</body>
</html>
