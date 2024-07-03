<?php
require '../PHPExcel-v7.4/PHPExcel.php';
include("../../conn.php");
extract($_POST);

// Check if action is set
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action == "validate") {
        $response = array();

        // Check if file was uploaded successfully
        if ($_FILES['spreedsheetfile']['error'] === UPLOAD_ERR_OK) {
            $uploadedFileName = $_FILES['spreedsheetfile']['name'];
            $uploadedFileTmp = $_FILES['spreedsheetfile']['tmp_name'];
            $uploadedFileType = $_FILES['spreedsheetfile']['type'];

            if (in_array($uploadedFileType, array('application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'))) {

                require '../PHPExcel-v7.4/PHPExcel/IOFactory.php';

                if ($uploadedFileType === 'text/csv') {
                    $fileData = file_get_contents($uploadedFileTmp);
                    $csvData = str_getcsv($fileData, "\n");
                    $uploadedHeaders = str_getcsv($csvData[0]);

                    $errors = array();
                    $emptyCells = array();

                    // Validate headers against template
                    $templateHeaders = array("Question", "Option 1", "Option 2", "Option 3", "Option 4", "Correct Answer");
                    if (count($uploadedHeaders) !== count($templateHeaders) || array_diff($uploadedHeaders, $templateHeaders)) {
                        $response['errors'] = "Error: Uploaded headers do not match the template.";
                        echo json_encode($response);
                        exit;
                    }

                    foreach ($csvData as $key => $row) {
                        $rowData = str_getcsv($row);
                        foreach ($rowData as $index => $value) {
                            if ($value === "" || is_null($value)) {
                                $errors[] = "Error: Field '{$uploadedHeaders[$index]}' in row " . ($key + 1) . " is empty.";
                                $emptyCell = "Row: " . ($key + 1) . ", Column: " . ($index + 1);
                                if (!in_array($emptyCell, $emptyCells)) {
                                    $emptyCells[] = $emptyCell;
                                }
                            }
                        }
                    }

                    if (!empty($errors)) {
                        $response['errors'] = $errors;
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $objPHPExcel = PHPExcel_IOFactory::load($uploadedFileTmp);
                    $sheet = $objPHPExcel->getActiveSheet();
                    $uploadedHeaders = $sheet->rangeToArray('A1:F1', NULL, TRUE, TRUE, TRUE)[1]; // Read as string

                    $errors = array();
                    $emptyCells = array();

                    // Validate headers against template
                    $templateHeaders = array("Question", "Option 1", "Option 2", "Option 3", "Option 4", "Correct Answer");
                    if (count($uploadedHeaders) !== count($templateHeaders) || array_diff($uploadedHeaders, $templateHeaders)) {
                        $response['errors'] = "Error: Uploaded headers do not match the template.";
                        echo json_encode($response);
                        exit;
                    }

                    foreach ($sheet->rangeToArray('A2:F'.$sheet->getHighestRow(), NULL, TRUE, TRUE, TRUE) as $key => $row) {
                        foreach ($row as $index => $value) {
                            // Cast boolean values to strings explicitly
                            if (is_bool($value)) {
                                $value = $value ? 'TRUE' : 'FALSE';
                            }
                            if ($value === "" || is_null($value)) {
                                $errors[] = "Error: Field '{$uploadedHeaders[$index]}' in row " . ($key + 1) . " is empty.";
                                $emptyCell = "Row: " . ($key + 1) . ", Column: " . ($index + 1);
                                if (!in_array($emptyCell, $emptyCells)) {
                                    $emptyCells[] = $emptyCell;
                                }
                            }
                        }
                    }
                }

                if (!empty($errors)) {
                    $response['errors'] = $errors;
                    echo json_encode($response);
                    exit;
                }

                $tableHTML = '<style>
                                table {
                                    border-collapse: collapse;
                                    width: 100%;
                                }
                                th, td {
                                    border: 1px solid #dddddd;
                                    padding: 8px;
                                }
                                tr:nth-child(even) {
                                    background-color: #f0f0f0;
                                }
                              </style>';
                $tableHTML .= '<table>';
                $tableHTML .= '<thead><tr>';
                foreach ($uploadedHeaders as $header) {
                    $tableHTML .= '<th>' . $header . '</th>';
                }
                $tableHTML .= '</tr></thead><tbody>';
                foreach ($sheet->rangeToArray('A2:F'.$sheet->getHighestRow(), NULL, TRUE, TRUE, TRUE) as $key => $row) {
                    $tableHTML .= '<tr>';
                    foreach ($row as $value) {
                        // Cast boolean values to strings explicitly
                        if (is_bool($value)) {
                            $value = $value ? 'TRUE' : 'FALSE';
                        }
                        $tableHTML .= '<td>' . $value . '</td>';
                    }
                    $tableHTML .= '</tr>';
                }
                $tableHTML .= '</tbody></table><br>';
                $tableHTML .= '<button type="button" class="btn btn-success" id="uploadButton">Upload</button>';

                $response['tableHTML'] = $tableHTML;

                if (!empty($emptyCells)) {
                    $response['emptyCells'] = $emptyCells;
                }
            } else {
                $response['error'] = "Please upload a valid file (CSV, XLS, or XLSX).";
            }
        } else {
            $response['error'] = "Error uploading file.";
        }

        echo json_encode($response);
    } elseif ($action == "excelupload") {
        $response = array();

        if ($_FILES['spreedsheetfile']['error'] === UPLOAD_ERR_OK) {
            $uploadedFileName = $_FILES['spreedsheetfile']['name'];
            $uploadedFileTmp = $_FILES['spreedsheetfile']['tmp_name'];

            $insertCount = 0;
            $objPHPExcel = PHPExcel_IOFactory::load($uploadedFileTmp);
            $sheet = $objPHPExcel->getActiveSheet();
            
            $data = array();
            foreach ($sheet->rangeToArray('A2:F'.$sheet->getHighestRow(), NULL, TRUE, TRUE, TRUE) as $key => $row) {
                foreach ($row as $index => $value) {
                    // Cast boolean values to strings explicitly
                    if (is_bool($value)) {
                        $row[$index] = $value ? 'TRUE' : 'FALSE';
                    }
                }

                if (isset($row['F'])) {
                    $question = $row['A'];
                    $sqlselect = "SELECT * FROM exam_question_tbl WHERE exam_id=:exam_id AND exam_question=:question";
                    $stmt = $conn->prepare($sqlselect);
                    $stmt->execute(array(':exam_id' => $examId, ':question' => $question));
                    $result = $stmt->fetchAll();
                    if (count($result) == 0) {
                        $data[] = array(
                            'exam_id' => $examId,
                            'exam_question' => $question,
                            'exam_ch1' => $row['B'],
                            'exam_ch2' => $row['C'],
                            'exam_ch3' => $row['D'],
                            'exam_ch4' => $row['E'],
                            'exam_answer' => $row['F'],
                        );
                    }
                }
            }

            $sql = "INSERT INTO exam_question_tbl (exam_id, exam_question, exam_ch1, exam_ch2, exam_ch3, exam_ch4, exam_answer ) VALUES (:exam_id, :exam_question, :exam_ch1, :exam_ch2, :exam_ch3, :exam_ch4, :exam_answer)";
            $stmt = $conn->prepare($sql);
            foreach ($data as $row) {
                if ($stmt->execute($row)) {
                    $insertCount++;
                } else {
                    $errorInfo = $stmt->errorInfo();
                    $response['error'] = "Error: " . $sql . "<br>" . $errorInfo[2];
                    echo json_encode($response);
                    exit;
                }
            }

            $response['message'] = "$insertCount records inserted successfully.";
        } else {
            $response['error'] = "Error uploading file: " . $_FILES['spreedsheetfile']['error'];
        }

        echo json_encode($response);
    } else {
        echo json_encode(array("error" => "Invalid action."));
    }
} else {
    echo json_encode(array("error" => "No action specified."));
}
?>
