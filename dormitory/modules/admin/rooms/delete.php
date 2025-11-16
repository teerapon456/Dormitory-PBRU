<?php
require_once __DIR__ . '/../auth_check.php';

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header("Location: view.php");
    exit;
}

// ตรวจสอบ room_id
if (!isset($_POST['room_id']) || !is_numeric($_POST['room_id'])) {
    $_SESSION['error'] = "ไม่พบข้อมูลห้องที่ต้องการลบ";
    header("Location: view.php");
    exit;
}

$room_id = (int)$_POST['room_id'];

try {
    // เริ่ม transaction
    Database::getInstance()->beginTransaction();

    // ตรวจสอบว่ามีห้องนี้อยู่จริง
    $room = Database::getInstance()->fetch(
        "SELECT * FROM rooms WHERE room_id = :id",
        [':id' => $room_id]
    );

    if (!$room) {
        throw new Exception("ไม่พบข้อมูลห้องที่ต้องการลบ");
    }

    // ตรวจสอบว่ามีผู้พักอยู่หรือไม่
    if ($room['current_occupancy'] > 0) {
        throw new Exception("ไม่สามารถลบห้องได้เนื่องจากยังมีผู้พักอยู่");
    }

    // ลบข้อมูลห้อง
    $result = Database::getInstance()->delete(
        "rooms",
        "room_id = :id",
        [':id' => $room_id]
    );

    if ($result) {
        Database::getInstance()->commit();
        $_SESSION['success'] = "ลบข้อมูลห้องเรียบร้อยแล้ว";
    } else {
        throw new Exception("ไม่สามารถลบข้อมูลห้องได้");
    }
} catch (Exception $e) {
    Database::getInstance()->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

header("Location: view.php");
exit;