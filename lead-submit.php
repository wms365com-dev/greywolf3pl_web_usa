<?php
// Grey Wolf 3PL & Logistics Inc. - Generic lead capture handler

require_once __DIR__ . "/app-config.php";
require_once __DIR__ . "/app-mail.php";
require_once __DIR__ . "/app-db.php";

$TO_EMAIL = gw_config_to_email();
$SUBJECT_PREFIX = "Website Lead";

$LOG_DIR = gw_config_storage_dir();
$LOG_FILE = gw_config_storage_path("leads.csv");

function clean($v) {
  $v = trim((string)$v);
  return str_replace(["\r", "\n"], " ", $v);
}

function val($k) {
  return isset($_POST[$k]) ? clean($_POST[$k]) : "";
}

function back_href() {
  $sourcePage = val("source_page");
  if ($sourcePage !== "") {
    return gw_config_site_href(ltrim($sourcePage, "/"));
  }

  return gw_config_site_href("index.html");
}

function fail($msg) {
  http_response_code(400);
  echo "<h2>Form submission error</h2><p>" . htmlspecialchars($msg) . "</p>";
  echo "<p><a href=\"" . htmlspecialchars(back_href()) . "\">Go back</a></p>";
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
  if ($t0 && (time() - $t0) < 2) {
    header("Location: " . gw_config_site_href("thank-you.html"));
    exit;
  }
}

$formType = val("form_type");
$sourcePage = val("source_page");

$company = val("company");
if ($company === "") {
  $company = val("company_name");
}

$name = val("name");
if ($name === "") {
  $name = val("full_name");
}

$email = val("email");
$phone = val("phone");

if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  fail("Please enter a valid email address.");
}

if ($name === "") {
  $name = "Website Visitor";
}

$service = val("service");
$websiteField = val("company_website");
$ordersPerMonth = val("orders_per_month");
$numberOfSkus = val("number_of_skus");
$palletsStorage = val("pallets_storage");
$businessInfo = isset($_POST["business_info"]) ? trim((string)$_POST["business_info"]) : "";
$notes = isset($_POST["notes"]) ? trim((string)$_POST["notes"]) : "";
$leadMagnet = val("lead_magnet");

$subjectLabel = $formType !== "" ? $formType : "General Lead";
$subject = $SUBJECT_PREFIX . " - " . $subjectLabel . ($company !== "" ? " - " . $company : "");

$lines = [];
$lines[] = "WEBSITE LEAD";
$lines[] = "----------------------------------------";
$lines[] = "Form Type: " . ($formType !== "" ? $formType : "general_lead");
$lines[] = "Source Page: " . ($sourcePage !== "" ? $sourcePage : "N/A");
$lines[] = "Company: " . ($company !== "" ? $company : "N/A");
$lines[] = "Name: " . $name;
$lines[] = "Email: " . $email;
$lines[] = "Phone: " . ($phone !== "" ? $phone : "N/A");
$lines[] = "Primary Service: " . ($service !== "" ? $service : "N/A");
$lines[] = "Company Website: " . ($websiteField !== "" ? $websiteField : "N/A");
$lines[] = "Orders Per Month: " . ($ordersPerMonth !== "" ? $ordersPerMonth : "N/A");
$lines[] = "Number of SKUs: " . ($numberOfSkus !== "" ? $numberOfSkus : "N/A");
$lines[] = "Pallet Storage Estimate: " . ($palletsStorage !== "" ? $palletsStorage : "N/A");
$lines[] = "Lead Magnet: " . ($leadMagnet !== "" ? $leadMagnet : "N/A");
$lines[] = "";
$lines[] = "Business Info:";
$lines[] = $businessInfo !== "" ? $businessInfo : "N/A";
$lines[] = "";
$lines[] = "Additional Notes:";
$lines[] = $notes !== "" ? $notes : "N/A";
$lines[] = "";
$lines[] = "Submitted from: " . gw_config_site_href($sourcePage !== "" ? ltrim($sourcePage, "/") : "/");
$lines[] = "Timestamp (server): " . date("c");

$body = implode("\n", $lines);

$saved = gw_db_insert_lead(array(
  "form_type" => $formType,
  "source_page" => $sourcePage,
  "company" => $company,
  "name" => $name,
  "email" => $email,
  "phone" => $phone,
  "service" => $service,
  "company_website" => $websiteField,
  "orders_per_month" => $ordersPerMonth,
  "number_of_skus" => $numberOfSkus,
  "pallets_storage" => $palletsStorage,
  "lead_magnet" => $leadMagnet,
  "business_info" => $businessInfo,
  "notes" => $notes
));

if (!$saved) {
  if (!is_dir($LOG_DIR)) {
    @mkdir($LOG_DIR, 0755, true);
  }

  if (is_dir($LOG_DIR)) {
    if (!file_exists($LOG_FILE)) {
      $header = "timestamp,form_type,source_page,company,name,email,phone,service,company_website,orders_per_month,number_of_skus,pallets_storage,lead_magnet,business_info,notes\n";
      @file_put_contents($LOG_FILE, $header, FILE_APPEND);
    }

    $row = [
      date("c"),
      $formType,
      $sourcePage,
      $company,
      $name,
      $email,
      $phone,
      $service,
      $websiteField,
      $ordersPerMonth,
      $numberOfSkus,
      $palletsStorage,
      $leadMagnet,
      str_replace(["\r", "\n"], " ", $businessInfo),
      str_replace(["\r", "\n"], " ", $notes)
    ];

    $csv = implode(",", array_map(function ($x) {
      $x = (string)$x;
      if (strpos($x, ",") !== false || strpos($x, "\"") !== false) {
        return '"' . str_replace('"', '""', $x) . '"';
      }
      return $x;
    }, $row)) . "\n";

    @file_put_contents($LOG_FILE, $csv, FILE_APPEND);
  }
}

$sent = gw_mail_send($TO_EMAIL, $subject, $body, $email, $name, array(
  "from_name" => "Grey Wolf 3PL"
));

if ($sent) {
  header("Location: " . gw_config_site_href("thank-you.html"));
  exit;
}

http_response_code(200);
echo "<h2>Thanks — we received your information.</h2>";
echo "<p>Your submission was saved even though server email did not send automatically.</p>";
echo "<p><a href=\"mailto:" . htmlspecialchars($TO_EMAIL) . "\">Email Grey Wolf directly</a></p>";
echo "<p><a href=\"" . htmlspecialchars(back_href()) . "\">Back to page</a></p>";
exit;
?>

