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

if (!function_exists("gw_config_api_url")) {
  function gw_config_api_url() {
    $explicit = trim((string)gw_env("GW_API_URL", ""));
    if ($explicit !== "") {
      return rtrim($explicit, "/");
    }

    $railwayDomain = trim((string)gw_env("RAILWAY_PUBLIC_DOMAIN", ""));
    if ($railwayDomain !== "") {
      return "https://" . $railwayDomain;
    }

    return "https://api.greywolf3pl.com";
  }
}

if (!function_exists("gw_config_api_href")) {
  function gw_config_api_href($path = "") {
    $base = gw_config_api_url();
    $path = trim((string)$path);

    if ($path === "" || $path === "/") {
      return $base . "/";
    }

    return $base . "/" . ltrim($path, "/");
  }
}

if (!function_exists("gw_normalize_origin")) {
  function gw_normalize_origin($origin) {
    $origin = trim((string)$origin);
    if ($origin === "") {
      return "";
    }

    if (strtolower($origin) === "null") {
      return "null";
    }

    $parts = @parse_url($origin);
    if (!is_array($parts) || empty($parts["host"])) {
      return rtrim($origin, "/");
    }

    $scheme = !empty($parts["scheme"]) ? strtolower((string)$parts["scheme"]) : "https";
    $host = strtolower((string)$parts["host"]);
    $port = isset($parts["port"]) ? ":" . (int)$parts["port"] : "";

    return $scheme . "://" . $host . $port;
  }
}

if (!function_exists("gw_config_allowed_origins")) {
  function gw_config_allowed_origins() {
    static $origins = null;

    if ($origins !== null) {
      return $origins;
    }

    $configured = trim((string)gw_env("GW_ALLOWED_ORIGINS", ""));
    $rawOrigins = array();

    if ($configured !== "") {
      $rawOrigins = preg_split('/\s*,\s*/', $configured);
    } else {
      $rawOrigins = array(
        gw_config_site_url(),
        "https://www.greywolf3pl.com",
        "https://greywolf3pl.com",
        "http://www.greywolf3pl.com",
        "http://greywolf3pl.com"
      );

      $railwayDomain = trim((string)gw_env("RAILWAY_PUBLIC_DOMAIN", ""));
      if ($railwayDomain !== "") {
        $rawOrigins[] = "https://" . $railwayDomain;
      }
    }

    $origins = array_values(array_filter(array_unique(array_map("gw_normalize_origin", $rawOrigins))));
    return $origins;
  }
}

if (!function_exists("gw_is_allowed_origin")) {
  function gw_is_allowed_origin($origin) {
    $origin = gw_normalize_origin($origin);
    if ($origin === "") {
      return false;
    }

    return in_array($origin, gw_config_allowed_origins(), true);
  }
}

if (!function_exists("gw_send_cors_headers")) {
  function gw_send_cors_headers($methods = "GET, POST, OPTIONS", $headers = "Content-Type, Accept, X-Requested-With") {
    $origin = isset($_SERVER["HTTP_ORIGIN"]) ? gw_normalize_origin($_SERVER["HTTP_ORIGIN"]) : "";
    if ($origin !== "" && gw_is_allowed_origin($origin)) {
      header("Access-Control-Allow-Origin: " . $origin);
      header("Vary: Origin");
      header("Access-Control-Allow-Methods: " . $methods);
      header("Access-Control-Allow-Headers: " . $headers);
      header("Access-Control-Max-Age: 86400");
    }
  }
}

if (!function_exists("gw_handle_preflight")) {
  function gw_handle_preflight($methods = "GET, POST, OPTIONS", $headers = "Content-Type, Accept, X-Requested-With") {
    if (strtoupper((string)($_SERVER["REQUEST_METHOD"] ?? "")) !== "OPTIONS") {
      return;
    }

    gw_send_cors_headers($methods, $headers);
    $origin = isset($_SERVER["HTTP_ORIGIN"]) ? gw_normalize_origin($_SERVER["HTTP_ORIGIN"]) : "";

    if ($origin !== "" && !gw_is_allowed_origin($origin)) {
      http_response_code(403);
      exit;
    }

    http_response_code(204);
    exit;
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
