<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ads Platform - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            background-attachment: fixed;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #0d47a1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-container {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        
        .hero-section {
            text-align: center;
            margin-bottom: 2rem;
            animation: fadeInDown 1s ease;
        }
        
        .hero-section h1 {
            font-weight: 700;
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #0d47a1;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .hero-section p {
            font-size: 1.25rem;
            color: #1976d2;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(33, 150, 243, 0.2);
            transition: all 0.3s ease;
            border: none;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.8s ease;
            overflow: hidden;
        }
        
        .login-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(33, 150, 243, 0.3);
        }
        
        .login-card .card-body {
            padding: 2rem;
            text-align: center;
        }
        
        .login-card i {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .login-card:hover i {
            transform: scale(1.1);
        }
        
        .login-card .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .login-card .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(to right, #1976d2, #2196f3);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .footer {
            background: linear-gradient(to right, #1976d2, #2196f3);
            color: white;
            padding: 1.5rem 0;
            margin-top: 2rem;
            text-align: center;
        }
        
        .footer a {
            color: #bbdefb;
            text-decoration: none;
        }
        
        .footer a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .role-description {
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
        
        .fade-in-delay-1 {
            animation-delay: 0.2s;
        }
        
        .fade-in-delay-2 {
            animation-delay: 0.4s;
        }
        
        .fade-in-delay-3 {
            animation-delay: 0.6s;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2.2rem;
            }
            
            .hero-section p {
                font-size: 1rem;
            }
            
            .login-card .card-body {
                padding: 1.5rem;
            }
            
            .login-card i {
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .hero-section h1 {
                font-size: 1.8rem;
            }
            
            .hero-section {
                margin-bottom: 1rem;
            }
            
            .login-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="bi bi-megaphone fs-3 me-2"></i>
                    <h2 class="mb-0">Ads Platform</h2>
                </div>
                <div>
                    <nav class="d-flex">
                        <a href="privacy_policy.php" class="text-white me-3 text-decoration-none">Privacy Policy</a>
                        <a href="terms_of_service.php" class="text-white me-3 text-decoration-none">Terms of Service</a>
                        <a href="contact.php" class="text-white text-decoration-none">Contact Us</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="hero-section">
                        <h1>Welcome to Ads Platform</h1>
                        <p class="lead">Manage advertising campaigns, track performance, and maximize your ROI with our comprehensive platform</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="card login-card h-100 fade-in-delay-1">
                                <div class="card-body">
                                    <i class="bi bi-person-badge text-primary"></i>
                                    <h5 class="card-title">Admin Login</h5>
                                    <div class="role-description">
                                        <p class="card-text">Access the admin panel to manage campaigns and advertisers.</p>
                                    </div>
                                    <a href="login.php" class="btn btn-primary">Login as Admin</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="card login-card h-100 fade-in-delay-2">
                                <div class="card-body">
                                    <i class="bi bi-person-check text-success"></i>
                                    <h5 class="card-title">Super Admin Login</h5>
                                    <div class="role-description">
                                        <p class="card-text">Access the super admin panel to manage everything.</p>
                                    </div>
                                    <a href="login.php" class="btn btn-success">Login as Super Admin</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="card login-card h-100 fade-in-delay-3">
                                <div class="card-body">
                                    <i class="bi bi-person text-info"></i>
                                    <h5 class="card-title">Publisher Login</h5>
                                    <div class="role-description">
                                        <p class="card-text">Access your publisher dashboard to view your campaigns.</p>
                                    </div>
                                    <a href="publisher_login.php" class="btn btn-info">Login as Publisher</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p class="text-muted">Select your role to access the appropriate dashboard.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 Ads Platform. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <a href="privacy_policy.php" class="me-3">Privacy Policy</a>
                        <a href="terms_of_service.php" class="me-3">Terms of Service</a>
                        <a href="contact.php">Contact Us</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add subtle hover effects to cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.login-card');
            
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    const icon = this.querySelector('i');
                    icon.style.transform = 'scale(1.1)';
                });
                
                card.addEventListener('mouseleave', function() {
                    const icon = this.querySelector('i');
                    icon.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>