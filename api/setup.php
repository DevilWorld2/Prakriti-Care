<?php
// Database setup script
require_once __DIR__ . '/config/database.php';

try {
    // Connect to MySQL (without specifying database)
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    echo "Database '" . DB_NAME . "' created successfully!\n";

    // Select the database
    $pdo->exec("USE " . DB_NAME);

    // Read and execute the schema file
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');

    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    echo "Database schema created successfully!\n";
    echo "Setup completed. You can now use the PrakritiCare API.\n";
    echo "\nDefault admin credentials:\n";
    echo "Email: admin@prakriti.care\n";
    echo "Password: password\n";
    echo "\nPlease change the default password after first login!\n";

} catch (PDOException $e) {
    echo "Setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>