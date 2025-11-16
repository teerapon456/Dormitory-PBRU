<?php
class Config
{
    // ตั้งค่าการแสดงผลข้อผิดพลาด
    public static $errorReporting = E_ALL;
    public static $displayErrors = 1;

    // ตั้งค่าเขตเวลา
    public static $timezone = 'Asia/Bangkok';

    // ตั้งค่าพื้นฐานของระบบ
    public static $siteName = 'ระบบจัดการหอพัก';
    public static $siteUrl = 'http://localhost/dormitory';
    public static $baseUrl = 'http://localhost/dormitory'; // URL พื้นฐานของระบบ

    // ตั้งค่าเส้นทางไฟล์
    public static $rootPath;
    public static $uploadPath;
    public static $assetsPath;

    // ตั้งค่าการอัพโหลดไฟล์
    public static $maxFileSize = 5242880; // 5MB
    public static $allowedFileTypes = ['jpg', 'jpeg', 'png', 'pdf'];

    // ตั้งค่าการส่งอีเมล
    public static $smtp = [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@gmail.com',
        'password' => 'your-password',
        'from_email' => 'your-email@gmail.com',
        'from_name' => 'ระบบจัดการหอพัก'
    ];

    // ตั้งค่าการเข้ารหัส
    public static $hashCost = 12;

    // ตั้งค่าการจัดการเซสชัน
    public static $session = [
        'cookie_httponly' => 1,
        'use_only_cookies' => 1,
        'cookie_secure' => 1
    ];

    // ตั้งค่าการจัดการแคช
    public static $cache = [
        'enabled' => true,
        'duration' => 3600 // 1 ชั่วโมง
    ];

    // ตั้งค่าการจัดการการล็อก
    public static $log = [
        'enabled' => true,
        'path' => null,
        'level' => 'DEBUG' // DEBUG, INFO, WARNING, ERROR, CRITICAL
    ];

    // ตั้งค่าการเชื่อมต่อฐานข้อมูล
    public static $dbHost = 'localhost';
    public static $dbUser = 'root';
    public static $dbPass = '';
    public static $dbName = 'dormitory_db';

    // ตั้งค่าอื่นๆ ของระบบ
    public static $adminEmail = 'admin@example.com';

    // ตั้งค่า session
    public static $sessionLifetime = 3600; // 1 ชั่วโมง
    public static $sessionPath = '/';
    public static $sessionDomain = '';
    public static $sessionSecure = false;
    public static $sessionHttpOnly = true;

    // ตั้งค่า upload
    public static $uploadDir = __DIR__ . '/../uploads/';

    // ตั้งค่า logging
    public static $logDir = __DIR__ . '/../logs/';
    public static $logLevel = 'debug'; // debug, info, warning, error

    // ตั้งค่า security
    public static $passwordMinLength = 6;
    public static $passwordRequireSpecial = true;
    public static $passwordRequireNumber = true;
    public static $passwordRequireUppercase = true;
    public static $passwordRequireLowercase = true;

    // ฟังก์ชันสำหรับเริ่มต้นการตั้งค่า
    public static function init()
    {
        // ตั้งค่า error reporting
        error_reporting(self::$errorReporting);
        ini_set('display_errors', self::$displayErrors);

        // ตั้งค่า timezone
        date_default_timezone_set(self::$timezone);

        // ตั้งค่าเส้นทางไฟล์
        self::$rootPath = dirname(dirname(__FILE__));
        self::$uploadPath = self::$rootPath . '/uploads';
        self::$assetsPath = self::$rootPath . '/assets';

        // ตั้งค่า session
        foreach (self::$session as $key => $value) {
            ini_set("session.$key", $value);
        }

        // ตั้งค่า log path
        self::$log['path'] = self::$rootPath . '/logs';
    }
}
