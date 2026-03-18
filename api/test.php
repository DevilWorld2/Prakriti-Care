<?php
// Test script to verify API setup
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';

try {
    $db = Database::getInstance();
    $result = $db->selectOne("SELECT COUNT(*) as count FROM users");

    echo "Database connection successful!\n";
    echo "Users in database: " . $result['count'] . "\n";
    echo "API is ready to use.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>