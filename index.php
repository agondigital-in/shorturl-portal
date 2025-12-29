<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agon Digital - Premium Advertising Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #ec4899;
            --dark: #0f172a;
            --light: #f8fafc;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--dark);
            color: #fff;
            overflow-x: hidden;
        }
        
        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
        }
        
        .bg-animation::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%236366f1' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
</style>
</head>
<body>
    <div class="bg-animation"></div>

    <style>
        /* Floating Orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            animation: float 20s infinite;
            pointer-events: none;
        }
        
        .orb-1 {
            width: 600px;
            height: 600px;
            background: var(--primary);
            top: -200px;
            right: -200px;
        }
        
        .orb-2 {
            width: 400px;
            height: 400px;
            background: var(--secondary);
            bottom: -100px;
            left: -100px;
            animation-delay: -10s;
        }
        
        .orb-3 {
            width: 300px;
            height: 300px;
            background: #06b6d4;
            top: 50%;
            left: 50%;
            animation-delay: -5s;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(50px, -50px) scale(1.1); }
            50% { transform: translate(-30px, 30px) scale(0.9); }
            75% { transform: translate(30px, 50px) scale(1.05); }
        }
        
        /* Navbar */
        .navbar {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .navbar.scrolled {
            padding: 0.5rem 0;
            background: rgba(15, 23, 42, 0.95);
        }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.7) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            color: #fff !important;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .btn-nav {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff !important;
            padding: 0.6rem 1.5rem !important;
            border-radius: 50px;
            font-weight: 600;
        }
        
        .btn-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 120px 0 80px;
            position: relative;
        }
        
        .hero-content {
            position: relative;
            z-index: 10;
        }
        
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(99, 102, 241, 0.2);
            border: 1px solid rgba(99, 102, 241, 0.3);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            margin-bottom: 2rem;
            animation: fadeInUp 1s;
        }
        
        .hero-badge i {
            color: var(--primary);
        }
        
        .hero h1 {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            animation: fadeInUp 1s 0.1s both;
        }
        
        .hero h1 span {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero p {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 2.5rem;
            max-width: 500px;
            animation: fadeInUp 1s 0.2s both;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            animation: fadeInUp 1s 0.3s both;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.4);
            color: #fff;
        }
        
        .btn-outline-custom {
            background: transparent;
            color: #fff;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            border: 2px solid rgba(255, 255, 255, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-outline-custom:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
            color: #fff;
            transform: translateY(-3px);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Hero Image/Dashboard Preview */
        .hero-visual {
            position: relative;
            animation: fadeInUp 1s 0.4s both;
        }
        
        .dashboard-preview {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }
        
        .preview-header {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .preview-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .preview-dot.red { background: #ef4444; }
        .preview-dot.yellow { background: #eab308; }
        .preview-dot.green { background: #22c55e; }
        
        .preview-content {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 20px;
        }
        
        .preview-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .preview-stat:last-child {
            border-bottom: none;
        }
        
        .preview-stat-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }
        
        .preview-stat-value {
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .preview-stat-value.green { color: #22c55e; }
        .preview-stat-value.blue { color: #3b82f6; }
        .preview-stat-value.purple { color: var(--primary); }
        .preview-stat-value.pink { color: var(--secondary); }
        
        /* Stats Section */
        .stats-section {
            padding: 80px 0;
            position: relative;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        
        .stat-icon.purple { background: rgba(99, 102, 241, 0.2); color: var(--primary); }
        .stat-icon.pink { background: rgba(236, 72, 153, 0.2); color: var(--secondary); }
        .stat-icon.cyan { background: rgba(6, 182, 212, 0.2); color: #06b6d4; }
        .stat-icon.green { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.95rem;
        }

        /* Features Section */
        .features-section {
            padding: 100px 0;
            position: relative;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-badge {
            display: inline-block;
            background: rgba(99, 102, 241, 0.2);
            color: var(--primary);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
        }
        
        .section-header h2 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .section-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 2.5rem;
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), transparent);
            opacity: 0;
            transition: opacity 0.4s;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }
        
        .feature-card:hover::before {
            opacity: 1;
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .feature-icon.gradient-1 {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3), rgba(236, 72, 153, 0.3));
            color: #fff;
        }
        
        .feature-icon.gradient-2 {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.3), rgba(34, 197, 94, 0.3));
            color: #fff;
        }
        
        .feature-icon.gradient-3 {
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.3), rgba(249, 115, 22, 0.3));
            color: #fff;
        }
        
        .feature-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .feature-card p {
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.7;
        }

        /* Login Cards Section */
        .login-section {
            padding: 100px 0;
            position: relative;
        }
        
        .login-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .login-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            padding: 3rem;
            text-align: center;
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.4s;
            pointer-events: none;
            z-index: 0;
        }
        
        .login-card:hover {
            transform: translateY(-15px);
            border-color: var(--primary);
            box-shadow: 0 30px 60px rgba(99, 102, 241, 0.2);
        }
        
        .login-card:hover::before {
            opacity: 1;
        }
        
        .login-card.publisher::before {
            background: radial-gradient(circle, rgba(236, 72, 153, 0.1) 0%, transparent 70%);
        }
        
        .login-card.publisher:hover {
            border-color: var(--secondary);
            box-shadow: 0 30px 60px rgba(236, 72, 153, 0.2);
        }
        
        .login-icon {
            width: 100px;
            height: 100px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 2rem;
            position: relative;
        }
        
        .login-icon.admin {
            background: linear-gradient(135deg, var(--primary), #818cf8);
            color: #fff;
        }
        
        .login-icon.publisher {
            background: linear-gradient(135deg, var(--secondary), #f472b6);
            color: #fff;
        }
        
        .login-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .login-card p {
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 2rem;
            line-height: 1.7;
        }
        
        .login-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 1rem 2.5rem;
            border-radius: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
            z-index: 10;
        }
        
        .login-btn.admin {
            background: linear-gradient(135deg, var(--primary), #818cf8);
            color: #fff;
        }
        
        .login-btn.publisher {
            background: transparent;
            border: 2px solid var(--secondary);
            color: var(--secondary);
        }
        
        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            color: #fff;
        }
        
        .login-btn.publisher:hover {
            background: var(--secondary);
        }

        /* Footer */
        .footer {
            background: rgba(0, 0, 0, 0.3);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 60px 0 30px;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-brand {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .footer-links {
            display: flex;
            gap: 2rem;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .footer-links a:hover {
            color: #fff;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 3rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero-buttons {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .login-grid {
                grid-template-columns: 1fr;
            }
            
            .section-header h2 {
                font-size: 2rem;
            }
            
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>

    <!-- Floating Orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-rocket me-2"></i>Agon Digital
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars text-white"></i>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#stats">Stats</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="nav-link btn-nav" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <div class="hero-badge">
                            <i class="fas fa-bolt"></i>
                            <span>Premium Advertising Platform</span>
                        </div>
                        <h1>Grow Your Business with <span>Smart Ads</span></h1>
                        <p>Powerful platform for managing campaigns, tracking performance, and maximizing your ROI with real-time analytics.</p>
                        <div class="hero-buttons">
                            <a href="login.php" class="btn-primary-custom">
                                <i class="fas fa-rocket"></i>
                                Get Started
                            </a>
                            <a href="#features" class="btn-outline-custom">
                                <i class="fas fa-play-circle"></i>
                                Learn More
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-visual">
                        <div class="dashboard-preview">
                            <div class="preview-header">
                                <div class="preview-dot red"></div>
                                <div class="preview-dot yellow"></div>
                                <div class="preview-dot green"></div>
                            </div>
                            <div class="preview-content">
                                <div class="preview-stat">
                                    <span class="preview-stat-label">Total Clicks</span>
                                    <span class="preview-stat-value green">1,234,567</span>
                                </div>
                                <div class="preview-stat">
                                    <span class="preview-stat-label">Active Campaigns</span>
                                    <span class="preview-stat-value blue">248</span>
                                </div>
                                <div class="preview-stat">
                                    <span class="preview-stat-label">Revenue</span>
                                    <span class="preview-stat-value purple">$45,890</span>
                                </div>
                                <div class="preview-stat">
                                    <span class="preview-stat-label">Conversion Rate</span>
                                    <span class="preview-stat-value pink">12.5%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section" id="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Active Campaigns</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon pink">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Advertisers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon cyan">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="stat-number">2K+</div>
                    <div class="stat-label">Publishers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-mouse-pointer"></i>
                    </div>
                    <div class="stat-number">1M+</div>
                    <div class="stat-label">Monthly Clicks</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Section -->
    <section class="login-section" id="login">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">Get Started</span>
                <h2>Choose Your Portal</h2>
                <p>Access your dashboard and start managing your campaigns today</p>
            </div>
            <div class="login-grid">
                <div class="login-card admin">
                    <div class="login-icon admin">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3>Admin Portal</h3>
                    <p>Full control over campaigns, advertisers, publishers, and analytics. Manage everything from one powerful dashboard.</p>
                    <a href="login.php" class="login-btn admin">
                        <i class="fas fa-sign-in-alt"></i>
                        Login as Admin
                    </a>
                </div>
                <div class="login-card publisher">
                    <div class="login-icon publisher">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3>Publisher Portal</h3>
                    <p>Track your campaigns, monitor performance metrics, and access detailed reports to optimize your earnings.</p>
                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <a href="publisher_login.php" class="login-btn publisher">
                            <i class="fas fa-sign-in-alt"></i>
                            Login
                        </a>
                        <!-- <a href="publisher_register.php" class="login-btn admin" style="background: linear-gradient(135deg, var(--secondary), #f472b6);">
                            <i class="fas fa-user-plus"></i>
                            Register
                        </a> -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">Features</span>
                <h2>Everything You Need</h2>
                <p>Powerful tools to manage, track, and optimize your advertising campaigns</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon gradient-1">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Campaign Management</h3>
                    <p>Create, manage, and optimize your advertising campaigns with powerful tools and real-time analytics dashboard.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon gradient-2">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Advanced Analytics</h3>
                    <p>Get deep insights with comprehensive reports, data visualization, and performance tracking metrics.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon gradient-3">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <h3>Publisher Network</h3>
                    <p>Connect with thousands of publishers worldwide to expand your reach and maximize your impact.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <i class="fas fa-rocket me-2"></i>Agon Digital
                </div>
                <div class="footer-links">
                    <a href="contact.php">
                        <i class="fas fa-envelope"></i> Contact
                    </a>
                    <a href="privacy_policy.php">
                        <i class="fas fa-shield-alt"></i> Privacy
                    </a>
                    <a href="terms_of_service.php">
                        <i class="fas fa-file-contract"></i> Terms
                    </a>
                    <a href="#">
                        <i class="fas fa-question-circle"></i> Support
                    </a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Agon Digital. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animate stats on scroll
        const observerOptions = {
            threshold: 0.5
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.stat-card, .feature-card, .login-card').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>
