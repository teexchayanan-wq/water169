<?php
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

$sql = "SELECT * FROM soldiers ORDER BY group_id, soldier_code";
$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Cannot read soldiers"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = [];

while ($row = $result->fetch_assoc()) {
    if (isset($row['id'])) {
        $row['id'] = (string) $row['id'];
    }
    if (isset($row['soldier_id'])) {
        $row['soldier_id'] = (string) $row['soldier_id'];
    }
    $data[] = $row;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>
