<?php
require_once __DIR__ . '/../../includes/header.php';

// ตรวจสอบการล็อกอิน
if (!Auth::isLoggedIn()) {
    header('Location: ' . Config::$baseUrl . '/modules/users/login.php');
    exit;
}

// ดึงข้อมูลนักศึกษาและห้องพัก
$student = Database::getInstance()->fetch("
    SELECT s.*, 
           r.id as room_id,
           r.room_number,
           b.name as building_name
    FROM students s
    LEFT JOIN room_assignments ra ON s.id = ra.student_id AND ra.status = 'active'
    LEFT JOIN rooms r ON ra.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    WHERE s.id = ?
", [$_SESSION['user_id']]);

// ตรวจสอบว่ามีห้องพักหรือไม่
if (!$student['room_id']) {
    $_SESSION['error'] = 'คุณยังไม่ได้เข้าพักในหอพัก';
    header('Location: ' . Config::$baseUrl);
    exit;
}

// ดึงประวัติการแจ้งซ่อม
$repairs = Database::getInstance()->fetchAll("
    SELECT rr.*, 
           rh.action,
           rh.details,
           rh.created_at as history_date,
           CONCAT(u.first_name, ' ', u.last_name) as performed_by_name
    FROM repair_requests rr
    LEFT JOIN repair_history rh ON rr.id = rh.repair_id
    LEFT JOIN users u ON rh.performed_by = u.id
    WHERE rr.student_id = ?
    ORDER BY rr.created_at DESC, rh.created_at DESC
", [$_SESSION['user_id']]);

// จัดกลุ่มข้อมูลตามการแจ้งซ่อม
$grouped_repairs = [];
foreach ($repairs as $repair) {
    if (!isset($grouped_repairs[$repair['id']])) {
        $grouped_repairs[$repair['id']] = [
            'id' => $repair['id'],
            'repair_type' => $repair['repair_type'],
            'description' => $repair['description'],
            'image_path' => $repair['image_path'],
            'status' => $repair['status'],
            'created_at' => $repair['created_at'],
            'completed_at' => $repair['completed_at'],
            'history' => []
        ];
    }
    if ($repair['action']) {
        $grouped_repairs[$repair['id']]['history'][] = [
            'action' => $repair['action'],
            'details' => $repair['details'],
            'date' => $repair['history_date'],
            'performed_by' => $repair['performed_by_name']
        ];
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>ประวัติการแจ้งซ่อม</h2>
            <a href="<?php echo Config::$baseUrl; ?>/modules/public/repairs/add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> แจ้งซ่อมใหม่
            </a>
        </div>
    </div>
</div>

<!-- ข้อมูลห้องพัก -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">ข้อมูลห้องพัก</h5>
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1">
                    <strong>อาคาร:</strong>
                    <?php echo htmlspecialchars($student['building_name']); ?>
                </p>
                <p class="mb-1">
                    <strong>ห้อง:</strong>
                    <?php echo htmlspecialchars($student['room_number']); ?>
                </p>
            </div>
            <div class="col-md-6">
                <p class="mb-1">
                    <strong>รหัสนักศึกษา:</strong>
                    <?php echo htmlspecialchars($student['student_id']); ?>
                </p>
                <p class="mb-1">
                    <strong>ชื่อ-นามสกุล:</strong>
                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- รายการแจ้งซ่อม -->
<?php if (empty($grouped_repairs)): ?>
    <div class="alert alert-info">
        ไม่พบประวัติการแจ้งซ่อม
    </div>
<?php else: ?>
    <?php foreach ($grouped_repairs as $repair): ?>
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="card-title mb-1">
                            <?php
                            $type_text = [
                                'plumbing' => 'ประปา',
                                'electrical' => 'ไฟฟ้า',
                                'furniture' => 'เฟอร์นิเจอร์',
                                'air_conditioner' => 'แอร์คอนดิชันเนอร์',
                                'other' => 'อื่นๆ'
                            ];
                            echo $type_text[$repair['repair_type']] ?? $repair['repair_type'];
                            ?>
                        </h5>
                        <p class="text-muted mb-0">
                            <?php echo date('d/m/Y H:i', strtotime($repair['created_at'])); ?>
                        </p>
                    </div>
                    <span class="badge bg-<?php
                                            echo [
                                                'pending' => 'warning',
                                                'in_progress' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'danger'
                                            ][$repair['status']] ?? 'secondary';
                                            ?>">
                        <?php
                        echo [
                            'pending' => 'รอดำเนินการ',
                            'in_progress' => 'กำลังดำเนินการ',
                            'completed' => 'เสร็จสิ้น',
                            'cancelled' => 'ยกเลิก'
                        ][$repair['status']] ?? $repair['status'];
                        ?>
                    </span>
                </div>

                <p class="card-text">
                    <?php echo nl2br(htmlspecialchars($repair['description'])); ?>
                </p>

                <?php if ($repair['image_path']): ?>
                    <div class="mb-3">
                        <img src="<?php echo Config::$baseUrl . '/' . $repair['image_path']; ?>"
                            alt="รูปภาพประกอบ" class="img-fluid rounded" style="max-height: 200px;">
                    </div>
                <?php endif; ?>

                <?php if (!empty($repair['history'])): ?>
                    <hr>
                    <h6 class="mb-3">ประวัติการดำเนินการ</h6>
                    <div class="timeline">
                        <?php foreach ($repair['history'] as $history): ?>
                            <div class="timeline-item">
                                <div class="timeline-date">
                                    <?php echo date('d/m/Y H:i', strtotime($history['date'])); ?>
                                </div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>
                                                <?php
                                                echo [
                                                    'created' => 'แจ้งซ่อม',
                                                    'assigned' => 'มอบหมายงาน',
                                                    'in_progress' => 'เริ่มดำเนินการ',
                                                    'completed' => 'ดำเนินการเสร็จสิ้น',
                                                    'cancelled' => 'ยกเลิก'
                                                ][$history['action']] ?? $history['action'];
                                                ?>
                                            </strong>
                                            <p class="mb-0"><?php echo htmlspecialchars($history['details']); ?></p>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($history['performed_by']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline-item {
        position: relative;
        padding-bottom: 20px;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -30px;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #e9ecef;
    }

    .timeline-item::after {
        content: '';
        position: absolute;
        left: -36px;
        top: 0;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background-color: #0d6efd;
        border: 2px solid #fff;
    }

    .timeline-date {
        font-size: 0.875rem;
        color: #6c757d;
        margin-bottom: 5px;
    }

    .timeline-content {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 0.25rem;
    }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>