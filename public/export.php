<?php
require_once __DIR__ . '/../src/partials.php';
require_once '/var/www/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_login();

$pdo = db();
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$type = strtolower(trim($_GET['type'] ?? ''));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) || $from > $to) {
    http_response_code(400);
    exit('Invalid date range');
}

if (!in_array($type, ['xlsx', 'pdf'], true)) {
    http_response_code(400);
    exit('Invalid export type');
}

$sql = "
    SELECT
        p.code,
        p.name,
        p.unit,
        p.current_stock,
        p.min_stock,
        COALESCE(SUM(CASE WHEN m.movement_type = 'IN' THEN m.quantity ELSE 0 END), 0) AS qty_in,
        COALESCE(SUM(CASE WHEN m.movement_type = 'OUT' THEN m.quantity ELSE 0 END), 0) AS qty_out
    FROM products p
    LEFT JOIN stock_movements m
        ON m.product_id = p.id
       AND m.created_at::date BETWEEN :from_date AND :to_date
    GROUP BY p.id, p.code, p.name, p.unit, p.current_stock, p.min_stock
    ORDER BY p.name, p.code
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['from_date' => $from, 'to_date' => $to]);
$rows = $stmt->fetchAll();

$filenameBase = 'stock_report_' . $from . '_' . $to;

if ($type === 'xlsx') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Stock Report');

    $headers = ['รหัสสินค้า', 'สินค้า', 'หน่วย', 'คงเหลือ', 'รับเข้า', 'เบิกออก', 'สต๊อกขั้นต่ำ'];
    foreach ($headers as $index => $header) {
        $column = chr(65 + $index);
        $sheet->setCellValue($column . '1', $header);
    }

    $rowNumber = 2;
    foreach ($rows as $row) {
        $values = [
            $row['code'],
            $row['name'],
            $row['unit'],
            (int)$row['current_stock'],
            (int)$row['qty_in'],
            (int)$row['qty_out'],
            (int)$row['min_stock'],
        ];
        foreach ($values as $index => $value) {
            $column = chr(65 + $index);
            $sheet->setCellValue($column . $rowNumber, $value);
        }
        $rowNumber++;
    }

    $sheet->getStyle('A1:G1')->getFont()->setBold(true);
    $sheet->freezePane('A2');
    foreach (range('A', 'G') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filenameBase . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

$fontRegular = '/var/www/fonts/NotoSansThai-Regular.ttf';
$fontBold = '/var/www/fonts/NotoSansThai-Bold.ttf';
$fontFace = '';
if (is_file($fontRegular)) {
    $fontFace .= '@font-face{font-family:NotoThai;src:url("file://' . $fontRegular . '") format("truetype");font-weight:400;font-style:normal;}';
}
if (is_file($fontBold)) {
    $fontFace .= '@font-face{font-family:NotoThai;src:url("file://' . $fontBold . '") format("truetype");font-weight:700;font-style:normal;}';
}

$html = '<!doctype html><html><head><meta charset="UTF-8"><style>'
    . $fontFace
    . 'body{font-family:NotoThai,"Noto Sans Thai",DejaVu Sans,sans-serif;font-size:10px;color:#222}'
    . 'h2{text-align:center;margin:0 0 8px}'
    . 'p{margin:4px 0 10px}'
    . 'table{width:100%;border-collapse:collapse}'
    . 'th,td{border:1px solid #888;padding:5px}'
    . 'th{background:#eee}'
    . 'td.num{text-align:right}'
    . '</style></head><body>';
$html .= '<h2>รายงานสรุปสต๊อกสินค้า</h2>';
$html .= '<p>ช่วงวันที่ ' . h($from) . ' ถึง ' . h($to) . '</p>';
$html .= '<table><thead><tr><th>รหัส</th><th>สินค้า</th><th>หน่วย</th><th>คงเหลือ</th><th>เข้า</th><th>ออก</th><th>ขั้นต่ำ</th></tr></thead><tbody>';

foreach ($rows as $row) {
    $html .= '<tr>'
        . '<td>' . h($row['code']) . '</td>'
        . '<td>' . h($row['name']) . '</td>'
        . '<td>' . h($row['unit']) . '</td>'
        . '<td class="num">' . number_format((int)$row['current_stock']) . '</td>'
        . '<td class="num">' . number_format((int)$row['qty_in']) . '</td>'
        . '<td class="num">' . number_format((int)$row['qty_out']) . '</td>'
        . '<td class="num">' . number_format((int)$row['min_stock']) . '</td>'
        . '</tr>';
}

$html .= '</tbody></table></body></html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->setChroot('/var/www');
$options->set('defaultFont', 'NotoThai');

$pdf = new Dompdf($options);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('A4', 'landscape');
$pdf->render();
$pdf->stream($filenameBase . '.pdf', ['Attachment' => true]);
exit;
