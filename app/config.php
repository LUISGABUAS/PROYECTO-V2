<?php
define('SERVIDOR', 'localhost');
define('USUARIO', 'root');
define('PASSWORD', '');
define('BD', 'ventas');

$servidor = "mysql:dbname=".BD.";host=".SERVIDOR;

try {
    $pdo = new PDO($servidor, USUARIO, PASSWORD,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
   //echo "Conexión establecida";
} catch (PDOException $e) {
    //print "Error de conexión: " . $e->getMessage();
    echo "Error de conexión: " ;
}

$URL = "http://localhost/PROYECTO";

date_default_timezone_set("America/Mexico_City");
$fechaHora = date("Y-m-d H:i:s");

