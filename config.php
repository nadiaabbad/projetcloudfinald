<?php
$serverName = "tcp:projetcloudvf.database.windows.net,1433";
$connectionOptions = array(
    "Database" => "projetcloudvf",
    "Uid" => "azureuser",
    "PWD" => "123Klerviaadam",
    "MultipleActiveResultSets" => false,
    "Encrypt" => true,
    "TrustServerCertificate" => false
);

// Établir la connexion à la base de données
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Vérifier la connexion
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}
?>
