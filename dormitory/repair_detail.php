<?php
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();
$error = null;
$repair = null;

// Get request_id from URL parameter
$request_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);

if ($request_id) {
    try {
        // Get repair request details with all related information
        $repair = $db->fetch("
            SELECT 
                r.*,
                -- User/Contact information
                COALESCE(c.full_name, u.full_name) as contact_name,
                COALESCE(c.phone_number, u.phone_number) as contact_phone,
                COALESCE(c.email, u.email) as contact_email,
                -- Location information
                l.location_name_th,
                l.description as location_description,
                l.is_public_area,
                l.floor_number as location_floor,
                -- Building information
                b.building_name,
                b.total_floors,
                -- Room information
                rm.room_number,
                rm.floor_number as room_floor,
                rm.max_capacity,
                rm.current_occupancy,
                -- Category and Item information
                i.item_name,
                i.description_th as item_description,
                cat.category_name,
                cat.description as category_description
            FROM repair_requests r
            LEFT JOIN public_repair_contacts c ON r.contact_id = c.contact_id
            LEFT JOIN users u ON r.user_id = u.user_id
            LEFT JOIN repair_locations l ON r.location_id = l.location_id
            LEFT JOIN buildings b ON l.building_id = b.building_id
            LEFT JOIN rooms rm ON r.room_id = rm.room_id
            LEFT JOIN repair_items i ON r.item_id = i.item_id
            LEFT JOIN repair_categories cat ON i.category_id = cat.category_id
            WHERE r.request_id = ?
        ", [$request_id]);

        if (!$repair) {
            throw new Exception('ไม่พบข้อมูลการแจ้งซ่อม');
        }

        // Get repair history with admin information
        $history = $db->fetchAll("
            SELECT 
                h.*,
                u.full_name as admin_name,
                u.email as admin_email,
                u.phone_number as admin_phone
            FROM repair_history h
            LEFT JOIN users u ON h.admin_id = u.user_id
            WHERE h.request_id = ?
            ORDER BY h.created_time DESC
        ", [$repair['request_id']]);

        // Get repair images
        $images = $db->fetchAll("
            SELECT *
            FROM repair_images
            WHERE request_id = ?
            ORDER BY created_time ASC
        ", [$repair['request_id']]);

        $repair['images'] = $images;
        $repair['history'] = $history;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">
                    <i class="fas fa-tools text-primary me-2"></i>รายละเอียดการแจ้งซ่อม
                </h2>
                <a href="repair_status.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>กลับไปหน้ารายการ
                </a>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
            <?php elseif ($repair): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">หมายเลขแจ้งซ่อม: <?php echo htmlspecialchars($repair['request_id']); ?></h5>
                        <span class="badge bg-light text-dark">
                            <?php
                                $status_class = [
                                    'รอดำเนินการ' => 'warning',
                                    'กำลังดำเนินการ' => 'info',
                                    'เสร็จสิ้น' => 'success',
                                    'ยกเลิก' => 'danger'
                                ];
                                $badge_class = $status_class[$repair['status']] ?? 'secondary';
                                ?>
                            <span class="badge bg-<?php echo $badge_class; ?>">
                                <?php echo htmlspecialchars($repair['status']); ?>
                            </span>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">ข้อมูลการแจ้งซ่อม</h6>
                            <div class="mb-3">
                                <p class="mb-2"><strong>วันที่แจ้ง:</strong>
                                    <?php echo date('d/m/Y H:i', strtotime($repair['created_time'])); ?>
                                </p>

                                <!-- ประเภทการซ่อม -->
                                <p class="mb-2"><strong>ประเภท:</strong>
                                    <?php if ($repair['category_name']): ?>
                                    <?php echo htmlspecialchars($repair['category_name']); ?>
                                    <?php if ($repair['category_description']): ?>
                                    <small class="text-muted d-block">
                                        <?php echo htmlspecialchars($repair['category_description']); ?>
                                    </small>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <?php echo htmlspecialchars($repair['title']); ?>
                                    <?php endif; ?>
                                </p>

                                <!-- รายการที่แจ้งซ่อม -->
                                <?php if ($repair['item_name']): ?>
                                <p class="mb-2"><strong>รายการ:</strong>
                                    <?php echo htmlspecialchars($repair['item_name']); ?>
                                    <?php if ($repair['item_description']): ?>
                                    <small class="text-muted d-block">
                                        <?php echo htmlspecialchars($repair['item_description']); ?>
                                    </small>
                                    <?php endif; ?>
                                </p>
                                <?php endif; ?>

                                <!-- สถานที่ -->
                                <p class="mb-2"><strong>สถานที่:</strong>
                                    <?php if ($repair['location_name_th']): ?>
                                    <?php echo htmlspecialchars($repair['location_name_th']); ?>
                                    <?php if ($repair['location_description']): ?>
                                    <small class="text-muted d-block">
                                        <?php echo htmlspecialchars($repair['location_description']); ?>
                                    </small>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <?php echo htmlspecialchars($repair['location'] ?? '-'); ?>
                                    <?php endif; ?>
                                </p>

                                <!-- อาคารและห้อง -->
                                <?php if ($repair['building_name']): ?>
                                <p class="mb-2"><strong>อาคาร:</strong>
                                    <?php echo htmlspecialchars($repair['building_name']); ?>
                                    <?php if ($repair['location_floor']): ?>
                                    <span class="text-muted">
                                        (ชั้น <?php echo htmlspecialchars($repair['location_floor']); ?>)
                                    </span>
                                    <?php endif; ?>
                                </p>
                                <?php endif; ?>

                                <?php if ($repair['room_number']): ?>
                                <p class="mb-2"><strong>ห้อง:</strong>
                                    <?php echo htmlspecialchars($repair['room_number']); ?>
                                    <?php if ($repair['room_floor']): ?>
                                    <span class="text-muted">
                                        (ชั้น <?php echo htmlspecialchars($repair['room_floor']); ?>)
                                    </span>
                                    <?php endif; ?>
                                </p>
                                <?php endif; ?>

                                <!-- รายละเอียด -->
                                <p class="mb-2"><strong>รายละเอียด:</strong></p>
                                <div class="border rounded p-3 bg-light">
                                    <?php echo nl2br(htmlspecialchars($repair['description'])); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">ข้อมูลผู้แจ้ง</h6>
                            <p class="mb-2"><strong>ชื่อ-นามสกุล:</strong>
                                <?php echo htmlspecialchars($repair['contact_name']); ?>
                            </p>
                            <p class="mb-2"><strong>เบอร์โทร:</strong>
                                <?php echo htmlspecialchars($repair['contact_phone']); ?>
                            </p>
                            <?php if (!empty($repair['contact_email'])): ?>
                            <p class="mb-2"><strong>อีเมล:</strong>
                                <?php echo htmlspecialchars($repair['contact_email']); ?>
                            </p>
                            <?php endif; ?>

                            <!-- แสดงข้อมูลเพิ่มเติมกรณีเป็นการแจ้งซ่อมจากบุคคลภายนอก -->
                            <?php if ($repair['contact_id']): ?>
                            <?php
                                    $contact = $db->fetch("
                                SELECT *
                                FROM public_repair_contacts
                                WHERE contact_id = ?
                            ", [$repair['contact_id']]);

                                    if ($contact && !empty($contact['address'])): ?>
                            <p class="mb-2"><strong>ที่อยู่:</strong>
                                <?php echo nl2br(htmlspecialchars($contact['address'])); ?>
                            </p>
                            <?php endif; ?>

                            <?php if ($contact && !empty($contact['preferred_contact_time'])): ?>
                            <p class="mb-2"><strong>เวลาที่สะดวกติดต่อ:</strong>
                                <?php echo htmlspecialchars($contact['preferred_contact_time']); ?>
                            </p>
                            <?php endif; ?>

                            <?php if ($contact && !empty($contact['additional_notes'])): ?>
                            <p class="mb-2"><strong>หมายเหตุเพิ่มเติม:</strong>
                                <?php echo nl2br(htmlspecialchars($contact['additional_notes'])); ?>
                            </p>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($repair['images'])): ?>
                    <div class="mt-4">
                        <h6 class="border-bottom pb-2 mb-3">รูปภาพประกอบ</h6>
                        <div class="row g-3">
                            <?php foreach ($repair['images'] as $image): ?>
                            <div class="col-md-3 col-sm-6">
                                <a href="<?php echo Config::$baseUrl . '/' . $image['image_path']; ?>" target="_blank"
                                    class="d-block">
                                    <img src="<?php echo Config::$baseUrl . '/' . $image['image_path']; ?>"
                                        alt="รูปภาพประกอบ" class="img-fluid rounded shadow-sm">
                                </a>
                                <?php if (!empty($image['image_description'])): ?>
                                <small class="text-muted d-block mt-1">
                                    <?php echo htmlspecialchars($image['image_description']); ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($repair['history'])): ?>
                    <div class="mt-4">
                        <h6 class="border-bottom pb-2 mb-3">ประวัติการดำเนินการ</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>วันที่/เวลา</th>
                                        <th>การดำเนินการ</th>
                                        <th>ผู้ดำเนินการ</th>
                                        <th>หมายเหตุ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($repair['history'] as $history): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($history['created_time'])); ?></td>
                                        <td>
                                            <?php
                                                        $action_class = [
                                                            'มอบหมายงาน' => 'text-info',
                                                            'เริ่มดำเนินการ' => 'text-primary',
                                                            'เสร็จสิ้น' => 'text-success',
                                                            'ยกเลิก' => 'text-danger'
                                                        ];
                                                        $class = $action_class[$history['action']] ?? '';
                                                        ?>
                                            <span class="<?php echo $class; ?>">
                                                <?php echo htmlspecialchars($history['action']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($history['admin_name']): ?>
                                            <?php echo htmlspecialchars($history['admin_name']); ?>
                                            <?php if ($history['admin_phone']): ?>
                                            <small class="text-muted d-block">
                                                โทร: <?php echo htmlspecialchars($history['admin_phone']); ?>
                                            </small>
                                            <?php endif; ?>
                                            <?php else: ?>
                                            -
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo nl2br(htmlspecialchars($history['notes'] ?? '')); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>กรุณาระบุหมายเลขแจ้งซ่อมที่ต้องการดูรายละเอียด
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>