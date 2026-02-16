<?php
session_start();
require_once __DIR__ . '/../../../controllers/AuthController.php';

$current_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$current_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

$old_ip = $_SESSION['ia_last_ip'] ?? null;
$old_agent = $_SESSION['ia_last_agent'] ?? null;

$alerts = [];
if ($old_ip && $old_ip !== $current_ip) {
    $alerts[] = "⚠️ Nouvelle adresse IP détectée : $current_ip";
}
if ($old_agent && $old_agent !== $current_agent) {
    $alerts[] = "⚠️ Nouveau navigateur détecté";
}

$_SESSION['ia_last_ip'] = $current_ip;
$_SESSION['ia_last_agent'] = $current_agent;
$ia_security_alert = $alerts ? implode("<br>", $alerts) : null;

$authController = new AuthController();
$isLoggedIn = $authController->isLoggedIn();

if ($isLoggedIn) {
    $currentUser = $authController->getCurrentUser();
    $currentUserArray = $currentUser ? (method_exists($currentUser, 'toArray') ? $currentUser->toArray() : (array)$currentUser) : null;
    $userRole = $currentUser ? $currentUser->getRole() : null;
    $userName = $currentUser ? $currentUser->getPrenom() . ' ' . $currentUser->getNom() : 'Utilisateur';
} else {
    $currentUserArray = null;
    $currentUser = null;
    $userRole = null;
    $userName = null;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MedChain — Confiance & Clarté pour Votre Suivi Médical</title>

  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />

  <style>
    /* ══════════════════════════════════════
       RESET & ROOT VARIABLES
    ══════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --green:        #1D9E75;
      --green-dark:   #0F6E56;
      --green-deep:   #094D3C;
      --green-light:  #E8F7F2;
      --green-pale:   #F2FBF7;
      --navy:         #1E3A52;
      --navy-light:   #2C4964;
      --gray-700:     #374151;
      --gray-500:     #6B7280;
      --gray-200:     #E5E7EB;
      --gray-100:     #F9FAFB;
      --white:        #ffffff;
      --shadow-sm:    0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
      --shadow-md:    0 4px 16px rgba(0,0,0,.08);
      --shadow-lg:    0 12px 40px rgba(0,0,0,.10);
      --shadow-green: 0 8px 30px rgba(29,158,117,.22);
      --radius-sm:    8px;
      --radius-md:    12px;
      --radius-lg:    20px;
      --radius-xl:    28px;
    }

    html { scroll-behavior: smooth; }
    body {
      font-family: 'DM Sans', sans-serif;
      color: var(--gray-700);
      background: var(--white);
      line-height: 1.65;
      -webkit-font-smoothing: antialiased;
    }

    a { color: inherit; text-decoration: none; }
    img { display: block; max-width: 100%; }

    /* ══ PRELOADER ══ */
    #preloader {
      position: fixed; inset: 0; z-index: 9999;
      background: var(--white);
      display: flex; align-items: center; justify-content: center;
    }
    #preloader::after {
      content: '';
      width: 38px; height: 38px;
      border: 3px solid var(--green-light);
      border-top-color: var(--green);
      border-radius: 50%;
      animation: spin .7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ══════════════════════════════════════
       TOPBAR
    ══════════════════════════════════════ */
    .mc-topbar {
      background: var(--green-dark);
      padding: 8px 0;
      font-size: 12.5px;
      color: rgba(255,255,255,.85);
      letter-spacing: .01em;
    }
    .mc-topbar .inner {
      max-width: 1240px; margin: 0 auto; padding: 0 28px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .tb-left { display: flex; gap: 22px; align-items: center; }
    .tb-left span { display: flex; align-items: center; gap: 7px; }
    .tb-left a { color: rgba(255,255,255,.85); transition: color .2s; }
    .tb-left a:hover { color: #fff; }
    .tb-socials { display: flex; gap: 7px; }
    .tb-socials a {
      color: rgba(255,255,255,.7);
      width: 26px; height: 26px;
      display: flex; align-items: center; justify-content: center;
      border-radius: 50%; border: 1px solid rgba(255,255,255,.25);
      font-size: 11.5px; transition: all .2s;
    }
    .tb-socials a:hover {
      background: rgba(255,255,255,.2);
      color: #fff; border-color: rgba(255,255,255,.5);
    }

    /* ══════════════════════════════════════
       HEADER / NAV
    ══════════════════════════════════════ */
    #header {
      background: var(--white);
      border-bottom: 1px solid rgba(0,0,0,.06);
      position: sticky; top: 0; z-index: 200;
      box-shadow: 0 2px 16px rgba(0,0,0,.05);
      transition: box-shadow .3s;
    }
    body.scrolled #header { box-shadow: 0 4px 24px rgba(0,0,0,.10); }

    .mc-branding {
      max-width: 1240px; margin: 0 auto; padding: 0 28px;
      display: flex; align-items: center; justify-content: space-between;
      height: 70px; gap: 20px;
    }

    /* Logo */
    .mc-logo { display: flex; align-items: center; gap: 11px; flex-shrink: 0; }
    .mc-logo .mc-icon {
      width: 40px; height: 40px; border-radius: 11px;
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 12px rgba(29,158,117,.35);
    }
    .mc-logo .mc-icon i { color: #fff; font-size: 19px; }
    .mc-logo .mc-wordmark {
      font-family: 'Syne', sans-serif; font-size: 21px;
      font-weight: 700; color: var(--navy); letter-spacing: -.3px;
    }
    .mc-logo .mc-wordmark span { color: var(--green); }

    /* Nav links */
    .mc-nav { display: flex; align-items: center; gap: 1px; flex: 1; justify-content: center; }
    .mc-nav a {
      font-size: 14px; font-weight: 500; color: var(--gray-500);
      padding: 7px 14px; border-radius: var(--radius-sm);
      transition: all .2s; white-space: nowrap; position: relative;
    }
    .mc-nav a:hover { color: var(--green); background: rgba(29,158,117,.07); }
    .mc-nav a.active {
      color: var(--green); background: rgba(29,158,117,.10);
      font-weight: 600;
    }
    .mc-nav a.active::before {
      content: '';
      display: inline-block; width: 5px; height: 5px;
      border-radius: 50%; background: var(--green);
      margin-right: 7px; vertical-align: middle; margin-top: -2px;
    }
    .nav-badge {
      display: inline-block; font-size: 10px; font-weight: 700;
      background: var(--green); color: #fff;
      padding: 1px 7px; border-radius: 20px; margin-left: 5px;
      vertical-align: middle; line-height: 1.7;
      animation: pulse-badge 2s ease-in-out infinite;
    }
    @keyframes pulse-badge {
      0%, 100% { box-shadow: 0 0 0 0 rgba(29,158,117,.4); }
      50% { box-shadow: 0 0 0 5px rgba(29,158,117,0); }
    }

    /* User menu dropdown */
    .user-menu {
      position: relative;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      padding: 6px 12px;
      border-radius: var(--radius-md);
      transition: all 0.2s;
    }
    .user-menu:hover {
      background: rgba(29,158,117,.1);
    }
    .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
    }
    .user-avatar i {
      font-size: 18px;
    }
    .user-name {
      font-size: 14px;
      font-weight: 500;
      color: var(--navy);
    }
    .dropdown-menu-custom {
      position: absolute;
      top: 100%;
      right: 0;
      background: var(--white);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-lg);
      min-width: 200px;
      display: none;
      z-index: 1000;
      margin-top: 8px;
      border: 1px solid var(--gray-200);
    }
    .user-menu.open .dropdown-menu-custom {
      display: block;
    }
    .dropdown-menu-custom a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 16px;
      color: var(--gray-700);
      text-decoration: none;
      transition: background 0.2s;
      font-size: 13px;
    }
    .dropdown-menu-custom a:hover {
      background: var(--green-pale);
      color: var(--green);
    }
    .dropdown-divider {
      height: 1px;
      background: var(--gray-200);
      margin: 6px 0;
    }

    /* Action buttons */
    .mc-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .btn-outline-mc {
      font-size: 13.5px; font-weight: 500;
      padding: 8px 20px; border-radius: var(--radius-sm);
      border: 1.5px solid rgba(0,0,0,.15);
      background: transparent; color: var(--navy);
      cursor: pointer; transition: all .2s;
      display: inline-flex; align-items: center;
      text-decoration: none;
    }
    .btn-outline-mc:hover {
      border-color: var(--green); color: var(--green);
      background: rgba(29,158,117,.05);
    }
    .btn-solid-mc {
      font-size: 13.5px; font-weight: 600;
      padding: 8px 22px; border-radius: var(--radius-sm);
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      color: #fff; border: none; cursor: pointer;
      transition: all .25s;
      display: inline-flex; align-items: center; gap: 7px;
      box-shadow: 0 3px 12px rgba(29,158,117,.30);
      text-decoration: none;
    }
    .btn-solid-mc:hover {
      background: linear-gradient(135deg, var(--green-dark), var(--green-deep));
      box-shadow: 0 5px 18px rgba(29,158,117,.40);
      transform: translateY(-1px);
      color: #fff;
    }
    .btn-logout {
      background: transparent;
      border: 1.5px solid #FEE2E2;
      color: #EF4444;
    }
    .btn-logout:hover {
      background: #FEF2F2;
      border-color: #EF4444;
      color: #DC2626;
      transform: none;
      box-shadow: none;
    }
    .mc-divider { width: 1px; height: 28px; background: var(--gray-200); margin: 0 2px; }

    /* Mobile toggle */
    .mc-mobile-toggle {
      display: none; background: transparent;
      border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm);
      padding: 7px 9px; cursor: pointer; color: var(--navy);
      flex-direction: column; gap: 4px;
    }
    .mc-mobile-toggle span { display: block; width: 18px; height: 2px; background: currentColor; border-radius: 2px; }

    /* Mobile nav */
    .mc-mobile-nav {
      display: none; flex-direction: column;
      background: var(--white); border-top: 1px solid var(--gray-200);
      padding: 12px 28px 18px;
    }
    .mc-mobile-nav a {
      font-size: 14px; font-weight: 500; color: var(--gray-500);
      padding: 11px 0; border-bottom: 1px solid rgba(0,0,0,.05);
      transition: color .2s;
    }
    .mc-mobile-nav a.active { color: var(--green); }
    .mc-mobile-nav a:last-of-type { border-bottom: none; }
    .mc-mob-btns { display: flex; gap: 10px; margin-top: 14px; }
    .mc-mob-btns a { border-bottom: none !important; padding: 0 !important; }
    .mc-mob-btns .btn-solid-mc, .mc-mob-btns .btn-outline-mc { flex: 1; justify-content: center; }

    @media (max-width: 1160px) {
      .mc-nav { display: none; }
      .mc-mobile-toggle { display: flex; }
    }
    @media (max-width: 768px) {
      .tb-left span:last-child { display: none; }
    }

    /* Reste du CSS identique à l'original... */
    .hero-section {
      background: linear-gradient(145deg, #f0faf6 0%, #e8f7f1 50%, #ddf3ea 100%);
      min-height: calc(100vh - 106px);
      display: flex; align-items: center;
      padding: 80px 0;
      position: relative; overflow: hidden;
    }
    .hero-section::before {
      content: '';
      position: absolute; top: -120px; right: -120px;
      width: 500px; height: 500px; border-radius: 50%;
      background: radial-gradient(circle, rgba(29,158,117,.10) 0%, transparent 70%);
      pointer-events: none;
    }
    .hero-section::after {
      content: '';
      position: absolute; bottom: -80px; left: -80px;
      width: 380px; height: 380px; border-radius: 50%;
      background: radial-gradient(circle, rgba(29,158,117,.07) 0%, transparent 70%);
      pointer-events: none;
    }
    .trusted-badge {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(29,158,117,.13); color: var(--green-dark);
      border: 1px solid rgba(29,158,117,.25);
      border-radius: 50px; padding: 7px 18px;
      font-size: 13px; font-weight: 600; margin-bottom: 1.2rem;
      letter-spacing: .01em;
    }
    .trusted-badge i { font-size: 14px; color: var(--green); }
    .hero-title {
      font-family: 'Syne', sans-serif;
      font-size: clamp(2rem, 4vw, 2.9rem);
      font-weight: 800;
      color: var(--navy);
      line-height: 1.22;
      letter-spacing: -.5px;
      margin-bottom: 1.1rem;
    }
    .hero-title .highlight { color: var(--green); }
    .hero-desc {
      font-size: 16px; color: var(--gray-500);
      line-height: 1.7; max-width: 480px;
      margin-bottom: 2rem;
    }
    .why-box {
      background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 100%);
      border-radius: var(--radius-lg);
      padding: 28px 30px;
      margin-bottom: 24px;
      box-shadow: var(--shadow-green);
      position: relative; overflow: hidden;
    }
    .why-box::before {
      content: '';
      position: absolute; top: -30px; right: -30px;
      width: 130px; height: 130px; border-radius: 50%;
      background: rgba(255,255,255,.08);
      pointer-events: none;
    }
    .why-box h3 {
      font-family: 'Syne', sans-serif; font-size: 20px;
      font-weight: 700; color: #fff; margin-bottom: 10px;
    }
    .why-box p { font-size: 14.5px; color: rgba(255,255,255,.85); line-height: 1.65; margin-bottom: 18px; }
    .why-box .more-btn {
      display: inline-flex; align-items: center; gap: 7px;
      background: rgba(255,255,255,.18); color: #fff;
      border: 1px solid rgba(255,255,255,.30);
      padding: 9px 20px; border-radius: var(--radius-sm);
      font-size: 14px; font-weight: 500;
      transition: all .2s;
    }
    .why-box .more-btn:hover { background: #fff; color: var(--green); }
    .icon-boxes { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .icon-box {
      background: var(--white);
      border: 1px solid rgba(29,158,117,.15);
      border-radius: var(--radius-md);
      padding: 20px;
      transition: all .25s;
      box-shadow: var(--shadow-sm);
    }
    .icon-box:hover {
      border-color: var(--green);
      box-shadow: 0 6px 20px rgba(29,158,117,.12);
      transform: translateY(-3px);
    }
    .icon-box i {
      font-size: 26px; color: var(--green);
      margin-bottom: 10px; display: block;
    }
    .icon-box h4 { font-size: 14px; font-weight: 700; color: var(--navy); margin-bottom: 6px; }
    .icon-box p { font-size: 13px; color: var(--gray-500); line-height: 1.55; margin: 0; }
    .hero-img-wrap { position: relative; }
    .hero-img-wrap::before {
      content: '';
      position: absolute; inset: -12px;
      border-radius: calc(var(--radius-xl) + 12px);
      background: linear-gradient(135deg, rgba(29,158,117,.15), rgba(15,110,86,.10));
      z-index: 0;
    }
    .hero-illustration {
      position: relative; z-index: 1;
      border-radius: var(--radius-xl);
      box-shadow: 0 24px 70px rgba(29,158,117,.22);
      width: 100%; object-fit: cover; max-height: 440px;
    }
    .hero-float-badge {
      position: absolute; bottom: 24px; left: -20px; z-index: 2;
      background: var(--white);
      border-radius: var(--radius-md);
      padding: 12px 18px;
      box-shadow: 0 8px 28px rgba(0,0,0,.12);
      display: flex; align-items: center; gap: 12px;
      min-width: 200px;
    }
    .hero-float-badge .badge-icon {
      width: 42px; height: 42px; border-radius: 10px;
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .hero-float-badge .badge-icon i { color: #fff; font-size: 18px; }
    .hero-float-badge .badge-text strong { font-size: 15px; color: var(--navy); font-weight: 700; display: block; }
    .hero-float-badge .badge-text span { font-size: 12px; color: var(--gray-500); }
    .stats-section {
      padding: 60px 0;
      background: var(--white);
      border-top: 1px solid var(--gray-200);
      border-bottom: 1px solid var(--gray-200);
    }
    .stat-item { text-align: center; padding: 20px; }
    .stat-icon {
      width: 56px; height: 56px; border-radius: var(--radius-md);
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 16px;
      box-shadow: 0 6px 18px rgba(29,158,117,.25);
    }
    .stat-icon i { color: #fff; font-size: 22px; }
    .stat-number {
      font-family: 'Syne', sans-serif;
      font-size: 2rem; font-weight: 800;
      color: var(--green); display: block; line-height: 1;
      margin-bottom: 6px;
    }
    .stat-label { font-size: 14px; color: var(--gray-500); font-weight: 500; }
    .section-pad { padding: 90px 0; }
    .section-pad-light { padding: 90px 0; background: var(--green-pale); }
    .section-eyebrow {
      display: inline-block; font-size: 12px; font-weight: 700;
      color: var(--green); text-transform: uppercase; letter-spacing: .12em;
      margin-bottom: 10px;
    }
    .section-heading {
      font-family: 'Syne', sans-serif;
      font-size: clamp(1.7rem, 3vw, 2.3rem);
      font-weight: 800; color: var(--navy);
      letter-spacing: -.4px; margin-bottom: 14px; line-height: 1.22;
    }
    .section-heading::after {
      content: '';
      display: block; width: 48px; height: 4px;
      background: linear-gradient(90deg, var(--green), var(--green-dark));
      border-radius: 4px; margin-top: 14px;
    }
    .section-heading.centered::after { margin-left: auto; margin-right: auto; }
    .section-sub {
      font-size: 16px; color: var(--gray-500); max-width: 560px; line-height: 1.7;
    }
    .section-sub.centered { margin: 0 auto; }
    .service-card {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius-lg);
      padding: 32px 28px;
      transition: all .3s;
      height: 100%;
      position: relative; overflow: hidden;
    }
    .service-card::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0; height: 3px;
      background: linear-gradient(90deg, var(--green), var(--green-dark));
      opacity: 0; transition: opacity .3s;
    }
    .service-card:hover {
      border-color: rgba(29,158,117,.4);
      box-shadow: 0 16px 50px rgba(29,158,117,.13);
      transform: translateY(-6px);
    }
    .service-card:hover::before { opacity: 1; }
    .service-card:hover .svc-icon { background: linear-gradient(135deg, var(--green), var(--green-dark)); }
    .service-card:hover .svc-icon i { color: #fff; }
    .svc-icon {
      width: 54px; height: 54px; border-radius: var(--radius-md);
      background: var(--green-light);
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 20px; transition: all .3s;
    }
    .svc-icon i { font-size: 22px; color: var(--green); transition: color .3s; }
    .service-card h3 {
      font-family: 'Syne', sans-serif; font-size: 17px;
      font-weight: 700; color: var(--navy); margin-bottom: 10px;
    }
    .service-card p { font-size: 14.5px; color: var(--gray-500); line-height: 1.65; margin: 0; }
    .service-card a { text-decoration: none; color: inherit; }
    .offer-tabs .nav-link {
      border-radius: 50px; padding: 9px 24px;
      font-size: 14px; font-weight: 500; color: var(--gray-700);
      border: 1.5px solid var(--gray-200); transition: all .25s;
      background: var(--white);
    }
    .offer-tabs .nav-link:hover { border-color: var(--green); color: var(--green); }
    .offer-tabs .nav-link.active {
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      color: #fff; border-color: transparent;
      box-shadow: 0 4px 16px rgba(29,158,117,.30);
    }
    .offer-card {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius-lg);
      overflow: hidden; transition: all .3s; height: 100%;
    }
    .offer-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 16px 50px rgba(29,158,117,.14);
      border-color: rgba(29,158,117,.4);
    }
    .offer-card img { width: 100%; height: 175px; object-fit: cover; }
    .offer-card .card-body { padding: 18px 20px; }
    .offer-badge {
      display: inline-block; font-size: 11.5px; padding: 4px 12px;
      border-radius: 50px; font-weight: 700; letter-spacing: .02em;
      background: var(--green-light); color: var(--green-dark);
    }
    .offer-badge.premium { background: #FFF8E7; color: #B45309; }
    .offer-badge.famille { background: #EEF2FF; color: #3730A3; }
    .offer-badge.urgences { background: #FEF2F2; color: #B91C1C; }
    .offer-badge.entreprise { background: #F0FDF4; color: #15803D; }
    .offer-badge.pro { background: #F5F3FF; color: #6D28D9; }
    .offer-card h5 {
      font-size: 15px; font-weight: 700; margin: 12px 0 7px;
      color: var(--navy); line-height: 1.4;
    }
    .offer-card .instructor { font-size: 13px; color: var(--gray-500); margin-bottom: 8px; }
    .offer-card .stars i { color: #FBBF24; font-size: 12px; }
    .offer-card .price { font-size: 17px; font-weight: 800; color: var(--navy); }
    .offer-card .price.free { color: var(--green); }
    .enroll-link {
      font-size: 13px; color: var(--green); font-weight: 600;
      display: inline-flex; align-items: center; gap: 4px;
      transition: color .2s;
    }
    .enroll-link:hover { color: var(--green-dark); }
    .testi-card {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius-lg);
      padding: 30px;
      height: 100%;
      transition: all .3s;
      position: relative;
    }
    .testi-card::before {
      content: '\201C';
      position: absolute; top: 20px; right: 26px;
      font-family: Georgia, serif; font-size: 72px; line-height: 1;
      color: var(--green-light); pointer-events: none;
    }
    .testi-card:hover {
      box-shadow: 0 12px 40px rgba(29,158,117,.10);
      border-color: rgba(29,158,117,.3);
    }
    .testi-avatar {
      width: 52px; height: 52px; border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--green-light);
    }
    .testi-name { font-size: 15px; font-weight: 700; color: var(--navy); }
    .testi-role { font-size: 13px; color: var(--gray-500); }
    .testi-stars i { color: #FBBF24; font-size: 13px; }
    .testi-text { font-size: 14.5px; color: var(--gray-700); line-height: 1.7; margin: 0; font-style: italic; }
    .cta-section {
      background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 50%, var(--green-deep) 100%);
      padding: 90px 0;
      position: relative; overflow: hidden;
    }
    .cta-section::before {
      content: '';
      position: absolute; top: -80px; right: -80px;
      width: 400px; height: 400px; border-radius: 50%;
      background: rgba(255,255,255,.06); pointer-events: none;
    }
    .cta-section::after {
      content: '';
      position: absolute; bottom: -60px; left: -60px;
      width: 300px; height: 300px; border-radius: 50%;
      background: rgba(255,255,255,.04); pointer-events: none;
    }
    .cta-section h2 {
      font-family: 'Syne', sans-serif; font-size: clamp(1.8rem, 3vw, 2.6rem);
      font-weight: 800; color: #fff; letter-spacing: -.4px; margin-bottom: 16px;
    }
    .cta-section p { font-size: 17px; color: rgba(255,255,255,.8); margin-bottom: 36px; }
    .btn-cta-white {
      display: inline-flex; align-items: center; gap: 10px;
      background: #fff; color: var(--green);
      font-size: 16px; font-weight: 700;
      padding: 14px 44px; border-radius: 50px; border: none;
      box-shadow: 0 6px 24px rgba(0,0,0,.15);
      transition: all .25s; cursor: pointer;
      text-decoration: none;
    }
    .btn-cta-white:hover {
      background: var(--green-light); color: var(--green-dark);
      transform: translateY(-2px);
      box-shadow: 0 10px 32px rgba(0,0,0,.20);
    }
    .footer {
      background: var(--navy);
      padding: 70px 0 0;
      color: rgba(255,255,255,.75);
    }
    .footer-logo-text {
      font-family: 'Syne', sans-serif; font-size: 22px;
      font-weight: 700; color: #fff;
    }
    .footer-logo-text span { color: var(--green); }
    .footer-about p { font-size: 14.5px; line-height: 1.7; margin-top: 14px; }
    .footer-social { display: flex; gap: 10px; margin-top: 22px; }
    .footer-social a {
      width: 36px; height: 36px; border-radius: 50%;
      border: 1px solid rgba(255,255,255,.2);
      display: flex; align-items: center; justify-content: center;
      color: rgba(255,255,255,.6); font-size: 14px; transition: all .2s;
    }
    .footer-social a:hover { background: var(--green); border-color: var(--green); color: #fff; }
    .footer-col h4 {
      font-family: 'Syne', sans-serif; font-size: 15px;
      font-weight: 700; color: #fff; margin-bottom: 20px;
      padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,.1);
    }
    .footer-col ul { list-style: none; padding: 0; }
    .footer-col ul li { margin-bottom: 10px; }
    .footer-col ul a {
      font-size: 14px; color: rgba(255,255,255,.65);
      display: flex; align-items: center; gap: 8px; transition: color .2s;
    }
    .footer-col ul a:hover { color: var(--green); }
    .footer-col ul a i { font-size: 11px; color: var(--green); }
    .footer-contact p { font-size: 14px; line-height: 1.8; }
    .footer-contact strong { color: rgba(255,255,255,.85); }
    .footer-bottom {
      margin-top: 50px;
      border-top: 1px solid rgba(255,255,255,.08);
      padding: 20px 0;
      text-align: center;
      font-size: 13.5px;
      color: rgba(255,255,255,.4);
    }
    .footer-bottom strong { color: var(--green); }
    .scroll-top {
      position: fixed; right: 22px; bottom: 22px; z-index: 99;
      width: 44px; height: 44px; border-radius: 50%;
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      color: #fff; font-size: 22px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 6px 20px rgba(29,158,117,.40);
      opacity: 0; pointer-events: none;
      transition: all .3s;
    }
    .scroll-top.active { opacity: 1; pointer-events: all; }
    .scroll-top:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(29,158,117,.50); color: #fff; }
    @media (max-width: 992px) {
      .hero-section { padding: 60px 0; min-height: auto; }
      .hero-float-badge { display: none; }
      .icon-boxes { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 576px) {
      .icon-boxes { grid-template-columns: 1fr; }
      .hero-title { font-size: 1.8rem; }
      .section-heading { font-size: 1.6rem; }
    }
  </style>
</head>

<body>

<div id="preloader"></div>

<!-- ══ HEADER ══ -->
<header id="header">

  <!-- Topbar -->
  <div class="mc-topbar">
    <div class="inner">
      <div class="tb-left">
        <span>
          <i class="bi bi-envelope" style="font-size:12px;"></i>
          <a href="mailto:contact@medchain.com">contact@medchain.com</a>
        </span>
        <span>
          <i class="bi bi-telephone" style="font-size:12px;"></i>
          +216 71 000 000
        </span>
      </div>
      <div class="tb-socials">
        <a href="#" title="Twitter/X"><i class="bi bi-twitter-x"></i></a>
        <a href="#" title="Facebook"><i class="bi bi-facebook"></i></a>
        <a href="#" title="Instagram"><i class="bi bi-instagram"></i></a>
        <a href="#" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
      </div>
    </div>
  </div>

  <!-- Branding + Nav -->
  <div class="mc-branding">
    <a href="index.php" class="mc-logo">
      <div class="mc-icon"><i class="bi bi-plus-square-fill"></i></div>
      <div class="mc-wordmark">Med<span>Chain</span></div>
    </a>

    <nav class="mc-nav">
      <a href="index.php" class="active">Accueil</a>
      <a href="innovation.php">L'Innovation</a>
      <a href="fonctionnalites.php">Fonctionnalités</a>
      <a href="securite.php">Sécurité</a>
      <a href="cas_usage.php">Cas d'Usage</a>
      <a href="blog.php">Blog <span class="nav-badge">New</span></a>
    </nav>

    <div class="mc-actions">
      <?php if ($isLoggedIn): ?>
        <!-- Utilisateur connecté -->
        <div class="user-menu">
          <div class="user-avatar">
            <i class="bi bi-person-fill"></i>
          </div>
          <span class="user-name"><?= htmlspecialchars($userName) ?></span>
          <i class="bi bi-chevron-down" style="font-size: 12px;"></i>
          <div class="dropdown-menu-custom">
            <a href="../auth/profile.php">
              <i class="bi bi-person-circle"></i> Mon profil
            </a>
            <?php if ($userRole === 'patient'): ?>
              <a href="../rendezvous/index.php">
                <i class="bi bi-calendar-check"></i> Mes Rendez-vous
              </a>
              <a href="../ficherdv/index.php">
                <i class="bi bi-file-medical"></i> Mes Fiches
              </a>
              <a href="../intervention-annulee/create.php">
                <i class="bi bi-x-circle"></i> Annuler Intervention
              </a>
            <?php endif; ?>
            <?php if ($userRole === 'medecin'): ?>
              <a href="../../backoffice/rendezvous/medecin-index.php">
                <i class="bi bi-speedometer2"></i> Espace Médecin
              </a>
            <?php endif; ?>
            <?php if ($userRole === 'admin'): ?>
              <a href="../../backoffice/admin-dashboard.php">
                <i class="bi bi-speedometer2"></i> Administration
              </a>
            <?php endif; ?>
            <div class="dropdown-divider"></div>
            <a href="../../../controllers/logout.php">
              <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
          </div>
        </div>
      <?php else: ?>
        <!-- Utilisateur non connecté -->
        <a href="../auth/login.php" class="btn-outline-mc">Connexion</a>
        <div class="mc-divider"></div>
        <a href="../auth/register.php" class="btn-solid-mc">
          <i class="bi bi-person-plus" style="font-size:14px;"></i>
          Inscription
        </a>
      <?php endif; ?>
      <button class="mc-mobile-toggle" id="mobileToggle" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>

  <!-- Mobile Nav -->
  <div class="mc-mobile-nav" id="mobileNav">
    <a href="index.php" class="active">Accueil</a>
    <a href="innovation.php">L'Innovation</a>
    <a href="fonctionnalites.php">Fonctionnalités</a>
    <a href="securite.php">Sécurité Blockchain</a>
    <a href="cas_usage.php">Cas d'Usage</a>
    <a href="blog.php">Blog</a>
    <div class="mc-mob-btns">
      <?php if ($isLoggedIn): ?>
        <a href="../auth/profile.php" class="btn-outline-mc">Mon profil</a>
        <?php if ($userRole === 'admin'): ?>
          <a href="../../backoffice/admin-dashboard.php" class="btn-outline-mc">Admin</a>
        <?php endif; ?>
        <a href="../../../controllers/logout.php" class="btn-solid-mc">Déconnexion</a>
      <?php else: ?>
        <a href="../auth/login.php" class="btn-outline-mc">Connexion</a>
        <a href="../auth/register.php" class="btn-solid-mc">Inscription</a>
      <?php endif; ?>
    </div>
  </div>

</header>

<!-- ══ RESTE DU CONTENU IDENTIQUE À L'ORIGINAL ══ -->
<!-- Hero Section -->
<section class="hero-section">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6" data-aos="fade-right" data-aos-delay="100">
        <div class="trusted-badge">
          <i class="bi bi-patch-check-fill"></i>
          Plateforme médicale de confiance
        </div>
        <h1 class="hero-title">
          MedChain : <span class="highlight">Confiance</span> &amp; Clarté pour Votre Suivi Médical.
        </h1>
        <p class="hero-desc">Gérez, partagez et sécurisez vos données de santé grâce à la technologie blockchain. Accédez à votre dossier médical où que vous soyez, en toute simplicité.</p>
        <div class="why-box" data-aos="fade-up" data-aos-delay="200">
          <h3>Pourquoi MedChain ?</h3>
          <p>Une solution blockchain certifiée, conçue pour les patients et les praticiens, avec un chiffrement de niveau bancaire et un accès instantané 24h/24.</p>
          <a href="innovation.php" class="more-btn">
            Découvrir l'innovation <i class="bi bi-arrow-right"></i>
          </a>
        </div>
        <div class="icon-boxes" data-aos="fade-up" data-aos-delay="300">
          <div class="icon-box">
            <i class="bi bi-shield-lock-fill"></i>
            <h4>Sécurité Blockchain</h4>
            <p>Chiffrement 256-bit et traçabilité immuable de chaque accès à vos données.</p>
          </div>
          <div class="icon-box">
            <i class="bi bi-people-fill"></i>
            <h4>Partage Praticien</h4>
            <p>Partagez vos données médicales en un clic avec vos médecins.</p>
          </div>
        </div>
      </div>
      <div class="col-lg-6 d-flex justify-content-center" data-aos="fade-left" data-aos-delay="200">
        <div class="hero-img-wrap w-100">
          <img src="https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=700&q=80" alt="Équipe médicale MedChain" class="hero-illustration" />
          <div class="hero-float-badge" data-aos="fade-right" data-aos-delay="600">
            <div class="badge-icon"><i class="bi bi-shield-check"></i></div>
            <div class="badge-text">
              <strong>99.9% Sécurisé</strong>
              <span>Certifié blockchain</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
  <div class="container">
    <div class="row g-3 justify-content-center">
      <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
        <div class="stat-item">
          <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
          <span class="stat-number">10 000+</span>
          <span class="stat-label">Patients connectés</span>
        </div>
      </div>
      <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
        <div class="stat-item">
          <div class="stat-icon"><i class="bi bi-person-badge-fill"></i></div>
          <span class="stat-number">500+</span>
          <span class="stat-label">Praticiens partenaires</span>
        </div>
      </div>
      <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
        <div class="stat-item">
          <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
          <span class="stat-number">99.9%</span>
          <span class="stat-label">Disponibilité garantie</span>
        </div>
      </div>
      <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
        <div class="stat-item">
          <div class="stat-icon"><i class="bi bi-lock-fill"></i></div>
          <span class="stat-number">256-bit</span>
          <span class="stat-label">Chiffrement blockchain</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Services Section -->
<section class="section-pad">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <span class="section-eyebrow">Ce que nous offrons</span>
      <h2 class="section-heading centered">Nos Services</h2>
      <p class="section-sub centered">Tout ce dont vous avez besoin pour gérer votre santé numérique en toute confiance.</p>
    </div>
    <div class="row g-4">
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
        <div class="service-card h-100">
          <a href="../templete_front/dossier.php">
            <div class="svc-icon"><i class="bi bi-folder-plus"></i></div>
            <h3>Accès Dossier</h3>
            <p>Gérez votre santé et celle de votre famille en toute simplicité depuis une interface unifiée et sécurisée.</p>
          </a>
        </div>
      </div>
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
        <div class="service-card h-100">
          <a href="../templete_front/partage.php">
            <div class="svc-icon"><i class="bi bi-share-fill"></i></div>
            <h3>Partage Sécurisé</h3>
            <p>Partagez vos données avec vos praticiens en toute sécurité grâce au chiffrement blockchain avancé.</p>
          </a>
        </div>
      </div>
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
        <div class="service-card h-100">
          <a href="../templete_front/suivi.php">
            <div class="svc-icon"><i class="bi bi-heart-pulse-fill"></i></div>
            <h3>Suivi Post-Opératoire</h3>
            <p>Un engagement qualité pour votre suivi santé après l'opération avec des alertes en temps réel.</p>
          </a>
        </div>
      </div>
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
        <div class="service-card h-100">
          <div class="svc-icon"><i class="bi bi-bell-fill"></i></div>
          <h3>Alertes Médicales</h3>
          <p>Recevez des rappels automatiques pour vos rendez-vous, traitements et suivis médicaux importants.</p>
        </div>
      </div>
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
        <div class="service-card h-100">
          <div class="svc-icon"><i class="bi bi-graph-up-arrow"></i></div>
          <h3>Suivi de Santé</h3>
          <p>Visualisez l'évolution de vos indicateurs de santé sur des graphiques clairs et personnalisés.</p>
        </div>
      </div>
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
        <div class="service-card h-100">
          <div class="svc-icon"><i class="bi bi-chat-dots-fill"></i></div>
          <h3>Messagerie Sécurisée</h3>
          <p>Communiquez directement avec vos praticiens via une messagerie chiffrée et confidentielle.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Offres Section -->
<section class="section-pad-light">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <span class="section-eyebrow">Plans & Tarifs</span>
      <h2 class="section-heading centered">Nos Offres</h2>
      <p class="section-sub centered">Choisissez le profil qui correspond à votre situation et accédez à toutes les fonctionnalités MedChain.</p>
    </div>
    <ul class="nav offer-tabs flex-wrap justify-content-center gap-2 mb-5" id="offerTabs" role="tablist" data-aos="fade-up" data-aos-delay="100">
      <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#pane-patient" role="tab">Patient</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#pane-praticien" role="tab">Praticien</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#pane-etablissement" role="tab">Établissement</a></li>
    </ul>
    <div class="tab-content">
      <!-- Patient -->
      <div class="tab-pane fade show active" id="pane-patient" role="tabpanel">
        <div class="row g-4">
          <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
            <div class="offer-card">
              <img src="https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=400&q=70" alt="Dossier" />
              <div class="card-body">
                <span class="offer-badge">Gratuit</span>
                <h5>Dossier Médical Numérique</h5>
                <p class="instructor">Accessible 24h/24 — partout dans le monde</p>
                <div class="stars mb-2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><small class="ms-1 text-muted">5.0 (8 200)</small></div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <span class="price free">Gratuit</span>
                  <a href="../auth/register.php" class="enroll-link"><i class="bi bi-person-plus"></i>S'inscrire</a>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
            <div class="offer-card">
              <img src="https://images.unsplash.com/photo-1559757175-0eb30cd8c063?w=400&q=70" alt="Suivi" />
              <div class="card-body">
                <span class="offer-badge premium">Premium</span>
                <h5>Suivi Post-Opératoire Avancé</h5>
                <p class="instructor">Alertes intelligentes — Rapports automatiques</p>
                <div class="stars mb-2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i><small class="ms-1 text-muted">4.5 (4 100)</small></div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <span class="price">29 €/mois</span>
                  <a href="../auth/register.php" class="enroll-link"><i class="bi bi-cart2"></i>Choisir</a>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
            <div class="offer-card">
              <img src="https://images.unsplash.com/photo-1505751172876-fa1923c5c528?w=400&q=70" alt="Famille" />
              <div class="card-body">
                <span class="offer-badge famille">Famille</span>
                <h5>Compte Famille</h5>
                <p class="instructor">Jusqu'à 5 membres — Gestion centralisée</p>
                <div class="stars mb-2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><small class="ms-1 text-muted">5.0 (2 900)</small></div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <span class="price">49 €/mois</span>
                  <a href="../auth/register.php" class="enroll-link"><i class="bi bi-cart2"></i>Choisir</a>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
            <div class="offer-card">
              <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=400&q=70" alt="Urgences" />
              <div class="card-body">
                <span class="offer-badge urgences">Urgences</span>
                <h5>Carte d'Urgence Médicale</h5>
                <p class="instructor">QR Code — Données critiques accessibles</p>
                <div class="stars mb-2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i><small class="ms-1 text-muted">4.5 (6 500)</small></div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <span class="price">Inclus</span>
                  <a href="../auth/register.php" class="enroll-link"><i class="bi bi-qr-code"></i>Activer</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Praticien -->
      <div class="tab-pane fade" id="pane-praticien" role="tabpanel">
        <div class="row g-4">
          <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
            <div class="offer-card">
              <img src="https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=400&q=70" alt="Médecin" />
              <div class="card-body">
                <span class="offer-badge">Essentiel</span>
                <h5>Accès Dossiers Patients</h5>
                <p class="instructor">Consultation sécurisée — Historique complet</p>
                <div class="stars mb-2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><small class="ms-1 text-muted">5.0 (3 400)</small></div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <span class="price">79 €/mois</span>
                  <a href="../auth/register.php" class="enroll-link"><i class="bi bi-person-plus"></i>Créer compte</a>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
            <div class="offer-card">
              <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=400&q=70" alt="Ordonnance" />
              <div class="card-body">
                <span class="offer-badge pro">Pro</span>
                <h5>Ordonnances Numériques</h5>
                <p class="instructor">Signature électronique — Envoi direct pharmacie</p>
                <div class="stars mb-2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i><small class="ms-1 text-muted">4.5 (1 800)</small></div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <span class="price">119 €/mois</span>
                  <a href="../auth/register.php" class="enroll-link"><i class="bi bi-cart2"></i>Choisir</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Établissement -->
      <div class="tab-pane fade" id="pane-etablissement" role="tabpanel">
        <div class="row g-4">
          <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
            <div class="offer-card">
              <img src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=400&q=70" alt="Hôpital" />
              <div class="card-body">
                <span class="offer-badge entreprise">Entreprise</span>
                <h5>Solution Hôpital / Clinique</h5>
                <p class="instructor">Déploiement complet — Support dédié 24/7</p>
                <div class="stars mb-2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><small class="ms-1 text-muted">5.0 (980)</small></div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <span class="price">Sur devis</span>
                  <a href="contact.php" class="enroll-link"><i class="bi bi-envelope"></i>Nous contacter</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Témoignages Section -->
<section class="section-pad">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <span class="section-eyebrow">Ils nous font confiance</span>
      <h2 class="section-heading centered">Témoignages</h2>
      <p class="section-sub centered">Ce que disent nos patients et praticiens après avoir adopté MedChain.</p>
    </div>
    <div class="row g-4">
      <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
        <div class="testi-card">
          <div class="d-flex align-items-center gap-3 mb-16" style="margin-bottom:16px;">
            <img src="https://randomuser.me/api/portraits/women/44.jpg" class="testi-avatar" alt="" />
            <div>
              <div class="testi-name">Amira Ben Ali</div>
              <div class="testi-role">Patiente — Tunis</div>
            </div>
          </div>
          <div class="testi-stars mb-3"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
          <p class="testi-text">Depuis MedChain, mon médecin accède instantanément à mon historique. Plus de paperasse, plus de doublons d'examens. Un vrai gain de temps.</p>
        </div>
      </div>
      <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
        <div class="testi-card">
          <div class="d-flex align-items-center gap-3" style="margin-bottom:16px;">
            <img src="https://randomuser.me/api/portraits/men/32.jpg" class="testi-avatar" alt="" />
            <div>
              <div class="testi-name">Dr. Karim Mejri</div>
              <div class="testi-role">Cardiologue — Sfax</div>
            </div>
          </div>
          <div class="testi-stars mb-3"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i></div>
          <p class="testi-text">La sécurité des données patients est ma priorité. MedChain offre un chiffrement irréprochable et une traçabilité totale des accès. Je recommande vivement.</p>
        </div>
      </div>
      <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
        <div class="testi-card">
          <div class="d-flex align-items-center gap-3" style="margin-bottom:16px;">
            <img src="https://randomuser.me/api/portraits/women/68.jpg" class="testi-avatar" alt="" />
            <div>
              <div class="testi-name">Sonia Gharbi</div>
              <div class="testi-role">Patiente — Sousse</div>
            </div>
          </div>
          <div class="testi-stars mb-3"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
          <p class="testi-text">Après mon opération, le suivi post-opératoire via MedChain m'a vraiment rassurée. Les alertes automatiques et le contact facile avec mon chirurgien — parfait.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA Section -->
<section class="cta-section" data-aos="fade-up">
  <div class="container text-center" style="position:relative;z-index:1;">
    <h2>Prêt à sécuriser votre santé ?</h2>
    <p>Rejoignez des milliers de patients et praticiens qui font confiance à MedChain chaque jour.</p>
    <a href="../auth/register.php" class="btn-cta-white">
      <i class="bi bi-person-plus-fill"></i>
      Créer mon compte gratuitement
    </a>
  </div>
</section>

<!-- Footer -->
<footer class="footer">
  <div class="container">
    <div class="row gy-5">
      <div class="col-lg-4 col-md-6 footer-about">
        <a href="index.php" class="d-flex align-items-center gap-2 mb-1">
          <i class="bi bi-plus-square-fill fs-4" style="color:var(--green);"></i>
          <span class="footer-logo-text">Med<span>Chain</span></span>
        </a>
        <p>MedChain révolutionne la gestion des données médicales grâce à la blockchain, offrant sécurité, transparence et accessibilité à tous.</p>
        <div class="footer-social">
          <a href="#"><i class="bi bi-twitter-x"></i></a>
          <a href="#"><i class="bi bi-facebook"></i></a>
          <a href="#"><i class="bi bi-instagram"></i></a>
          <a href="#"><i class="bi bi-linkedin"></i></a>
        </div>
      </div>
      <div class="col-lg-2 col-md-6 footer-col">
        <h4>Plateforme</h4>
        <ul>
          <li><a href="innovation.php"><i class="bi bi-chevron-right"></i>L'Innovation</a></li>
          <li><a href="fonctionnalites.php"><i class="bi bi-chevron-right"></i>Fonctionnalités</a></li>
          <li><a href="securite.php"><i class="bi bi-chevron-right"></i>Sécurité</a></li>
          <li><a href="cas_usage.php"><i class="bi bi-chevron-right"></i>Cas d'Usage</a></li>
          <li><a href="blog.php"><i class="bi bi-chevron-right"></i>Blog</a></li>
        </ul>
      </div>
      <div class="col-lg-2 col-md-6 footer-col">
        <h4>Compte</h4>
        <ul>
          <li><a href="../auth/login.php"><i class="bi bi-chevron-right"></i>Connexion</a></li>
          <li><a href="../auth/register.php"><i class="bi bi-chevron-right"></i>Inscription</a></li>
          <li><a href="../auth/profile.php"><i class="bi bi-chevron-right"></i>Mon Profil</a></li>
          <li><a href="../templete_front/dossier.php"><i class="bi bi-chevron-right"></i>Mon Dossier</a></li>
        </ul>
      </div>
      <div class="col-lg-4 col-md-6 footer-col footer-contact">
        <h4>Contact</h4>
        <p>Avenue Habib Bourguiba, Tunis, 1001</p>
        <p><strong>Tél :</strong> +216 71 000 000</p>
        <p><strong>Email :</strong> contact@medchain.com</p>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© <span id="year"></span> <strong>MedChain</strong>. Tous droits réservés.</p>
  </div>
</footer>

<!-- Scroll Top -->
<a href="#" id="scroll-top" class="scroll-top">
  <i class="bi bi-arrow-up-short"></i>
</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init({ duration: 650, easing: 'ease-out-quart', once: true, offset: 60 });
  document.getElementById('year').textContent = new Date().getFullYear();
  window.addEventListener('load', () => {
    const p = document.getElementById('preloader');
    if (p) { p.style.opacity = '0'; setTimeout(() => p.remove(), 300); }
  });
  const scrollTop = document.getElementById('scroll-top');
  window.addEventListener('scroll', () => {
    const scrolled = window.scrollY > 80;
    scrollTop.classList.toggle('active', scrolled);
    document.body.classList.toggle('scrolled', scrolled);
  });
  scrollTop.addEventListener('click', e => { e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
  const mobileToggle = document.getElementById('mobileToggle');
  const mobileNav = document.getElementById('mobileNav');
  if (mobileToggle && mobileNav) {
    mobileToggle.addEventListener('click', () => {
      const open = mobileNav.style.display === 'flex';
      mobileNav.style.display = open ? 'none' : 'flex';
    });
  }
  // Toggle user dropdown menu on click
  const userMenu = document.querySelector('.user-menu');
  if (userMenu) {
    userMenu.addEventListener('click', function(e) {
      // Don't toggle if clicking a link inside the dropdown
      if (e.target.closest('.dropdown-menu-custom a')) return;
      e.stopPropagation();
      this.classList.toggle('open');
    });
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
      userMenu.classList.remove('open');
    });
  }
</script>
</body>
</html>