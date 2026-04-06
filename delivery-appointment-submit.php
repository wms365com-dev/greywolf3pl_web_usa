<?php
require_once __DIR__ . "/appointment-lib.php";

global $GW_APPOINTMENT_TO_EMAIL;

$appointmentPageHref = gw_config_site_href("delivery-appointment.html");
$homePageHref = gw_config_site_href("index.html");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  gw_app_render_response_page(array(
    "title" => "Invalid request",
    "headline" => "This booking link only accepts form submissions.",
    "message" => "Please return to the delivery appointment page and submit the booking form there.",
    "primary_href" => $appointmentPageHref,
    "primary_label" => "Go to booking page",
    "secondary_href" => $homePageHref,
    "secondary_label" => "Back to home",
    "theme" => "error"
  ));
}

$honeypot = gw_app_val("website");
if ($honeypot !== "") {
  header("Location: " . $appointmentPageHref);
  exit;
}

$started = gw_app_val("form_started_at");
if ($started !== "") {
  $startStamp = strtotime($started);
  if ($startStamp && (time() - $startStamp) < 3) {
    header("Location: " . $appointmentPageHref);
    exit;
  }
}

$post = array(
  "company_name" => gw_app_val("company_name"),
  "carrier_name" => gw_app_val("carrier_name"),
  "load_type" => gw_app_val("load_type"),
  "reference_number" => gw_app_val("reference_number"),
  "secondary_reference" => gw_app_val("secondary_reference"),
  "equipment_id" => gw_app_val("equipment_id"),
  "contact_name" => gw_app_val("contact_name"),
  "contact_email" => gw_app_val("contact_email"),
  "contact_phone" => gw_app_val("contact_phone"),
  "appointment_date" => gw_app_val("appointment_date"),
  "appointment_time" => gw_app_val("appointment_time"),
  "duration_minutes" => gw_app_val("duration_minutes"),
  "pallet_count" => gw_app_val("pallet_count"),
  "piece_count" => gw_app_val("piece_count"),
  "unload_type" => gw_app_val("unload_type"),
  "notes" => isset($_POST["notes"]) ? gw_app_textarea($_POST["notes"]) : ""
);

$requiredLabels = array(
  "company_name" => "Company or vendor name",
  "carrier_name" => "Carrier name",
  "load_type" => "Load type",
  "reference_number" => "Reference",
  "contact_name" => "Contact name",
  "contact_email" => "Contact email",
  "contact_phone" => "Contact phone",
  "appointment_date" => "Appointment date",
  "appointment_time" => "Appointment time",
  "duration_minutes" => "Unload duration"
);

$missing = array();
foreach ($requiredLabels as $key => $label) {
  if (!isset($post[$key]) || trim((string)$post[$key]) === "") {
    $missing[] = $label;
  }
}

if (!empty($missing)) {
  gw_app_render_response_page(array(
    "title" => "Missing information",
    "headline" => "A few booking details are still missing.",
    "message" => "Please go back and complete: " . gw_app_h(implode(", ", $missing)) . ".",
    "primary_href" => $appointmentPageHref,
    "primary_label" => "Back to booking form",
    "secondary_href" => "tel:+14164518894",
    "secondary_label" => "Call 416-451-8894",
    "theme" => "error"
  ));
}

if (!filter_var($post["contact_email"], FILTER_VALIDATE_EMAIL)) {
  gw_app_render_response_page(array(
    "title" => "Invalid email",
    "headline" => "Please enter a valid contact email.",
    "message" => "We use this address to confirm the dock appointment and share any after-hours approval details.",
    "primary_href" => $appointmentPageHref,
    "primary_label" => "Back to booking form",
    "secondary_href" => "mailto:" . $GW_APPOINTMENT_TO_EMAIL,
    "secondary_label" => "Email Grey Wolf",
    "theme" => "error"
  ));
}

$duration = (int)$post["duration_minutes"];
if (!in_array($duration, gw_app_allowed_durations(), true)) {
  gw_app_render_response_page(array(
    "title" => "Invalid duration",
    "headline" => "Choose one of the listed unload durations.",
    "message" => "Appointments are scheduled in 30-minute blocks so dock capacity stays accurate.",
    "primary_href" => $appointmentPageHref,
    "primary_label" => "Back to booking form",
    "secondary_href" => $homePageHref,
    "secondary_label" => "Back to home",
    "theme" => "error"
  ));
}

if (!gw_app_is_checked("ack_fees")) {
  gw_app_render_response_page(array(
    "title" => "Acknowledgement required",
    "headline" => "Please acknowledge the standard and after-hours appointment policy.",
    "message" => "That confirmation helps carriers understand that bookings outside Monday to Friday, 8:30 AM to 4:00 PM may carry extra fees and require manual approval.",
    "primary_href" => $appointmentPageHref,
    "primary_label" => "Back to booking form",
    "secondary_href" => "tel:+14164518894",
    "secondary_label" => "Call 416-451-8894",
    "theme" => "error"
  ));
}

$startDateTime = gw_app_parse_datetime($post["appointment_date"], $post["appointment_time"]);
if (!$startDateTime) {
  gw_app_render_response_page(array(
    "title" => "Invalid date or time",
    "headline" => "Please choose a valid appointment date and time.",
    "message" => "The requested booking window could not be parsed. Try again using the date and time fields on the form.",
    "primary_href" => $appointmentPageHref,
    "primary_label" => "Back to booking form",
    "secondary_href" => $homePageHref,
    "secondary_label" => "Back to home",
    "theme" => "error"
  ));
}

$endDateTime = $startDateTime->modify("+" . $duration . " minutes");
if ($endDateTime <= new DateTimeImmutable()) {
  gw_app_render_response_page(array(
    "title" => "Past appointment",
    "headline" => "Please choose a future appointment window.",
    "message" => "The requested dock window has already passed. Select a new appointment time and resubmit.",
    "primary_href" => $appointmentPageHref,
    "primary_label" => "Back to booking form",
    "secondary_href" => "tel:+14164518894",
    "secondary_label" => "Call dispatch",
    "theme" => "error"
  ));
}

$lock = gw_app_open_lock("write");
if (!$lock) {
  gw_app_render_response_page(array(
    "title" => "Scheduling unavailable",
    "headline" => "The dock calendar is temporarily unavailable.",
    "message" => "Please try again in a moment, or contact Grey Wolf directly if you need immediate help securing an inbound slot.",
    "primary_href" => $appointmentPageHref,
    "primary_label" => "Try again",
    "secondary_href" => "mailto:" . $GW_APPOINTMENT_TO_EMAIL,
    "secondary_label" => "Email Grey Wolf",
    "theme" => "error"
  ));
}

$customerSent = false;
$internalSent = false;
$appointment = false;

try {
  $appointments = gw_app_load_appointments();

  $duplicate = gw_app_find_duplicate(
    $appointments,
    $post["carrier_name"],
    $post["reference_number"],
    $post["contact_email"],
    $startDateTime
  );

  if ($duplicate) {
    gw_app_render_response_page(array(
      "title" => "Appointment already exists",
      "headline" => "That delivery is already on the schedule.",
      "message" => "A booking already exists for that carrier, reference, email, and start time. If you need to change it, call 416-451-8894 or email " . $GW_APPOINTMENT_TO_EMAIL . ".",
      "summary" => array(
        "Appointment ID" => $duplicate["appointment_id"],
        "Status" => gw_app_status_label($duplicate["status"]),
        "Scheduled date" => $duplicate["appointment_date"],
        "Scheduled time" => gw_app_time_label(gw_app_row_start($duplicate))
      ),
      "primary_href" => $appointmentPageHref,
      "primary_label" => "Book a different time",
      "secondary_href" => "mailto:" . $GW_APPOINTMENT_TO_EMAIL . "?subject=Update%20Delivery%20Appointment",
      "secondary_label" => "Email Grey Wolf",
      "theme" => "error"
    ));
  }

  $capacity = gw_app_capacity_summary($appointments, $startDateTime, $endDateTime);
  if (!$capacity["available"]) {
    gw_app_render_response_page(array(
      "title" => "Time slot full",
      "headline" => "All 3 dock doors are already booked for that window.",
      "message" => "Choose a different appointment time, shorten the unload duration, or contact Grey Wolf if the delivery is urgent.",
      "summary" => array(
        "Requested window" => gw_app_window_label($startDateTime, $endDateTime),
        "Dock capacity" => "3 doors in use",
        "Next step" => "Pick a different slot"
      ),
      "primary_href" => $appointmentPageHref,
      "primary_label" => "Choose another time",
      "secondary_href" => "tel:+14164518894",
      "secondary_label" => "Call dispatch",
      "theme" => "error"
    ));
  }

  $dockDoor = gw_app_next_dock_door($capacity["overlaps"]);
  $post["dock_door"] = $dockDoor;
  $appointment = gw_app_build_appointment($post);

  if (!$appointment) {
    gw_app_render_response_page(array(
      "title" => "Scheduling error",
      "headline" => "The appointment could not be prepared.",
      "message" => "Please go back and try again. If the issue continues, email " . $GW_APPOINTMENT_TO_EMAIL . " with the carrier name and requested dock time.",
      "primary_href" => $appointmentPageHref,
      "primary_label" => "Back to booking form",
      "secondary_href" => "mailto:" . $GW_APPOINTMENT_TO_EMAIL,
      "secondary_label" => "Email Grey Wolf",
      "theme" => "error"
    ));
  }

  if ($appointment["status"] !== "confirmed") {
    $appointment["dock_door"] = "";
  }

  if (!gw_app_append_appointment($appointment)) {
    gw_app_render_response_page(array(
      "title" => "Scheduling unavailable",
      "headline" => "We couldn't save the appointment right now.",
      "message" => "No booking was confirmed. Please try again in a moment or contact Grey Wolf directly.",
      "primary_href" => $appointmentPageHref,
      "primary_label" => "Try again",
      "secondary_href" => "tel:+14164518894",
      "secondary_label" => "Call dispatch",
      "theme" => "error"
    ));
  }
} finally {
  gw_app_close_lock($lock);
}

$internalSubject = $appointment["status"] === "confirmed"
  ? "Delivery Appointment Confirmed - " . $appointment["carrier_name"] . " - " . $appointment["appointment_date"] . " " . $appointment["appointment_time"]
  : "Delivery Appointment Request - After Hours Review - " . $appointment["carrier_name"] . " - " . $appointment["appointment_date"] . " " . $appointment["appointment_time"];

$customerSubject = $appointment["status"] === "confirmed"
  ? "Your Grey Wolf Delivery Appointment Is Confirmed"
  : "Grey Wolf Delivery Appointment Request Received";

$internalSent = gw_app_send_email(
  $GW_APPOINTMENT_TO_EMAIL,
  $internalSubject,
  gw_app_format_internal_email($appointment),
  $appointment["contact_email"],
  $appointment["contact_name"]
);

$customerSent = gw_app_send_email(
  $appointment["contact_email"],
  $customerSubject,
  gw_app_format_customer_email($appointment),
  $GW_APPOINTMENT_TO_EMAIL,
  "Grey Wolf 3PL"
);

$startLabel = gw_app_window_label(gw_app_row_start($appointment), gw_app_row_end($appointment));
$summary = array(
  "Appointment ID" => $appointment["appointment_id"],
  "Carrier" => $appointment["carrier_name"],
  "Load type" => $appointment["load_type"],
  "Scheduled window" => $startLabel
);

if ($appointment["status"] === "confirmed") {
  $summary["Dock door"] = !empty($appointment["dock_door"]) ? "Door " . $appointment["dock_door"] : "Assigned on arrival";
} else {
  $summary["Status"] = "Pending after-hours confirmation";
}

$message = $appointment["status"] === "confirmed"
  ? "The appointment is booked. A confirmation email has been prepared for " . gw_app_h($appointment["contact_email"]) . ", and Grey Wolf has been notified at " . gw_app_h($GW_APPOINTMENT_TO_EMAIL) . "."
  : "The request is saved and marked for after-hours review. Grey Wolf has been notified, and a follow-up confirmation email will be sent once the extra-fee window is reviewed.";

if (!$customerSent || !$internalSent) {
  $message .= " The booking is still saved even though one or more server emails did not send automatically.";
}

gw_app_render_response_page(array(
  "title" => $appointment["status"] === "confirmed" ? "Appointment confirmed" : "Appointment request received",
  "headline" => $appointment["status"] === "confirmed" ? "Your delivery appointment is on the schedule." : "Your after-hours appointment request is pending review.",
  "message" => $message,
  "summary" => $summary,
  "primary_href" => $appointmentPageHref,
  "primary_label" => "Book another appointment",
  "secondary_href" => "mailto:" . $GW_APPOINTMENT_TO_EMAIL . "?subject=Delivery%20Appointment%20" . rawurlencode($appointment["appointment_id"]),
  "secondary_label" => "Email Grey Wolf"
));
