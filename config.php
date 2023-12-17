<?php
$serverName = "tcp:projetcloudfinal.database.windows.net,1433";
$connectionOptions = array(
    "Database" => "projetcloudfinal",
    "Uid" => "azure",
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
