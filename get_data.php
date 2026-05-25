<?php
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

$date = $_GET['date'] ?? '';

if ($date === '') {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Missing date"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "
SELECT
    urine_records.soldier_id,
    soldiers.group_id,
    urine_records.record_date,
    urine_records.time_period AS record_time,
    urine_records.urine_level
FROM urine_records
JOIN soldiers ON soldiers.id = urine_records.soldier_id
WHERE urine_records.record_date = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $date);
$stmt->execute();

$result = $stmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {
    $row['soldier_id'] = (string) $row['soldier_id'];
    $row['urine_level'] = (int) $row['urine_level'];
    $data[] = $row;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>
