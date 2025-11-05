<?php
// database.php
$serverName = "localhost"; 
$connectionInfo = [
    "Database" => "db-sis",
    "UID" => "sa",
    "PWD" => "Liz12345",
    "TrustServerCertificate" => true,  // evita errores SSL locales
    "Encrypt" => "optional"
];

$conn = sqlsrv_connect($serverName, $connectionInfo);

if ($conn === false) {
    die("❌ Error al conectar con SQL Server: " . print_r(sqlsrv_errors(), true));
} else {
    // echo "✅ Conexión exitosa a SQL Server (db-sis)";
}
?>
