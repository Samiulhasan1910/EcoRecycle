<?php

// Minimal DB connection file
$conn = mysqli_connect("localhost", "root", "", "project");

if (!$conn)
{
    die("Database Connection Failed");
}

mysqli_set_charset($conn, "utf8mb4");

?>
