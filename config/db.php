<?php

$hostname = "localhost";
$username = "root";
$password = "";
$database = "labs";

$mysql = new mysqli(
    $hostname,
    $username,
    $password,
    $database
);

if (mysqli_connect_errno()) {
    die('Conexiunea a esuat...');
}
