<?php
// Shared delivery appointment helpers for Grey Wolf 3PL.

require_once __DIR__ . "/app-config.php";
require_once __DIR__ . "/app-mail.php";
require_once __DIR__ . "/app-db.php";

date_default_timezone_set("America/Toronto");

$GW_APPOINTMENT_TO_EMAIL = gw_config_to_email();
$GW_APPOINTMENT_FROM_DOMAIN = gw_config_from_domain();
$GW_APPOINTMENT_LOG_DIR = gw_config_storage_dir();
$GW_APPOINTMENT_LOG_FILE = gw_config_storage_path("appointments.csv");
$GW_APPOINTMENT_LOCK_FILE = gw_config_storage_path("appointments.lock");
$GW_APPOINTMENT_DOCK_COUNT = 3;
$GW_APPOINTMENT_STANDARD_START = 8 * 60 + 30; // 8:30 AM
$GW_APPOINTMENT_STANDARD_END = 16 * 60; // 4:00 PM
$GW_APPOINTMENT_SLOT_MINUTES = 30;

function gw_app_clean($value) {
  $value = trim((string)$value);
  return str_replace(array("\r", "\n"), " ", $value);
}

function gw_app_textarea($value) {
  $value = trim((string)$value);
  $value = str_replace("\r\n", "\n", $value);
  return str_replace("\r", "\n", $value);
}

function gw_app_val($key, $default = "") {
  return isset($_POST[$key]) ? gw_app_clean($_POST[$key]) : $default;
}

function gw_app_is_checked($key) {
  return isset($_POST[$key]) && (string)$_POST[$key] !== "";
}

function gw_app_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function gw_app_allowed_durations() {
  return array(30, 60, 90, 120, 150, 180);
}

function gw_app_status_label($status) {
  $map = array(
    "confirmed" => "Confirmed",
    "pending_after_hours" => "Pending After-Hours Review",
    "declined" => "Declined",
    "cancelled" => "Cancelled"
  );

  return isset($map[$status]) ? $map[$status] : ucfirst(str_replace("_", " ", (string)$status));
}

function gw_app_is_active_status($status) {
  return !in_array((string)$status, array("declined", "cancelled"), true);
}

function gw_app_ensure_storage() {
  global $GW_APPOINTMENT_LOG_DIR, $GW_APPOINTMENT_LOG_FILE;

  if (gw_db_is_configured() && gw_db_connection()) {
    return true;
  }

  if (!is_dir($GW_APPOINTMENT_LOG_DIR)) {
    @mkdir($GW_APPOINTMENT_LOG_DIR, 0755, true);
  }

  if (is_dir($GW_APPOINTMENT_LOG_DIR) && !file_exists($GW_APPOINTMENT_LOG_FILE)) {
    $handle = @fopen($GW_APPOINTMENT_LOG_FILE, "a");
    if ($handle) {
      fputcsv($handle, gw_app_csv_headers());
      fclose($handle);
    }
  }
}

function gw_app_csv_headers() {
  return array(
    "created_at",
    "appointment_id",
    "status",
    "service_window",
    "dock_door",
    "company_name",
    "carrier_name",
    "load_type",
    "reference_number",
    "secondary_reference",
    "equipment_id",
    "contact_name",
    "contact_email",
    "contact_phone",
    "appointment_date",
    "appointment_time",
    "end_date",
    "end_time",
    "duration_minutes",
    "pallet_count",
    "piece_count",
    "unload_type",
    "notes",
    "source_page"
  );
}

function gw_app_open_lock($mode) {
  global $GW_APPOINTMENT_LOCK_FILE;

  if (gw_db_is_configured() && gw_db_connection()) {
    if ($mode === "write") {
      return gw_db_open_appointment_lock();
    }
    return array("type" => "db-read");
  }

  gw_app_ensure_storage();
  $handle = @fopen($GW_APPOINTMENT_LOCK_FILE, "c+");
  if (!$handle) {
    return false;
  }

  $lockType = $mode === "read" ? LOCK_SH : LOCK_EX;
  if (!@flock($handle, $lockType)) {
    fclose($handle);
    return false;
  }

  return $handle;
}

function gw_app_close_lock($handle) {
  if (is_array($handle) && isset($handle["type"]) && strpos((string)$handle["type"], "db") === 0) {
    gw_db_close_appointment_lock($handle);
    return;
  }

  if ($handle) {
    @flock($handle, LOCK_UN);
    @fclose($handle);
  }
}

function gw_app_load_appointments() {
  global $GW_APPOINTMENT_LOG_FILE;

  if (gw_db_is_configured()) {
    $appointments = gw_db_load_appointments();
    if (is_array($appointments)) {
      return $appointments;
    }
  }

  gw_app_ensure_storage();
  if (!file_exists($GW_APPOINTMENT_LOG_FILE)) {
    return array();
  }

  $appointments = array();
  $handle = @fopen($GW_APPOINTMENT_LOG_FILE, "r");
  if (!$handle) {
    return $appointments;
  }

  $headers = fgetcsv($handle);
  if (!$headers) {
    fclose($handle);
    return $appointments;
  }

  while (($row = fgetcsv($handle)) !== false) {
    if (!$row || count($row) === 1 && trim((string)$row[0]) === "") {
      continue;
    }

    $appointment = array();
    foreach ($headers as $index => $header) {
      $appointment[$header] = isset($row[$index]) ? $row[$index] : "";
    }
    $appointments[] = $appointment;
  }

  fclose($handle);
  return $appointments;
}

function gw_app_load_appointments_read_locked() {
  if (gw_db_is_configured()) {
    return gw_app_load_appointments();
  }

  $lock = gw_app_open_lock("read");
  if (!$lock) {
    return array();
  }

  try {
    return gw_app_load_appointments();
  } finally {
    gw_app_close_lock($lock);
  }
}

function gw_app_append_appointment($appointment) {
  global $GW_APPOINTMENT_LOG_FILE;

  if (gw_db_is_configured()) {
    $saved = gw_db_insert_appointment($appointment);
    if ($saved) {
      return true;
    }
  }

  gw_app_ensure_storage();
  $handle = @fopen($GW_APPOINTMENT_LOG_FILE, "a");
  if (!$handle) {
    return false;
  }

  $row = array();
  foreach (gw_app_csv_headers() as $header) {
    $row[] = isset($appointment[$header]) ? $appointment[$header] : "";
  }

  $written = fputcsv($handle, $row);
  fclose($handle);
  return $written !== false;
}

function gw_app_generate_id() {
  try {
    $token = strtoupper(bin2hex(random_bytes(2)));
  } catch (Exception $e) {
    $token = strtoupper(substr(md5(uniqid("", true)), 0, 4));
  }

  return "GW-APT-" . date("Ymd-His") . "-" . $token;
}

function gw_app_parse_datetime($date, $time) {
  $value = trim((string)$date . " " . (string)$time);
  $dateTime = DateTimeImmutable::createFromFormat("Y-m-d H:i", $value);

  if (!$dateTime) {
    return false;
  }

  return $dateTime;
}

function gw_app_minutes_since_midnight($dateTime) {
  return ((int)$dateTime->format("H") * 60) + (int)$dateTime->format("i");
}

function gw_app_is_standard_window($startDateTime, $endDateTime) {
  global $GW_APPOINTMENT_STANDARD_START, $GW_APPOINTMENT_STANDARD_END;

  if ($startDateTime->format("Y-m-d") !== $endDateTime->format("Y-m-d")) {
    return false;
  }

  if ((int)$startDateTime->format("N") > 5) {
    return false;
  }

  $startMinutes = gw_app_minutes_since_midnight($startDateTime);
  $endMinutes = gw_app_minutes_since_midnight($endDateTime);

  return $startMinutes >= $GW_APPOINTMENT_STANDARD_START && $endMinutes <= $GW_APPOINTMENT_STANDARD_END;
}

function gw_app_time_label($dateTime) {
  return $dateTime->format("g:i A");
}

function gw_app_date_label($dateTime) {
  return $dateTime->format("l, F j, Y");
}

function gw_app_window_label($startDateTime, $endDateTime) {
  return gw_app_date_label($startDateTime) . " - " . gw_app_time_label($startDateTime) . " to " . gw_app_time_label($endDateTime);
}

function gw_app_row_start($appointment) {
  if (empty($appointment["appointment_date"]) || empty($appointment["appointment_time"])) {
    return false;
  }

  return gw_app_parse_datetime($appointment["appointment_date"], $appointment["appointment_time"]);
}

function gw_app_row_end($appointment) {
  if (!empty($appointment["end_date"]) && !empty($appointment["end_time"])) {
    return gw_app_parse_datetime($appointment["end_date"], $appointment["end_time"]);
  }

  $start = gw_app_row_start($appointment);
  if (!$start) {
    return false;
  }

  $duration = isset($appointment["duration_minutes"]) ? (int)$appointment["duration_minutes"] : 60;
  return $start->modify("+" . max(30, $duration) . " minutes");
}

function gw_app_windows_overlap($startA, $endA, $startB, $endB) {
  return $startA < $endB && $endA > $startB;
}

function gw_app_capacity_summary($appointments, $startDateTime, $endDateTime) {
  global $GW_APPOINTMENT_DOCK_COUNT;

  $count = 0;
  $overlaps = array();

  foreach ($appointments as $appointment) {
    if (!gw_app_is_active_status(isset($appointment["status"]) ? $appointment["status"] : "")) {
      continue;
    }

    $existingStart = gw_app_row_start($appointment);
    $existingEnd = gw_app_row_end($appointment);

    if (!$existingStart || !$existingEnd) {
      continue;
    }

    if (gw_app_windows_overlap($startDateTime, $endDateTime, $existingStart, $existingEnd)) {
      $count += 1;
      $overlaps[] = $appointment;
    }
  }

  return array(
    "count" => $count,
    "remaining" => max(0, $GW_APPOINTMENT_DOCK_COUNT - $count),
    "available" => $count < $GW_APPOINTMENT_DOCK_COUNT,
    "overlaps" => $overlaps
  );
}

function gw_app_find_duplicate($appointments, $carrierName, $referenceNumber, $contactEmail, $startDateTime) {
  $referenceNeedle = strtolower(trim((string)$referenceNumber));
  $carrierNeedle = strtolower(trim((string)$carrierName));
  $emailNeedle = strtolower(trim((string)$contactEmail));
  $slotNeedle = $startDateTime->format("Y-m-d H:i");

  foreach ($appointments as $appointment) {
    if (!gw_app_is_active_status(isset($appointment["status"]) ? $appointment["status"] : "")) {
      continue;
    }

    $existingStart = gw_app_row_start($appointment);
    if (!$existingStart) {
      continue;
    }

    if (
      strtolower(trim((string)$appointment["reference_number"])) === $referenceNeedle &&
      strtolower(trim((string)$appointment["carrier_name"])) === $carrierNeedle &&
      strtolower(trim((string)$appointment["contact_email"])) === $emailNeedle &&
      $existingStart->format("Y-m-d H:i") === $slotNeedle
    ) {
      return $appointment;
    }
  }

  return false;
}

function gw_app_next_dock_door($overlaps) {
  global $GW_APPOINTMENT_DOCK_COUNT;

  $usedDoors = array();
  foreach ($overlaps as $appointment) {
    $door = isset($appointment["dock_door"]) ? (int)$appointment["dock_door"] : 0;
    if ($door > 0) {
      $usedDoors[] = $door;
    }
  }

  for ($door = 1; $door <= $GW_APPOINTMENT_DOCK_COUNT; $door += 1) {
    if (!in_array($door, $usedDoors, true)) {
      return $door;
    }
  }

  return "";
}

function gw_app_build_appointment($post) {
  $start = gw_app_parse_datetime($post["appointment_date"], $post["appointment_time"]);
  if (!$start) {
    return false;
  }

  $duration = (int)$post["duration_minutes"];
  $duration = in_array($duration, gw_app_allowed_durations(), true) ? $duration : 60;
  $end = $start->modify("+" . $duration . " minutes");

  $standard = gw_app_is_standard_window($start, $end);
  $status = $standard ? "confirmed" : "pending_after_hours";
  $dockDoor = !empty($post["dock_door"]) ? $post["dock_door"] : "";

  return array(
    "created_at" => date("c"),
    "appointment_id" => gw_app_generate_id(),
    "status" => $status,
    "service_window" => $standard ? "standard" : "after_hours",
    "dock_door" => $dockDoor,
    "company_name" => $post["company_name"],
    "carrier_name" => $post["carrier_name"],
    "load_type" => $post["load_type"],
    "reference_number" => $post["reference_number"],
    "secondary_reference" => $post["secondary_reference"],
    "equipment_id" => $post["equipment_id"],
    "contact_name" => $post["contact_name"],
    "contact_email" => $post["contact_email"],
    "contact_phone" => $post["contact_phone"],
    "appointment_date" => $start->format("Y-m-d"),
    "appointment_time" => $start->format("H:i"),
    "end_date" => $end->format("Y-m-d"),
    "end_time" => $end->format("H:i"),
    "duration_minutes" => $duration,
    "pallet_count" => $post["pallet_count"],
    "piece_count" => $post["piece_count"],
    "unload_type" => $post["unload_type"],
    "notes" => $post["notes"],
    "source_page" => "delivery-appointment.html"
  );
}

function gw_app_format_internal_email($appointment) {
  $start = gw_app_row_start($appointment);
  $end = gw_app_row_end($appointment);
  $dock = !empty($appointment["dock_door"]) ? "Dock Door " . $appointment["dock_door"] : "To be assigned";

  $lines = array();
  $lines[] = "DELIVERY APPOINTMENT";
  $lines[] = "----------------------------------------";
  $lines[] = "Appointment ID: " . $appointment["appointment_id"];
  $lines[] = "Status: " . gw_app_status_label($appointment["status"]);
  $lines[] = "Window: " . gw_app_window_label($start, $end);
  $lines[] = "Service Window: " . ucfirst(str_replace("_", " ", $appointment["service_window"]));
  $lines[] = "Dock Door: " . $dock;
  $lines[] = "";
  $lines[] = "Company / Vendor: " . ($appointment["company_name"] !== "" ? $appointment["company_name"] : "N/A");
  $lines[] = "Carrier Name: " . $appointment["carrier_name"];
  $lines[] = "Load Type: " . $appointment["load_type"];
  $lines[] = "Reference: " . $appointment["reference_number"];
  $lines[] = "Secondary Reference: " . ($appointment["secondary_reference"] !== "" ? $appointment["secondary_reference"] : "N/A");
  $lines[] = "Trailer / Container / Equipment: " . ($appointment["equipment_id"] !== "" ? $appointment["equipment_id"] : "N/A");
  $lines[] = "";
  $lines[] = "Contact Name: " . $appointment["contact_name"];
  $lines[] = "Contact Email: " . $appointment["contact_email"];
  $lines[] = "Contact Phone: " . $appointment["contact_phone"];
  $lines[] = "Pallet Count: " . ($appointment["pallet_count"] !== "" ? $appointment["pallet_count"] : "N/A");
  $lines[] = "Piece Count: " . ($appointment["piece_count"] !== "" ? $appointment["piece_count"] : "N/A");
  $lines[] = "Unload Type: " . ($appointment["unload_type"] !== "" ? $appointment["unload_type"] : "N/A");
  $lines[] = "";
  $lines[] = "Notes:";
  $lines[] = $appointment["notes"] !== "" ? $appointment["notes"] : "N/A";
  $lines[] = "";
  $lines[] = "Submitted from: " . gw_config_site_href("delivery-appointment.html");
  $lines[] = "Timestamp (server): " . date("c");

  return implode("\n", $lines);
}

function gw_app_format_customer_email($appointment) {
  $start = gw_app_row_start($appointment);
  $end = gw_app_row_end($appointment);
  $standard = $appointment["service_window"] === "standard";
  $dockText = !empty($appointment["dock_door"]) ? "Dock Door " . $appointment["dock_door"] : "Dock door to be assigned";

  $lines = array();
  $lines[] = "Hello " . $appointment["contact_name"] . ",";
  $lines[] = "";

  if ($standard) {
    $lines[] = "Your delivery appointment has been confirmed.";
    $lines[] = "Appointment ID: " . $appointment["appointment_id"];
    $lines[] = "Scheduled Window: " . gw_app_window_label($start, $end);
    $lines[] = "Assigned Dock: " . $dockText;
  } else {
    $lines[] = "Your after-hours delivery request has been received.";
    $lines[] = "Appointment ID: " . $appointment["appointment_id"];
    $lines[] = "Requested Window: " . gw_app_window_label($start, $end);
    $lines[] = "This request falls outside Monday to Friday, 8:30 AM to 4:00 PM.";
    $lines[] = "After-hours fees may apply, and Grey Wolf will confirm the appointment by email.";
  }

  $lines[] = "";
  $lines[] = "Carrier: " . $appointment["carrier_name"];
  $lines[] = "Load Type: " . $appointment["load_type"];
  $lines[] = "Reference: " . $appointment["reference_number"];
  $lines[] = "";
  $lines[] = "If anything changes, reply to " . gw_config_to_email() . " or call 416-451-8894.";
  $lines[] = "";
  $lines[] = "Grey Wolf 3PL & Logistics Inc";
  $lines[] = "1330 Courtney Park Dr E";
  $lines[] = "Mississauga, ON L5T 1K5";

  return implode("\n", $lines);
}

function gw_app_send_email($toEmail, $subject, $body, $replyToEmail, $replyToName) {
  return gw_mail_send($toEmail, $subject, $body, $replyToEmail, $replyToName, array(
    "from_name" => "Grey Wolf 3PL"
  ));
}

function gw_app_render_response_page($options) {
  $title = isset($options["title"]) ? $options["title"] : "Delivery Appointment";
  $headline = isset($options["headline"]) ? $options["headline"] : $title;
  $message = isset($options["message"]) ? $options["message"] : "";
  $summary = isset($options["summary"]) ? $options["summary"] : array();
  $primaryHref = isset($options["primary_href"]) ? $options["primary_href"] : "delivery-appointment.html";
  $primaryLabel = isset($options["primary_label"]) ? $options["primary_label"] : "Back to delivery appointments";
  $secondaryHref = isset($options["secondary_href"]) ? $options["secondary_href"] : "index.html";
  $secondaryLabel = isset($options["secondary_label"]) ? $options["secondary_label"] : "Back to home";
  $theme = isset($options["theme"]) ? $options["theme"] : "success";

  $accent = $theme === "error" ? "#e37363" : "#f0a14a";
  $background = $theme === "error" ? "rgba(227,115,99,0.14)" : "rgba(240,161,74,0.14)";

  echo "<!DOCTYPE html>\n";
  echo "<html lang=\"en\">\n<head>\n";
  echo "  <meta charset=\"UTF-8\">\n";
  echo "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
  echo "  <title>" . gw_app_h($title) . " | Grey Wolf 3PL</title>\n";
  echo "  <meta name=\"robots\" content=\"noindex, nofollow\">\n";
  echo "  <link rel=\"stylesheet\" href=\"style.css?v=20260324-2\">\n";
  echo "  <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n";
  echo "  <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n";
  echo "  <link href=\"https://fonts.googleapis.com/css2?family=Mulish:wght@400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap\" rel=\"stylesheet\">\n";
  echo "  <style>\n";
  echo "    body{margin:0;background:#0b1622;color:#e8eef4;font-family:'Mulish',sans-serif;}\n";
  echo "    .app-shell{min-height:100vh;display:grid;place-items:center;padding:2rem;}\n";
  echo "    .app-card{width:min(760px,100%);background:rgba(13,25,39,.92);border:1px solid rgba(255,255,255,.08);border-radius:28px;box-shadow:0 28px 60px rgba(0,0,0,.28);padding:2rem;}\n";
  echo "    .eyebrow{display:inline-flex;align-items:center;gap:.5rem;padding:.45rem .8rem;border-radius:999px;background:" . $background . ";color:" . $accent . ";font-weight:800;letter-spacing:.08em;text-transform:uppercase;font-size:.78rem;}\n";
  echo "    h1{font-family:'Poppins',sans-serif;font-size:clamp(2rem,4vw,3.25rem);line-height:1.02;margin:1rem 0;color:#f7fafc;}\n";
  echo "    p{color:#c4d2df;font-size:1.02rem;line-height:1.7;}\n";
  echo "    .summary{display:grid;gap:.85rem;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin:1.5rem 0;}\n";
  echo "    .summary div{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:1rem;}\n";
  echo "    .summary strong{display:block;color:#8ea4b8;font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.45rem;}\n";
  echo "    .summary span{color:#f5f7fb;font-weight:700;}\n";
  echo "    .actions{display:flex;flex-wrap:wrap;gap:.8rem;margin-top:1.5rem;}\n";
  echo "    .actions a{display:inline-flex;align-items:center;justify-content:center;border-radius:999px;padding:1rem 1.3rem;text-decoration:none;font-weight:800;}\n";
  echo "    .actions .primary{background:linear-gradient(135deg,#d86a2d,#f0a14a);color:#08131d;}\n";
  echo "    .actions .secondary{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);color:#eef4fb;}\n";
  echo "  </style>\n";
  echo "</head>\n<body>\n";
  echo "  <div class=\"app-shell\">\n";
  echo "    <div class=\"app-card\">\n";
  echo "      <span class=\"eyebrow\">" . ($theme === "error" ? "Scheduling issue" : "Appointment update") . "</span>\n";
  echo "      <h1>" . gw_app_h($headline) . "</h1>\n";
  echo "      <p>" . gw_app_h($message) . "</p>\n";

  if (!empty($summary)) {
    echo "      <div class=\"summary\">\n";
    foreach ($summary as $label => $value) {
      echo "        <div><strong>" . gw_app_h($label) . "</strong><span>" . gw_app_h($value) . "</span></div>\n";
    }
    echo "      </div>\n";
  }

  echo "      <div class=\"actions\">\n";
  echo "        <a class=\"primary\" href=\"" . gw_app_h($primaryHref) . "\">" . gw_app_h($primaryLabel) . "</a>\n";
  echo "        <a class=\"secondary\" href=\"" . gw_app_h($secondaryHref) . "\">" . gw_app_h($secondaryLabel) . "</a>\n";
  echo "      </div>\n";
  echo "    </div>\n";
  echo "  </div>\n";
  echo "</body>\n</html>";
  exit;
}

function gw_app_daily_slot_counts($appointments, $date) {
  global $GW_APPOINTMENT_STANDARD_START, $GW_APPOINTMENT_STANDARD_END, $GW_APPOINTMENT_SLOT_MINUTES;

  $dayStart = gw_app_parse_datetime($date, "00:00");
  if (!$dayStart) {
    return array();
  }

  $slots = array();
  for ($minutes = $GW_APPOINTMENT_STANDARD_START; $minutes < $GW_APPOINTMENT_STANDARD_END; $minutes += $GW_APPOINTMENT_SLOT_MINUTES) {
    $slotStart = $dayStart->modify("+" . $minutes . " minutes");
    $slotEnd = $slotStart->modify("+" . $GW_APPOINTMENT_SLOT_MINUTES . " minutes");
    $summary = gw_app_capacity_summary($appointments, $slotStart, $slotEnd);
    $slots[] = array(
      "label" => gw_app_time_label($slotStart),
      "count" => $summary["count"],
      "remaining" => $summary["remaining"]
    );
  }

  return $slots;
}

function gw_app_sort_by_start(&$appointments) {
  usort($appointments, function ($a, $b) {
    $startA = gw_app_row_start($a);
    $startB = gw_app_row_start($b);

    if (!$startA && !$startB) {
      return 0;
    }
    if (!$startA) {
      return 1;
    }
    if (!$startB) {
      return -1;
    }

    return $startA <=> $startB;
  });
}
