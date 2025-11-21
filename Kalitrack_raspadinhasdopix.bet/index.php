<?php

session_start();

function trackAffiliateCode()
{
    $referralCode = $_GET['ref'] ?? $_GET['referral'] ?? $_GET['afiliado'] ?? null;

    if ($referralCode) {
        $referralCode = trim($referralCode);
        $referralCode = preg_replace('/[^a-zA-Z0-9]/', '', $referralCode);

        if (strlen($referralCode) >= 3) {
            setcookie('affiliate_ref', $referralCode, time() + (30 * 24 * 60 * 60), '/');
            $_SESSION['affiliate_ref'] = $referralCode;

            error_log("Nova indicação capturada: " . $referralCode . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

            $cleanUrl = strtok($_SERVER["REQUEST_URI"], '?');
            if ($cleanUrl !== $_SERVER["REQUEST_URI"]) {
                header("Location: " . $cleanUrl, true, 302);
                exit;
            }
        }
    }

    if (!isset($_SESSION['affiliate_ref'])) {
        if (isset($_COOKIE['affiliate_ref'])) {
            $_SESSION['affiliate_ref'] = $_COOKIE['affiliate_ref'];
        }
    }
}

function getAffiliateCode()
{
    return $_SESSION['affiliate_ref'] ?? $_COOKIE['affiliate_ref'] ?? null;
}

trackAffiliateCode();

$affiliateCode = getAffiliateCode();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset=UTF-8="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-C6WN4VNG32"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'G-C6WN4VNG32');
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <title>Raspou Prêmios</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #1a2332 0%, #2a3441 100%);
            color: white;
            min-height: 100vh;
            line-height: 1.4;
        }

        svg.lucide {
            width: 18px;
            height: 18px;
            stroke-width: 2;
            margin-right: 5px;
            vertical-align: middle;
        }

        .header {
            background: rgba(26, 35, 50, 0.95);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-size: 12px;
            font-weight: 800;
            color: #4CAF50;
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .logo span {
            color: #FFC107;
        }

        .nav-menu {
            display: flex;
            gap: 30px;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            text-align: left;
            font-size: 15px;
            transition: color 0.3s;
        }

        .nav-menu a:hover {
            color: #4CAF50;
        }

        .nav-buttons {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 14px;
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .btn-primary {
            background: linear-gradient(90deg, #32cd32, #00c853);
            color: #070b2d;
            border: 2px solid #32cd32;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #2eb82e, #00b34d);
            border-color: #2eb82e;
            color: white;
        }

        .btn-danger {
            background: linear-gradient(90deg, #32cd32, #00c853);
            color: #070b2d;
            border: 2px solid #32cd32;
            font-weight: 700;
        }

        .btn-danger:hover {
            background: linear-gradient(90deg, #2eb82e, #00b34d);
            border-color: #2eb82e;
            color: white;
        }

        /* Menu de Usuário */
        .user-menu {
            position: relative;
            display: inline-block;
        }

        .user-menu-btn {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            color: white;
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 14px;
            transition: all 0.3s;
        }

        .user-menu-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .dropdown-arrow {
            font-size: 10px;
            transition: transform 0.3s;
        }

        .user-menu.active .dropdown-arrow {
            transform: rotate(180deg);
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(26, 35, 50, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            min-width: 200px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s;
            margin-top: 5px;
        }

        .user-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-balance-inline {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            font-size: 14px;
            line-height: 1.2;
            margin-right: 10px;
        }

        .user-balance-inline div:first-child {
            color: #888;
            font-weight: normal;
            text-align: right;
        }

        .user-balance-inline div:last-child {
            color: #00ff88;
            font-weight: bold;
            text-align: right;
            margin-right: 0;
        }

        .dropdown-item {
            display: block;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }

        .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #4CAF50;
        }

        .dropdown-item:last-child {
            border-radius: 0 0 8px 8px;
        }

        /* CSS - Modais de Cadastro/Acesso */

        #modalCadastro {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            z-index: 10000;
            font-family: sans-serif;
        }

        .modal-box {
            background: #111;
            border-radius: 10px;
            max-width: 360px;
            width: 90%;
            color: #fff;
            position: relative;
            overflow: hidden;
            padding: 0;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
        }

        .modal-image {
            width: 100%;
            display: block;
            margin-bottom: 5px;
        }

        .modal-content {
            padding: 25px;
            margin-top: 5px;
        }

        .modal-content h2 {
            text-align: center;
            margin-bottom: 0px;
        }

        .input-group {
            display: flex;
            align-items: center;
            background: #222;
            border-radius: 6px;
            margin-bottom: 15px;
            padding: 10px 12px;
            gap: 10px;
        }

        .input-group i {
            color: #aaa;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .input-group input {
            border: none;
            background: transparent;
            color: #fff;
            font-size: 14px;
            flex: 1;
            padding: 10px 0;
            outline: none;
        }

        .submit-btn {
            width: 100%;
            background: #00e676;
            border: none;
            padding: 12px;
            color: #000;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        .login-link {
            margin-top: 20px;
            font-size: 14px;
            text-align: center;
            color: #aaa;
        }

        .login-link a {
            color: #00e676;
            text-decoration: none;
        }

        .error-msg {
            margin-top: 10px;
            font-size: 14px;
            color: #f44336;
            text-align: center;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .hero-section {
            padding: 30px 0;
            display: block;
        }

        .main-banner {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
        }

        .banner-image {
            width: 100%;
            height: auto;
            display: block;
        }

        .carousel-container {
            position: relative;
            overflow: hidden;
        }

        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }

        .carousel-slide.active {
            opacity: 1;
            position: relative;
            z-index: 2;
        }


        .instant-win {
            position: relative;
            background: linear-gradient(135deg, #061243 0%, #0A1A5F 100%);
            border-radius: 18px;
            padding: 40px 30px;
            margin: 60px auto;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            color: white;
            max-width: 900px;
            isolation: isolate;
        }

        .instant-win::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
            transform: rotate(45deg);
            z-index: 0;
            animation: lightSweep 3s linear infinite;
            pointer-events: none;
        }

        @keyframes lightSweep {
            0% {
                transform: translateX(-100%) rotate(45deg);
            }

            100% {
                transform: translateX(100%) rotate(45deg);
            }
        }

        .instant-win h2 {
            font-size: 32px;
            margin-bottom: 0px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            z-index: 1;
            position: relative;
        }

        .instant-win .subtitle {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 30px;
            color: #FFC107;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
            z-index: 1;
            position: relative;
        }

        .instant-win-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 16px;
            margin-bottom: 30px;
            z-index: 1;
            position: relative;
        }

        .win-btn {
            padding: 14px 28px;
            border: none;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .win-btn.green {
            background: linear-gradient(90deg, #00e676, #00c853);
            color: #001b12;
        }

        .win-btn.green:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(0, 230, 118, 0.5);
        }

        .win-btn.red {
            background: linear-gradient(90deg, #ff1744, #d50000);
            color: #fff;
        }

        .win-btn.red:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(255, 23, 68, 0.4);
        }

        .instant-win .prizes-info {
            display: flex;
            justify-content: center;
            gap: 50px;
            margin-top: 10px;
            z-index: 1;
            position: relative;
            flex-wrap: wrap;
        }

        .prize-item {
            text-align: center;
            font-weight: 600;
            color: #fff;
        }

        .prize-item .icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 10px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 0;
            /* evita espaçamento vertical extra */
        }

        .prize-item svg.lucide {
            width: 22px;
            height: 22px;
            display: block;
            stroke-width: 1.8;
            margin-left: 5px;
        }

        .prize-item div:last-child {
            font-size: 12px;
            font-weight: 600;
        }

        .games-section {
            margin: 25px 0;
        }

        .games-section h2 {
            font-size: 26px;
            font-weight: 500;
            margin-bottom: 25px;
        }

        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .game-card {
            background: linear-gradient(0deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0) 50%);
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .game-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .game-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .game-info {
            padding: 20px;
        }

        .game-prize {
            font-size: 16px;
            font-weight: none;
            color: #4CAF50;
            margin-bottom: 8px;
        }

        .game-name {
            font-size: 20px;
            margin-bottom: 5px;
            font-weight: 400;
        }

        .game-details {
            font-size: 14px;
            color: #FFC107;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .game-card .btn-danger {
            width: 100%;
        }

        .features-section {
            margin: 50px 0;
            text-align: center;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: #4CAF50;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .feature-icon svg.lucide {
            width: 22px;
            height: 22px;
            display: block;
            stroke-width: 1.8;
            margin-left: 5px;
        }

        .feature-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .feature-description {
            color: #ccc;
            line-height: 1.6;
        }

        .cta-section {
            text-align: center;
            margin: 50px 0;
        }

        .cta-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .cta-subtitle {
            font-size: 16px;
            color: #ccc;
            margin-bottom: 30px;
        }

        .footer {
            background: rgba(26, 35, 50, 0.95);
            padding: 40px 0 20px;
            margin-top: 80px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 30px;
        }

        .footer-section h3 {
            color: #4CAF50;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .footer-section p {
            color: #ccc;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 10px;
        }

        .footer-section ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section ul li a:hover {
            color: #4CAF50;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #999;
            font-size: 14px;
        }

        .pix-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pix-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 30px;
        }

        .pix-brand img {
            height: 100%;
            max-height: 30px;
            object-fit: contain;
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .container {
                padding: 0 15px;
            }

            .games-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 20px;
            }

            .winners-grid {
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                gap: 12px;
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                padding: 0 15px;
            }

            .nav-menu {
                display: none;
            }

            .nav-buttons {
                gap: 8px;
            }

            .btn {
                padding: 8px 16px;
                font-size: 13px;
            }

            .user-dropdown {
                right: -10px;
                min-width: 180px;
            }

            .hero-section {
                padding: 20px 0;
            }

            .games-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .games-section {
                margin: 20px 0;
            }

            .instant-win {
                padding: 20px;
                margin: 20px 0;
            }

            .instant-win-buttons {
                flex-direction: column;
                gap: 10px;
            }

            .prizes-info {
                gap: 20px;
            }

            .winners-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .winner-card {
                padding: 15px;
                gap: 12px;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .features-section {
                margin: 30px 0;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .footer-bottom {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .cta-section {
                margin: 30px 0;
            }
        }

        @media (max-width: 480px) {
            svg.lucide {
                width: 18px;
                height: 18px;
                stroke-width: 1.8;
                margin-right: 4px;
            }
        }

        .logo {
            font-size: 20px;
        }

        .nav-buttons .btn {
            padding: 6px 12px;
            font-size: 12px;
        }

        .user-menu-btn {
            padding: 6px 10px;
            font-size: 12px;
        }

        .user-dropdown {
            right: -15px;
            min-width: 160px;
        }

        .hero-section {
            padding: 15px 0;
        }

        .main-banner {
            border-radius: 10px;
        }

        .instant-win {
            padding: 15px;
            border-radius: 10px;
        }

        .instant-win h2 {
            font-size: 25px;
        }

        .instant-win .subtitle {
            font-size: 22px;
        }

        .win-btn {
            padding: 8px 16px;
            font-size: 12px;
        }

        .games-section h2,
        .winners-section h2 {
            font-size: 26px;
        }

        .game-card {
            border-radius: 10px;
        }

        .game-info {
            padding: 15px;
        }

        .game-prize {
            font-size: 14px;
        }

        .game-name {
            font-size: 18px;
        }

        .winner-card {
            padding: 12px;
            border-radius: 8px;
        }

        .winner-avatar {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }

        .winner-name {
            font-size: 14px;
        }

        .winner-prize {
            font-size: 16px;
        }

        .feature-card {
            padding: 20px;
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            font-size: 20px;
        }

        .cta-title {
            font-size: 24px;
        }

        .cta-section .btn {
            padding: 12px 30px;
            font-size: 16px;
        }
        }

        /* CSS - Modais de Cadastro/Acesso */

        .modal-box {
            padding: 0;
            max-width: 95%;
            border-radius: 8px;
        }

        .modal-content {
            padding: 20px;
        }

        .input-group input {
            font-size: 15px;
        }

        .submit-btn {
            font-size: 15px;
            padding: 10px;
        }
        }

        @media (max-width: 360px) {
            .container {
                padding: 0 10px;
            }

            .nav-container {
                padding: 0 10px;
            }

            .winner-card {
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }

            .winner-info {
                text-align: center;
            }

            .winner-name-line {
                justify-content: center;
            }

            .instant-win-buttons {
                gap: 8px;
            }

            .win-btn {
                padding: 6px 12px;
                font-size: 11px;
            }

            .user-dropdown {
                right: -20px;
                min-width: 150px;
            }

            .dropdown-item {
                padding: 10px 12px;
                font-size: 13px;
            }

            .user-balance {
                padding: 12px;
                font-size: 14px;
            }
        }
    </style>

    <link rel="stylesheet" href="/assets/css/carrossel-wins.css">

    <script>
        function getURLParameters() {
            const params = new URLSearchParams(window.location.search);
            return {
                click_id: params.get('click_id') || '',
                pixel_id: params.get('pixel_id') || '',
                campaign_id: params.get('CampaignID') || '',
                adset_id: params.get('adSETID') || '',
                creative_id: params.get('CreativeID') || '',
                utm_source: params.get('utm_source') || '',
                utm_campaign: params.get('utm_campaign') || '',
                utm_medium: params.get('utm_medium') || '',
                utm_content: params.get('utm_content') || '',
                utm_term: params.get('utm_term') || '',
                utm_id: params.get('utm_id') || '',
                fbclid: params.get('fbclid') || ''
            };
        }

        function trackAffiliateClick(affiliateCode) {
            if (typeof (Storage) !== "undefined") {
                localStorage.setItem('affiliate_ref', affiliateCode);
                localStorage.setItem('affiliate_ref_date', Date.now());
            }

            if (typeof gtag !== 'undefined') {
                gtag('event', 'affiliate_click', {
                    'affiliate_code': affiliateCode,
                    'page_location': window.location.href
                });
            }

            console.log('Código de afiliado rastreado:', affiliateCode);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const refCode = urlParams.get('ref') || urlParams.get('referral') || urlParams.get('afiliado');

            if (refCode) {
                trackAffiliateClick(refCode);
            }
        });
    </script>

    <link rel="stylesheet" href="/assets/css/deposit-success-modal.css">

</head>

<body>
    <header class="header">
        <div class="nav-container">
            <a href="/" class="logo">
                <img src="/images/logo.png" alt="Raspou Prêmios" style="height: 72px;">
            </a>
            <nav class="nav-menu">
                <a href="/"> <i data-lucide="house"></i> Início</a>
                <a href="#raspadinhas"><i data-lucide="layout-grid"></i> Raspadinhas</a>
            </nav>

            <div id="authButtons" class="nav-buttons">
                <a href="#" class="btn btn-primary" onclick="toggleModalCadastro(true); return false;"><i
                        data-lucide="user-round-plus"></i> Registrar</a>
                <a href="#" class="btn btn-outline" onclick="toggleModalLogin(true); return false;"><i
                        data-lucide="log-in"></i> Entrar</a>
            </div>

            <div id="userInfo" style="display:none; align-items: center; gap: 10px;">
                <div class="user-balance-inline">
                    <div>Saldo</div>
                    <div id="userBalance"> </div>
                </div>

                <div class="user-menu">
                    <button id="userMenuBtn" class="user-menu-btn">
                        <span><i data-lucide="user" style="width: 24px; height: 24px;"></i></span>
                        <span class="dropdown-arrow"><i data-lucide="chevron-down"></i></span>
                    </button>
                    <div id="userDropdown" class="user-dropdown">
                        <div class="dropdown-divider"></div>
                        <a href="/perfil/" class="dropdown-item"><i data-lucide="user-cog"></i> Perfil</a>
                        <a href="/#raspadinhas" class="dropdown-item"><i data-lucide="layout-grid"></i> Jogar</a>
                        <a href="/saque/" class="dropdown-item"><i data-lucide="banknote-arrow-down"></i> Sacar</a>
                        <a href="/historico/" class="dropdown-item"><i data-lucide="gamepad-2"></i> Histórico</a>
                        <a href="/deposito/" class="dropdown-item"><i data-lucide="banknote-arrow-up"></i> Depositar</a>
                        <a href="/api/logout.php" class="dropdown-item"><i data-lucide="log-out"></i> Sair</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <section class="hero-section">
            <div class="main-banner carousel-container">
                <div class="carousel-wrapper">
                    <div class="carousel-slide active">
                        <a href="#">
                            <img src="images/banner1-raspeaqui.png" alt="Banner 1" class="banner-image">
                        </a>
                    </div>
                    <div class="carousel-slide">
                        <a href="#">
                            <img src="images/banner2-raspeaqui.png" alt="Banner 2" class="banner-image">
                        </a>
                    </div>
                </div>
        </section>

        <section class="winners-section">
            <div class="winners-container">
                <div class="winners-header">
                    <h2 class="winners-title"><i data-lucide="trophy"
                            style="width: 32px; height: 32px; color: #26B733;"></i> Últimos Ganhadores</h2>
                    <div class="total-distributed">
                        <span class="distributed-label"><i data-lucide="banknote-arrow-down"></i> Total
                            Distribuído</span>
                        <span class="distributed-value">R$ 1.247.350</span>
                    </div>
                </div>

                <div class="winners-carousel">
                    <div class="winners-track" id="winnersTrack">

                        <div class="winner-item">
                            <div class="winner-avatar">
                                <div class="avatar-circle"
                                    style="background: linear-gradient(135deg, #4338ca, #3730a3);">
                                    <span class="avatar-text">F</span>
                                </div>
                            </div>
                            <div class="winner-info">
                                <div class="winner-name">Fernanda***</div>
                                <div class="winner-time">há 5 min</div>
                            </div>
                            <div class="winner-prize">
                                <div class="prize-value">R$ 100</div>
                                <div class="prize-type">PIX</div>
                            </div>
                        </div>

                        <div class="winner-item">
                            <div class="winner-avatar">
                                <div class="avatar-circle"
                                    style="background: linear-gradient(135deg, #7c3aed, #6d28d9);">
                                    <span class="avatar-text">R</span>
                                </div>
                            </div>
                            <div class="winner-info">
                                <div class="winner-name">Rodrigo***</div>
                                <div class="winner-time">há 8 min</div>
                            </div>
                            <div class="winner-prize">
                                <div class="prize-value">Apple Watch</div>
                                <div class="prize-type">PRODUTO</div>
                            </div>
                        </div>

                        <div class="winner-item">
                            <div class="winner-avatar">
                                <div class="avatar-circle"
                                    style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                    <span class="avatar-text">P</span>
                                </div>
                            </div>
                            <div class="winner-info">
                                <div class="winner-name">Patrícia***</div>
                                <div class="winner-time">há 12 min</div>
                            </div>
                            <div class="winner-prize">
                                <div class="prize-value">R$ 250</div>
                                <div class="prize-type">PIX</div>
                            </div>
                        </div>

                        <div class="winner-item">
                            <div class="winner-avatar">
                                <div class="avatar-circle"
                                    style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                                    <span class="avatar-text">M</span>
                                </div>
                            </div>
                            <div class="winner-info">
                                <div class="winner-name">Marcelo***</div>
                                <div class="winner-time">há 15 min</div>
                            </div>
                            <div class="winner-prize">
                                <div class="prize-value">R$ 50</div>
                                <div class="prize-type">PIX</div>
                            </div>
                        </div>

                        <div class="winner-item">
                            <div class="winner-avatar">
                                <div class="avatar-circle"
                                    style="background: linear-gradient(135deg, #ec4899, #db2777);">
                                    <span class="avatar-text">V</span>
                                </div>
                            </div>
                            <div class="winner-info">
                                <div class="winner-name">Vanessa***</div>
                                <div class="winner-time">há 18 min</div>
                            </div>
                            <div class="winner-prize">
                                <div class="prize-value">iPhone 15</div>
                                <div class="prize-type">PRODUTO</div>
                            </div>
                        </div>

                        <div class="winner-item">
                            <div class="winner-avatar">
                                <div class="avatar-circle"
                                    style="background: linear-gradient(135deg, #10b981, #059669);">
                                    <span class="avatar-text">A</span>
                                </div>
                            </div>
                            <div class="winner-info">
                                <div class="winner-name">André***</div>
                                <div class="winner-time">há 22 min</div>
                            </div>
                            <div class="winner-prize">
                                <div class="prize-value">R$ 500</div>
                                <div class="prize-type">PIX</div>
                            </div>
                        </div>

                        <div class="winner-item">
                            <div class="winner-avatar">
                                <div class="avatar-circle"
                                    style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                                    <span class="avatar-text">B</span>
                                </div>
                            </div>
                            <div class="winner-info">
                                <div class="winner-name">Beatriz***</div>
                                <div class="winner-time">há 25 min</div>
                            </div>
                            <div class="winner-prize">
                                <div class="prize-value">AirPods Pro</div>
                                <div class="prize-type">PRODUTO</div>
                            </div>
                        </div>

                        <div class="winner-item">
                            <div class="winner-avatar">
                                <div class="avatar-circle"
                                    style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                                    <span class="avatar-text">T</span>
                                </div>
                            </div>
                            <div class="winner-info">
                                <div class="winner-name">Tatiane***</div>
                                <div class="winner-time">há 28 min</div>
                            </div>
                            <div class="winner-prize">
                                <div class="prize-value">R$ 1.000</div>
                                <div class="prize-type">PIX</div>
                            </div>
                        </div>

                        <div class="winner-item">
                            <div class="winner-avatar">
                                <div class="avatar-circle"
                                    style="background: linear-gradient(135deg, #4338ca, #3730a3);">
                                    <span class="avatar-text">L</span>
                                </div>
                            </div>
                            <div class="winner-info">
                                <div class="winner-name">Leandro***</div>
                                <div class="winner-time">há 28 min</div>
                            </div>
                            <div class="winner-prize">
                                <div class="prize-value">R$ 100</div>
                                <div class="prize-type">PIX</div>
                            </div>
                        </div>

                        <div class="winner-item">
                            <div class="winner-avatar">
                                <div class="avatar-circle"
                                    style="background: linear-gradient(135deg, #7c3aed, #6d28d9);">
                                    <span class="avatar-text">R</span>
                                </div>
                            </div>
                            <div class="winner-info">
                                <div class="winner-name">Renata***</div>
                                <div class="winner-time">há 30 min</div>
                            </div>
                            <div class="winner-prize">
                                <div class="prize-value">Apple Watch</div>
                                <div class="prize-type">PRODUTO</div>
                            </div>
                        </div>

                        <div class="winner-item">
                            <div class="winner-avatar">
                                <div class="avatar-circle"
                                    style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                    <span class="avatar-text">F</span>
                                </div>
                            </div>
                            <div class="winner-info">
                                <div class="winner-name">Felipe***</div>
                                <div class="winner-time">há 31 min</div>
                            </div>
                            <div class="winner-prize">
                                <div class="prize-value">R$ 250</div>
                                <div class="prize-type">PIX</div>
                            </div>
                        </div>

                        <div class="winner-item">
                            <div class="winner-avatar">
                                <div class="avatar-circle"
                                    style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                                    <span class="avatar-text">A</span>
                                </div>
                            </div>
                            <div class="winner-info">
                                <div class="winner-name">Aline***</div>
                                <div class="winner-time">há 32 min</div>
                            </div>
                            <div class="winner-prize">
                                <div class="prize-value">R$ 50</div>
                                <div class="prize-type">PIX</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="games-section" id="raspadinhas">
            <h2><i data-lucide="badge-dollar-sign" style="width: 32px; height: 32px; color: #26B733;"></i>Raspadinhas
            </h2>
            <div class="games-grid">
                <div class="game-card">
                    <div class="game-image">
                        <img src="images/raspadinha1.png" alt="Raspa da Sorte">
                    </div>
                    <div class="game-info">
                        <div class="game-name">Raspa da Sorte</div>
                        <div class="game-details">Prêmios até: R$ 1.000,00</div>
                        <div class="game-prize"><i data-lucide="audio-waveform"
                                style="transform: rotate(30deg); width: 22px; height: 22px;"></i>Apenas R$ 1,00 a rodada
                            <i data-lucide="corner-right-down"></i>
                        </div>
                        <a href="/jogo/jogo.php" class="btn btn-danger">JOGAR AGORA →</a>
                    </div>
                </div>

                <div class="game-card">
                    <div class="game-image">
                        <img src="images/raspadinha2.png" alt="Raspa da Emoção">
                    </div>
                    <div class="game-info">
                        <div class="game-name">Raspa da Emoção</div>
                        <div class="game-details">Prêmios até: R$ 5.000,00</div>
                        <div class="game-prize"><i data-lucide="audio-waveform"
                                style="transform: rotate(30deg); width: 22px; height: 22px;"></i>Apenas R$ 2,00 a rodada
                            <i data-lucide="corner-right-down"></i>
                        </div>
                        <a href="/jogo/jogo2.php" class="btn btn-danger">JOGAR AGORA →</a>
                    </div>
                </div>

                <div class="game-card">
                    <div class="game-image">
                        <img src="images/raspadinha3.png" alt="Raspa da Alegria">
                    </div>
                    <div class="game-info">
                        <div class="game-name">Raspa da Alegria</div>
                        <div class="game-details">Prêmios até: R$ 10.000,00</div>
                        <div class="game-prize"><i data-lucide="audio-waveform"
                                style="transform: rotate(30deg); width: 22px; height: 22px;"></i>Apenas R$ 5,00 a rodada
                            <i data-lucide="corner-right-down"></i>
                        </div>
                        <a href="/jogo/jogo3.php" class="btn btn-danger">JOGAR AGORA →</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="instant-win">
            <h2>Raspou</h2>
            <h2>Achou?</h2>
            <p class="subtitle">PIX Pingou!</p>
            <div class="instant-win-buttons">
                <button class="win-btn green"><i data-lucide="check-check"></i> ACHE 3 IGUAIS</button>
                <button class="win-btn red"><i data-lucide="trophy"></i> GANHE NA HORA!</button>
            </div>
            <div class="prizes-info">
                <div class="prize-item">
                    <div class="icon"> <i data-lucide="hand-coins"></i></div>
                    <div>Prêmios em<br>Dinheiro</div>
                </div>
                <div class="prize-item">
                    <div class="icon"> <i data-lucide="gift"></i></div>
                    <div>Eletrônicos<br>& Prêmios</div>
                </div>
            </div>
        </section>

        <section class="features-section">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i data-lucide="shield-check"></i>️</div>
                    <h3 class="feature-title">Segurança Garantida</h3>
                    <p class="feature-description">Todas as transações são protegidas com criptografia de ponta a ponta.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon"><i data-lucide="zap"></i></div>
                    <h3 class="feature-title">Depósito Instantâneo</h3>
                    <p class="feature-description">Os créditos são adicionados imediatamente após confirmação do PIX.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon"><i data-lucide="phone"></i></div>
                    <h3 class="feature-title">Suporte 24/7</h3>
                    <p class="feature-description">Nosso suporte está disponível 24 horas por dia para ajudar.</p>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <h2 class="cta-title">Pronto para Ganhar?</h2>
            <p class="cta-subtitle">Junte-se a milhares de jogadores que já estão se divertindo e ganhando prêmios em
                nossa plataforma.</p>
            <a href="#raspadinhas" class="btn btn-danger" style="font-size: 18px; padding: 15px 40px;">JOGAR AGORA</a>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i data-lucide="audio-waveform" style="transform: rotate(30deg);"></i>Raspou Prêmios</h3>
                    <p>A Raspou Prêmios é um Título de Capitalização de Pagamento Único, modalidade Filantropia
                        Premiável, emitido pela Kovr Capitalização S.A. – KOVRCAP (CNPJ nº 93.202.448/0001-79).</p>
                    <p>Aprovado pela SUSEP através do processo indicado na cautela, nos termos da Lei Federal nº
                        14.332/2022 e das Circulares SUSEP nº 656/2022 e 676/2022.</p>
                </div>

                <div class="footer-section">
                    <h3><i data-lucide="link"></i>Links Rápidos</h3>
                    <ul>
                        <li><a href="/">Início</a></li>
                        <li><a href="/perfil/">Meu Perfil</a></li>
                        <li><a href="/historico/">Histórico</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3><i data-lucide="phone"></i>Suporte</h3>
                    <ul>
                        <li><a href="#">Como Jogar</a></li>
                        <li><a href="#">Termos de Uso</a></li>
                        <li><a href="#">Política de Privacidade</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div>
                    <p>© 2025 Raspou Prêmios - Kovr Capitalização S.A. – KOVRCAP CNPJ n.º 93.202.448/0001-79</p>
                    <p><strong>Todos os direitos reservados.</strong></p>

                    <p style="font-size: 12px; margin-top: 10px;">Jogue com responsabilidade. Proibida a venda para
                        menores de 18 anos.</p>
                </div>
                <div class="pix-logo">
                    <span>Pagamentos Digitais por:</span>
                    <div class="pix-brand">
                        <img src="/images/logo-pix.png" alt="Logo Pix" />
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <div id="modalCadastro" style="display: none;">
        <div class="modal-box">

            <button onclick="toggleModalCadastro(false)" class="close-btn">×</button>

            <div class="modal-content">
                <h2><i data-lucide="user-round-pen" style="width: 22px; height: 22px; color: #26B733;"></i>Crie sua
                    conta</h2>
                <p style="margin-bottom: 20px; text-align: center;">Preencha abaixo para registrar-se!</p>

                <form id="formCadastro">
                    <div class="input-group">
                        <i data-lucide="user"></i>
                        <input type="text" id="cadUsername" name="cadUsername" placeholder="Nome completo" required
                            autocomplete="name" maxlength="60">
                    </div>

                    <div class="input-group">
                        <i data-lucide="phone"></i>
                        <input type="tel" id="cadTelefone" name="cadTelefone" placeholder="(00) 00000-0000" required
                            autocomplete="tel" maxlength="15">
                    </div>

                    <div class="input-group">
                        <i data-lucide="mail"></i>
                        <input type="email" id="cadEmail" name="cadEmail" placeholder="Digite seu melhor e-mail"
                            required autocomplete="email" maxlength="80">
                    </div>

                    <div class="input-group">
                        <i data-lucide="lock"></i>
                        <input type="password" id="cadSenha" name="cadSenha" placeholder="Mínimo 6 caracteres" required
                            autocomplete="new-password" minlength="6" maxlength="20">
                    </div>

                    <button type="submit" class="submit-btn">Registrar-se →</button>
                </form>

                <p class="login-link">
                    Já tem uma conta? <a href="#" onclick="toggleLoginModal()">Conecte-se</a>
                </p>

                <div id="cadMsg" class="error-msg"></div>
            </div>
        </div>
    </div>


    <div id="modalLogin" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
        background: rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:10000;">
        <div class="modal-box">

            <button onclick="toggleModalLogin(false)" class="close-btn">×</button>

            <div class="modal-content">
                <h2><i data-lucide="log-in" style="width: 22px; height: 22px; color: #26B733;"></i> Acesse sua conta
                </h2>
                <p style="margin-bottom: 20px; text-align: center;">Digite seus dados para continuar:</p>

                <form id="formLogin">
                    <div class="input-group">
                        <i data-lucide="user"></i>
                        <input type="text" id="loginUsername" name="loginUsername" placeholder="Usuário ou e-mail"
                            required autocomplete="username">
                    </div>

                    <div class="input-group">
                        <i data-lucide="lock"></i>
                        <input type="password" id="loginSenha" name="loginSenha" placeholder="Sua senha" required
                            autocomplete="current-password">
                    </div>

                    <button type="submit" class="submit-btn">Entrar agora →</button>
                </form>

                <p class="login-link">
                    Ainda não tem conta? <a href="#"
                        onclick="toggleModalCadastro(true); toggleModalLogin(false); return false;">Cadastre-se</a>
                </p>

                <div id="loginMsg" class="error-msg"></div>
            </div>
        </div>
    </div>

    <script>

        class SimpleSecureAuth {
            constructor() {
                this.token = null;
                this.tokenExpiry = null;
            }

            async getToken() {
                if (this.token && this.tokenExpiry && Date.now() < this.tokenExpiry) {
                    return this.token;
                }

                try {
                    const response = await fetch('/api/token_service.php', {
                        method: 'GET',
                        credentials: 'include',
                        headers: {
                            'Cache-Control': 'no-cache'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`Erro ${response.status}`);
                    }

                    const data = await response.json();

                    if (!data.token) {
                        throw new Error('Token não recebido');
                    }

                    this.token = data.token;
                    this.tokenExpiry = Date.now() + ((data.expires_in - 60) * 1000);

                    console.log('Token obtido com sucesso');
                    return this.token;

                } catch (error) {
                    console.error('Erro ao obter token:', error);
                    throw error;
                }
            }

            async secureRequest(url, options = {}) {
                try {
                    const token = await this.getToken();

                    const secureOptions = {
                        ...options,
                        headers: {
                            ...options.headers,
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        }
                    };

                    return await fetch(url, secureOptions);

                } catch (error) {
                    console.error('Erro na requisição segura:', error);
                    throw error;
                }
            }
        }

        const authSystem = new SimpleSecureAuth();

        function toggleModalLogin(show) {
            const modal = document.getElementById('modalLogin');
            modal.style.display = show ? 'flex' : 'none';
        }

        function toggleModalCadastro(show) {
            const modal = document.getElementById('modalCadastro');
            modal.style.display = show ? 'flex' : 'none';
        }

        function toggleLoginModal() {
            toggleModalCadastro(false);
            toggleModalLogin(true);
        }


        document.addEventListener('DOMContentLoaded', function () {
            const formCadastro = document.getElementById('formCadastro');

            if (formCadastro) {
                formCadastro.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const submitBtn = this.querySelector('.submit-btn');
                    const msgDiv = document.getElementById('cadMsg');
                    const originalText = submitBtn.textContent;

                    msgDiv.textContent = '';
                    msgDiv.style.color = '';
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Registrando...';

                    try {
                        const formData = {
                            username: document.getElementById('cadUsername').value.trim(),
                            email: document.getElementById('cadEmail').value.trim(),
                            phone: document.getElementById('cadTelefone').value.trim(),
                            senha: document.getElementById('cadSenha').value,
                            affiliate_ref: '<?php echo $affiliateCode ?? ""; ?>',
                            ...getURLParameters()
                        };

                        if (!formData.username || !formData.email || !formData.phone || !formData.senha) {
                            throw new Error('Preencha todos os campos obrigatórios');
                        }

                        if (formData.senha.length < 6) {
                            throw new Error('A senha deve ter pelo menos 6 caracteres');
                        }

                        console.log('Iniciando cadastro com proteção...');

                        const response = await authSystem.secureRequest('/api/cadastro.php', {
                            method: 'POST',
                            body: JSON.stringify(formData)
                        });

                        const result = await response.json();

                        if (response.ok && result.sucesso) {
                            msgDiv.style.color = '#4CAF50';
                            msgDiv.textContent = 'Cadastro realizado com sucesso! Efetuando login...';

                            const loginResponse = await fetch('/api/login.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                credentials: 'include',
                                body: JSON.stringify({
                                    username: formData.username,
                                    senha: formData.senha
                                })
                            });

                            if (loginResponse.ok) {
                                setTimeout(() => {
                                    toggleModalCadastro(false);
                                    fetchUserInfo();
                                    setTimeout(() => {
                                        window.location.href = '/deposito/';
                                    }, 800);
                                }, 1000);
                            } else {
                                msgDiv.textContent = 'Cadastro OK, mas erro no login. Entre manualmente.';
                            }

                        } else {
                            throw new Error(result.erro || 'Erro no cadastro');
                        }

                    } catch (error) {
                        console.error('Erro no cadastro:', error);
                        msgDiv.style.color = '#f44336';

                        if (error.message.includes('Token') || error.message.includes('401')) {
                            msgDiv.textContent = 'Erro de segurança. Recarregue a página e tente novamente.';
                        } else if (error.message.includes('fetch')) {
                            msgDiv.textContent = 'Erro de conexão. Verifique sua internet.';
                        } else {
                            msgDiv.textContent = error.message || 'Erro interno. Tente novamente.';
                        }

                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const formLogin = document.getElementById('formLogin');

            if (formLogin) {
                formLogin.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const username = document.getElementById('loginUsername').value.trim();
                    const senha = document.getElementById('loginSenha').value;
                    const msgDiv = document.getElementById('loginMsg');

                    msgDiv.textContent = '';

                    try {
                        const response = await fetch('/api/login.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include',
                            body: JSON.stringify({ username, senha })
                        });

                        const result = await response.json();

                        if (response.ok) {
                            msgDiv.style.color = '#4CAF50';
                            msgDiv.textContent = 'Login realizado com sucesso!';
                            setTimeout(() => {
                                toggleModalLogin(false);
                                fetchUserInfo();
                            }, 1500);
                        } else {
                            msgDiv.style.color = '#f44336';
                            msgDiv.textContent = result.erro || 'Usuário ou senha inválidos.';
                        }
                    } catch (error) {
                        msgDiv.style.color = '#f44336';
                        msgDiv.textContent = 'Erro ao conectar ao servidor.';
                        console.error(error);
                    }
                });
            }
        });

        async function fetchUserInfo() {
            try {
                const res = await fetch('/api/user_info.php', { credentials: 'include' });
                if (!res.ok) throw new Error('Não autenticado');
                const data = await res.json();
                document.getElementById('authButtons').style.display = 'none';
                document.getElementById('userInfo').style.display = 'flex';
                if (data.saldo) {
                    document.getElementById('userBalance').textContent = data.saldo;
                }
            } catch {
                document.getElementById('authButtons').style.display = 'flex';
                document.getElementById('userInfo').style.display = 'none';
            }
        }

        function getURLParameters() {
            const params = new URLSearchParams(window.location.search);
            return {
                click_id: params.get('click_id') || '',
                pixel_id: params.get('pixel_id') || '',
                campaign_id: params.get('CampaignID') || '',
                adset_id: params.get('adSETID') || '',
                creative_id: params.get('CreativeID') || '',
                utm_source: params.get('utm_source') || '',
                utm_campaign: params.get('utm_campaign') || '',
                utm_medium: params.get('utm_medium') || '',
                utm_content: params.get('utm_content') || '',
                utm_term: params.get('utm_term') || '',
                utm_id: params.get('utm_id') || '',
                fbclid: params.get('fbclid') || ''
            };
        }

        document.addEventListener('DOMContentLoaded', function () {
            const userMenuBtn = document.getElementById('userMenuBtn');
            const userDropdown = document.getElementById('userDropdown');
            const userMenu = document.querySelector('.user-menu');

            if (userMenuBtn && userDropdown) {
                userMenuBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('show');
                    userMenu.classList.toggle('active');
                });

                document.addEventListener('click', function (e) {
                    if (!userMenu.contains(e.target)) {
                        userDropdown.classList.remove('show');
                        userMenu.classList.remove('active');
                    }
                });
            }

            fetchUserInfo();
        });

    </script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const slides = document.querySelectorAll('.carousel-slide');
            let current = 0;

            setInterval(() => {
                slides[current].classList.remove('active');
                current = (current + 1) % slides.length;
                slides[current].classList.add('active');
            }, 5000);
        });
    </script>

    <script src="/assets/js/simple-tracking.js"></script>
    <script src="/assets/js/carrossel-wins.js"></script>
    <script src="/assets/js/deposit-success-modal.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const transacaoId = localStorage.getItem('pendingTransactionId');
            if (!transacaoId) return;

            try {
                const res = await fetch(`/api/status_transaction.php?transaction_id=${transacaoId}`);
                const data = await res.json();

                if (data.success && data.status === 'paid') {
                    localStorage.removeItem('pendingTransactionId');

                    const saldo = await fetch('/api/user_info.php', { credentials: 'include' })
                        .then(r => r.json())
                        .then(d => parseFloat((d.saldo || '0').replace(',', '.')))
                        .catch(() => 0.00);

                    const waitForModal = () => {
                        if (typeof window.showDepositSuccessModal === 'function') {
                            window.showDepositSuccessModal(
                                data.amount || 0.00,
                                'PIX',
                                transacaoId,
                                saldo
                            );
                        } else {
                            setTimeout(waitForModal, 300);
                        }
                    };
                    waitForModal();
                }
            } catch (err) {
                console.error('Erro ao verificar transação pendente:', err);
            }
        });
    </script>

    <script>
        // document.addEventListener('DOMContentLoaded', function () {
        //     const lastShown = localStorage.getItem('modalCadastroShown');
        //     const today = new Date().toDateString();

        //     if (lastShown !== today) {
        //         fetch('/api/user_info.php', { credentials: 'include' })
        //             .then(res => res.ok ? res.json() : Promise.reject())
        //             .then(data => {
        //             })
        //             .catch(() => {
        //                 setTimeout(() => {
        //                     toggleModalCadastro(true);
        //                     localStorage.setItem('modalCadastroShown', today);
        //                 }, 1500);
        //             });
        //     }
        // });
    </script>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>