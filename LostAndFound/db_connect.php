<?php
// db_connect.php

//connect to server
$connect = mysqli_connect("localhost", "root", "", "lostandfound");

if(!$connect)
{
    die('ERROR:' .mysqli_connect_error());
}

if (!isset($_SESSION['is_admin'])) {
    $_SESSION['is_admin'] = false;
}

function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>