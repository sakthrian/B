<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
include '../../config.php';

$conn->begin_transaction();

try {
    $data = file_get_contents('php://input');
    $requestData = json_decode($data, true);
    error_log(json_encode($requestData));

    $fc_id = $requestData['fc_id'];

    $sql = "Select * from co_attainment where fc_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fc_id);
    $stmt->execute();
    $result = $stmt->get_result(); 

    if($result->fetch_assoc())
    {
        echo json_encode(['success' => false, 'message' => 'Frozen marks cannot be updated.']);
        return;
    }

    $attainmentData = $requestData['attainment_data'];

    if (empty($attainmentData)) {
        throw new Exception('No attainment data received.');
    }

    // Get faculty_course ID
    // $sql = "SELECT id FROM faculty_course WHERE course_id = ? AND faculty_id = ?";
    // $stmt = $conn->prepare($sql);
    // $stmt->bind_param("ss", $courseId, $facultyId);
    // $stmt->execute();
    // $result = $stmt->get_result();

    // if (!$row = $result->fetch_assoc()) {
    //     throw new Exception('Faculty-Course mapping not found.');
    // }

    // $fc_id = $row['id'];

    // Prepare Insert/Update statement
    $insertStmt = $conn->prepare("INSERT INTO co_overall (fc_id, co_id, cia, se, da, ia, ca) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            cia = VALUES(cia), 
            se = VALUES(se), 
            da = VALUES(da), 
            ia = VALUES(ia), 
            ca = VALUES(ca)");

    if (!$insertStmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    // **Step 1: Group CO Data Together**
    $coData = [];
    foreach ($attainmentData as $record) {
        $coNumber = $record['co_number'];
        $category = $record['category'];
        $value = $record['value'];

        // Get co_id
        $coSql = "SELECT co.id 
                    FROM course_outcome co
                    JOIN faculty_course fc
                    ON fc.course_id = co.course_id
                    WHERE fc.id = ? AND co.co_number = ?";
        $coStmt = $conn->prepare($coSql);
        $coStmt->bind_param("ii", $fc_id, $coNumber);
        $coStmt->execute();
        $coResult = $coStmt->get_result();

        if (!$coRow = $coResult->fetch_assoc()) {
            throw new Exception("CO ID not found for CO$coNumber");
        }

        $co_id = $coRow['id'];

        // **Group values for the same co_id**
        if (!isset($coData[$co_id])) {
            $coData[$co_id] = ['cia' => 0, 'se' => 0, 'da' => 0, 'ia' => 0, 'ca' => 0];
        }

        // Assign correct value (Convert category to lowercase to match array keys)
        $categoryKey = strtolower($category); // Convert "CIA" to "cia", "SE" to "se", etc.

        if (isset($coData[$co_id][$categoryKey])) {  
            $coData[$co_id][$categoryKey] = $value;  
        }
    }

    // **Step 2: Insert/Update All CO Data Together**
    foreach ($coData as $co_id => $values) {
        $insertStmt->bind_param("iiddddd", $fc_id, $co_id, $values['cia'], $values['se'], $values['da'], $values['ia'], $values['ca']);
        if (!$insertStmt->execute()) {
            throw new Exception('Error saving attainment data: ' . $insertStmt->error);
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'CO Attainment saved successfully!']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>
