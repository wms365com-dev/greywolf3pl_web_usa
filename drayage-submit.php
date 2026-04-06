<?php
// Grey Wolf 3PL & Logistics Inc. - Drayage request handler

require_once __DIR__ . "/app-config.php";
require_once __DIR__ . "/app-mail.php";
require_once __DIR__ . "/app-http.php";
require_once __DIR__ . "/app-db.php";

gw_handle_preflight("POST, OPTIONS", "Content-Type, Accept, X-Requested-With");
gw_send_cors_headers("POST, OPTIONS", "Content-Type, Accept, X-Requested-With");

$TO_EMAIL = gw_config_to_email();
$SUBJECT_PREFIX = "Drayage Request";
$GOOGLE_SHEET_WEBHOOK_URL = gw_config_google_sheet_webhook_url();

$LOG_DIR = gw_config_storage_dir();
$LOG_FILE = gw_config_storage_path("drayage.csv");

function clean($value) {
  $value = trim((string)$value);
  return str_replace(["\r", "\n"], " ", $value);
}

function val($key) {
  return isset($_POST[$key]) ? clean($_POST[$key]) : "";
}

function is_ajax_request() {
  $requestedWith = strtolower(trim((string)($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "")));
  $accept = strtolower((string)($_SERVER["HTTP_ACCEPT"] ?? ""));
  return $requestedWith === "xmlhttprequest" || strpos($accept, "application/json") !== false;
}

function json_response($payload, $statusCode = 200) {
  http_response_code($statusCode);
  header("X-Robots-Tag: noindex, nofollow", true);
  header("Content-Type: application/json; charset=UTF-8");
  echo json_encode($payload);
  exit;
}

function fail($message) {
  if (is_ajax_request()) {
    json_response([
      "ok" => false,
      "message" => $message
    ], 400);
  }

  http_response_code(400);
  echo "<h2>Form submission error</h2><p>" . htmlspecialchars($message) . "</p>";
  echo "<p><a href=\"" . htmlspecialchars(gw_config_site_href("drayage.html")) . "\">Go back</a></p>";
  exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  fail("Invalid request method.");
}

$honeypot = val("website");
if ($honeypot !== "") {
  header("Location: " . gw_config_site_href("thank-you.html"));
  exit;
}

$started = val("form_started_at");
if ($started) {
  $t0 = strtotime($started);
  if ($t0 && (time() - $t0) < 3) {
    header("Location: " . gw_config_site_href("thank-you.html"));
    exit;
  }
}

$companyName = val("company_name");
$contactName = val("contact_name");
$email = val("email");
$phone = val("phone");
$draftTrackingId = val("draft_tracking_id");
$loadType = val("load_type");
$referenceNumber = val("reference_number");
$secondaryReference = val("secondary_reference");
$shipFromAddress1 = val("ship_from_address_1");
$shipFromUnit = val("ship_from_unit");
$shipFromCity = val("ship_from_city");
$shipFromProvince = val("ship_from_province");
$shipFromPostalCode = val("ship_from_postal_code");
$shipToAddress1 = val("ship_to_address_1");
$shipToUnit = val("ship_to_unit");
$shipToCity = val("ship_to_city");
$shipToProvince = val("ship_to_province");
$shipToPostalCode = val("ship_to_postal_code");
$serviceDate = val("service_date");
$serviceNeeded = val("service_needed");
$containerSize = val("container_size");
$unitCount = val("unit_count");
$notes = isset($_POST["notes"]) ? trim((string)$_POST["notes"]) : "";

$requiredLabels = [
  "contact_name" => "Contact Name",
  "phone" => "Phone",
  "load_type" => "Load Type",
  "reference_number" => "Reference Number",
  "ship_from_address_1" => "Ship From Address",
  "ship_from_city" => "Ship From City",
  "ship_from_province" => "Ship From Province / State",
  "ship_from_postal_code" => "Ship From Postal / ZIP Code",
  "ship_to_address_1" => "Ship To Address",
  "ship_to_city" => "Ship To City",
  "ship_to_province" => "Ship To Province / State",
  "ship_to_postal_code" => "Ship To Postal / ZIP Code",
  "service_date" => "Service Date",
  "service_needed" => "Service Needed"
];

$fieldValues = [
  "contact_name" => $contactName,
  "phone" => $phone,
  "load_type" => $loadType,
  "reference_number" => $referenceNumber,
  "ship_from_address_1" => $shipFromAddress1,
  "ship_from_city" => $shipFromCity,
  "ship_from_province" => $shipFromProvince,
  "ship_from_postal_code" => $shipFromPostalCode,
  "ship_to_address_1" => $shipToAddress1,
  "ship_to_city" => $shipToCity,
  "ship_to_province" => $shipToProvince,
  "ship_to_postal_code" => $shipToPostalCode,
  "service_date" => $serviceDate,
  "service_needed" => $serviceNeeded
];

$missing = [];
foreach ($requiredLabels as $key => $label) {
  if (!isset($fieldValues[$key]) || $fieldValues[$key] === "") {
    $missing[] = $label;
  }
}

if (!empty($missing)) {
  fail("Please fill in the required fields: " . implode(", ", $missing) . ".");
}

if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  fail("Please enter a valid email address.");
}

$subjectLabel = $companyName !== "" ? $companyName : $contactName;
$subject = $SUBJECT_PREFIX . " - " . $subjectLabel . " - " . $referenceNumber;

$lines = [];
$lines[] = "DRAYAGE REQUEST (Website Form)";
$lines[] = "----------------------------------------";
$lines[] = "Company / Customer: " . ($companyName !== "" ? $companyName : "N/A");
$lines[] = "Contact Name: " . $contactName;
$lines[] = "Email: " . ($email !== "" ? $email : "N/A");
$lines[] = "Phone: " . $phone;
$lines[] = "Draft Tracking ID: " . ($draftTrackingId !== "" ? $draftTrackingId : "N/A");
$lines[] = "";
$lines[] = "Load Type: " . $loadType;
$lines[] = "Reference Number: " . $referenceNumber;
$lines[] = "Secondary Reference: " . ($secondaryReference !== "" ? $secondaryReference : "N/A");
$lines[] = "Ship From Address: " . $shipFromAddress1;
$lines[] = "Ship From Unit / Suite: " . ($shipFromUnit !== "" ? $shipFromUnit : "N/A");
$lines[] = "Ship From City: " . $shipFromCity;
$lines[] = "Ship From Province / State: " . $shipFromProvince;
$lines[] = "Ship From Postal / ZIP Code: " . $shipFromPostalCode;
$lines[] = "Ship To Address: " . $shipToAddress1;
$lines[] = "Ship To Unit / Suite: " . ($shipToUnit !== "" ? $shipToUnit : "N/A");
$lines[] = "Ship To City: " . $shipToCity;
$lines[] = "Ship To Province / State: " . $shipToProvince;
$lines[] = "Ship To Postal / ZIP Code: " . $shipToPostalCode;
$lines[] = "Service Date: " . $serviceDate;
$lines[] = "Service Needed: " . $serviceNeeded;
$lines[] = "Container / Trailer Size: " . ($containerSize !== "" ? $containerSize : "N/A");
$lines[] = "Units / Containers: " . ($unitCount !== "" ? $unitCount : "N/A");
$lines[] = "";
$lines[] = "Additional Notes:";
$lines[] = $notes !== "" ? $notes : "N/A";
$lines[] = "";
$lines[] = "Submitted from: " . gw_config_site_href("drayage.html");
$lines[] = "Timestamp (server): " . date("c");

$body = implode("\n", $lines);

$sheetTrackingId = $draftTrackingId;
if ($sheetTrackingId === "") {
  $trackingSuffix = substr(md5(uniqid((string)mt_rand(), true)), 0, 6);
  $sheetTrackingId = "drayage-submit-" . time() . "-" . $trackingSuffix;
}
$sheetPayload = [
  "tracking_id" => $sheetTrackingId,
  "status" => "submitted",
  "step_reached" => 3,
  "updated_at" => date("c"),
  "source_page" => gw_config_site_href("drayage.html"),
  "page_title" => "Ontario Drayage Services in Greater Toronto Area | Grey Wolf 3PL",
  "company_name" => $companyName,
  "contact_name" => $contactName,
  "phone" => $phone,
  "email" => $email,
  "load_type" => $loadType,
  "reference_number" => $referenceNumber,
  "secondary_reference" => $secondaryReference,
  "service_date" => $serviceDate,
  "service_needed" => $serviceNeeded,
  "container_size" => $containerSize,
  "unit_count" => $unitCount,
  "ship_from_address_1" => $shipFromAddress1,
  "ship_from_unit" => $shipFromUnit,
  "ship_from_city" => $shipFromCity,
  "ship_from_province" => $shipFromProvince,
  "ship_from_postal_code" => $shipFromPostalCode,
  "ship_to_address_1" => $shipToAddress1,
  "ship_to_unit" => $shipToUnit,
  "ship_to_city" => $shipToCity,
  "ship_to_province" => $shipToProvince,
  "ship_to_postal_code" => $shipToPostalCode,
  "notes" => str_replace(["\r", "\n"], " ", $notes)
];

$saved = gw_db_insert_drayage_request(array(
  "tracking_id" => $sheetTrackingId,
  "company_name" => $companyName,
  "contact_name" => $contactName,
  "email" => $email,
  "phone" => $phone,
  "load_type" => $loadType,
  "reference_number" => $referenceNumber,
  "secondary_reference" => $secondaryReference,
  "ship_from_address_1" => $shipFromAddress1,
  "ship_from_unit" => $shipFromUnit,
  "ship_from_city" => $shipFromCity,
  "ship_from_province" => $shipFromProvince,
  "ship_from_postal_code" => $shipFromPostalCode,
  "ship_to_address_1" => $shipToAddress1,
  "ship_to_unit" => $shipToUnit,
  "ship_to_city" => $shipToCity,
  "ship_to_province" => $shipToProvince,
  "ship_to_postal_code" => $shipToPostalCode,
  "service_date" => $serviceDate,
  "service_needed" => $serviceNeeded,
  "container_size" => $containerSize,
  "unit_count" => $unitCount,
  "notes" => $notes,
  "source_page" => gw_config_site_href("drayage.html"),
  "sheet_synced" => false
));

if (!$saved) {
  if (!is_dir($LOG_DIR)) {
    @mkdir($LOG_DIR, 0755, true);
  }

  if (is_dir($LOG_DIR)) {
    if (!file_exists($LOG_FILE)) {
      $header = "timestamp,company_name,contact_name,email,phone,load_type,reference_number,secondary_reference,ship_from_address_1,ship_from_unit,ship_from_city,ship_from_province,ship_from_postal_code,ship_to_address_1,ship_to_unit,ship_to_city,ship_to_province,ship_to_postal_code,service_date,service_needed,container_size,unit_count,notes\n";
      @file_put_contents($LOG_FILE, $header, FILE_APPEND);
    }

    $row = [
      date("c"),
      $companyName,
      $contactName,
      $email,
      $phone,
      $loadType,
      $referenceNumber,
      $secondaryReference,
      $shipFromAddress1,
      $shipFromUnit,
      $shipFromCity,
      $shipFromProvince,
      $shipFromPostalCode,
      $shipToAddress1,
      $shipToUnit,
      $shipToCity,
      $shipToProvince,
      $shipToPostalCode,
      $serviceDate,
      $serviceNeeded,
      $containerSize,
      $unitCount,
      str_replace(["\r", "\n"], " ", $notes)
    ];

    $csv = implode(",", array_map(function ($value) {
      $value = (string)$value;
      if (strpos($value, ",") !== false || strpos($value, "\"") !== false) {
        return '"' . str_replace('"', '""', $value) . '"';
      }
      return $value;
    }, $row)) . "\n";

    @file_put_contents($LOG_FILE, $csv, FILE_APPEND);
  }
}

$sheetSync = $GOOGLE_SHEET_WEBHOOK_URL !== ""
  ? gw_http_post_json($GOOGLE_SHEET_WEBHOOK_URL, $sheetPayload, 10)
  : array("ok" => false, "status" => 0, "body" => "", "message" => "Google Sheet webhook URL is not configured.");
$sheetSynced = $sheetSync["ok"];

if ($saved) {
  gw_db_update_drayage_request_sync_status($sheetTrackingId, $sheetSynced);
}

$sent = gw_mail_send($TO_EMAIL, $subject, $body, $email, $contactName, array(
  "from_name" => "Grey Wolf 3PL"
));

if (is_ajax_request()) {
  json_response([
    "ok" => true,
    "saved" => true,
    "email_sent" => $sent,
    "sheet_synced" => $sheetSynced,
    "message" => $sent
      ? "The drayage request was saved and Grey Wolf received the server email."
      : "The drayage request was saved. The site could not send the server email automatically, so use the generated email draft if needed."
  ]);
}

if ($sent) {
  header("Location: " . gw_config_site_href("thank-you.html"));
  exit;
}

http_response_code(200);
echo "<h2>Thanks - we received your drayage request.</h2>";
echo "<p>Your information was saved even though the server could not send the email automatically.</p>";
if (!$sheetSynced) {
  echo "<p>Grey Wolf may need to review the request from the local server backup because the Google Sheet sync did not complete.</p>";
}
echo "<p><a href=\"mailto:" . htmlspecialchars($TO_EMAIL) . "?subject=Drayage%20Request\">Email Grey Wolf directly</a></p>";
echo "<p><a href=\"" . htmlspecialchars(gw_config_site_href("drayage.html")) . "\">Back to drayage page</a></p>";
exit;
?>
