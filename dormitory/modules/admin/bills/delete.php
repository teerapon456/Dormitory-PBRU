<?php
require_once __DIR__ . '/../auth_check.php';

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header("Location: list.php");
    exit;
}

// ตรวจสอบ bill_id
if (!isset($_POST['bill_id']) || !is_numeric($_POST['bill_id'])) {
    $_SESSION['error'] = "ไม่พบรายการที่ต้องการลบ";
    header("Location: list.php");
    exit;
}

$bill_id = (int)$_POST['bill_id'];

try {
    // ตรวจสอบว่ามีรายการนี้อยู่จริง
    $bill = Database::getInstance()->fetch("
        SELECT * FROM utility_bills WHERE bill_id = :bill_id
    ", [':bill_id' => $bill_id]);

    if (!$bill) {
        throw new Exception("ไม่พบรายการที่ต้องการลบ");
    }

    // ตรวจสอบว่าบิลนี้ยังไม่ได้ชำระเงิน
    if ($bill['status'] === 'ชำระแล้ว') {
        throw new Exception("ไม่สามารถลบรายการที่ชำระเงินแล้ว");
    }

    // ลบรายการ
    $result = Database::getInstance()->delete(
        "utility_bills",
        "bill_id = :bill_id",
        [':bill_id' => $bill_id]
    );

    if ($result) {
        $_SESSION['success'] = "ลบรายการค่าใช้จ่ายเรียบร้อยแล้ว";
    } else {
        throw new Exception("ไม่สามารถลบรายการได้");
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: list.php");
exit;
