    </div> <!-- End of container-fluid -->

    <!-- Footer -->
    <footer class="footer py-4 mt-auto">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-md-5">
                    <div class="d-flex align-items-center mb-3">
                        <div class="footer-brand-icon me-3">
                            <i class="fas fa-building"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Dorm<span class="brand-highlight">itory</span></h5>
                            <p class="text-muted mb-0 small">ระบบจัดการหอพักที่ทันสมัย</p>
                        </div>
                    </div>
                    <p class="text-muted mb-0">ระบบบริหารจัดการหอพักที่ช่วยให้การจัดการห้องพัก การแจ้งซ่อม
                        การจัดการค่าใช้จ่าย และการติดต่อสื่อสารเป็นไปอย่างมีประสิทธิภาพและสะดวกรวดเร็ว</p>
                </div>
                <div class="col-md-3">
                    <h5 class="mb-3 footer-heading">ลิงก์ด่วน</h5>
                    <ul class="footer-links">
                        <li><a href="<?php echo Config::$baseUrl; ?>"><i class="fas fa-home me-2"></i>หน้าแรก</a></li>
                        <?php if (Auth::isLoggedIn()): ?>
                        <li><a href="<?php echo Config::$baseUrl; ?>/modules/users/user_repair.php"><i
                                    class="fas fa-tools me-2"></i>แจ้งซ่อมหอพัก</a></li>
                        <?php else: ?>
                        <li><a
                                href="<?php echo Config::$baseUrl; ?>/login.php?return=<?php echo urlencode(Config::$baseUrl . '/modules/users/user_repair.php'); ?>"><i
                                    class="fas fa-tools me-2"></i>แจ้งซ่อมหอพัก</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo Config::$baseUrl; ?>/public_repair.php"><i
                                    class="fas fa-wrench me-2"></i>แจ้งซ่อมทั่วไป</a></li>
                        <li><a href="<?php echo Config::$baseUrl; ?>/modules/contact.php"><i
                                    class="fas fa-envelope me-2"></i>ติดต่อเรา</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3 footer-heading">ติดต่อเรา</h5>
                    <ul class="footer-contact">
                        <li><i class="fas fa-map-marker-alt me-2"></i>123 ถนนพหลโยธิน เขตจตุจักร กรุงเทพฯ 10900</li>
                        <li><i class="fas fa-phone me-2"></i>02-123-4567</li>
                        <li><i class="fas fa-envelope me-2"></i>contact@dormitory.ac.th</li>
                    </ul>
                    <div class="social-icons mt-3">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-line"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr class="footer-divider">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> ระบบจัดการหอพัก. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Version 1.0.0 | <a
                            href="<?php echo Config::$baseUrl; ?>/privacy-policy.php">นโยบายความเป็นส่วนตัว</a></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="<?php echo Config::$baseUrl; ?>/assets/js/script.js"></script>

    <style>
.footer {
    background-color: #f8f9fa;
    border-top: 1px solid #e9ecef;
    color: var(--gray-700);
}

.footer-brand-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background-color: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.footer-heading {
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--gray-800);
    position: relative;
    padding-bottom: 0.5rem;
}

.footer-heading::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 3px;
    background-color: var(--primary-color);
    border-radius: 2px;
}

.footer-links,
.footer-contact {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li,
.footer-contact li {
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
}

.footer-links a {
    color: var(--gray-600);
    text-decoration: none;
    transition: color 0.2s;
    display: inline-block;
}

.footer-links a:hover {
    color: var(--primary-color);
    transform: translateX(3px);
}

.footer-contact li {
    color: var(--gray-600);
    display: flex;
    align-items: flex-start;
}

.footer-contact li i {
    margin-top: 0.2rem;
    color: var(--primary-color);
}

.social-icons {
    display: flex;
    gap: 10px;
}

.social-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: var(--gray-200);
    color: var(--gray-700);
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s;
}

.social-icon:hover {
    background-color: var(--primary-color);
    color: white;
    transform: translateY(-3px);
}

.footer-divider {
    margin: 1.5rem 0;
    border-color: var(--gray-200);
    opacity: 0.7;
}

@media (max-width: 768px) {
    .footer-heading::after {
        left: 50%;
        transform: translateX(-50%);
    }

    .footer h5.footer-heading {
        text-align: center;
        margin-top: 1.5rem;
    }

    .footer-links,
    .footer-contact {
        text-align: center;
    }

    .social-icons {
        justify-content: center;
    }
}
    </style>
    </body>

    </html>