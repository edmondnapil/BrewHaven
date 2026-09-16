<?php
require_once __DIR__ . '/auth_helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $nextIdNumber = generate_next_id_number();
    echo json_encode([
        'success' => true,
        'id_number' => $nextIdNumber
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error generating ID number: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
