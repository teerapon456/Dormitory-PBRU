<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Functions
{
    private static $db;
    public static function init()
    {
        self::$db = Database::getInstance();
    }

    // ฟังก์ชันสำหรับเข้ารหัสรหัสผ่าน
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    // ฟังก์ชันสำหรับตรวจสอบรหัสผ่าน
    public static function verifyPassword($password, $hash)
    {
        // *** สำคัญ: วิธีการตรวจสอบแบบตรงๆ นี้ไม่ปลอดภัย ใช้สำหรับระบบทดสอบเท่านั้น ***
        // เปรียบเทียบรหัสผ่านที่ผู้ใช้กรอกตรงๆ กับรหัสผ่านที่เก็บในฐานข้อมูล
        // ข้อดี: ง่ายต่อการทดสอบ สามารถทำงานได้กับรหัสผ่านที่ไม่ได้เข้ารหัส
        // ข้อเสีย: ความปลอดภัยต่ำ มีความเสี่ยงหากฐานข้อมูลถูกเข้าถึงโดยผู้ไม่หวังดี
        return $password === $hash;

        /* วิธีที่ปลอดภัยและควรใช้ในระบบจริง
         * ต้องเปลี่ยนเป็นระบบ hash ด้วย password_verify()
         * และข้อมูลในฐานข้อมูลจะต้องเก็บในรูปแบบที่เข้ารหัสด้วย password_hash()
         *
         * ขั้นตอนการเปลี่ยนเป็นระบบ hash:
         * 1. แก้ไขฟังก์ชันนี้ให้ใช้ password_verify แทนการเปรียบเทียบตรงๆ
         * 2. ในฟังก์ชัน Auth::login ให้ใช้ password_verify แทน
         * 3. อัพเดทรหัสผ่านของผู้ใช้ที่มีอยู่ในระบบให้เป็น hash ทั้งหมด
         */
        // return password_verify($password, $hash);
    }

    // ฟังก์ชันสำหรับสร้าง token
    public static function generateToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }

    // ฟังก์ชันสำหรับตรวจสอบไฟล์ที่อัพโหลด
    public static function validateFile($file, $allowedTypes = null)
    {
        if ($allowedTypes === null) {
            $allowedTypes = Config::$allowedFileTypes;
        }

        // ตรวจสอบขนาดไฟล์
        if ($file['size'] > Config::$maxFileSize) {
            throw new Exception('ไฟล์มีขนาดใหญ่เกินไป');
        }

        // ตรวจสอบประเภทไฟล์
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('ประเภทไฟล์ไม่ถูกต้อง');
        }

        return true;
    }

    // ฟังก์ชันสำหรับบันทึกไฟล์
    public static function saveFile($file, $destination)
    {
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('ไม่สามารถบันทึกไฟล์ได้');
        }
        return $destination;
    }

    // ฟังก์ชันสำหรับสร้างชื่อไฟล์ที่ไม่ซ้ำกัน
    public static function generateUniqueFilename($originalName)
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return uniqid() . '_' . time() . '.' . $extension;
    }

    // ฟังก์ชันสำหรับตรวจสอบสิทธิ์การเข้าถึง
    public static function checkPermission($requiredRole)
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $requiredRole) {
            throw new Exception('ไม่มีสิทธิ์เข้าถึง');
        }
    }

    // ฟังก์ชันสำหรับบันทึก log
    public static function log($message, $level = 'INFO')
    {
        if (!Config::$log['enabled']) return;

        $logFile = Config::$log['path'] . '/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message\n";

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    // ฟังก์ชันสำหรับบันทึกกิจกรรมผู้ใช้
    public static function logActivity($userId, $action, $description = '')
    {
        try {
            if (!self::$db) {
                self::init();
            }

            return self::$db->insert('activity_logs', [
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            self::log('Failed to log activity: ' . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    // ฟังก์ชันสำหรับส่งอีเมล
    public static function sendEmail($to, $subject, $message)
    {
        $headers = [
            'From' => Config::$smtp['from_name'] . ' <' . Config::$smtp['from_email'] . '>',
            'Reply-To' => Config::$smtp['from_email'],
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8'
        ];

        return mail($to, $subject, $message, $headers);
    }

    // ฟังก์ชันสำหรับสร้างการแจ้งเตือน
    public static function createNotification($userId, $typeId, $title, $message)
    {
        try {
            self::$db->beginTransaction();

            // เพิ่มการแจ้งเตือน
            $notificationId = self::$db->insert('notifications', [
                'type_id' => $typeId,
                'title' => $title,
                'message' => $message
            ]);

            // เพิ่มผู้รับการแจ้งเตือน
            self::$db->insert('notification_recipients', [
                'notification_id' => $notificationId,
                'user_id' => $userId
            ]);

            self::$db->commit();
            return true;
        } catch (Exception $e) {
            self::$db->rollBack();
            self::log($e->getMessage(), 'ERROR');
            return false;
        }
    }

    // ฟังก์ชันสำหรับดึงข้อมูลผู้ใช้
    public static function getUser($userId)
    {
        if (!self::$db) {
            self::init();
        }
        return self::$db->fetch("SELECT * FROM users WHERE user_id = ?", [$userId]);
    }

    // ฟังก์ชันสำหรับดึงข้อมูลห้องพัก
    public static function getRoom($roomId)
    {
        if (!self::$db) {
            self::init();
        }
        return self::$db->fetch("
            SELECT r.*, b.building_name 
            FROM rooms r 
            JOIN buildings b ON r.building_id = b.building_id 
            WHERE r.room_id = ?
        ", [$roomId]);
    }

    // ฟังก์ชันสำหรับดึงข้อมูลการแจ้งซ่อม
    public static function getRepairRequest($requestId)
    {
        if (!self::$db) {
            self::init();
        }
        return self::$db->fetch("
            SELECT rr.*, 
                   u.full_name as user_name,
                   r.room_number,
                   b.building_name,
                   rc.category_name,
                   ri.item_name,
                   rl.location_name_th
            FROM repair_requests rr
            LEFT JOIN users u ON rr.user_id = u.user_id
            LEFT JOIN rooms r ON rr.room_id = r.room_id
            LEFT JOIN buildings b ON r.building_id = b.building_id
            LEFT JOIN repair_categories rc ON rr.category_id = rc.category_id
            LEFT JOIN repair_items ri ON rr.item_id = ri.item_id
            LEFT JOIN repair_locations rl ON rr.location_id = rl.location_id
            WHERE rr.request_id = ?
        ", [$requestId]);
    }

    // ฟังก์ชันสำหรับดึงข้อมูลบิล
    public static function getBill($billId)
    {
        if (!self::$db) {
            self::init();
        }
        return self::$db->fetch("
            SELECT ub.*, 
                   r.room_number,
                   b.building_name
            FROM utility_bills ub
            JOIN rooms r ON ub.room_id = r.room_id
            JOIN buildings b ON r.building_id = b.building_id
            WHERE ub.bill_id = ?
        ", [$billId]);
    }

    function logError($message, $type = 'ERROR')
    {
        if (Config::$log['enabled']) {
            $logDir = dirname(dirname(__FILE__)) . '/logs';
            if (!file_exists($logDir)) {
                mkdir($logDir, 0777, true);
            }

            $logFile = $logDir . '/' . date('Y-m-d') . '.log';
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;

            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
    }
}
