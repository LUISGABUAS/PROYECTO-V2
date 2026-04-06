<?php
$stmt = $pdo->query('SELECT *
    FROM clientes
');

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
