<?php

require_once __DIR__ . "/app-config.php";
require_once __DIR__ . "/app-db.php";

header("Content-Type: application/json; charset=UTF-8");

$databaseConfigured = gw_db_is_configured();
$databaseReady = false;
if ($databaseConfigured) {
  $databaseReady = gw_db_connection() instanceof PDO;
}

echo json_encode(array(
  "ok" => !$databaseConfigured || $databaseReady,
  "service" => "greywolf3pl",
  "database_configured" => $databaseConfigured,
  "database_ready" => $databaseReady,
  "storage_ready" => is_dir(gw_config_storage_dir()) || @mkdir(gw_config_storage_dir(), 0755, true),
  "time" => date("c")
));
