<?php
require_once __DIR__ . '/config/database.php';

// Only run from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

echo "Starting Primary Key Migration...\n";

$db = new Database();
try {
    $pdo = $db->connect();
    echo "Connected to database.\n";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

$changes = [
    'borrowing_status_event_reasons' => 'borrowEventID',
    'borrowing_status_history'       => 'borrowHistoryID',
    'user_status_event_reasons'      => 'userEventID',
    'user_status_history'            => 'userHistoryID'
];

foreach ($changes as $table => $newPkName) {
    echo "\nProcessing table '$table'...\n";

    // 1. Check if table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    if ($stmt->rowCount() == 0) {
        echo "  [SKIP] Table '$table' not found.\n";
        continue;
    }

    // 2. Get current Primary Key
    $stmt = $pdo->query("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");
    $pkInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pkInfo) {
        echo "  [SKIP] No PRIMARY KEY found on '$table'.\n";
        continue;
    }

    $currentPk = $pkInfo['Column_name'];
    echo "  Current PK: '$currentPk'\n";

    if ($currentPk === $newPkName) {
        echo "  [OK] Primary key is already renamed to '$newPkName'.\n";
        continue;
    }

    // 3. Get column definition to preserve type/extra (AUTO_INCREMENT, etc.)
    $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$currentPk'");
    $colInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$colInfo) {
        echo "  [ERROR] Could not fetch column info for '$currentPk'.\n";
        continue;
    }

    $type = $colInfo['Type']; // e.g. int(11)
    $extra = $colInfo['Extra']; // e.g. auto_increment
    
    // Sometimes 'Extra' contains 'DEFAULT_GENERATED' or other things in newer MySQL versions,
    // but for simple auto_increment keys, this should be fine.
    // 'NOT NULL' is implicit for PKs usually, but explicit is good.
    // However, SHOW COLUMNS 'Null' field says 'NO'.
    
    $nullDef = ($colInfo['Null'] === 'YES') ? 'NULL' : 'NOT NULL';
    
    // Construct ALTER statement
    // Syntax: ALTER TABLE tbl CHANGE old_col new_col definition
    $sql = "ALTER TABLE $table CHANGE COLUMN `$currentPk` `$newPkName` $type $nullDef $extra";
    
    echo "  Executing: $sql\n";

    try {
        $pdo->exec($sql);
        echo "  [SUCCESS] Renamed '$currentPk' to '$newPkName'.\n";
    } catch (PDOException $e) {
        echo "  [ERROR] Failed to rename: " . $e->getMessage() . "\n";
    }
}

echo "\nMigration completed.\n";
