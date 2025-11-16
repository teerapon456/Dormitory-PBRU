<?php
require_once __DIR__ . '/functions.php';

// Define PHP Session constants if not defined
if (!defined('PHP_SESSION_NONE')) {
    define('PHP_SESSION_NONE', 1);
}

if (!defined('PASSWORD_DEFAULT')) {
    define('PASSWORD_DEFAULT', 1);
}

// Start output buffering
if (!function_exists('ob_start')) {
    function ob_start()
    {
        return true;
    }
}

// Define password functions if they don't exist
if (!function_exists('password_verify')) {
    function password_verify($password, $hash)
    {
        return $password === $hash; // ตรวจสอบรหัสผ่านโดยตรง
    }
}

if (!function_exists('password_hash')) {
    function password_hash($password, $algo, array $options = array())
    {
        return $password; // ไม่ต้อง hash รหัสผ่าน
    }
}

if (!function_exists('hash_equals')) {
    function hash_equals($known_string, $user_string)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($known_string, $user_string);
        }
        if (strlen($known_string) !== strlen($user_string)) {
            return false;
        }
        $ret = 0;
        for ($i = 0; $i < strlen($known_string); $i++) {
            $ret |= ord($known_string[$i]) ^ ord($user_string[$i]);
        }
        return $ret === 0;
    }
}

// Define session functions if they don't exist
if (!function_exists('session_status')) {
    function session_status()
    {
        return PHP_SESSION_NONE;
    }
}

if (!function_exists('session_start')) {
    function session_start($options = [])
    {
        return true;
    }
}

if (!function_exists('session_destroy')) {
    function session_destroy()
    {
        return true;
    }
}

if (!function_exists('session_name')) {
    function session_name($name = null)
    {
        return 'PHPSESSID';
    }
}

if (!function_exists('setcookie')) {
    function setcookie($name, $value = "", $expires = 0, $path = "", $domain = "", $secure = false, $httponly = false)
    {
        return true;
    }
}

// Start output buffering
ob_start();

class Auth
{
    private static $db;

    public static function init()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        self::$db = Database::getInstance();
    }

    // ฟังก์ชันสำหรับล็อกอิน
    public static function login($username, $password)
    {
        try {
            if (!self::$db) {
                self::init();
            }

            // ค้นหาผู้ใช้จากฐานข้อมูล
            $user = self::$db->fetch("
                SELECT user_id, username, password, email, full_name, role, room_id
                FROM users 
                WHERE username = ?
            ", [$username]);

            if (!$user) {
                return false;
            }

            // ตรวจสอบรหัสผ่านโดยตรง
            if ($password !== $user['password']) {
                return false;
            }

            // สร้าง session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['room_id'] = $user['room_id'];
            $_SESSION['last_activity'] = time();

            return true;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    // ฟังก์ชันสำหรับล็อกเอาท์
    public static function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = array();
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // ฟังก์ชันสำหรับตรวจสอบการล็อกอิน
    public static function isLoggedIn()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // ตรวจสอบ session timeout (30 นาที)
        if (!isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > 1800)) {
            self::logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    // ฟังก์ชันสำหรับตรวจสอบสิทธิ์
    public static function hasPermission($requiredRole)
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        return $_SESSION['role'] === $requiredRole;
    }

    // ฟังก์ชันสำหรับดึงข้อมูลผู้ใช้ปัจจุบัน
    public static function getCurrentUser()
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        try {
            if (!self::$db) {
                self::init();
            }

            return self::$db->fetch("
                SELECT u.*, r.room_number, b.building_name
                FROM users u
                LEFT JOIN rooms r ON u.room_id = r.room_id
                LEFT JOIN buildings b ON r.building_id = b.building_id
                WHERE u.user_id = ?
            ", [$_SESSION['user_id']]);
        } catch (Exception $e) {
            error_log("Get current user error: " . $e->getMessage());
            return null;
        }
    }

    // ฟังก์ชันสำหรับสร้างผู้ใช้ใหม่
    public static function register($data)
    {
        try {
            if (!self::$db) {
                self::init();
            }

            // ตรวจสอบ username และ email ซ้ำ
            $existingUser = self::$db->fetch(
                "SELECT user_id FROM users WHERE username = ? OR email = ?",
                [$data['username'], $data['email']]
            );

            if ($existingUser) {
                throw new Exception('ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว');
            }

            // กำหนดค่าเริ่มต้น
            $data['role'] = $data['role'] ?? 'นักศึกษา';
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

            // เพิ่มผู้ใช้
            $userId = self::$db->insert('users', [
                'username' => $data['username'],
                'password' => $data['password'],
                'email' => $data['email'],
                'full_name' => $data['full_name'],
                'role' => $data['role'],
                'phone_number' => $data['phone_number'] ?? null,
                'created_at' => $data['created_at']
            ]);

            // ถ้าเป็นนักศึกษา ให้เพิ่มข้อมูลในตาราง students
            if ($data['role'] === 'นักศึกษา' && !empty($data['user_id'])) {
                self::$db->insert('users', [
                    'user_id' => $userId,
                ]);
            }

            // บันทึก log
            Functions::log("New user {$data['username']} registered", 'INFO');

            return $userId;
        } catch (Exception $e) {
            Functions::log($e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    // ฟังก์ชันสำหรับอัพเดทข้อมูลผู้ใช้
    public static function updateUser($userId, $data)
    {
        try {
            if (!self::$db) {
                self::init();
            }
            // ถ้ามีการเปลี่ยนรหัสผ่าน
            if (isset($data['password'])) {
                $data['password'] = Functions::hashPassword($data['password']);
            }

            // อัพเดทข้อมูล
            self::$db->update(
                'users',
                $data,
                'user_id = ?',
                [$userId]
            );

            // บันทึก log
            Functions::log("User ID {$userId} updated", 'INFO');

            return true;
        } catch (Exception $e) {
            Functions::log($e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    // ฟังก์ชันสำหรับลบผู้ใช้
    public static function deleteUser($userId)
    {
        try {
            if (!self::$db) {
                self::init();
            }
            // ตรวจสอบว่ามีผู้ใช้อยู่จริง
            $user = Functions::getUser($userId);
            if (!$user) {
                throw new Exception('ไม่พบข้อมูลผู้ใช้');
            }

            // ลบผู้ใช้
            self::$db->delete('users', 'user_id = ?', [$userId]);

            // บันทึก log
            Functions::log("User ID {$userId} deleted", 'INFO');

            return true;
        } catch (Exception $e) {
            Functions::log($e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    // ฟังก์ชันสำหรับดึงสิทธิ์ตามบทบาท
    private static function getPermissions($role)
    {
        $permissions = [];

        switch ($role) {
            case 'ผู้ดูแลระบบ':
                $permissions = [
                    'view_dashboard' => true,
                    'manage_users' => true,
                    'manage_buildings' => true,
                    'manage_rooms' => true,
                    'manage_repairs' => true,
                    'manage_bills' => true,
                    'manage_notifications' => true,
                    'view_reports' => true
                ];
                break;
            case 'นักศึกษา':
                $permissions = [
                    'view_dashboard' => true,
                    'view_room' => true,
                    'view_bills' => true,
                    'create_repair' => true,
                    'view_notifications' => true
                ];
                break;
            default:
                $permissions = [
                    'view_dashboard' => true,
                    'view_notifications' => true
                ];
        }

        return $permissions;
    }

    // ตรวจสอบว่าเป็นผู้ดูแลระบบหรือไม่
    public static function isAdmin()
    {
        return self::isLoggedIn() && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === '1' || $_SESSION['role'] === 'ผู้ดูแลระบบ');
    }

    // บังคับให้ต้องล็อกอิน
    public static function requireLogin()
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . Config::$baseUrl . '/login.php');
            exit;
        }
    }

    // บังคับให้ต้องเป็นผู้ดูแลระบบ
    public static function requireAdmin()
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            $_SESSION['error'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
            header('Location: ' . Config::$baseUrl . '/login.php');
            exit;
        }
    }

    // ดึง user ID จาก session
    public static function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    // ดึงชื่อผู้ใช้จาก session
    public static function getUsername()
    {
        return $_SESSION['username'] ?? null;
    }

    // ดึงบทบาทจาก session
    public static function getRole()
    {
        return $_SESSION['role'] ?? null;
    }

    // ดึงชื่อเต็มจาก session
    public static function getFullName()
    {
        return $_SESSION['full_name'] ?? null;
    }

    // URL สำหรับ redirect หลังจากล็อกอิน
    public static function getRedirectUrl()
    {
        if (!self::isLoggedIn()) {
            return Config::$baseUrl . '/login.php';
        }

        $role = $_SESSION['role'];

        switch ($role) {
            case 'admin':
            case '1':
            case 'ผู้ดูแลระบบ':
                return Config::$baseUrl . '/modules/admin/dashboard.php';
            case 'student':
            case 'นักศึกษา':
                return Config::$baseUrl . '/student_repair.php';
            default:
                return Config::$baseUrl . '/index.php';
        }
    }
}