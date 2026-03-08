<?php
$host = "localhost";
$user = "root";     
$pass = "lily1245";            
$db   = "localflair_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
