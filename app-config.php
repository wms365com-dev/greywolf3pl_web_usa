<?php

if (!function_exists("gw_env")) {
  function gw_env($key, $default = "") {
    $value = getenv((string)$key);
    if ($value === false || $value === "") {
      return $default;
    }

    return $value;
  }
}

if (!function_exists("gw_env_bool")) {
  function gw_env_bool($key, $default = false) {
    $value = getenv((string)$key);
    if ($value === false || $value === "") {
      return (bool)$default;
    }

    return in_array(strtolower(trim((string)$value)), array("1", "true", "yes", "on"), true);
  }
}

if (!function_exists("gw_config_to_email")) {
  function gw_config_to_email() {
    return trim((string)gw_env("GW_TO_EMAIL", "info@greywolf3pl.com"));
  }
}

if (!function_exists("gw_config_from_domain")) {
  function gw_config_from_domain() {
    return trim((string)gw_env("GW_FROM_DOMAIN", "greywolf3pl.com"));
  }
}

if (!function_exists("gw_config_site_url")) {
  function gw_config_site_url() {
    $explicit = trim((string)gw_env("GW_SITE_URL", ""));
    if ($explicit !== "") {
      return rtrim($explicit, "/");
    }

    $railwayDomain = trim((string)gw_env("RAILWAY_PUBLIC_DOMAIN", ""));
    if ($railwayDomain !== "") {
      return "https://" . $railwayDomain;
    }

    return "https://www.greywolf3pl.com";
  }
}

if (!function_exists("gw_config_site_href")) {
  function gw_config_site_href($path = "") {
    $base = gw_config_site_url();
    $path = trim((string)$path);

    if ($path === "" || $path === "/") {
      return $base . "/";
    }

    return $base . "/" . ltrim($path, "/");
  }
}

if (!function_exists("gw_config_google_sheet_webhook_url")) {
  function gw_config_google_sheet_webhook_url() {
    return trim((string)gw_env(
      "GW_GOOGLE_SHEET_WEBHOOK_URL",
      "https://script.google.com/macros/s/AKfycbzKtrMBi3_Z5thT2MIU1ACdRlJwtuQ-CXIYkDJCxB7CtLoH-owo3fixF1ddR1e877gb/exec"
    ));
  }
}

if (!function_exists("gw_config_storage_dir")) {
  function gw_config_storage_dir() {
    static $dir = null;

    if ($dir === null) {
      $default = __DIR__ . DIRECTORY_SEPARATOR . "form_submissions";
      $dir = rtrim((string)gw_env("GW_STORAGE_DIR", $default), "\\/");
    }

    return $dir;
  }
}

if (!function_exists("gw_config_storage_path")) {
  function gw_config_storage_path($fileName = "") {
    $base = gw_config_storage_dir();
    $fileName = ltrim((string)$fileName, "\\/");

    if ($fileName === "") {
      return $base;
    }

    return $base . DIRECTORY_SEPARATOR . $fileName;
  }
}

if (!function_exists("gw_config_mail_from_email")) {
  function gw_config_mail_from_email() {
    $default = "no-reply@" . gw_config_from_domain();
    return trim((string)gw_env("GW_SMTP_FROM_EMAIL", $default));
  }
}

if (!function_exists("gw_config_mail_from_name")) {
  function gw_config_mail_from_name() {
    return trim((string)gw_env("GW_SMTP_FROM_NAME", "Grey Wolf 3PL"));
  }
}

if (!function_exists("gw_config_smtp_options")) {
  function gw_config_smtp_options() {
    return array(
      "host" => trim((string)gw_env("GW_SMTP_HOST", "")),
      "port" => (int)gw_env("GW_SMTP_PORT", "587"),
      "username" => trim((string)gw_env("GW_SMTP_USERNAME", "")),
      "password" => (string)gw_env("GW_SMTP_PASSWORD", ""),
      "secure" => trim((string)gw_env("GW_SMTP_SECURE", "tls")),
      "auth" => gw_env_bool("GW_SMTP_AUTH", true),
      "debug" => (int)gw_env("GW_SMTP_DEBUG", "0"),
      "disable_native_fallback" => gw_env_bool("GW_SMTP_DISABLE_NATIVE_FALLBACK", false)
    );
  }
}
