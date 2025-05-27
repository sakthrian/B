<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {
    $file = $_FILES['csvFile'];
    
  
    $allowedTypes = ['text/csv', 'application/vnd.ms-excel'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a CSV file.']);
        exit;
    }

    // Open and read CSV file
    $handle = fopen($file['tmp_name'], 'r');
    
    // Skip header row
    fgetcsv($handle);
    
    $successCount = 0;
    $errorCount = 0;

    $stmt = $conn->prepare("INSERT INTO student (register_no, name, year, semester, section, batch) VALUES (?, ?, ?, ?, ?, ?)");

    while (($data = fgetcsv($handle)) !== FALSE) {
        // Validate data
        if (count($data) >= 6) {
            $register_no = trim($data[0]);
            $name = trim($data[1]);
            $year = intval(trim($data[2]));
            $semester = intval(trim($data[3]));
            $section = trim($data[4]);
            $batch = trim($data[5]);

            // Validate individual fields
            if (!empty($register_no) && !empty($name) && $year > 0 && $semester > 0 && !empty($section) && !empty($batch)) {
                $stmt->bind_param("ssisss", $register_no, $name, $year, $semester, $section, $batch);
                
                if ($stmt->execute()) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
        } else {
            $errorCount++;
        }
    }

    $stmt->close();
    fclose($handle);

    echo json_encode([
        'success' => true, 
        'message' => "Upload complete. Successful: $successCount, Errors: $errorCount"
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
}
?>