<?php
header('Content-Type: application/json');

$dbFile = '../admin/visits.db';

try {
    // Connect to SQLite
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS visits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT,
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Get visitor data
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    // Insert new visit record
    $stmt = $pdo->prepare("INSERT INTO visits (ip, user_agent) VALUES (:ip, :ua)");
    $stmt->execute(['ip' => $ip, 'ua' => $ua]);

    // Cleanup old JSON file if needed (optional, keeping it for backward compatibility or just deleting)
    if (file_exists('stats.json')) {
        @unlink('stats.json');
    }

    echo json_encode(['status' => 'success', 'message' => 'Visit recorded']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
