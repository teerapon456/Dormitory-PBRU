<?php
require_once __DIR__ . '/includes/header.php';

// ตรวจสอบว่ามี URL สำหรับ redirect กลับหลังจากล็อกอินหรือไม่
$return_url = isset($_GET['return']) ? $_GET['return'] : Config::$baseUrl . '/student_repair.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่แล้วหรือไม่
if (Auth::isLoggedIn()) {
    header('Location: ' . $return_url);
    exit;
}
// จัดการการล็อกอิน
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // ตรวจสอบว่ากรอกข้อมูลครบหรือไม่
        if (empty($username) || empty($password)) {
            throw new Exception('กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
        }

        // ใช้ Auth::login() แทนการตรวจสอบเอง
        if (!Auth::login($username, $password)) {
            throw new Exception('ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
        }

        // แสดงข้อความต้อนรับ
        $_SESSION['success'] = "ยินดีต้อนรับ " . Auth::getFullName();

        // Redirect ไปยัง URL ที่กำหนด
        header('Location: ' . $return_url);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 my-3">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <h4 class="fw-bold mb-1">เข้าสู่ระบบ</h4>
                        <p class="text-muted small mb-0">กรุณาเข้าสู่ระบบเพื่อใช้งาน</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger py-2">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success py-2">
                            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; ?>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <form method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label small fw-bold">ชื่อผู้ใช้</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-user text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="username" name="username"
                                    required
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                    autocomplete="username" placeholder="กรอกชื่อผู้ใช้">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label small fw-bold">รหัสผ่าน</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password" class="form-control border-start-0 border-end-0" id="password"
                                    name="password" required autocomplete="current-password" placeholder="กรอกรหัสผ่าน">
                                <button class="btn btn-light border border-start-0" type="button" id="togglePassword">
                                    <i class="fas fa-eye text-muted"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0 small">ยังไม่มีบัญชี? <a href="register.php"
                                class="text-decoration-none">สมัครสมาชิก</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        var form = document.querySelector('.needs-validation');
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });

        // Toggle password visibility
        var togglePassword = document.getElementById('togglePassword');
        var password = document.getElementById('password');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function() {
                var type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);

                var icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>