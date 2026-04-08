<?php

require_once(dirname(__DIR__, 2) . '/config.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(isset($_SESSION['sesion_email'])){
    session_destroy();
    header("Location: " .$URL. "/login/index.php");
}
