<?php
require('tcpdf/tcpdf.php'); // Make sure TCPDF is in the correct path

$conn = new mysqli("localhost", "root", "", "crm_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch client data
$result = $conn->query("SELECT * FROM clients");

// Create new PDF document
$pdf = new TCPDF();
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Client Report - ' . date("d-m-Y"), 0, 1, 'C');

// Table header
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(50, 10, 'Name', 1);
$pdf->Cell(70, 10, 'Email', 1);
$pdf->Cell(30, 10, 'Status', 1);
$pdf->Ln();

// Table rows
$pdf->SetFont('helvetica', '', 12);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $pdf->Cell(50, 10, $row['name'], 1);
        $pdf->Cell(70, 10, $row['email'], 1);
        $pdf->Cell(30, 10, $row['status'], 1);
        $pdf->Ln();
    }
} else {
    $pdf->Cell(0, 10, 'No clients found.', 1, 1, 'C');
}

// Output PDF
$pdf->Output('client_report.pdf', 'D');
?>
