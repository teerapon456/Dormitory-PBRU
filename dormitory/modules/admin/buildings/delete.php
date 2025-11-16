<?php
require_once __DIR__ . '/../auth_check.php';

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header("Location: list.php");
    exit;
}

// ตรวจสอบ building_id
if (!isset($_POST['building_id']) || !is_numeric($_POST['building_id'])) {
    $_SESSION['error'] = "ไม่พบข้อมูลอาคารที่ต้องการลบ";
    header("Location: list.php");
    exit;
}

$building_id = (int)$_POST['building_id'];

try {
    // เริ่ม transaction
    Database::getInstance()->beginTransaction();

    // ตรวจสอบว่ามีอาคารนี้อยู่จริง
    $building = Database::getInstance()->fetch(
        "SELECT * FROM buildings WHERE building_id = :id",
        [':id' => $building_id]
    );

    if (!$building) {
        throw new Exception("ไม่พบข้อมูลอาคารที่ต้องการลบ");
    }

    // ตรวจสอบว่ามีห้องที่มีผู้พักอยู่หรือไม่
    $occupied_rooms = Database::getInstance()->fetch(
        "SELECT COUNT(*) as count FROM rooms r 
         INNER JOIN users u ON r.room_id = u.room_id 
         WHERE r.building_id = :id",
        [':id' => $building_id]
    );

    if ($occupied_rooms['count'] > 0) {
        throw new Exception("ไม่สามารถลบอาคารได้เนื่องจากยังมีห้องที่มีผู้พักอยู่");
    }

    // ลบข้อมูลห้องพักในอาคารนี้
    Database::getInstance()->delete(
        "rooms",
        "building_id = :id",
        [':id' => $building_id]
    );

    // ลบข้อมูลอาคาร
    $result = Database::getInstance()->delete(
        "buildings",
        "building_id = :id",
        [':id' => $building_id]
    );

    if ($result) {
        Database::getInstance()->commit();
        $_SESSION['success'] = "ลบข้อมูลอาคารเรียบร้อยแล้ว";
    } else {
        throw new Exception("ไม่สามารถลบข้อมูลอาคารได้");
    }
} catch (Exception $e) {
    Database::getInstance()->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

header("Location: list.php");
exit;
