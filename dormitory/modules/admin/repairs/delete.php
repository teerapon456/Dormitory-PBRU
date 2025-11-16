<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../../config/database.php';

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: view.php');
    exit;
}

// ตรวจสอบ request_id
if (!isset($_POST['request_id']) || !is_numeric($_POST['request_id'])) {
    $_SESSION['error'] = 'Invalid repair request ID';
    header('Location: view.php');
    exit;
}

$request_id = intval($_POST['request_id']);

try {
    $db = Database::getInstance();

    // ดึงข้อมูลรูปภาพที่เกี่ยวข้อง
    $images = $db->fetchAll(
        "SELECT image_path FROM repair_images WHERE request_id = ?",
        [$request_id]
    );

    // ลบรูปภาพจากระบบไฟล์
    foreach ($images as $image) {
        $image_path = __DIR__ . '/../../../' . $image['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // ลบข้อมูลรูปภาพจากฐานข้อมูล
    $db->delete(
        "repair_images",
        "request_id = ?",
        [$request_id]
    );

    // ลบข้อมูลการแจ้งซ่อม
    $result = $db->delete(
        "repair_requests",
        "request_id = ?",
        [$request_id]
    );

    if ($result) {
        $_SESSION['success'] = 'ลบรายการแจ้งซ่อมเรียบร้อยแล้ว';
    } else {
        throw new Exception('ไม่สามารถลบรายการแจ้งซ่อมได้');
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'เกิดข้อผิดพลาดในการลบรายการแจ้งซ่อม: ' . $e->getMessage();
}

// ส่งกลับไปหน้ารายการ
header('Location: view.php');
exit;
