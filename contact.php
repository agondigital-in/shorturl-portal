<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Ads Platform</title>
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
        
        .header {
            background: linear-gradient(to right, #1976d2, #2196f3);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .main-content {
            flex: 1;
            padding: 2rem 0;
        }
        
        .content-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(33, 150, 243, 0.2);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer {
            background: linear-gradient(to right, #1976d2, #2196f3);
            color: white;
            padding: 1.5rem 0;
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
        
        h1, h2 {
            color: #0d47a1;
        }
        
        h1 {
            border-bottom: 2px solid #bbdefb;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .contact-info {
            background: rgba(227, 242, 253, 0.5);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .contact-icon {
            font-size: 2rem;
            color: #1976d2;
            margin-bottom: 1rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #bbdefb;
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #1976d2;
            box-shadow: 0 0 0 0.25rem rgba(25, 118, 210, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(to right, #1976d2, #2196f3);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 8px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, #1565c0, #1e88e5);
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
                        <a href="contact.php" class="text-white text-decoration-none fw-bold">Contact Us</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="content-card">
                        <h1>Contact Us</h1>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="contact-info">
                                  
                                </div>
                                
                                <div class="contact-info">
                                    <div class="text-center">
                                        <div class="contact-icon">
                                            <i class="bi bi-envelope"></i>
                                        </div>
                                        <h3>Email Us</h3>
                                        <p>General Inquiries: info@agondigital.in<br>
                                        Support: support@agondigital.in<br>
                                        Sales: sales@agondigital.in</p>
                                    </div>
                                </div>
                                
                               
                            </div>
                            
                            <div class="col-md-6">
                                <h3>Send us a Message</h3>
                                <form>
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="subject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="subject" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea class="form-control" id="message" rows="5" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Send Message</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <h3>Business Hours</h3>
                                <p>Monday - Friday: 9:00 AM - 6:00 PM<br>
                                Saturday: 10:00 AM - 4:00 PM<br>
                                Sunday: Closed</p>
                                
                                <h3>Follow Us</h3>
                                <div class="d-flex">
                                    <a href="#" class="btn btn-outline-primary me-2"><i class="bi bi-facebook"></i> Facebook</a>
                                    <a href="#" class="btn btn-outline-info me-2"><i class="bi bi-twitter"></i> Twitter</a>
                                    <a href="#" class="btn btn-outline-danger me-2"><i class="bi bi-instagram"></i> Instagram</a>
                                    <a href="#" class="btn btn-outline-primary"><i class="bi bi-linkedin"></i> LinkedIn</a>
                                </div>
                            </div>
                        </div>
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
</body>
</html>