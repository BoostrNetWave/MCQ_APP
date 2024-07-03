<?php
// Include the PHPExcel library
require '../PHPExcel-v7.4/PHPExcel.php';
require '../../conn.php';

// Create a new Spreadsheet object
$spreadsheet = new PHPExcel();
$spreadsheet->getProperties()
    ->setCreator('learners')
    ->setTitle('questions')
    ->setLastModifiedBy('Abhisek')
    ->setDescription('questionUpload')
    ->setSubject('QuestionBank')
    ->setKeywords('phpexcel implementation')
    ->setCategory('importing');

$ews = $spreadsheet->getSheet(0);
$ews->setTitle('Questions');

$ews->setCellValue('a1', 'Question');
$ews->setCellValue('b1', 'Option 1');
$ews->setCellValue('c1', 'Option 2');
$ews->setCellValue('d1', 'Option 3');
$ews->setCellValue('e1', 'Option 4');
$ews->setCellValue('f1', 'Correct Answer');
// $ews->setCellValue('g1', 'Explanation');


$header = 'a1:f1';
$ews->getStyle($header)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FF00FF00');
$style = array(
    'font' => array('bold' => true, ),
    'alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER, ),
);
$ews->getStyle($header)->applyFromArray($style);

// Set column widths
for ($col = ord('a'); $col <= ord('f'); $col++) {
    $ews->getColumnDimension(chr($col))->setAutoSize(true);
}

// Set headers to prompt file download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="QuestionTemplate.xlsx"');
header('Cache-Control: max-age=0');

$writer = PHPExcel_IOFactory::createWriter($spreadsheet, 'Excel2007');
ob_end_clean();
$writer->setIncludeCharts(true);
$writer->save('php://output');
exit;
?>
