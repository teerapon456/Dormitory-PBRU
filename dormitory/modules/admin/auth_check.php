<?php
// ตรวจสอบว่ามีการ include header.php แล้วหรือไม่
if (!defined('HEADER_INCLUDED')) {
    require_once __DIR__ . '/../../includes/header.php';
}

// ตรวจสอบสิทธิ์การเข้าถึง
if (!Auth::isAdmin()) {
    $_SESSION['error'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
    header('Location: ' . Config::$baseUrl . '/login.php');
    exit;
}
