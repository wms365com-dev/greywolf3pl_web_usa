<?php

require_once __DIR__ . "/app-config.php";

if (!function_exists("gw_db_parse_config")) {
  function gw_db_parse_config() {
    static $config = null;

    if ($config !== null) {
      return $config;
    }

    $databaseUrl = trim((string)gw_env("DATABASE_URL", ""));
    if ($databaseUrl !== "") {
      $parts = parse_url($databaseUrl);
      if (is_array($parts) && !empty($parts["host"]) && !empty($parts["path"])) {
        $query = array();
        if (!empty($parts["query"])) {
          parse_str((string)$parts["query"], $query);
        }

        $config = array(
          "host" => (string)$parts["host"],
          "port" => isset($parts["port"]) ? (int)$parts["port"] : 5432,
          "dbname" => ltrim((string)$parts["path"], "/"),
          "user" => isset($parts["user"]) ? rawurldecode((string)$parts["user"]) : "",
          "password" => isset($parts["pass"]) ? rawurldecode((string)$parts["pass"]) : "",
          "sslmode" => isset($query["sslmode"]) ? (string)$query["sslmode"] : (string)gw_env("PGSSLMODE", gw_env("GW_DB_SSLMODE", "require"))
        );

        return $config;
      }
    }

    $host = trim((string)gw_env("PGHOST", ""));
    $database = trim((string)gw_env("PGDATABASE", ""));
    if ($host !== "" && $database !== "") {
      $config = array(
        "host" => $host,
        "port" => (int)gw_env("PGPORT", "5432"),
        "dbname" => $database,
        "user" => trim((string)gw_env("PGUSER", "")),
        "password" => (string)gw_env("PGPASSWORD", ""),
        "sslmode" => (string)gw_env("PGSSLMODE", gw_env("GW_DB_SSLMODE", "require"))
      );

      return $config;
    }

    $config = false;
    return $config;
  }
}

if (!function_exists("gw_db_is_configured")) {
  function gw_db_is_configured() {
    return gw_db_parse_config() !== false;
  }
}

if (!function_exists("gw_db_tx_connection")) {
  function gw_db_tx_connection($set = null) {
    static $connection = null;

    if (func_num_args() > 0) {
      $connection = $set;
    }

    return $connection;
  }
}

if (!function_exists("gw_db_connection")) {
  function gw_db_connection() {
    $transactionConnection = gw_db_tx_connection();
    if ($transactionConnection instanceof PDO) {
      return $transactionConnection;
    }

    static $pdo = null;
    static $attempted = false;
    static $schemaReady = false;

    if ($pdo instanceof PDO) {
      if (!$schemaReady) {
        $schemaReady = gw_db_ensure_schema($pdo);
      }
      return $pdo;
    }

    if ($attempted) {
      return false;
    }

    $attempted = true;
    $config = gw_db_parse_config();
    if ($config === false) {
      return false;
    }

    $dsn = "pgsql:host=" . $config["host"] . ";port=" . ((int)$config["port"] > 0 ? (int)$config["port"] : 5432) . ";dbname=" . $config["dbname"];
    $sslmode = strtolower(trim((string)$config["sslmode"]));
    if ($sslmode !== "") {
      $dsn .= ";sslmode=" . $sslmode;
    }

    try {
      $pdo = new PDO(
        $dsn,
        $config["user"],
        $config["password"],
        array(
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false
        )
      );
    } catch (Throwable $error) {
      $pdo = false;
      return false;
    }

    $schemaReady = gw_db_ensure_schema($pdo);
    if (!$schemaReady) {
      return false;
    }

    return $pdo;
  }
}

if (!function_exists("gw_db_ensure_schema")) {
  function gw_db_ensure_schema($pdo) {
    static $done = false;
    if ($done) {
      return true;
    }

    $statements = array(
      "CREATE TABLE IF NOT EXISTS quotes (
        id BIGSERIAL PRIMARY KEY,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        company TEXT NOT NULL,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT,
        service TEXT NOT NULL,
        start_date TEXT,
        product TEXT NOT NULL,
        pallets TEXT,
        pallet_size TEXT,
        inbound_frequency TEXT,
        outbound_frequency TEXT,
        pick_pack TEXT,
        special_requirements TEXT,
        notes TEXT,
        source_url TEXT
      )",
      "CREATE TABLE IF NOT EXISTS leads (
        id BIGSERIAL PRIMARY KEY,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        form_type TEXT,
        source_page TEXT,
        company TEXT,
        name TEXT,
        email TEXT NOT NULL,
        phone TEXT,
        service TEXT,
        company_website TEXT,
        orders_per_month TEXT,
        number_of_skus TEXT,
        pallets_storage TEXT,
        lead_magnet TEXT,
        business_info TEXT,
        notes TEXT
      )",
      "CREATE TABLE IF NOT EXISTS drayage_requests (
        id BIGSERIAL PRIMARY KEY,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        tracking_id TEXT,
        company_name TEXT,
        contact_name TEXT NOT NULL,
        email TEXT,
        phone TEXT NOT NULL,
        load_type TEXT NOT NULL,
        reference_number TEXT NOT NULL,
        secondary_reference TEXT,
        ship_from_address_1 TEXT NOT NULL,
        ship_from_unit TEXT,
        ship_from_city TEXT NOT NULL,
        ship_from_province TEXT NOT NULL,
        ship_from_postal_code TEXT NOT NULL,
        ship_to_address_1 TEXT NOT NULL,
        ship_to_unit TEXT,
        ship_to_city TEXT NOT NULL,
        ship_to_province TEXT NOT NULL,
        ship_to_postal_code TEXT NOT NULL,
        service_date TEXT NOT NULL,
        service_needed TEXT NOT NULL,
        container_size TEXT,
        unit_count TEXT,
        notes TEXT,
        source_page TEXT,
        sheet_synced BOOLEAN NOT NULL DEFAULT FALSE
      )",
      "CREATE TABLE IF NOT EXISTS drayage_draft_events (
        id BIGSERIAL PRIMARY KEY,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        tracking_id TEXT NOT NULL,
        status TEXT NOT NULL,
        step_reached INTEGER,
        contact_name TEXT,
        phone TEXT,
        email TEXT,
        reference_number TEXT,
        service_date TEXT,
        google_sync_ok BOOLEAN NOT NULL DEFAULT FALSE,
        google_message TEXT,
        payload_json TEXT
      )",
      "CREATE TABLE IF NOT EXISTS delivery_appointments (
        id BIGSERIAL PRIMARY KEY,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        appointment_id TEXT NOT NULL UNIQUE,
        status TEXT NOT NULL,
        service_window TEXT NOT NULL,
        dock_door INTEGER,
        company_name TEXT,
        carrier_name TEXT,
        load_type TEXT,
        reference_number TEXT,
        secondary_reference TEXT,
        equipment_id TEXT,
        contact_name TEXT,
        contact_email TEXT,
        contact_phone TEXT,
        appointment_date TEXT NOT NULL,
        appointment_time TEXT NOT NULL,
        end_date TEXT,
        end_time TEXT,
        appointment_start TIMESTAMPTZ NOT NULL,
        appointment_end TIMESTAMPTZ NOT NULL,
        duration_minutes INTEGER NOT NULL,
        pallet_count TEXT,
        piece_count TEXT,
        unload_type TEXT,
        notes TEXT,
        source_page TEXT
      )",
      "CREATE INDEX IF NOT EXISTS idx_delivery_appointments_window ON delivery_appointments (appointment_start, appointment_end)",
      "CREATE INDEX IF NOT EXISTS idx_delivery_appointments_reference ON delivery_appointments (reference_number)",
      "CREATE INDEX IF NOT EXISTS idx_drayage_requests_reference ON drayage_requests (reference_number)",
      "CREATE INDEX IF NOT EXISTS idx_drayage_draft_events_tracking ON drayage_draft_events (tracking_id)"
    );

    try {
      foreach ($statements as $sql) {
        $pdo->exec($sql);
      }
    } catch (Throwable $error) {
      return false;
    }

    $done = true;
    return true;
  }
}

if (!function_exists("gw_db_insert_row")) {
  function gw_db_insert_row($table, $data) {
    $pdo = gw_db_connection();
    if (!$pdo) {
      return false;
    }

    $columns = array_keys($data);
    $placeholders = array();
    foreach ($columns as $column) {
      $placeholders[] = ":" . $column;
    }

    $sql = "INSERT INTO " . $table . " (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";

    try {
      $statement = $pdo->prepare($sql);
      foreach ($data as $column => $value) {
        $statement->bindValue(":" . $column, $value);
      }
      return $statement->execute();
    } catch (Throwable $error) {
      return false;
    }
  }
}

if (!function_exists("gw_db_insert_quote")) {
  function gw_db_insert_quote($data) {
    return gw_db_insert_row("quotes", $data);
  }
}

if (!function_exists("gw_db_insert_lead")) {
  function gw_db_insert_lead($data) {
    return gw_db_insert_row("leads", $data);
  }
}

if (!function_exists("gw_db_insert_drayage_request")) {
  function gw_db_insert_drayage_request($data) {
    return gw_db_insert_row("drayage_requests", $data);
  }
}

if (!function_exists("gw_db_update_drayage_request_sync_status")) {
  function gw_db_update_drayage_request_sync_status($trackingId, $sheetSynced) {
    $pdo = gw_db_connection();
    if (!$pdo || trim((string)$trackingId) === "") {
      return false;
    }

    try {
      $statement = $pdo->prepare("UPDATE drayage_requests SET sheet_synced = :sheet_synced WHERE tracking_id = :tracking_id");
      return $statement->execute(array(
        ":sheet_synced" => $sheetSynced,
        ":tracking_id" => $trackingId
      ));
    } catch (Throwable $error) {
      return false;
    }
  }
}

if (!function_exists("gw_db_insert_drayage_draft_event")) {
  function gw_db_insert_drayage_draft_event($data) {
    return gw_db_insert_row("drayage_draft_events", $data);
  }
}

if (!function_exists("gw_db_normalize_appointment_row")) {
  function gw_db_normalize_appointment_row($row) {
    $normalized = array();
    foreach ($row as $key => $value) {
      if ($value === null) {
        $normalized[$key] = "";
      } else {
        $normalized[$key] = (string)$value;
      }
    }

    if (!isset($normalized["dock_door"]) || $normalized["dock_door"] === "0") {
      $normalized["dock_door"] = "";
    }

    return $normalized;
  }
}

if (!function_exists("gw_db_load_appointments")) {
  function gw_db_load_appointments() {
    $pdo = gw_db_connection();
    if (!$pdo) {
      return false;
    }

    try {
      $statement = $pdo->query("SELECT created_at, appointment_id, status, service_window, dock_door, company_name, carrier_name, load_type, reference_number, secondary_reference, equipment_id, contact_name, contact_email, contact_phone, appointment_date, appointment_time, end_date, end_time, duration_minutes, pallet_count, piece_count, unload_type, notes, source_page FROM delivery_appointments ORDER BY appointment_start ASC");
      $rows = $statement->fetchAll();
      return array_map("gw_db_normalize_appointment_row", $rows);
    } catch (Throwable $error) {
      return false;
    }
  }
}

if (!function_exists("gw_db_insert_appointment")) {
  function gw_db_insert_appointment($appointment) {
    $start = gw_app_parse_datetime($appointment["appointment_date"], $appointment["appointment_time"]);
    $end = gw_app_row_end($appointment);
    if (!$start || !$end) {
      return false;
    }

    return gw_db_insert_row("delivery_appointments", array(
      "created_at" => $appointment["created_at"],
      "appointment_id" => $appointment["appointment_id"],
      "status" => $appointment["status"],
      "service_window" => $appointment["service_window"],
      "dock_door" => $appointment["dock_door"] !== "" ? (int)$appointment["dock_door"] : null,
      "company_name" => $appointment["company_name"],
      "carrier_name" => $appointment["carrier_name"],
      "load_type" => $appointment["load_type"],
      "reference_number" => $appointment["reference_number"],
      "secondary_reference" => $appointment["secondary_reference"],
      "equipment_id" => $appointment["equipment_id"],
      "contact_name" => $appointment["contact_name"],
      "contact_email" => $appointment["contact_email"],
      "contact_phone" => $appointment["contact_phone"],
      "appointment_date" => $appointment["appointment_date"],
      "appointment_time" => $appointment["appointment_time"],
      "end_date" => $appointment["end_date"],
      "end_time" => $appointment["end_time"],
      "appointment_start" => $start->format("c"),
      "appointment_end" => $end->format("c"),
      "duration_minutes" => (int)$appointment["duration_minutes"],
      "pallet_count" => $appointment["pallet_count"],
      "piece_count" => $appointment["piece_count"],
      "unload_type" => $appointment["unload_type"],
      "notes" => $appointment["notes"],
      "source_page" => $appointment["source_page"]
    ));
  }
}

if (!function_exists("gw_db_open_appointment_lock")) {
  function gw_db_open_appointment_lock() {
    $pdo = gw_db_connection();
    if (!$pdo) {
      return false;
    }

    try {
      if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
      }
      $pdo->query("SELECT pg_advisory_xact_lock(4174518894)");
      gw_db_tx_connection($pdo);
      return array("type" => "db", "pdo" => $pdo);
    } catch (Throwable $error) {
      try {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
      } catch (Throwable $ignored) {
      }
      gw_db_tx_connection(null);
      return false;
    }
  }
}

if (!function_exists("gw_db_close_appointment_lock")) {
  function gw_db_close_appointment_lock($lock) {
    $pdo = is_array($lock) && isset($lock["pdo"]) && $lock["pdo"] instanceof PDO ? $lock["pdo"] : null;
    if (!$pdo) {
      gw_db_tx_connection(null);
      return;
    }

    try {
      if ($pdo->inTransaction()) {
        $pdo->commit();
      }
    } catch (Throwable $error) {
      try {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
      } catch (Throwable $ignored) {
      }
    }

    gw_db_tx_connection(null);
  }
}
