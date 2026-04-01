<?php
// Grey Wolf 3PL & Logistics Inc. - Quote Form Handler (Static site + PHP mail)
// Sends email to info@greywolf3pl.com and stores a copy on server for backup.

require_once __DIR__ . "/app-config.php";
require_once __DIR__ . "/app-mail.php";
require_once __DIR__ . "/app-db.php";

// ====== CONFIG ======
$TO_EMAIL = gw_config_to_email();
$SUBJECT_PREFIX = "Quote Request (Website)";

// Where to store submissions (server-side backup)
$LOG_DIR = gw_config_storage_dir();
$LOG_FILE = gw_config_storage_path("quotes.csv");

// ====== HELPERS ======
function clean($v) {
  $v = trim((string)$v);
  $v = str_replace(["\r", "\n"], " ", $v); // prevent header injection
  return $v;
}
function val($k) {
  return isset($_POST[$k]) ? clean($_POST[$k]) : "";
}
function fail($msg) {
  // Simple fallback response (keeps it clear for users)
  http_response_code(400);
  echo "<h2>Form submission error</h2><p>" . htmlspecialchars($msg) . "</p>";
  echo "<p><a href=\"index.html\">Go back</a></p>";
  exit;
}

// Only accept POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  fail("Invalid request method.");
}

// Honeypot: bots often fill hidden fields
$honeypot = val("website");
if ($honeypot !== "") {
  // Pretend success to bots
  header("Location: thank-you.html");
  exit;
}

// Timing check: very fast submissions are often bots
$started = val("form_started_at");
if ($started) {
  $t0 = strtotime($started);
  if ($t0 && (time() - $t0) < 3) {
    // Too fast
    header("Location: thank-you.html");
    exit;
  }
}

// Required fields
$company = val("company");
$name    = val("name");
$email   = val("email");
$service = val("service");
$product = val("product");

if ($company === "" || $name === "" || $email === "" || $service === "" || $product === "") {
  fail("Please fill in all required fields (Company, Name, Email, Service, Product Description).");
}

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  fail("Please enter a valid email address.");
}

// Optional fields
$phone       = val("phone");
$startDate   = val("startDate");
$pallets     = val("pallets");
$palletSize  = val("palletSize");
$inboundFreq = val("inboundFreq");
$outboundFreq= val("outboundFreq");
$pickPack    = val("pickPack");
$special     = val("special");
$notes       = isset($_POST["notes"]) ? trim((string)$_POST["notes"]) : "";

// Build subject/body
$subject = $SUBJECT_PREFIX . " - " . $company;

$lines = [];
$lines[] = "QUOTE REQUEST (Website Form)";
$lines[] = "----------------------------------------";
$lines[] = "Company: " . $company;
$lines[] = "Name: " . $name;
$lines[] = "Email: " . $email;
$lines[] = "Phone: " . ($phone ? $phone : "N/A");
$lines[] = "";
$lines[] = "Service Needed: " . $service;
$lines[] = "Start Date: " . ($startDate ? $startDate : "N/A");
$lines[] = "Product Type/Description: " . $product;
$lines[] = "# of Pallets (approx.): " . ($pallets ? $pallets : "N/A");
$lines[] = "Pallet Size/Weight: " . ($palletSize ? $palletSize : "N/A");
$lines[] = "Inbound Frequency: " . ($inboundFreq ? $inboundFreq : "N/A");
$lines[] = "Outbound Frequency: " . ($outboundFreq ? $outboundFreq : "N/A");
$lines[] = "Pick & Pack: " . ($pickPack ? $pickPack : "N/A");
$lines[] = "Special Requirements: " . ($special ? $special : "N/A");
$lines[] = "";
$lines[] = "Additional Notes:";
$lines[] = ($notes !== "" ? $notes : "N/A");
$lines[] = "";
$lines[] = "Submitted from: " . gw_config_site_href("/");
$lines[] = "Timestamp (server): " . date("c");

$body = implode("\n", $lines);

// ====== SERVER-SIDE BACKUP LOG ======
$saved = gw_db_insert_quote(array(
  "company" => $company,
  "name" => $name,
  "email" => $email,
  "phone" => $phone,
  "service" => $service,
  "start_date" => $startDate,
  "product" => str_replace(["\r", "\n"], " ", $product),
  "pallets" => $pallets,
  "pallet_size" => str_replace(["\r", "\n"], " ", $palletSize),
  "inbound_frequency" => str_replace(["\r", "\n"], " ", $inboundFreq),
  "outbound_frequency" => str_replace(["\r", "\n"], " ", $outboundFreq),
  "pick_pack" => str_replace(["\r", "\n"], " ", $pickPack),
  "special_requirements" => str_replace(["\r", "\n"], " ", $special),
  "notes" => $notes,
  "source_url" => gw_config_site_href("/")
));

if (!$saved) {
  if (!is_dir($LOG_DIR)) {
    @mkdir($LOG_DIR, 0755, true);
  }
  if (is_dir($LOG_DIR)) {
    // Ensure CSV header exists
    if (!file_exists($LOG_FILE)) {
      $hdr = "timestamp,company,name,email,phone,service,startDate,product,pallets,palletSize,inboundFreq,outboundFreq,pickPack,special,notes\n";
      @file_put_contents($LOG_FILE, $hdr, FILE_APPEND);
    }
    $row = [
      date("c"),
      $company,
      $name,
      $email,
      $phone,
      $service,
      $startDate,
      str_replace(["\r","\n"], " ", $product),
      $pallets,
      str_replace(["\r","\n"], " ", $palletSize),
      str_replace(["\r","\n"], " ", $inboundFreq),
      str_replace(["\r","\n"], " ", $outboundFreq),
      str_replace(["\r","\n"], " ", $pickPack),
      str_replace(["\r","\n"], " ", $special),
      '"' . str_replace('"', '""', $notes) . '"'
    ];
    $csv = implode(",", array_map(function($x){
      // Wrap fields that contain commas
      $x = (string)$x;
      if (strpos($x, ",") !== false && $x[0] !== '"') {
        return '"' . str_replace('"', '""', $x) . '"';
      }
      return $x;
    }, $row)) . "\n";
    @file_put_contents($LOG_FILE, $csv, FILE_APPEND);
  }
}

// ====== SEND EMAIL ======
$sent = gw_mail_send($TO_EMAIL, $subject, $body, $email, $name, array(
  "from_name" => "Grey Wolf 3PL"
));

// Redirect to thank-you either way (prevents user resubmits)
if ($sent) {
  header("Location: thank-you.html");
  exit;
} else {
  // If mail() fails (some hosts require SMTP), still show a friendly message with next step.
  http_response_code(200);
  echo "<h2>Thanks — we received your request.</h2>";
  echo "<p>We couldn't auto-email right now (server mail disabled). Your submission was saved and we'll still follow up.</p>";
  echo "<p>If you need to reach us immediately: <a href=\"mailto:" . htmlspecialchars($TO_EMAIL) . "\">" . htmlspecialchars($TO_EMAIL) . "</a></p>";
  echo "<p><a href=\"index.html\">Back to Home</a></p>";
  exit;
}
?>

