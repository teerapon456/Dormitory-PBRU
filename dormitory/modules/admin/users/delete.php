<?php
require_once __DIR__ . '/../auth_check.php';

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header("Location: view.php");
    exit;
}

// ตรวจสอบ CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Invalid security token";
    header("Location: view.php");
    exit;
}

// ตรวจสอบ user_id
if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    $_SESSION['error'] = "ไม่พบข้อมูลผู้ใช้ที่ต้องการลบ";
    header("Location: view.php");
    exit;
}

$user_id = (int)$_POST['user_id'];

try {
    // เริ่ม transaction
    Database::getInstance()->beginTransaction();

    // ตรวจสอบว่ามีผู้ใช้นี้อยู่จริง
    $user = Database::getInstance()->fetch(
        "SELECT * FROM users WHERE user_id = :id",
        [':id' => $user_id]
    );

    if (!$user) {
        throw new Exception("ไม่พบข้อมูลผู้ใช้ที่ต้องการลบ");
    }

    // ป้องกันการลบบัญชีตัวเอง
    if ($user_id == $_SESSION['user_id']) {
        throw new Exception("ไม่สามารถลบบัญชีของตัวเองได้");
    }

    // ป้องกันการลบบัญชี admin คนสุดท้าย
    if ($user['role'] === 'admin') {
        $admin_count = Database::getInstance()->fetch(
            "SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND status = 'active'",
            []
        )['count'];

        if ($admin_count <= 1) {
            throw new Exception("ไม่สามารถลบบัญชีผู้ดูแลระบบคนสุดท้ายได้");
        }
    }

    // ตรวจสอบว่าผู้ใช้มีการทำรายการค้างอยู่หรือไม่
    $pending_transactions = Database::getInstance()->fetch(
        "SELECT COUNT(*) as count FROM repairs WHERE user_id = :id AND status IN ('pending', 'in_progress')",
        [':id' => $user_id]
    )['count'];

    if ($pending_transactions > 0) {
        throw new Exception("ไม่สามารถลบบัญชีผู้ใช้ได้เนื่องจากมีรายการแจ้งซ่อมที่ยังไม่เสร็จสิ้น");
    }

    // ลบข้อมูลผู้ใช้
    $result = Database::getInstance()->delete(
        "users",
        "user_id = :id",
        [':id' => $user_id]
    );

    if ($result) {
        // บันทึกประวัติการลบ
        Database::getInstance()->insert(
            "activity_logs",
            [
                'user_id' => $_SESSION['user_id'],
                'action' => 'delete',
                'module' => 'users',
                'description' => "ลบบัญชีผู้ใช้ {$user['username']} (ID: {$user_id})",
                'created_at' => date('Y-m-d H:i:s')
            ]
        );

        Database::getInstance()->commit();
        $_SESSION['success'] = "ลบบัญชีผู้ใช้ {$user['username']} เรียบร้อยแล้ว";
    } else {
        throw new Exception("ไม่สามารถลบข้อมูลผู้ใช้ได้");
    }
} catch (Exception $e) {
    Database::getInstance()->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

header("Location: view.php");
exit;
