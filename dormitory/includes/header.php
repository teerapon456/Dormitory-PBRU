<?php

// เริ่มใช้ output buffering เพื่อป้องกัน "headers already sent"
ob_start();

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// เริ่มต้น Auth
Auth::init();

// ตรวจสอบ session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    Auth::logout();
    header('Location: ' . Config::$baseUrl . '/login.php?msg=timeout');
    exit;
}

$_SESSION['last_activity'] = time();

// ตรวจสอบสถานะการล็อกอิน
$isLoggedIn = Auth::isLoggedIn();
$isAdmin = Auth::isAdmin();

// กำหนดตัวแปรสำหรับหน้าปัจจุบัน
$current_page = basename($_SERVER['PHP_SELF']);

// ตรวจสอบว่าอยู่ในโฟลเดอร์ admin หรือไม่
$is_admin_page = strpos($_SERVER['PHP_SELF'], '/modules/admin/') !== false;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ระบบจัดการหอพัก - บริหารจัดการหอพักอย่างมีประสิทธิภาพและทันสมัย">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>ระบบจัดการหอพัก</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo Config::$baseUrl; ?>/assets/css/style.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #60a5fa;
            --secondary-color: #3b82f6;
            --accent-color: #a5b4fc;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-color: #f9fafb;
            --dark-color: #111827;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--gray-700);
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .navbar-brand,
        .nav-link,
        .btn {
            font-family: 'Prompt', sans-serif;
        }

        main {
            flex: 1 0 auto;
        }

        /* Navbar Styles */
        .navbar {
            background: #ffffff;
            box-shadow: 0 1px 15px rgba(0, 0, 0, 0.05);
            padding: 0.5rem 0;
            z-index: 1030;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
        }

        .navbar-brand-icon {
            background-color: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
        }

        .nav-link {
            font-weight: 500;
            padding: 0.75rem 1rem !important;
            border-radius: 0.5rem;
            color: var(--gray-700) !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color) !important;
        }

        .nav-link.active {
            font-weight: 600;
        }

        .navbar-nav .nav-item {
            margin: 0 0.25rem;
        }

        /* Dropdown Styles */
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.1);
            border-radius: 0.75rem;
            padding: 0.75rem 0;
            margin-top: 0.5rem;
            animation: fadeIn 0.2s ease-out;
        }

        .dropdown-item {
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            color: var(--gray-700);
            transition: all 0.2s ease;
            border-radius: 0.5rem;
            margin: 0 0.5rem;
            width: calc(100% - 1rem);
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            background-color: rgba(37, 99, 235, 0.08);
            color: var(--primary-color);
        }

        .dropdown-item.active {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
            color: var(--gray-500);
        }

        .dropdown-item:hover i {
            color: var(--primary-color);
        }

        /* Button Styles */
        .btn {
            font-weight: 500;
            padding: 0.6rem 1.25rem;
            border-radius: 0.5rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(37, 99, 235, 0.2);
        }

        /* Alert Styles */
        .alert {
            border-radius: 0.75rem;
            border: none;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: #065f46;
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: #b91c1c;
        }

        .alert i {
            font-size: 1.25rem;
        }

        /* Badge and Notification Styles */
        .brand-highlight {
            color: var(--primary-color);
            font-weight: 700;
        }

        .navbar-notification {
            position: relative;
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translate(25%, -25%);
            box-shadow: 0 2px 5px rgba(239, 68, 68, 0.4);
        }

        /* Card Styles */
        .card {
            border: none;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }

        .card-header {
            background-color: rgba(249, 250, 251, 0.8);
            border-bottom: 1px solid var(--gray-200);
            padding: 1rem 1.25rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* User Profile Button */
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--gray-100);
            margin-right: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            border: 2px solid var(--primary-light);
            font-size: 1rem;
        }

        /* Animation classes */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animated-element {
            animation: fadeIn 0.5s ease-out;
        }

        /* Page Banner Styles */
        .page-banner {
            background: linear-gradient(120deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .page-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('<?php echo Config::$baseUrl; ?>/assets/images/pattern.svg');
            opacity: 0.1;
        }

        .page-banner h1 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            position: relative;
        }

        .page-banner p {
            font-weight: 300;
            margin-bottom: 0;
            font-size: 1.1rem;
            position: relative;
        }

        /* Main container padding */
        .main-container {
            padding-top: 0.5rem;
            padding-bottom: 2rem;
        }

        /* Quick Action Buttons */
        .quick-action {
            padding: 0.45rem 0.85rem;
            font-size: 0.85rem;
            margin-left: 0.5rem;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
        }

        .quick-action i {
            margin-right: 0.5rem;
        }

        /* Mobile Responsive Adjustments */
        @media (max-width: 992px) {
            .navbar-collapse {
                background-color: white;
                padding: 1rem;
                border-radius: 0.75rem;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                margin-top: 0.5rem;
                max-height: 80vh;
                overflow-y: auto;
            }

            .navbar-toggler {
                border: none;
                padding: 0.5rem;
            }

            .navbar-toggler:focus {
                box-shadow: none;
            }

            .user-info {
                display: none;
            }
        }

        /* Dropdown submenu styles */
        .dropdown-item-submenu {
            position: relative;
            cursor: pointer;
        }

        .dropdown-submenu-content {
            display: flex;
            align-items: center;
            padding: 0.25rem 1rem;
        }

        .dropdown-submenu {
            position: absolute;
            left: 100%;
            top: 0;
            display: none;
            margin-top: 0;
            min-width: 200px;
            padding: 0.5rem 0;
            background-color: #fff;
            border: 1px solid rgba(0, 0, 0, .15);
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
            z-index: 1000;
        }

        .dropdown-item-submenu:hover .dropdown-submenu {
            display: block;
        }

        .dropdown-item-submenu:hover {
            background-color: #f8f9fa;
        }

        @media (max-width: 992px) {
            .dropdown-submenu {
                position: static;
                border: none;
                box-shadow: none;
                margin-left: 1rem;
                padding-top: 0;
                padding-bottom: 0;
            }

            .dropdown-submenu-content {
                padding-left: 0;
            }
        }
    </style>
</head>

<body>
    <?php require_once __DIR__ . '/navbar.php'; ?>
    <main class="py-2">
        <div class="container main-container">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show animated-element" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <div>
                            <?php
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show animated-element" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div>
                            <?php
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap components
            var dropdowns = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            dropdowns.forEach(function(dropdown) {
                new bootstrap.Dropdown(dropdown);
            });

            // Handle mobile menu toggle
            var navbarToggler = document.querySelector('.navbar-toggler');
            var navbarCollapse = document.querySelector('.navbar-collapse');
            var dropdownMenus = document.querySelectorAll('.dropdown-menu');

            if (navbarToggler && navbarCollapse) {
                navbarToggler.addEventListener('click', function(e) {
                    e.stopPropagation();
                    navbarCollapse.classList.toggle('show');
                });
            }

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.matches('.dropdown-toggle')) {
                    dropdownMenus.forEach(function(menu) {
                        if (menu.classList.contains('show')) {
                            menu.classList.remove('show');
                        }
                    });
                }

                // Close mobile menu when clicking outside
                if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                    if (!navbarCollapse.contains(e.target) && !navbarToggler.contains(e.target)) {
                        navbarCollapse.classList.remove('show');
                    }
                }
            });

            // Handle submenu hover on desktop
            if (window.innerWidth >= 992) {
                document.querySelectorAll('.dropdown-item-submenu').forEach(function(submenu) {
                    submenu.addEventListener('mouseenter', function() {
                        var submenuContent = this.querySelector('.dropdown-submenu');
                        if (submenuContent) {
                            submenuContent.style.display = 'block';
                        }
                    });

                    submenu.addEventListener('mouseleave', function() {
                        var submenuContent = this.querySelector('.dropdown-submenu');
                        if (submenuContent) {
                            submenuContent.style.display = 'none';
                        }
                    });
                });
            }

            // Handle submenu click on mobile
            if (window.innerWidth < 992) {
                document.querySelectorAll('.dropdown-item-submenu').forEach(function(submenu) {
                    submenu.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var submenuContent = this.querySelector('.dropdown-submenu');
                        if (submenuContent) {
                            submenuContent.style.display = submenuContent.style.display ===
                                'block' ? 'none' : 'block';
                        }
                    });
                });
            }

            // Handle dropdown items click
            document.querySelectorAll('.dropdown-item').forEach(function(item) {
                item.addEventListener('click', function(e) {
                    if (!this.closest('.dropdown-item-submenu') && this.getAttribute('href')) {
                        window.location.href = this.getAttribute('href');
                    }
                });
            });

            // Close alert messages automatically
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    if (alert && alert.parentNode) {
                        alert.classList.remove('show');
                        setTimeout(function() {
                            alert.remove();
                        }, 150);
                    }
                }, 5000);
            });
        });
    </script>
</body>