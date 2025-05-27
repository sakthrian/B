<?php
include '../../config.php';

$fc_id = $_GET['fc_id'];

$sql = "SELECT * FROM actions WHERE fc_id = ? order by category desc, category_id asc";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$actionsData = [];
while ($row = $result->fetch_assoc()) {
    $actionsData[] = [
        'id' => $row['id'],
        'fc_id' => $row['fc_id'],
        'category' => $row['category'],
        'category_id' => $row['category_id'],
        'action_required' => $row['action_required'],
        'action_required_date' => $row['action_required_date'],
        'action_taken' => $row['action_taken'],
        'action_taken_date' => $row['action_taken_date']
    ];
}
error_log('Actions Data: ' . print_r($actionsData, true));
header('Content-Type: application/json');
echo json_encode($actionsData);
?>