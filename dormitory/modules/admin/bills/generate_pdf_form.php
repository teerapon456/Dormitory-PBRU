<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../../config/database.php';

$page_title = "พิมพ์ใบแจ้งค่าใช้จ่าย";
require_once __DIR__ . '/../header.php';

// Get buildings data
$sql = "SELECT b.building_id, b.building_name,
        COUNT(DISTINCT r.room_id) as total_rooms,
        COUNT(DISTINCT CASE WHEN r.current_occupancy > 0 THEN r.room_id END) as occupied_rooms
        FROM buildings b
        LEFT JOIN rooms r ON b.building_id = r.building_id
        GROUP BY b.building_id
        ORDER BY b.building_name";

$stmt = $conn->prepare($sql);
$stmt->execute();
$buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate last 12 months
$months = [];
for ($i = 0; $i < 12; $i++) {
    $date = date('Y-m', strtotime("-$i months"));
    $months[$date] = date('F Y', strtotime($date));
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">หน้าหลัก</a></li>
        <li class="breadcrumb-item"><a href="index.php">จัดการค่าใช้จ่าย</a></li>
        <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-print me-1"></i>
            เลือกเงื่อนไขการพิมพ์
        </div>
        <div class="card-body">
            <form action="generate_pdf.php" method="get" target="_blank">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="month" class="form-label">เดือน</label>
                        <select name="month" id="month" class="form-select" required>
                            <?php foreach ($months as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="building_id" class="form-label">อาคาร</label>
                        <select name="building_id" id="building_id" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($buildings as $building): ?>
                            <option value="<?php echo $building['building_id']; ?>">
                                <?php echo $building['building_name']; ?>
                                (<?php echo $building['occupied_rooms']; ?>/<?php echo $building['total_rooms']; ?>
                                ห้อง)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-print me-1"></i> พิมพ์ PDF
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> ยกเลิก
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>