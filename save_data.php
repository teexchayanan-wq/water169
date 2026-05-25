<?php
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$soldier_id = $data['soldier_id'] ?? null;
$record_date = $data['record_date'] ?? null;
$record_time = $data['record_time'] ?? null;
$urine_level = $data['urine_level'] ?? null;

if ($soldier_id === null || !$record_date || !$record_time) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($urine_level === null || $urine_level === '') {
    $sql = "
    DELETE FROM urine_records
    WHERE soldier_id = ? AND record_date = ? AND time_period = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $soldier_id, $record_date, $record_time);
    $stmt->execute();

    echo json_encode(["status" => "success"], JSON_UNESCAPED_UNICODE);
    exit;
}

$urine_level = (int) $urine_level;

if ($urine_level < 0 || $urine_level > 4) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid urine level"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "
INSERT INTO urine_records
(soldier_id, record_date, time_period, urine_level)
VALUES (?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
urine_level = VALUES(urine_level)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $soldier_id, $record_date, $record_time, $urine_level);
$stmt->execute();

echo json_encode(["status" => "success"], JSON_UNESCAPED_UNICODE);
?>
