<?php
require 'config.php';
$db = getDB();
$res = $db->query("SHOW CREATE TABLE transactions");
if ($res) {
    $row = $res->fetch_assoc();
    echo "Transactions Table Schema:\n";
    echo $row['Create Table'] . "\n\n";
} else {
    echo "Failed to get transactions schema: " . $db->error . "\n";
}

$res = $db->query("SHOW CREATE TABLE accounts");
if ($res) {
    $row = $res->fetch_assoc();
    echo "Accounts Table Schema:\n";
    echo $row['Create Table'] . "\n\n";
} else {
    echo "Failed to get accounts schema: " . $db->error . "\n";
}
