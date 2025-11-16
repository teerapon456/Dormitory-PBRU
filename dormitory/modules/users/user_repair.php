<?php
// เริ่มใช้ output buffering เพื่อป้องกันปัญหา headers already sent
ob_start();

// นำเข้าไฟล์ config เพื่อใช้ค่า baseUrl
require_once __DIR__ . '/../../config/config.php';

// หน้านี้เป็นหน้า redirect ไปยังหน้า student_repair.php ในโฟลเดอร์ modules/public/repairs/
// โดยเราทำแบบนี้เพื่อให้ URL มีความเป็นมิตรกับผู้ใช้มากขึ้น
// และยังคงสามารถใช้ระบบการแจ้งซ่อมเดิมได้
header('Location: ' . Config::$baseUrl . '/modules/public/repairs/student_repair.php');
exit;

// ล้างและปิด output buffer
ob_end_flush();