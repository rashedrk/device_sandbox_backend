<?php
// Database setup script - Run this once to create the devices table

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    echo "Connected to database successfully!\n\n";

    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/database/setup.sql');

    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $conn->exec($statement);
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }

    echo "\n✓ Database setup completed successfully!\n";
    echo "\nSample devices have been inserted. Try accessing:\n";
    echo "GET /devices\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
