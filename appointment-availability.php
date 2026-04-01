<?php
require_once __DIR__ . "/appointment-lib.php";

header("Content-Type: application/json; charset=UTF-8");
header("X-Robots-Tag: noindex, nofollow", true);

$date = isset($_GET["date"]) ? gw_app_clean($_GET["date"]) : "";
$time = isset($_GET["time"]) ? gw_app_clean($_GET["time"]) : "";
$duration = isset($_GET["duration"]) ? (int)$_GET["duration"] : 60;

if ($date === "" || $time === "" || !in_array($duration, gw_app_allowed_durations(), true)) {
  echo json_encode(array(
    "ok" => false,
    "available" => false,
    "message" => "Choose a date, start time, and listed unload duration to check dock availability."
  ));
  exit;
}

$startDateTime = gw_app_parse_datetime($date, $time);
if (!$startDateTime) {
  echo json_encode(array(
    "ok" => false,
    "available" => false,
    "message" => "The requested appointment time could not be read."
  ));
  exit;
}

$endDateTime = $startDateTime->modify("+" . $duration . " minutes");
$appointments = gw_app_load_appointments_read_locked();
$capacity = gw_app_capacity_summary($appointments, $startDateTime, $endDateTime);
$standard = gw_app_is_standard_window($startDateTime, $endDateTime);

$message = "";
if ($capacity["available"]) {
  if ($standard) {
    $message = $capacity["remaining"] . " of 3 dock doors remain for this standard-hours window.";
  } else {
    $message = $capacity["remaining"] . " of 3 dock doors remain for this after-hours window. Extra fees apply and Grey Wolf will confirm by email.";
  }
} else {
  $message = "All 3 dock doors are already booked for this window. Please choose a different time.";
}

echo json_encode(array(
  "ok" => true,
  "available" => $capacity["available"],
  "remaining" => $capacity["remaining"],
  "count" => $capacity["count"],
  "standard" => $standard,
  "service_window" => $standard ? "standard" : "after_hours",
  "window_label" => gw_app_window_label($startDateTime, $endDateTime),
  "message" => $message
));
exit;
