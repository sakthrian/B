<?php
// unfreeze_test.php

require_once '../config.php'; // adjust the path to your DB connection

$testId = $_GET['test_id'] ?? null;
$testType = $_GET['test_type'] ?? null;

if ($testId && $testType) {

    $table = $testType === "test" ? "test" : "assignment";

    $stmt = $conn->prepare("UPDATE $table SET status = NULL WHERE id = ?");
    $stmt->bind_param("i", $testId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid parameters"]);
}
?>
