<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    die("Unauthorized access");
}

$db = new Database();
$conn = $db->connect();


$total_events = $conn->query("SELECT COUNT(*) FROM event")->fetchColumn();
$upcoming_events = $conn->query("SELECT COUNT(*) FROM event WHERE event_date >= CURDATE()")->fetchColumn();
$past_events = $total_events - $upcoming_events;

$pending_requests = $conn->query("SELECT COUNT(*) FROM change_requests WHERE status='pending'")->fetchColumn();
$approved_requests = $conn->query("SELECT COUNT(*) FROM change_requests WHERE status='approved'")->fetchColumn();
$rejected_requests = $conn->query("SELECT COUNT(*) FROM change_requests WHERE status='rejected'")->fetchColumn();
$total_requests = $pending_requests + $approved_requests + $rejected_requests;

$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();

$format = $_GET['format'] ?? 'pdf';

if ($format === 'pdf') {
  
    require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    
    $pdf->SetCreator('Church Reservation System');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('KPI Report');
    $pdf->SetSubject('Church Reservation KPI Report');
    

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    

    $pdf->AddPage();
    
   
    $pdf->SetFont('helvetica', '', 12);
    
    
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 15, 'Church Reservation System', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'KPI Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'Generated on: ' . date('F d, Y h:i A'), 0, 1, 'C');
    $pdf->Ln(10);
    
    
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetFillColor(33, 150, 243);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, 'EVENTS OVERVIEW', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Ln(5);
    
    $pdf->Cell(80, 8, 'Total Events:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, $total_events, 0, 1);
    $pdf->SetFont('helvetica', '', 11);
    
    $pdf->Cell(80, 8, 'Upcoming Events:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, $upcoming_events, 0, 1);
    $pdf->SetFont('helvetica', '', 11);
    
    $pdf->Cell(80, 8, 'Past Events:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, $past_events, 0, 1);
    $pdf->Ln(10);
    
    // Change Requests
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetFillColor(255, 152, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, 'CHANGE REQUESTS', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Ln(5);
    
    $pdf->Cell(80, 8, 'Pending Requests:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(230, 81, 0);
    $pdf->Cell(0, 8, $pending_requests, 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    
    $pdf->Cell(80, 8, 'Approved Requests:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(46, 125, 50);
    $pdf->Cell(0, 8, $approved_requests, 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    
    $pdf->Cell(80, 8, 'Rejected Requests:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(198, 40, 40);
    $pdf->Cell(0, 8, $rejected_requests, 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    
    $pdf->Cell(80, 8, 'Total Requests:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, $total_requests, 0, 1);
    $pdf->Ln(10);
    
   
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetFillColor(156, 39, 176);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, 'USER STATISTICS', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Ln(5);
    
    $pdf->Cell(80, 8, 'Total Registered Users:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, $total_users, 0, 1);
    
   
    $pdf->Output('Church_KPI_Report_' . date('Y-m-d') . '.pdf', 'D');
    
} elseif ($format === 'excel') {
    
    require_once '../vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('KPI Report');
    
    $sheet->mergeCells('A1:B1');
    $sheet->setCellValue('A1', 'Church Reservation System - KPI Report');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $sheet->mergeCells('A2:B2');
    $sheet->setCellValue('A2', 'Generated on: ' . date('F d, Y h:i A'));
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    

    $row = 4;
    $sheet->mergeCells("A$row:B$row");
    $sheet->setCellValue("A$row", 'EVENTS OVERVIEW');
    $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle("A$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2196F3');
    $sheet->getStyle("A$row")->getFont()->getColor()->setRGB('FFFFFF');
    
    $row++;
    $sheet->setCellValue("A$row", 'Total Events');
    $sheet->setCellValue("B$row", $total_events);
    $row++;
    $sheet->setCellValue("A$row", 'Upcoming Events');
    $sheet->setCellValue("B$row", $upcoming_events);
    $row++;
    $sheet->setCellValue("A$row", 'Past Events');
    $sheet->setCellValue("B$row", $past_events);
    
  
    $row += 2;
    $sheet->mergeCells("A$row:B$row");
    $sheet->setCellValue("A$row", 'CHANGE REQUESTS');
    $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle("A$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FF9800');
    $sheet->getStyle("A$row")->getFont()->getColor()->setRGB('FFFFFF');
    
    $row++;
    $sheet->setCellValue("A$row", 'Pending Requests');
    $sheet->setCellValue("B$row", $pending_requests);
    $row++;
    $sheet->setCellValue("A$row", 'Approved Requests');
    $sheet->setCellValue("B$row", $approved_requests);
    $row++;
    $sheet->setCellValue("A$row", 'Rejected Requests');
    $sheet->setCellValue("B$row", $rejected_requests);
    $row++;
    $sheet->setCellValue("A$row", 'Total Requests');
    $sheet->setCellValue("B$row", $total_requests);
    
  
    $row += 2;
    $sheet->mergeCells("A$row:B$row");
    $sheet->setCellValue("A$row", 'USER STATISTICS');
    $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle("A$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('9C27B0');
    $sheet->getStyle("A$row")->getFont()->getColor()->setRGB('FFFFFF');
    
    $row++;
    $sheet->setCellValue("A$row", 'Total Registered Users');
    $sheet->setCellValue("B$row", $total_users);
    
   
    $sheet->getColumnDimension('A')->setWidth(30);
    $sheet->getColumnDimension('B')->setWidth(20);
    
   
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Church_KPI_Report_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>
