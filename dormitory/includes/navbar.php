<?php
// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Check if user is logged in and is admin
$isLoggedIn = Auth::isLoggedIn();
$isAdmin = Auth::isAdmin();
$currentUser = $isLoggedIn ? Auth::getCurrentUser() : null;
?>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo Config::$baseUrl; ?>">
            <div class="navbar-brand-icon">
                <i class="fas fa-building"></i>
            </div>
            <span>ระบบจัดการหอพัก</span>
        </a>

        <!-- Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Left Menu -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>"
                        href="<?php echo Config::$baseUrl; ?>">
                        <i class="fas fa-home me-1"></i>หน้าแรก
                    </a>
                </li>
                <?php if ($isLoggedIn): ?>
                <?php if ($isAdmin): ?>
                <!-- Admin Menu -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>"
                        href="<?php echo Config::$baseUrl; ?>/modules/admin/dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>แดชบอร์ด
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'repairs.php' ? 'active' : ''; ?>"
                        href="<?php echo Config::$baseUrl; ?>/modules/admin/repairs.php">
                        <i class="fas fa-tools me-1"></i>จัดการแจ้งซ่อม
                    </a>
                </li>
                <?php else: ?>
                <!-- Student Menu -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'student_repair.php' ? 'active' : ''; ?>"
                        href="<?php echo Config::$baseUrl; ?>/student_repair.php">
                        <i class="fas fa-tools me-1"></i>แจ้งซ่อม
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'repair_status.php' ? 'active' : ''; ?>"
                        href="<?php echo Config::$baseUrl; ?>/repair_status.php">
                        <i class="fas fa-clipboard-list me-1"></i>สถานะการซ่อม
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>
            </ul>

            <!-- Right Menu -->
            <ul class="navbar-nav">
                <?php if ($isLoggedIn && $currentUser): ?>
                <!-- User Profile Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($currentUser['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="<?php echo Config::$baseUrl; ?>/modules/users/profile.php">
                                <i class="fas fa-user-circle me-2"></i>โปรไฟล์
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo Config::$baseUrl; ?>/change_password.php">
                                <i class="fas fa-key me-2"></i>เปลี่ยนรหัสผ่าน
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo Config::$baseUrl; ?>/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </li>
                <?php else: ?>
                <!-- Login/Register Buttons -->
                <li class="nav-item">
                    <a href="<?php echo Config::$baseUrl; ?>/login.php" class="nav-link">
                        <i class="fas fa-sign-in-alt me-1"></i>เข้าสู่ระบบ
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo Config::$baseUrl; ?>/register.php" class="nav-link">
                        <i class="fas fa-user-plus me-1"></i>สมัครสมาชิก
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
/* Dropdown hover effect */
.dropdown:hover .dropdown-menu {
    display: block;
}

.dropdown-menu {
    margin-top: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap dropdowns
    var dropdowns = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdowns.forEach(function(dropdown) {
        new bootstrap.Dropdown(dropdown);
    });
});
</script>