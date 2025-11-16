<?php
require_once __DIR__ . '/includes/header.php';

// ดึงข้อมูลสรุปสำหรับหน้าแรก
try {
    $stats = Database::getInstance()->fetch("
        SELECT 
            (SELECT COUNT(*) FROM students) as total_students,
            (SELECT COUNT(*) FROM rooms) as total_rooms,
            (SELECT COUNT(*) FROM room_assignments WHERE status = 'active') as occupied_rooms
    ");

    // คำนวณเปอร์เซ็นต์ห้องพักที่ถูกใช้งาน
    $occupancy_rate = $stats['total_rooms'] > 0
        ? round(($stats['occupied_rooms'] / $stats['total_rooms']) * 100, 1)
        : 0;

    // ตรวจสอบสถานะการล็อกอิน
    $isUserLoggedIn = Auth::isLoggedIn();
    $userName = Auth::getFullName();
    $userRole = Auth::getRole();
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด ให้ใช้ค่าเริ่มต้น
    $stats = [
        'total_students' => 0,
        'total_rooms' => 0,
        'occupied_rooms' => 0
    ];
    $occupancy_rate = 0;
}
?>

<!-- Hero Section -->
<div class="hero-section text-white position-relative">
    <div class="hero-overlay"></div>
    <div class="container position-relative py-5 hero-content">
        <div class="row min-vh-50 align-items-center">
            <div class="col-lg-8 text-center text-lg-start">
                <h1 class="display-4 fw-bold mb-4">ระบบจัดการหอพักนักศึกษา</h1>
                <p class="lead mb-4">บริหารจัดการหอพักแบบครบวงจร ง่าย สะดวก รวดเร็ว ปลอดภัย</p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <a href="<?php echo Config::$baseUrl; ?>/modules/users/login.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                    </a>
                    <a href="<?php echo Config::$baseUrl; ?>/modules/users/register.php"
                        class="btn btn-outline-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i>สมัครสมาชิก
                    </a>
                </div>
            </div>
            <!-- <div class="col-lg-4 d-none d-lg-block"> 
            <div class="stats-card p-4 bg-white text-dark rounded-3 shadow-lg">
                <div class="stats-item mb-3 p-3 rounded-3 bg-light">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary text-white rounded-circle me-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h5 class="mb-0"><?php echo number_format($stats['total_students']); ?></h5>
                            <p class="text-muted mb-0">นักศึกษา</p>
                        </div>
                    </div>
                </div>
                <div class="stats-item mb-3 p-3 rounded-3 bg-light">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success text-white rounded-circle me-3">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <div>
                            <h5 class="mb-0"><?php echo $occupancy_rate; ?>%</h5>
                            <p class="text-muted mb-0">อัตราการเข้าพัก</p>
                        </div>
                    </div>
                </div>
                <div class="stats-item p-3 rounded-3 bg-light">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info text-white rounded-circle me-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">24/7</h5>
                            <p class="text-muted mb-0">บริการตลอดเวลา</p>
                        </div>
                    </div>
                </div>
            </div>-->
        </div>
    </div>
</div>
<div class="shape-divider">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
        <path fill="#ffffff" fill-opacity="1"
            d="M0,96L60,112C120,128,240,160,360,160C480,160,600,128,720,112C840,96,960,96,1080,112C1200,128,1320,160,1380,176L1440,192L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z">
        </path>
    </svg>
</div>
</div>

<!-- Services Section -->
<section class="services-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase mb-2">บริการของเรา</h6>
            <h2 class="display-5 fw-bold">บริการครบวงจรสำหรับหอพัก</h2>
            <div class="section-divider mx-auto my-3"></div>
            <p class="text-muted lead">ระบบจัดการหอพักที่ครบถ้วนสำหรับทุกความต้องการ</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="service-card rounded-4 shadow-sm h-100 position-relative overflow-hidden">
                    <div class="service-icon">
                        <i class="fas fa-file-invoice text-white"></i>
                    </div>
                    <div class="service-content p-4">
                        <h3 class="h5 mb-3">ตรวจสอบบิล</h3>
                        <p class="text-muted mb-3">ติดติดและตรวจสอบบิลค่าน้ำ-ค่าไฟอย่างสะดวก</p>
                        <a href="<?php echo Config::$baseUrl; ?>/modules/public/bills/list.php"
                            class="btn btn-sm btn-outline-primary">ตรวจสอบบิล
                            <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="service-card rounded-4 shadow-sm h-100 position-relative overflow-hidden">
                    <div class="service-icon bg-info">
                        <i class="fas fa-tools text-white"></i>
                    </div>
                    <div class="service-content p-4">
                        <h3 class="h5 mb-3">ตรวจสอบซ่อมแซม</h3>
                        <p class="text-muted mb-3">ติดตามสถานะการซ่อมบำรุงและการดำเนินการแบบเรียลไทม์</p>
                        <a href="<?php echo Config::$baseUrl; ?>/repair_status.php"
                            class="btn btn-sm btn-outline-info">ตรวจสอบซ่อมแซม <i
                                class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="service-card rounded-4 shadow-sm h-100 position-relative overflow-hidden">
                    <div class="service-icon bg-success">
                        <i class="fas fa-wrench text-white"></i>
                    </div>
                    <div class="service-content p-4">
                        <h3 class="h5 mb-3">แจ้งซ่อมหอพัก</h3>
                        <p class="text-muted mb-3">แจ้งปัญหาและความต้องการซ่อมบำรุงภายในหอพักได้ทันที</p>
                        <a href="<?php echo Config::$baseUrl; ?>/student_repair.php"
                            class="btn btn-sm btn-outline-success">ซ่อมหอพัก <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="service-card rounded-4 shadow-sm h-100 position-relative overflow-hidden">
                    <div class="service-icon bg-warning">
                        <i class="fas fa-hammer text-white"></i>
                    </div>
                    <div class="service-content p-4">
                        <h3 class="h5 mb-3">แจ้งซ่อมทั่วไป</h3>
                        <p class="text-muted mb-3">แจ้งซ่อมบำรุงพื้นที่ส่วนกลางและบริเวณทั่วไปโดยรอบ</p>
                        <a href="<?php echo Config::$baseUrl; ?>/public_repair.php"
                            class="btn btn-sm btn-outline-warning">แจ้งซ่อมทั่วไป <i
                                class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="how-it-works-section py-5 bg-light">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase mb-2">วิธีการแจ้งซ่อม</h6>
            <h2 class="display-5 fw-bold">4 ขั้นตอนง่ายๆ ในการแจ้งซ่อม</h2>
            <div class="section-divider mx-auto my-3"></div>
        </div>

        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="timeline">
                    <div class="row g-0">
                        <div class="col-lg-3">
                            <div class="timeline-step">
                                <div class="timeline-number">1</div>
                                <div class="timeline-content">
                                    <div class="timeline-icon">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                    <h4 class="h5 mt-3">กรอกแบบฟอร์ม</h4>
                                    <p class="text-muted small">ระบุประเภทและรายละเอียดการซ่อมให้ชัดเจน</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="timeline-step">
                                <div class="timeline-number">2</div>
                                <div class="timeline-content">
                                    <div class="timeline-icon">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                    <h4 class="h5 mt-3">แนบรูปภาพ</h4>
                                    <p class="text-muted small">เพิ่มรูปถ่ายเพื่อให้เห็นปัญหาได้ชัดเจน</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="timeline-step">
                                <div class="timeline-number">3</div>
                                <div class="timeline-content">
                                    <div class="timeline-icon">
                                        <i class="fas fa-paper-plane"></i>
                                    </div>
                                    <h4 class="h5 mt-3">ส่งคำขอ</h4>
                                    <p class="text-muted small">ยืนยันข้อมูลและส่งคำขอแจ้งซ่อมเข้าระบบ</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="timeline-step">
                                <div class="timeline-number">4</div>
                                <div class="timeline-content">
                                    <div class="timeline-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <h4 class="h5 mt-3">ติดตามสถานะ</h4>
                                    <p class="text-muted small">ตรวจสอบความคืบหน้าการซ่อมได้ตลอดเวลา</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="<?php echo Config::$baseUrl; ?>/student_repair.php" class="btn btn-primary me-2">
                <i class="fas fa-wrench me-2"></i>แจ้งซ่อมสำหรับผู้เข้าพัก
            </a>
            <a href="<?php echo Config::$baseUrl; ?>/public_repair.php" class="btn btn-outline-primary">
                <i class="fas fa-hammer me-2"></i>แจ้งซ่อมทั่วไป
            </a>
        </div>
    </div>
</section>

<!-- Announcements Section -->
<section class="announcements-section py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase mb-2">ข่าวสารล่าสุด</h6>
            <h2 class="display-5 fw-bold">ข่าวสารและประกาศ</h2>
            <div class="section-divider mx-auto my-3"></div>
        </div>

        <div class="row g-4">
            <?php
            try {
                $announcements = Database::getInstance()->fetchAll("
                    SELECT * FROM announcements 
                    WHERE status = 'active' 
                    ORDER BY created_at DESC 
                    LIMIT 3
                ");

                if (!empty($announcements)) {
                    foreach ($announcements as $announcement) {
                        echo '<div class="col-lg-4 col-md-6">
                            <div class="announcement-card h-100 rounded-4 shadow-sm overflow-hidden">
                                <div class="announcement-img">
                                    <img src="' . Config::$baseUrl . '/assets/images/announcement-placeholder.jpg" class="img-fluid w-100" alt="ประกาศ">
                                    <div class="announcement-date">
                                        <span class="day">' . date('d', strtotime($announcement['created_at'])) . '</span>
                                        <span class="month">' . date('M', strtotime($announcement['created_at'])) . '</span>
                                    </div>
                                </div>
                                <div class="announcement-content p-4">
                                    <h3 class="h5 mb-3">' . htmlspecialchars($announcement['title']) . '</h3>
                                    <p class="text-muted">' . htmlspecialchars(substr($announcement['content'], 0, 120)) . '...</p>
                                    <a href="' . Config::$baseUrl . '/modules/announcements/view.php?id=' . $announcement['id'] . '" class="btn btn-link text-primary p-0">
                                        อ่านเพิ่มเติม <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<div class="col-12 text-center">
                        <div class="alert alert-info py-4">
                            <i class="fas fa-info-circle me-2"></i> ไม่มีข่าวสารและประกาศในขณะนี้
                        </div>
                    </div>';
                }
            } catch (Exception $e) {
                echo '<div class="col-12 text-center">
                    <div class="alert alert-warning py-4">
                        <i class="fas fa-exclamation-triangle me-2"></i> ไม่สามารถโหลดข่าวสารได้ในขณะนี้
                    </div>
                </div>';
            }
            ?>
        </div>

        <div class="text-center mt-5">
            <a href="<?php echo Config::$baseUrl; ?>/modules/announcements/list.php" class="btn btn-outline-primary">
                ข่าวสารทั้งหมด <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section py-5 bg-primary text-white position-relative overflow-hidden">
    <div class="cta-shapes">
        <div class="shape-1"></div>
        <div class="shape-2"></div>
        <div class="shape-3"></div>
    </div>
    <div class="container position-relative">
        <div class="row align-items-center">
            <div class="col-lg-8 text-center text-lg-start">
                <h2 class="display-5 fw-bold mb-3">ต้องการความช่วยเหลือ?</h2>
                <p class="lead mb-4">ติดต่อเราได้ตลอด 24 ชั่วโมงเพื่อขอความช่วยเหลือในทุกเรื่อง</p>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-end">
                    <a href="tel:+66123456789" class="btn btn-light btn-lg">
                        <i class="fas fa-phone me-2"></i>โทรหาเรา
                    </a>
                    <a href="<?php echo Config::$baseUrl; ?>/modules/contact.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-envelope me-2"></i>ส่งข้อความ
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Floating Action Buttons -->
<div class="floating-action-buttons">
    <a href="<?php echo Config::$baseUrl; ?>/public_repair.php" class="fab-button fab-primary" title="แจ้งซ่อม">
        <i class="fas fa-tools"></i>
    </a>
    <a href="<?php echo Config::$baseUrl; ?>/modules/users/login.php" class="fab-button fab-success"
        title="เข้าสู่ระบบ">
        <i class="fas fa-sign-in-alt"></i>
    </a>
    <a href="tel:+66123456789" class="fab-button fab-info" title="โทรติดต่อ">
        <i class="fas fa-phone"></i>
    </a>
</div>

<!-- Custom Styles -->
<style>
    /* Hero Section */
    .hero-section {
        background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.5)), url('<?php echo Config::$baseUrl; ?>/assets/images/dormitory.jpg');
        background-size: cover;
        background-position: center;
        position: relative;
        overflow: hidden;
    }

    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(32, 76, 229, 0.7) 0%, rgba(32, 124, 229, 0.5) 100%);
    }

    .hero-content {
        z-index: 10;
    }

    .min-vh-50 {
        min-height: 50vh;
    }

    .shape-divider {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        overflow: hidden;
        line-height: 0;
    }

    /* Stats Card */
    .stats-card {
        transform: translateY(20px);
    }

    .stats-icon {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    /* Services Section */
    .section-divider {
        width: 50px;
        height: 3px;
        background-color: #0d6efd;
    }

    .service-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .service-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }

    .service-icon {
        position: absolute;
        top: 0;
        right: 0;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        background-color: #0d6efd;
        border-bottom-left-radius: 20px;
    }

    /* Timeline Section */
    .timeline {
        position: relative;
        padding: 30px 0;
    }

    .timeline::before {
        content: '';
        position: absolute;
        top: 40px;
        left: 15%;
        right: 15%;
        height: 3px;
        background-color: #dee2e6;
        z-index: 0;
    }

    .timeline-step {
        text-align: center;
        position: relative;
        padding: 0 15px;
    }

    .timeline-number {
        width: 50px;
        height: 50px;
        background-color: #0d6efd;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin: 0 auto 30px;
        position: relative;
        z-index: 1;
    }

    .timeline-icon {
        width: 80px;
        height: 80px;
        background-color: #f8f9fa;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #0d6efd;
        margin: 0 auto;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }

    /* Announcement Cards */
    .announcement-card {
        transition: all 0.3s ease;
    }

    .announcement-card:hover {
        transform: translateY(-10px);
    }

    .announcement-img {
        position: relative;
        height: 200px;
        overflow: hidden;
    }

    .announcement-img img {
        height: 100%;
        object-fit: cover;
    }

    .announcement-date {
        position: absolute;
        bottom: 0;
        right: 0;
        background-color: #0d6efd;
        color: white;
        padding: 10px 15px;
        text-align: center;
        line-height: 1;
    }

    .announcement-date .day {
        font-size: 1.5rem;
        font-weight: bold;
        display: block;
    }

    .announcement-date .month {
        font-size: 0.9rem;
    }

    /* CTA Section */
    .cta-section {
        background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
    }

    .cta-shapes .shape-1,
    .cta-shapes .shape-2,
    .cta-shapes .shape-3 {
        position: absolute;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .cta-shapes .shape-1 {
        width: 150px;
        height: 150px;
        top: -75px;
        left: -75px;
    }

    .cta-shapes .shape-2 {
        width: 100px;
        height: 100px;
        bottom: -50px;
        right: 10%;
    }

    .cta-shapes .shape-3 {
        width: 70px;
        height: 70px;
        top: 20%;
        right: 10%;
    }

    /* Floating Action Buttons */
    .floating-action-buttons {
        position: fixed;
        bottom: 30px;
        right: 30px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        z-index: 1000;
    }

    .fab-button {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        color: white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .fab-button:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
    }

    .fab-primary {
        background-color: #0d6efd;
    }

    .fab-success {
        background-color: #198754;
    }

    .fab-info {
        background-color: #0dcaf0;
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>