<?php
require_once __DIR__ . '/../../../includes/header.php';

$room_id = $_GET['room_id'];
$month = $_GET['month'];
$year = $_GET['year'];

// ดึงข้อมูลบิลค่าน้ำและค่าไฟของห้องในเดือนที่เลือก
$bills = Database::getInstance()->fetchAll("
    SELECT ub.*, 
           r.room_number,
           b.building_name
    FROM utility_bills ub
    LEFT JOIN rooms r ON ub.room_id = r.room_id
    LEFT JOIN buildings b ON r.building_id = b.building_id
    WHERE ub.room_id = ? 
    AND MONTH(ub.reading_time) = ?
    AND YEAR(ub.reading_time) = ?
    AND ub.bill_type IN ('น้ำ', 'ไฟฟ้า')
    ORDER BY ub.bill_type ASC
", [$room_id, $month, $year]);

if (empty($bills)) {
    header('Location: list.php');
    exit;
}

$total_amount = 0;
foreach ($bills as $bill) {
    $total_amount += $bill['amount'];
}

$first_bill = $bills[0]; // ใช้ข้อมูลห้องจากบิลแรก
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จรวมค่าน้ำค่าไฟ - หอพักนักศึกษา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>
    <!-- ส่วนปุ่มควบคุมสำหรับหน้าจอ -->
    <div class="screen-only container mt-4 mb-4">
        <div class="row">
            <div class="col-12 text-center">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> พิมพ์ใบเสร็จ
                </button>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> กลับ
                </a>
            </div>
        </div>
    </div>

    <!-- ส่วนใบเสร็จ -->
    <div class="receipt-container">
        <div class="receipt-content">
            <!-- โลโก้และชื่อหอพัก -->
            <div class="receipt-logo text-center">
                <i class="fas fa-home logo-icon"></i>
                <div class="brand-name">Dormitory</div>
            </div>

            <!-- หัวใบเสร็จ -->
            <div class="receipt-header text-center">
                <h1>ใบเสร็จรวมค่าน้ำค่าไฟ</h1>
                <h2>หอพักนักศึกษา</h2>
                <h3>ประจำเดือน <?php echo date('F Y', strtotime("$year-$month-01")); ?></h3>
            </div>

            <!-- ข้อมูลใบเสร็จ -->
            <div class="receipt-info">
                <table class="info-table">
                    <tr>
                        <td width="50%">
                            <strong>เลขที่ใบเสร็จ:</strong>
                            <?php echo date('Ym', strtotime("$year-$month-01")) . str_pad($room_id, 4, '0', STR_PAD_LEFT); ?>
                        </td>
                        <td width="50%" class="text-end">
                            <strong>ห้อง:</strong> <?php echo htmlspecialchars($first_bill['room_number']); ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>วันที่ออกใบเสร็จ:</strong> <?php echo date('d/m/Y'); ?>
                        </td>
                        <td class="text-end">
                            <strong>อาคาร:</strong> <?php echo htmlspecialchars($first_bill['building_name']); ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- รายการชำระเงิน -->
            <div class="receipt-details">
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th width="40%">รายการ</th>
                            <th width="20%" class="text-end">วันที่อ่านมิเตอร์</th>
                            <th width="20%" class="text-end">จำนวนเงิน</th>
                            <th width="20%">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bill['bill_type']); ?></td>
                                <td class="text-end"><?php echo date('d/m/Y', strtotime($bill['reading_time'])); ?></td>
                                <td class="text-end"><?php echo number_format($bill['amount'], 2); ?> บาท</td>
                                <td><?php echo htmlspecialchars($bill['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="2" class="text-end"><strong>รวมเป็นเงินทั้งสิ้น</strong></td>
                            <td class="text-end"><strong><?php echo number_format($total_amount, 2); ?> บาท</strong></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ลายเซ็น -->
            <div class="receipt-signatures">
                <table class="signature-table">
                    <tr>
                        <td width="50%" class="text-center">
                            <div class="signature-line"></div>
                            <p>ลายเซ็นผู้รับเงิน</p>
                            <p class="small">เจ้าหน้าที่การเงิน</p>
                        </td>
                        <td width="50%" class="text-center">
                            <div class="signature-line"></div>
                            <p>ลายเซ็นผู้ชำระเงิน</p>
                            <p class="small">ผู้พักอาศัย</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <style>
        /* สไตล์ทั่วไป */
        body {
            font-family: 'Sarabun', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        /* สไตล์สำหรับหน้าจอ */
        .screen-only {
            display: block;
        }

        /* สไตล์โลโก้ */
        .receipt-logo {
            margin-bottom: 2rem;
        }

        .logo-icon {
            font-size: 2.5rem;
            color: #0d6efd;
        }

        .brand-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0d6efd;
            margin-top: 0.5rem;
        }

        /* สไตล์ส่วนใบเสร็จ */
        .receipt-container {
            max-width: 21cm;
            margin: 0 auto;
            padding: 2cm;
            background: white;
        }

        .receipt-content {
            width: 100%;
        }

        .receipt-header {
            margin-bottom: 2rem;
        }

        .receipt-header h1 {
            font-size: 24pt;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .receipt-header h2 {
            font-size: 18pt;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .receipt-header h3 {
            font-size: 16pt;
            color: #666;
            margin-bottom: 1.5rem;
        }

        .info-table {
            width: 100%;
            margin-bottom: 1.5rem;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 0.5rem 0;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }

        .receipt-table th,
        .receipt-table td {
            border: 1px solid #000;
            padding: 0.75rem;
        }

        .receipt-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .total-row td {
            border-top: 2px solid #000;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .signature-table {
            width: 100%;
            margin-top: 3rem;
        }

        .signature-line {
            width: 60%;
            margin: 3rem auto 0.5rem;
            border-bottom: 1px solid #000;
        }

        .small {
            font-size: 0.9em;
            color: #666;
        }

        /* สไตล์สำหรับการพิมพ์ */
        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 0;
                background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .screen-only {
                display: none !important;
            }

            .receipt-container {
                padding: 2cm;
                box-shadow: none;
            }

            .receipt-header h1,
            .receipt-header h2,
            .receipt-header h3,
            .small {
                color: #000;
            }

            .receipt-table th {
                background-color: white !important;
            }

            .logo-icon,
            .brand-name {
                color: #000 !important;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</body>

</html>