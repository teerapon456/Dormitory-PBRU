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

// ดึงประวัติบิล
$bills = Database::getInstance()->fetchAll("
    SELECT ub.*, 
           ph.action,
           ph.details,
           ph.created_at as history_date,
           CONCAT(u.first_name, ' ', u.last_name) as performed_by_name
    FROM utility_bills ub
    LEFT JOIN payment_history ph ON ub.id = ph.bill_id
    LEFT JOIN users u ON ph.performed_by = u.id
    WHERE ub.room_id = ?
    ORDER BY ub.created_at DESC, ph.created_at DESC
", [$student['room_id']]);

// จัดกลุ่มข้อมูลตามบิล
$grouped_bills = [];
foreach ($bills as $bill) {
    if (!isset($grouped_bills[$bill['id']])) {
        $grouped_bills[$bill['id']] = [
            'id' => $bill['id'],
            'bill_type' => $bill['bill_type'],
            'description' => $bill['description'],
            'amount' => $bill['amount'],
            'status' => $bill['status'],
            'created_at' => $bill['created_at'],
            'due_date' => $bill['due_date'],
            'paid_at' => $bill['paid_at'],
            'history' => []
        ];
    }
    if ($bill['action']) {
        $grouped_bills[$bill['id']]['history'][] = [
            'action' => $bill['action'],
            'details' => $bill['details'],
            'date' => $bill['history_date'],
            'performed_by' => $bill['performed_by_name']
        ];
    }
}

// คำนวณยอดรวม
$total_amount = 0;
$paid_amount = 0;
$pending_amount = 0;

foreach ($grouped_bills as $bill) {
    $total_amount += $bill['amount'];
    if ($bill['status'] === 'paid') {
        $paid_amount += $bill['amount'];
    } else {
        $pending_amount += $bill['amount'];
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>ประวัติบิล</h2>
            <a href="<?php echo Config::$baseUrl; ?>/modules/public/bills/list.php" class="btn btn-primary">
                <i class="fas fa-list"></i> ดูบิลทั้งหมด
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

<!-- สรุปยอด -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">ยอดรวมทั้งหมด</h6>
                <h3 class="mb-0"><?php echo number_format($total_amount, 2); ?> บาท</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">ชำระแล้ว</h6>
                <h3 class="mb-0"><?php echo number_format($paid_amount, 2); ?> บาท</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h6 class="card-title">ค้างชำระ</h6>
                <h3 class="mb-0"><?php echo number_format($pending_amount, 2); ?> บาท</h3>
            </div>
        </div>
    </div>
</div>

<!-- รายการบิล -->
<?php if (empty($grouped_bills)): ?>
    <div class="alert alert-info">
        ไม่พบประวัติบิล
    </div>
<?php else: ?>
    <?php foreach ($grouped_bills as $bill): ?>
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="card-title mb-1">
                            <?php
                            $type_text = [
                                'electricity' => 'ค่าไฟฟ้า',
                                'water' => 'ค่าน้ำ',
                                'internet' => 'ค่าเน็ต',
                                'other' => 'อื่นๆ'
                            ];
                            echo $type_text[$bill['bill_type']] ?? $bill['bill_type'];
                            ?>
                        </h5>
                        <p class="text-muted mb-0">
                            <?php echo date('d/m/Y', strtotime($bill['created_at'])); ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <h4 class="mb-1"><?php echo number_format($bill['amount'], 2); ?> บาท</h4>
                        <span class="badge bg-<?php
                                                echo [
                                                    'pending' => 'warning',
                                                    'paid' => 'success',
                                                    'cancelled' => 'danger'
                                                ][$bill['status']] ?? 'secondary';
                                                ?>">
                            <?php
                            echo [
                                'pending' => 'รอดำเนินการ',
                                'paid' => 'ชำระแล้ว',
                                'cancelled' => 'ยกเลิก'
                            ][$bill['status']] ?? $bill['status'];
                            ?>
                        </span>
                    </div>
                </div>

                <p class="card-text">
                    <?php echo nl2br(htmlspecialchars($bill['description'])); ?>
                </p>

                <?php if ($bill['due_date']): ?>
                    <p class="text-muted mb-3">
                        <i class="fas fa-calendar"></i> กำหนดชำระ:
                        <?php echo date('d/m/Y', strtotime($bill['due_date'])); ?>
                    </p>
                <?php endif; ?>

                <?php if ($bill['paid_at']): ?>
                    <p class="text-success mb-3">
                        <i class="fas fa-check-circle"></i> ชำระเมื่อ:
                        <?php echo date('d/m/Y', strtotime($bill['paid_at'])); ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($bill['history'])): ?>
                    <hr>
                    <h6 class="mb-3">ประวัติการชำระ</h6>
                    <div class="timeline">
                        <?php foreach ($bill['history'] as $history): ?>
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
                                                    'created' => 'สร้างบิล',
                                                    'paid' => 'ชำระเงิน',
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