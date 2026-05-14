<?php
// super_admin/includes/header.php - Common Header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../login.php');
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = $page_title ?? 'Super Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Ads Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin-theme.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 280px;
            --header-height: 70px;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --sidebar-bg: linear-gradient(180deg, #1a1f3a 0%, #0f1419 100%);
        }
        
        * { font-family: 'Inter', sans-serif; }
        
        body {
            background: #f1f5f9;
            min-height: 100vh;
        }
        
        /* Top Navbar */
        .top-navbar {
            height: var(--header-height);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .top-navbar.expanded {
            left: 0;
        }
        
        .top-navbar .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            color: #1e293b;
        }
        
        .user-dropdown .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            border-radius: 50px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .user-dropdown .dropdown-toggle:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        
        .user-dropdown .dropdown-toggle::after { display: none; }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        /* Theme Switcher */
        .theme-switcher {
            display: flex;
            gap: 8px;
            background: #f8fafc;
            padding: 6px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        
        .theme-btn {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        
        .theme-btn:hover {
            background: #e2e8f0;
            color: #475569;
            transform: scale(1.05);
        }
        
        .theme-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
        }
        
        /* Dark Theme */
        body.dark-theme {
            background: #0f172a;
            color: #ffffff;
        }
        
        body.dark-theme .top-navbar {
            background: #1e293b;
            border-bottom-color: #334155;
        }
        
        body.dark-theme .sidebar {
            background: #1e293b;
            border-right-color: #334155;
        }
        
        body.dark-theme .sidebar-header {
            border-bottom-color: #334155;
            background: #1e293b;
        }
        
        body.dark-theme .sidebar-link {
            color: #ffffff;
        }
        
        body.dark-theme .sidebar-link:hover {
            background: #334155;
            color: #ffffff;
        }
        
        body.dark-theme .nav-section-title {
            color: #94a3b8;
        }
        
        body.dark-theme .card,
        body.dark-theme .stat-box,
        body.dark-theme .chart-card,
        body.dark-theme .recent-card {
            background: #1e293b;
            border-color: #334155;
            color: #ffffff;
        }
        
        body.dark-theme .page-title,
        body.dark-theme .camp-name,
        body.dark-theme .stat-box h3,
        body.dark-theme .chart-card h5,
        body.dark-theme .recent-header h5,
        body.dark-theme .dash-header h1 {
            color: #ffffff;
        }
        
        body.dark-theme .stat-box span,
        body.dark-theme .recent-table th {
            color: #e2e8f0;
        }
        
        body.dark-theme .recent-table td {
            border-bottom-color: #334155;
            color: #ffffff;
        }
        
        body.dark-theme .recent-table tr:hover {
            background: #334155;
        }
        
        body.dark-theme .recent-header {
            background: #1e293b;
            border-bottom-color: #334155;
        }
        
        body.dark-theme .user-dropdown .dropdown-toggle {
            background: #334155;
            border-color: #475569;
            color: #ffffff;
        }
        
        body.dark-theme .theme-switcher {
            background: #334155;
            border-color: #475569;
        }
        
        body.dark-theme .theme-btn {
            color: #94a3b8;
        }
        
        body.dark-theme .theme-btn:hover {
            background: #475569;
            color: #ffffff;
        }
        
        body.dark-theme .breadcrumb-item,
        body.dark-theme .breadcrumb-item a {
            color: #e2e8f0;
        }
        
        body.dark-theme .breadcrumb-item.active {
            color: #ffffff;
        }
        
        body.dark-theme .form-control,
        body.dark-theme .form-select {
            background: #334155;
            border-color: #475569;
            color: #ffffff;
        }
        
        body.dark-theme .form-label {
            color: #ffffff;
        }
        
        body.dark-theme .card-header {
            color: #ffffff;
            border-bottom-color: #334155;
        }
        
        body.dark-theme .dropdown-menu {
            background: #1e293b;
            border-color: #334155;
        }
        
        body.dark-theme .dropdown-item {
            color: #ffffff;
        }
        
        body.dark-theme .dropdown-item:hover {
            background: #334155;
            color: #ffffff;
        }
        
        body.dark-theme .empty-state {
            color: #94a3b8;
        }
        
        body.dark-theme .empty-state p {
            color: #e2e8f0;
        }
        
        /* Eye Protection Theme */
        body.eye-protection-theme {
            background: #f5f1e8;
            color: #3e3832;
        }
        
        body.eye-protection-theme .top-navbar {
            background: #faf8f3;
            border-bottom-color: #e8dfc8;
        }
        
        body.eye-protection-theme .sidebar {
            background: #faf8f3;
            border-right-color: #e8dfc8;
        }
        
        body.eye-protection-theme .sidebar-header {
            border-bottom-color: #e8dfc8;
            background: #faf8f3;
        }
        
        body.eye-protection-theme .sidebar-link {
            color: #5c5548;
        }
        
        body.eye-protection-theme .sidebar-link:hover {
            background: #f0ebe0;
            color: #3e3832;
        }
        
        body.eye-protection-theme .nav-section-title {
            color: #8b8272;
        }
        
        body.eye-protection-theme .card,
        body.eye-protection-theme .stat-box,
        body.eye-protection-theme .chart-card,
        body.eye-protection-theme .recent-card {
            background: #faf8f3;
            border-color: #e8dfc8;
            color: #3e3832;
        }
        
        body.eye-protection-theme .page-title,
        body.eye-protection-theme .camp-name,
        body.eye-protection-theme .stat-box h3 {
            color: #3e3832;
        }
        
        body.eye-protection-theme .stat-box span,
        body.eye-protection-theme .recent-table th {
            color: #6b6456;
        }
        
        body.eye-protection-theme .recent-table td {
            border-bottom-color: #e8dfc8;
            color: #5c5548;
        }
        
        body.eye-protection-theme .recent-table tr:hover {
            background: #f0ebe0;
        }
        
        body.eye-protection-theme .recent-header {
            background: #faf8f3;
            border-bottom-color: #e8dfc8;
        }
        
        body.eye-protection-theme .user-dropdown .dropdown-toggle {
            background: #f0ebe0;
            border-color: #d9d0ba;
            color: #3e3832;
        }
        
        body.eye-protection-theme .theme-switcher {
            background: #f0ebe0;
            border-color: #d9d0ba;
        }
        
        body.eye-protection-theme .theme-btn {
            color: #8b8272;
        }
        
        body.eye-protection-theme .theme-btn:hover {
            background: #e8dfc8;
            color: #5c5548;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #ffffff;
            z-index: 1001;
            transition: all 0.3s ease;
            overflow-y: auto;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.08);
            border-right: 1px solid #e2e8f0;
        }
        
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        
        /* Sidebar Toggle Button */
        .sidebar-toggle-btn {
            position: fixed;
            left: var(--sidebar-width);
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 0 12px 12px 0;
            color: white;
            font-size: 18px;
            cursor: pointer;
            z-index: 1002;
            transition: all 0.3s ease;
            box-shadow: 2px 0 15px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-toggle-btn:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            box-shadow: 2px 0 20px rgba(102, 126, 234, 0.6);
            width: 45px;
        }
        
        .sidebar-toggle-btn i {
            transition: transform 0.3s ease;
        }
        
        .sidebar.collapsed + .sidebar-toggle-btn {
            left: 0;
        }
        
        .sidebar.collapsed + .sidebar-toggle-btn i {
            transform: rotate(180deg);
        }
        
        .sidebar-header {
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 24px;
            border-bottom: 2px solid #f1f5f9;
            background: #ffffff;
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        
        .sidebar-logo-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }
        
        .sidebar-logo:hover .sidebar-logo-icon {
            transform: scale(1.05) rotate(5deg);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .sidebar-logo-text {
            font-size: 1.25rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .sidebar-nav {
            padding: 20px 16px;
        }
        
        .nav-section {
            margin-bottom: 28px;
        }
        
        .nav-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #64748b;
            padding: 0 12px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 18px;
            border-radius: 12px;
            color: #475569;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.3s ease;
            margin-bottom: 6px;
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            transform: scaleY(0);
            transition: transform 0.3s ease;
            border-radius: 0 4px 4px 0;
        }
        
        .sidebar-link:hover::before {
            transform: scaleY(1);
        }
        
        .sidebar-link:hover {
            background: #f8fafc;
            color: #0f172a;
            transform: translateX(4px);
        }
        
        .sidebar-link:hover i {
            transform: scale(1.15);
        }
        
        .sidebar-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
        }
        
        .sidebar-link.active::before {
            display: none;
        }
        
        .sidebar-link i {
            width: 24px;
            text-align: center;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .sidebar-link span {
            font-size: 15px;
            font-weight: 500;
        }
        
        /* Colorful Icons for Each Section */
        .nav-section:nth-child(1) .sidebar-link i { color: #8b5cf6; } /* Main - Purple */
        .nav-section:nth-child(2) .sidebar-link i { color: #f59e0b; } /* Campaigns - Orange */
        .nav-section:nth-child(3) .sidebar-link i { color: #3b82f6; } /* Users - Blue */
        .nav-section:nth-child(4) .sidebar-link i { color: #10b981; } /* Analytics - Green */
        .nav-section:nth-child(5) .sidebar-link i { color: #06b6d4; } /* CPV - Cyan */
        .nav-section:nth-child(6) .sidebar-link i { color: #ec4899; } /* Finance - Pink */
        .nav-section:nth-child(7) .sidebar-link i { color: #64748b; } /* Settings - Gray */
        
        .sidebar-link:hover i,
        .sidebar-link.active i {
            color: inherit;
        }
        
        .sidebar-link.active i {
            color: #ffffff;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding-top: var(--header-height);
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .content-wrapper {
            padding: 24px;
        }
        
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }
        
        .breadcrumb-item a {
            color: #64748b;
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: #1e293b;
            font-weight: 500;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            background: white;
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid #f1f5f9;
            padding: 20px 24px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .card-body { padding: 24px; }
        
        /* Stats Cards */
        .stat-card {
            border-radius: 16px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            color: white;
        }
        
        .stat-card.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card.success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-card.info { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }
        .stat-card.warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-card.danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        
        .stat-card-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 48px;
            opacity: 0.3;
        }
        
        .stat-card-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .stat-card-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Tables */
        .table { margin-bottom: 0; }
        
        .table th {
            background: #f8fafc;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 16px;
        }
        
        .table td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }
        
        .table tbody tr:hover { background: #f8fafc; }
        
        /* Buttons */
        .btn { border-radius: 8px; font-weight: 500; padding: 10px 20px; }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
            transform: translateY(-1px);
        }
        
        .btn-soft-primary { background: #eff6ff; color: #3b82f6; border: none; }
        .btn-soft-primary:hover { background: #3b82f6; color: white; }
        
        .btn-soft-danger { background: #fef2f2; color: #ef4444; border: none; }
        .btn-soft-danger:hover { background: #ef4444; color: white; }
        
        .btn-soft-success { background: #f0fdf4; color: #10b981; border: none; }
        .btn-soft-success:hover { background: #10b981; color: white; }
        
        .btn-soft-warning { background: #fffbeb; color: #f59e0b; border: none; }
        .btn-soft-warning:hover { background: #f59e0b; color: white; }
        
        /* Badges */
        .badge { font-weight: 500; padding: 6px 12px; border-radius: 6px; }
        .badge-soft-success { background: #f0fdf4; color: #10b981; }
        .badge-soft-danger { background: #fef2f2; color: #ef4444; }
        .badge-soft-warning { background: #fffbeb; color: #f59e0b; }
        .badge-soft-primary { background: #eff6ff; color: #3b82f6; }
        
        /* Forms */
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            font-size: 14px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }
        
        .form-label {
            font-weight: 500;
            color: #475569;
            margin-bottom: 8px;
        }
        
        /* Alerts */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
        }
        
        .alert-success { background: #f0fdf4; color: #166534; }
        .alert-danger { background: #fef2f2; color: #991b1b; }
        .alert-warning { background: #fffbeb; color: #92400e; }
        .alert-info { background: #eff6ff; color: #1e40af; }
        
        /* Mobile Responsive */
        
        /* Large Desktop */
        @media (min-width: 1400px) {
            .content-wrapper {
                max-width: 1400px;
                margin: 0 auto;
            }
        }
        
        /* Large Tablets and Below */
        @media (max-width: 1024px) {
            :root {
                --sidebar-width: 260px;
            }
            
            .sidebar-nav {
                padding: 18px 14px;
            }
            
            .sidebar-link {
                padding: 12px 14px;
                font-size: 14px;
            }
            
            .page-title {
                font-size: 1.6rem;
            }
        }
        
        /* Tablets and Mobile Landscape */
        @media (max-width: 991.98px) {
            :root {
                --sidebar-width: 280px;
                --header-height: 64px;
            }
            
            .sidebar { 
                transform: translateX(-100%);
                box-shadow: none;
            }
            
            .sidebar.show { 
                transform: translateX(0);
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
            }
            
            .sidebar.collapsed {
                transform: translateX(-100%);
            }
            
            .sidebar-toggle-btn {
                display: none !important;
            }
            
            .top-navbar { 
                left: 0;
                height: 64px;
            }
            
            .main-content { 
                margin-left: 0;
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1000;
                display: none;
                backdrop-filter: blur(2px);
            }
            
            .sidebar-overlay.show { 
                display: block;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .content-wrapper {
                padding: 18px;
            }
        }
        
        /* Mobile Portrait */
        @media (max-width: 768px) {
            :root {
                --header-height: 60px;
                --sidebar-width: 280px;
            }
            
            .top-navbar {
                height: 60px;
            }
            
            .top-navbar .container-fluid {
                padding: 0 16px;
            }
            
            .user-dropdown .dropdown-toggle {
                padding: 6px 12px;
                font-size: 0.85rem;
            }
            
            .user-avatar {
                width: 32px;
                height: 32px;
                font-size: 13px;
            }
            
            .theme-switcher {
                padding: 4px;
                gap: 6px;
            }
            
            .theme-btn {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }
            
            .content-wrapper {
                padding: 16px;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .sidebar-header {
                padding: 0 20px;
            }
            
            .sidebar-logo-icon {
                width: 38px;
                height: 38px;
                font-size: 18px;
            }
            
            .sidebar-logo-text {
                font-size: 1.1rem;
            }
            
            .sidebar-nav {
                padding: 16px 12px;
            }
            
            .nav-section {
                margin-bottom: 20px;
            }
            
            .nav-section-title {
                font-size: 10px;
                padding: 0 10px;
                margin-bottom: 8px;
            }
            
            .sidebar-link {
                padding: 10px 14px;
                font-size: 13px;
                gap: 12px;
            }
            
            .sidebar-link i {
                font-size: 15px;
            }
            
            .card-body {
                padding: 16px;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 14px;
            }
        }
        
        /* Small Mobile */
        @media (max-width: 576px) {
            :root {
                --header-height: 56px;
                --sidebar-width: 260px;
            }
            
            .top-navbar {
                height: 56px;
            }
            
            .top-navbar .container-fluid {
                padding: 0 12px;
            }
            
            .top-navbar .d-flex {
                gap: 8px !important;
            }
            
            .user-dropdown .dropdown-toggle {
                padding: 5px 10px;
                font-size: 0.8rem;
            }
            
            .user-dropdown .dropdown-toggle span {
                display: none !important;
            }
            
            .user-avatar {
                width: 30px;
                height: 30px;
                font-size: 12px;
            }
            
            .theme-switcher {
                padding: 3px;
                gap: 4px;
            }
            
            .theme-btn {
                width: 30px;
                height: 30px;
                font-size: 13px;
            }
            
            .content-wrapper {
                padding: 12px;
            }
            
            .page-title {
                font-size: 1.3rem;
            }
            
            .breadcrumb {
                font-size: 0.8rem;
            }
            
            .sidebar {
                width: 260px;
            }
            
            .sidebar-header {
                padding: 0 16px;
                height: 56px;
            }
            
            .sidebar-logo {
                gap: 10px;
            }
            
            .sidebar-logo-icon {
                width: 36px;
                height: 36px;
                font-size: 16px;
            }
            
            .sidebar-logo-text {
                font-size: 1rem;
            }
            
            .sidebar-nav {
                padding: 14px 10px;
            }
            
            .nav-section {
                margin-bottom: 18px;
            }
            
            .nav-section-title {
                font-size: 9px;
                padding: 0 8px;
                margin-bottom: 6px;
                letter-spacing: 1px;
            }
            
            .sidebar-link {
                padding: 10px 12px;
                font-size: 13px;
                gap: 10px;
                border-radius: 10px;
            }
            
            .sidebar-link i {
                width: 20px;
                font-size: 14px;
            }
            
            .sidebar-link span {
                font-size: 13px;
            }
            
            .card {
                border-radius: 12px;
            }
            
            .card-header {
                padding: 14px 16px;
                font-size: 0.95rem;
            }
            
            .card-body {
                padding: 14px;
            }
            
            .btn {
                padding: 8px 14px;
                font-size: 13px;
            }
            
            .form-control, .form-select {
                padding: 10px 14px;
                font-size: 14px;
            }
            
            .form-label {
                font-size: 0.85rem;
                margin-bottom: 6px;
            }
        }
        
        /* Extra Small Mobile */
        @media (max-width: 400px) {
            :root {
                --header-height: 52px;
                --sidebar-width: 240px;
            }
            
            .top-navbar {
                height: 52px;
            }
            
            .top-navbar .container-fluid {
                padding: 0 10px;
            }
            
            .user-dropdown .dropdown-toggle {
                padding: 4px 8px;
            }
            
            .user-avatar {
                width: 28px;
                height: 28px;
                font-size: 11px;
            }
            
            .theme-switcher {
                padding: 2px;
                gap: 3px;
            }
            
            .theme-btn {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }
            
            .content-wrapper {
                padding: 10px;
            }
            
            .page-title {
                font-size: 1.2rem;
            }
            
            .breadcrumb {
                font-size: 0.75rem;
            }
            
            .sidebar {
                width: 240px;
            }
            
            .sidebar-header {
                padding: 0 14px;
                height: 52px;
            }
            
            .sidebar-logo-icon {
                width: 34px;
                height: 34px;
                font-size: 15px;
            }
            
            .sidebar-logo-text {
                font-size: 0.95rem;
            }
            
            .sidebar-nav {
                padding: 12px 8px;
            }
            
            .nav-section {
                margin-bottom: 16px;
            }
            
            .nav-section-title {
                font-size: 8px;
                padding: 0 6px;
            }
            
            .sidebar-link {
                padding: 9px 10px;
                font-size: 12px;
                gap: 8px;
            }
            
            .sidebar-link i {
                width: 18px;
                font-size: 13px;
            }
            
            .sidebar-link span {
                font-size: 12px;
            }
            
            .card-header {
                padding: 12px 14px;
                font-size: 0.9rem;
            }
            
            .card-body {
                padding: 12px;
            }
            
            .btn {
                padding: 7px 12px;
                font-size: 12px;
            }
        }
        
        /* Landscape Orientation */
        @media (max-height: 600px) and (orientation: landscape) {
            .sidebar-nav {
                padding: 10px;
            }
            
            .nav-section {
                margin-bottom: 12px;
            }
            
            .nav-section-title {
                margin-bottom: 4px;
                font-size: 9px;
            }
            
            .sidebar-link {
                padding: 8px 12px;
                margin-bottom: 2px;
                font-size: 12px;
            }
            
            .sidebar-link i {
                font-size: 13px;
            }
        }
        
        /* Very Short Screens */
        @media (max-height: 500px) {
            .sidebar-header {
                height: 50px;
            }
            
            .sidebar-nav {
                padding: 8px;
            }
            
            .nav-section {
                margin-bottom: 10px;
            }
            
            .sidebar-link {
                padding: 6px 10px;
                font-size: 11px;
            }
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="sidebar-logo-text">Ads Platform</span>
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">🏠 MAIN</div>
                <a href="dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">📢 CAMPAIGNS</div>
                <a href="campaigns.php" class="sidebar-link <?php echo $current_page === 'campaigns.php' ? 'active' : ''; ?>">
                    <i class="fas fa-rocket"></i>
                    <span>All Campaigns</span>
                </a>
                <a href="add_campaign.php" class="sidebar-link <?php echo $current_page === 'add_campaign.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-square"></i>
                    <span>Add Campaign</span>
                </a>
                <a href="advertiser_campaigns.php" class="sidebar-link <?php echo $current_page === 'advertiser_campaigns.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>Advertiser Campaigns</span>
                </a>
                <a href="publisher_campaigns.php" class="sidebar-link <?php echo $current_page === 'publisher_campaigns.php' ? 'active' : ''; ?>">
                    <i class="fas fa-share-alt"></i>
                    <span>Publisher Campaigns</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">👥 USERS</div>
                <a href="advertisers.php" class="sidebar-link <?php echo $current_page === 'advertisers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-briefcase"></i>
                    <span>Advertisers</span>
                </a>
                <a href="publishers.php" class="sidebar-link <?php echo $current_page === 'publishers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Publishers</span>
                </a>
                <a href="admins.php" class="sidebar-link <?php echo $current_page === 'admins.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i>
                    <span>Admins</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">📊 ANALYTICS</div>
                <a href="all_publishers_daily_clicks.php" class="sidebar-link <?php echo $current_page === 'all_publishers_daily_clicks.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Publishers Stats</span>
                </a>
                <a href="daily_leads_entry.php" class="sidebar-link <?php echo $current_page === 'daily_leads_entry.php' ? 'active' : ''; ?>">
                    <i class="fas fa-pen-to-square"></i>
                    <span>Daily Leads Entry</span>
                </a>
                <a href="daily_report.php" class="sidebar-link <?php echo $current_page === 'daily_report.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-lines"></i>
                    <span>Daily Report</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">👁️ CPV</div>
                <a href="cpv.php" class="sidebar-link <?php echo $current_page === 'cpv.php' ? 'active' : ''; ?>">
                    <i class="fas fa-eye"></i>
                    <span>CPV Campaigns</span>
                </a>
                <a href="cpv_report.php" class="sidebar-link <?php echo $current_page === 'cpv_report.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>CPV Report</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">💰 FINANCE</div>
                <a href="payment_reports.php" class="sidebar-link <?php echo $current_page === 'payment_reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-money-check-dollar"></i>
                    <span>Payment Reports</span>
                </a>
                <a href="ie_budget.php" class="sidebar-link <?php echo $current_page === 'ie_budget.php' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper"></i>
                    <span>IE Budget</span>
                </a>
                <a href="office_expenses.php" class="sidebar-link <?php echo $current_page === 'office_expenses.php' ? 'active' : ''; ?>">
                    <i class="fas fa-wallet"></i>
                    <span>Office Expenses</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">⚙️ SETTINGS</div>
                <a href="change_password.php" class="sidebar-link <?php echo $current_page === 'change_password.php' ? 'active' : ''; ?>">
                    <i class="fas fa-lock"></i>
                    <span>Change Password</span>
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle-btn" id="sidebarToggleDesktop" title="Toggle Sidebar">
        <i class="fas fa-chevron-left"></i>
    </button>
    
    <!-- Top Navbar -->
    <nav class="top-navbar">
        <div class="container-fluid h-100">
            <div class="d-flex align-items-center justify-content-between h-100 px-3">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-link text-dark d-lg-none p-0" id="sidebarToggle">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                    <nav aria-label="breadcrumb" class="d-none d-md-block">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($page_title); ?></li>
                        </ol>
                    </nav>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <!-- Theme Switcher -->
                    <div class="theme-switcher">
                        <button class="theme-btn" data-theme="light" title="Bright Mode">
                            <i class="fas fa-sun"></i>
                        </button>
                        <button class="theme-btn" data-theme="dark" title="Dark Mode">
                            <i class="fas fa-moon"></i>
                        </button>
                        <button class="theme-btn" data-theme="eye-protection" title="Eye Protection">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="dropdown user-dropdown">
                        <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
                            </div>
                            <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                            <i class="fas fa-chevron-down ms-2" style="font-size: 10px;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="content-wrapper">
