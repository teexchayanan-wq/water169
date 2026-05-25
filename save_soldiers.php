<?php
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data) || !isset($data['groups']) || !is_array($data['groups'])) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid soldier data"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function splitSoldierLabel($label) {
    $label = trim(preg_replace('/\s+/', ' ', (string) $label));
    if ($label === '') return null;

    $parts = explode(' ', $label, 2);
    $code = trim($parts[0]);
    $name = trim($parts[1] ?? '');

    if ($code === '' || $name === '') return null;

    return [
        "soldier_code" => $code,
        "full_name" => $name
    ];
}

$conn->begin_transaction();

try {
    $upsert = $conn->prepare("
        INSERT INTO soldiers (soldier_code, full_name, group_id)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            full_name = VALUES(full_name),
            group_id = VALUES(group_id)
    ");

    $seenCodes = [];
    $saved = 0;

    foreach (['1', '2', '3'] as $groupId) {
        $rows = $data['groups'][$groupId] ?? [];
        if (!is_array($rows)) continue;

        foreach ($rows as $label) {
            $soldier = splitSoldierLabel($label);
            if (!$soldier) continue;

            $code = $soldier['soldier_code'];
            $name = $soldier['full_name'];
            $group = (int) $groupId;

            $upsert->bind_param("ssi", $code, $name, $group);
            $upsert->execute();

            $seenCodes[] = $code;
            $saved++;
        }
    }

    $deleted = 0;
    if (count($seenCodes) > 0) {
        $placeholders = implode(',', array_fill(0, count($seenCodes), '?'));
        $types = str_repeat('s', count($seenCodes));

        $sql = "
            DELETE soldiers
            FROM soldiers
            LEFT JOIN urine_records ON urine_records.soldier_id = soldiers.id
            WHERE urine_records.id IS NULL
              AND soldiers.soldier_code NOT IN ($placeholders)
        ";
        $delete = $conn->prepare($sql);
        $delete->bind_param($types, ...$seenCodes);
        $delete->execute();
        $deleted = $delete->affected_rows;
    }

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "saved" => $saved,
        "deleted" => $deleted
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Cannot save soldiers"
    ], JSON_UNESCAPED_UNICODE);
}
?>
