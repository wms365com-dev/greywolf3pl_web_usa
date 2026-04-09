<?php
require_once __DIR__ . "/app-config.php";
require_once __DIR__ . "/app-mail.php";

$TO_EMAIL = gw_config_to_email();
$FORM_PAGE = gw_config_site_href("GWNewCustomer.html");

function gw_new_customer_clean($value) {
  $value = trim((string)$value);
  return str_replace(array("\r", "\n"), " ", $value);
}

function gw_new_customer_textarea($value) {
  $value = trim((string)$value);
  $value = str_replace("\r\n", "\n", $value);
  return str_replace("\r", "\n", $value);
}

function gw_new_customer_val($key) {
  return isset($_POST[$key]) ? gw_new_customer_clean($_POST[$key]) : "";
}

function gw_new_customer_fail($message) {
  global $FORM_PAGE;

  http_response_code(400);
  echo "<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"><meta name=\"robots\" content=\"noindex, nofollow\"><title>New Customer Setup Error</title></head><body style=\"font-family:Arial,sans-serif;padding:2rem;background:#0b1622;color:#f7fafc;\">";
  echo "<h1 style=\"margin-top:0;\">Submission error</h1>";
  echo "<p>" . htmlspecialchars($message, ENT_QUOTES, "UTF-8") . "</p>";
  echo "<p><a href=\"" . htmlspecialchars($FORM_PAGE, ENT_QUOTES, "UTF-8") . "\" style=\"color:#f0a14a;\">Back to new customer setup</a></p>";
  echo "</body></html>";
  exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  gw_new_customer_fail("This form only accepts direct submissions.");
}

$honeypot = gw_new_customer_val("website");
if ($honeypot !== "") {
  header("Location: " . $FORM_PAGE . "?submitted=1");
  exit;
}

$started = gw_new_customer_val("form_started_at");
if ($started !== "") {
  $timestamp = strtotime($started);
  if ($timestamp && (time() - $timestamp) < 2) {
    header("Location: " . $FORM_PAGE . "?submitted=1");
    exit;
  }
}

$contactName = gw_new_customer_val("contact_name");
$companyName = gw_new_customer_val("company_name");
$email = gw_new_customer_val("email");
$phone = gw_new_customer_val("phone");
$websiteUrl = gw_new_customer_val("website_url");
$notes = isset($_POST["notes"]) ? gw_new_customer_textarea($_POST["notes"]) : "";

$billingAddress1 = gw_new_customer_val("billing_address_1");
$billingAddress2 = gw_new_customer_val("billing_address_2");
$billingCity = gw_new_customer_val("billing_city");
$billingCountry = gw_new_customer_val("billing_country");
$billingRegion = gw_new_customer_val("billing_region");
$billingPostal = gw_new_customer_val("billing_postal");

$shippingSameAsBilling = gw_new_customer_val("same_as_billing") !== "";
$shippingAddress1 = gw_new_customer_val("shipping_address_1");
$shippingAddress2 = gw_new_customer_val("shipping_address_2");
$shippingCity = gw_new_customer_val("shipping_city");
$shippingCountry = gw_new_customer_val("shipping_country");
$shippingRegion = gw_new_customer_val("shipping_region");
$shippingPostal = gw_new_customer_val("shipping_postal");

$missing = array();
if ($contactName === "") {
  $missing[] = "Contact name";
}
if ($companyName === "") {
  $missing[] = "Company name";
}
if ($email === "") {
  $missing[] = "Email";
}

if (!empty($missing)) {
  gw_new_customer_fail("Please complete the required fields: " . implode(", ", $missing) . ".");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  gw_new_customer_fail("Please enter a valid email address.");
}

$billingAddress = implode(", ", array_filter(array(
  $billingAddress1,
  $billingAddress2,
  $billingCity,
  $billingRegion,
  $billingPostal,
  $billingCountry
)));

$shippingAddress = implode(", ", array_filter(array(
  $shippingAddress1,
  $shippingAddress2,
  $shippingCity,
  $shippingRegion,
  $shippingPostal,
  $shippingCountry
)));

if ($shippingSameAsBilling && $shippingAddress === "") {
  $shippingAddress = $billingAddress;
}

$subject = "New Customer Setup - " . $companyName;

$lines = array();
$lines[] = "NEW CUSTOMER SETUP";
$lines[] = "----------------------------------------";
$lines[] = "Contact Name: " . $contactName;
$lines[] = "Company Name: " . $companyName;
$lines[] = "Email: " . $email;
$lines[] = "Phone: " . ($phone !== "" ? $phone : "N/A");
$lines[] = "Website: " . ($websiteUrl !== "" ? $websiteUrl : "N/A");
$lines[] = "";
$lines[] = "Billing Address:";
$lines[] = ($billingAddress !== "" ? $billingAddress : "N/A");
$lines[] = "";
$lines[] = "Shipping Same As Billing: " . ($shippingSameAsBilling ? "Yes" : "No");
$lines[] = "Shipping Address:";
$lines[] = ($shippingAddress !== "" ? $shippingAddress : "N/A");
$lines[] = "";
$lines[] = "Notes:";
$lines[] = ($notes !== "" ? $notes : "N/A");
$lines[] = "";
$lines[] = "Submitted from: " . $FORM_PAGE;
$lines[] = "Timestamp (server): " . date("c");

$body = implode("\n", $lines);

$sent = gw_mail_send($TO_EMAIL, $subject, $body, $email, $contactName, array(
  "from_name" => "Grey Wolf 3PL"
));

if ($sent) {
  header("Location: " . $FORM_PAGE . "?submitted=1");
  exit;
}

header("Location: " . $FORM_PAGE . "?error=1");
exit;
?>
