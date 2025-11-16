<?php
require_once __DIR__ . '/includes/header.php';

// ทำการออกจากระบบ
Auth::logout();

// แสดงข้อความแจ้งเตือน
$_SESSION['success'] = 'ออกจากระบบเรียบร้อยแล้ว';

// กลับไปยังหน้าหลัก
header('Location: ' . Config::$baseUrl);

// ถ้ามีการส่ง output ไปแล้ว ให้ใช้ JavaScript redirect แทน
if (headers_sent()) {
    echo '<script>window.location.href = "' . Config::$baseUrl . '";</script>';
} else {
    exit;
}