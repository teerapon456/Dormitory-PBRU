<?php
require_once('../../../vendor/autoload.php');
require_once('../../../config/database.php');

// Check if month and building_id are provided
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$building_id = isset($_GET['building_id']) ? $_GET['building_id'] : null;

// Create custom PDF class
class MYPDF extends TCPDF
{
    private $month;

    public function setMonth($month)
    {
        $this->month = $month;
    }

    public function Header()
    {
        $this->SetFont('thsarabun', 'B', 20);
        $this->Cell(0, 15, 'ใบแจ้งค่าใช้จ่าย', 0, 1, 'C');
        $this->SetFont('thsarabun', '', 16);
        $this->Cell(0, 10, 'หอพักนักศึกษา', 0, 1, 'C');
        $this->Cell(0, 10, 'ประจำเดือน ' . date('F Y', strtotime($this->month)), 0, 1, 'C');
        $this->Ln(5);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('thsarabun', '', 12);
        $this->Cell(0, 10, 'หน้า ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

try {
    // Get bills data
    $sql = "SELECT b.bill_id, b.bill_type, b.amount, b.reading_time, b.due_date,
            r.room_id, r.room_number, bd.building_name,
            GROUP_CONCAT(DISTINCT CONCAT(s.firstname, ' ', s.lastname) SEPARATOR ', ') as residents
            FROM bills b
            JOIN rooms r ON b.room_id = r.room_id
            JOIN buildings bd ON r.building_id = bd.building_id
            LEFT JOIN students s ON r.room_id = s.room_id
            WHERE DATE_FORMAT(b.reading_time, '%Y-%m') = :month";

    if ($building_id) {
        $sql .= " AND bd.building_id = :building_id";
    }

    $sql .= " GROUP BY b.bill_id, r.room_id, r.room_number, bd.building_name
              ORDER BY bd.building_name, r.room_number, b.bill_type";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':month', $month);
    if ($building_id) {
        $stmt->bindParam(':building_id', $building_id);
    }
    $stmt->execute();
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group bills by room
    $bills_by_room = [];
    foreach ($bills as $bill) {
        $room_key = $bill['building_name'] . ' - ' . $bill['room_number'];
        if (!isset($bills_by_room[$room_key])) {
            $bills_by_room[$room_key] = [
                'room_number' => $bill['room_number'],
                'building_name' => $bill['building_name'],
                'residents' => $bill['residents'],
                'bills' => []
            ];
        }
        $bills_by_room[$room_key]['bills'][] = $bill;
    }

    // Create new PDF document
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setMonth($month);

    // Set document information
    $pdf->SetCreator('Dormitory System');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('ใบแจ้งค่าใช้จ่าย - ' . date('F Y', strtotime($month)));

    // Set margins
    $pdf->SetMargins(15, 50, 15);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('thsarabun', '', 16);

    $grand_total = 0;
    foreach ($bills_by_room as $room_key => $room_data) {
        // Room header
        $pdf->SetFont('thsarabun', 'B', 16);
        $pdf->Cell(0, 10, $room_data['building_name'] . ' ห้อง ' . $room_data['room_number'], 0, 1);

        // Residents
        $pdf->SetFont('thsarabun', '', 14);
        $pdf->Cell(0, 8, 'ผู้พัก: ' . ($room_data['residents'] ?: '-'), 0, 1);

        // Table header
        $pdf->SetFont('thsarabun', '', 14);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(70, 10, 'ประเภท', 1, 0, 'C', true);
        $pdf->Cell(40, 10, 'จำนวนเงิน', 1, 0, 'C', true);
        $pdf->Cell(40, 10, 'วันที่', 1, 0, 'C', true);
        $pdf->Cell(40, 10, 'กำหนดชำระ', 1, 1, 'C', true);

        // Bills
        $room_total = 0;
        foreach ($room_data['bills'] as $bill) {
            $pdf->Cell(70, 10, $bill['bill_type'], 1, 0, 'L');
            $pdf->Cell(40, 10, number_format($bill['amount'], 2), 1, 0, 'R');
            $pdf->Cell(40, 10, date('d/m/Y', strtotime($bill['reading_time'])), 1, 0, 'C');
            $pdf->Cell(40, 10, date('d/m/Y', strtotime($bill['due_date'])), 1, 1, 'C');
            $room_total += $bill['amount'];
        }

        // Room total
        $pdf->SetFont('thsarabun', 'B', 14);
        $pdf->Cell(70, 10, 'รวม', 1, 0, 'R');
        $pdf->Cell(40, 10, number_format($room_total, 2), 1, 0, 'R');
        $pdf->Cell(80, 10, '', 1, 1, 'C');
        $grand_total += $room_total;

        // Add space between rooms
        $pdf->Ln(10);

        // Check if we need a new page
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
        }
    }

    // Grand total
    $pdf->SetFont('thsarabun', 'B', 16);
    $pdf->Cell(70, 10, 'รวมทั้งสิ้น', 1, 0, 'R');
    $pdf->Cell(40, 10, number_format($grand_total, 2), 1, 0, 'R');
    $pdf->Cell(80, 10, '', 1, 1, 'C');

    // Output PDF
    $pdf->Output('bills_' . $month . '.pdf', 'I');
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
