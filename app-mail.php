<?php

require_once __DIR__ . "/app-config.php";

if (!function_exists("gw_mail_clean_header_value")) {
  function gw_mail_clean_header_value($value) {
    $value = trim((string)$value);
    return str_replace(array("\r", "\n"), " ", $value);
  }
}

if (!function_exists("gw_mail_should_use_smtp")) {
  function gw_mail_should_use_smtp() {
    $smtp = gw_config_smtp_options();
    return $smtp["host"] !== "";
  }
}

if (!function_exists("gw_mail_send_native")) {
  function gw_mail_send_native($toEmail, $subject, $body, $replyToEmail = "", $replyToName = "", $options = array()) {
    $fromEmail = isset($options["from_email"]) && trim((string)$options["from_email"]) !== ""
      ? trim((string)$options["from_email"])
      : gw_config_mail_from_email();
    $fromName = isset($options["from_name"]) && trim((string)$options["from_name"]) !== ""
      ? trim((string)$options["from_name"])
      : gw_config_mail_from_name();

    $headers = array();
    $headers[] = "From: " . gw_mail_clean_header_value($fromName) . " <" . gw_mail_clean_header_value($fromEmail) . ">";

    if (trim((string)$replyToEmail) !== "") {
      $headers[] = "Reply-To: " . gw_mail_clean_header_value($replyToName) . " <" . gw_mail_clean_header_value($replyToEmail) . ">";
    }

    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/plain; charset=UTF-8";

    return @mail(
      gw_mail_clean_header_value($toEmail),
      gw_mail_clean_header_value($subject),
      (string)$body,
      implode("\r\n", $headers)
    );
  }
}

if (!function_exists("gw_mail_send_smtp")) {
  function gw_mail_send_smtp($toEmail, $subject, $body, $replyToEmail = "", $replyToName = "", $options = array()) {
    $smtp = gw_config_smtp_options();
    if ($smtp["host"] === "") {
      return false;
    }

    require_once __DIR__ . "/wp-includes/PHPMailer/Exception.php";
    require_once __DIR__ . "/wp-includes/PHPMailer/SMTP.php";
    require_once __DIR__ . "/wp-includes/PHPMailer/PHPMailer.php";

    $fromEmail = isset($options["from_email"]) && trim((string)$options["from_email"]) !== ""
      ? trim((string)$options["from_email"])
      : gw_config_mail_from_email();
    $fromName = isset($options["from_name"]) && trim((string)$options["from_name"]) !== ""
      ? trim((string)$options["from_name"])
      : gw_config_mail_from_name();

    $authEnabled = $smtp["auth"] && $smtp["username"] !== "";
    $secure = $smtp["secure"] !== "" ? strtolower($smtp["secure"]) : "";
    if (in_array($secure, array("none", "off", "false", "0"), true)) {
      $secure = "";
    }
    if ($secure === "" && (int)$smtp["port"] === 465) {
      $secure = "ssl";
    }

    try {
      $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
      $mailer->CharSet = "UTF-8";
      $mailer->isSMTP();
      $mailer->Host = $smtp["host"];
      $mailer->Port = $smtp["port"] > 0 ? (int)$smtp["port"] : 587;
      $mailer->SMTPAuth = $authEnabled;
      $mailer->SMTPDebug = max(0, (int)$smtp["debug"]);

      if ($authEnabled) {
        $mailer->Username = $smtp["username"];
        $mailer->Password = $smtp["password"];
      }

      if ($secure !== "") {
        $mailer->SMTPSecure = $secure;
      }

      $mailer->setFrom($fromEmail, $fromName);
      $mailer->addAddress($toEmail);

      if (trim((string)$replyToEmail) !== "") {
        $mailer->addReplyTo($replyToEmail, $replyToName);
      }

      $mailer->Subject = $subject;
      $mailer->Body = (string)$body;
      $mailer->isHTML(false);

      return $mailer->send();
    } catch (\Throwable $error) {
      return false;
    }
  }
}

if (!function_exists("gw_mail_send")) {
  function gw_mail_send($toEmail, $subject, $body, $replyToEmail = "", $replyToName = "", $options = array()) {
    $smtp = gw_config_smtp_options();

    if (gw_mail_should_use_smtp()) {
      $smtpSent = gw_mail_send_smtp($toEmail, $subject, $body, $replyToEmail, $replyToName, $options);
      if ($smtpSent) {
        return true;
      }

      if ($smtp["disable_native_fallback"]) {
        return false;
      }
    }

    return gw_mail_send_native($toEmail, $subject, $body, $replyToEmail, $replyToName, $options);
  }
}
