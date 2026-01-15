<?php
$conn = new PDO('mysql:host=127.0.0.1;dbname=chat', 'root', '');
$tables = $conn->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

echo "Tablas en la base de datos 'chat':\n\n";
foreach($tables as $table) {
    echo "✓ " . $table . "\n";
}

echo "\nVerificando chat general (id=1):\n";
$result = $conn->query("SELECT * FROM chat WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if ($result) {
    echo "✓ Chat general encontrado:\n";
    echo "  - ID: " . $result['id'] . "\n";
    echo "  - Type: " . $result['type'] . "\n";
    echo "  - Active: " . ($result['is_active'] ? 'Sí' : 'No') . "\n";
} else {
    echo "✗ Chat general NO encontrado\n";
}
