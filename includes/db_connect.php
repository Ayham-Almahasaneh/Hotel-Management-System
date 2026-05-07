<?php
declare(strict_types=1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'ottawa_albus_hotel';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed!");
}

$conn->set_charset("utf8mb4");