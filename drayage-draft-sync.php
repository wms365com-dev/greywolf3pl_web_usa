<?php
// Grey Wolf 3PL & Logistics Inc. - Drayage draft lead relay
// Accepts draft/save events from drayage.html, stores a local backup row,
// and relays the payload to the Google Apps Script web app.

require_once __DIR__ . "/app-config.php";
require_once __DIR__ . "/app-http.php";
require_once __DIR__ . "/app-db.php";

gw_handle_preflight("POST, OPTIONS", "Content-Type, Accept, X-Requested-With");
gw_send_cors_headers("POST, OPTIONS", "Content-Type, Accept, X-Requested-With");

header("Content-Type: application/json; charset=UTF-8");

$GOOGLE_WEBHOOK_URL = gw_config_google_sheet_webhook_url();
$LOG_DIR = gw_config_storage_dir();
$LOG_FILE = gw_config_storage_path("drayage_draft_events.csv");

function json_response($data, $status = 200) {
  http_response_code($status);
  echo json_encode($data);
  exit;
}

function clean_value($value) {
  return trim(str_replace(["\r", "\n"], " ", (string)$value));
}

function load_payload() {
  $raw = file_get_contents("php://input");
  if ($raw !== false && trim($raw) !== "") {
    $json = json_decode($raw, true);
    if (is_array($json)) {
      return $json;
    }
  }

  return $_POST;
}

function ensure_log_dir($dir) {
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
  }
}

function log_event($file, $payload, $googleOk, $googleMessage) {
  if (!file_exists($file)) {
    $header = "timestamp,tracking_id,status,step_reached,contact_name,phone,email,reference_number,service_date,google_sync_ok,google_message\n";
    @file_put_contents($file, $header, FILE_APPEND);
  }

  $row = [
    date("c"),
    clean_value($payload["tracking_id"] ?? ""),
    clean_value($payload["status"] ?? ""),
    clean_value($payload["step_reached"] ?? ""),
    clean_value($payload["contact_name"] ?? ""),
    clean_value($payload["phone"] ?? ""),
    clean_value($payload["email"] ?? ""),
    clean_value($payload["reference_number"] ?? ""),
    clean_value($payload["service_date"] ?? ""),
    $googleOk ? "yes" : "no",
    clean_value($googleMessage)
  ];

  $csv = implode(",", array_map(function ($value) {
    $value = (string)$value;
    if (strpos($value, ",") !== false || strpos($value, "\"") !== false) {
      return '"' . str_replace('"', '""', $value) . '"';
    }
    return $value;
  }, $row)) . "\n";

  @file_put_contents($file, $csv, FILE_APPEND);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  json_response(["ok" => false, "error" => "Invalid request method."], 405);
}

$payload = load_payload();
if (!is_array($payload)) {
  json_response(["ok" => false, "error" => "Invalid payload."], 400);
}

$trackingId = clean_value($payload["tracking_id"] ?? "");
$status = clean_value($payload["status"] ?? "");

if ($trackingId === "" || $status === "") {
  json_response(["ok" => false, "error" => "Missing tracking ID or status."], 400);
}

ensure_log_dir($LOG_DIR);
$syncResult = $GOOGLE_WEBHOOK_URL !== ""
  ? gw_http_post_json($GOOGLE_WEBHOOK_URL, $payload, 10)
  : array("ok" => false, "status" => 0, "body" => "", "message" => "Google webhook URL is not configured.");
$googleOk = $syncResult["ok"];
$googleMessage = $googleOk ? "Google Sheet sync completed." : $syncResult["message"];
$googleResponseBody = $syncResult["body"];

$saved = gw_db_insert_drayage_draft_event(array(
  "tracking_id" => clean_value($payload["tracking_id"] ?? ""),
  "status" => clean_value($payload["status"] ?? ""),
  "step_reached" => isset($payload["step_reached"]) && $payload["step_reached"] !== "" ? (int)$payload["step_reached"] : null,
  "contact_name" => clean_value($payload["contact_name"] ?? ""),
  "phone" => clean_value($payload["phone"] ?? ""),
  "email" => clean_value($payload["email"] ?? ""),
  "reference_number" => clean_value($payload["reference_number"] ?? ""),
  "service_date" => clean_value($payload["service_date"] ?? ""),
  "google_sync_ok" => $googleOk,
  "google_message" => $googleMessage,
  "payload_json" => json_encode($payload)
));

if (!$saved && is_dir($LOG_DIR)) {
  log_event($LOG_FILE, $payload, $googleOk, $googleMessage);
}

$response = [
  "ok" => $googleOk,
  "tracking_id" => $trackingId,
  "status" => $status,
  "message" => $googleMessage
];

if (!$googleOk && $googleResponseBody !== "") {
  $response["google_response"] = $googleResponseBody;
}

json_response($response, $googleOk ? 200 : 502);
?>
