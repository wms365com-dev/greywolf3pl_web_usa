<?php

if (!function_exists("gw_http_post_json")) {
  function gw_http_post_json($url, $payload, $timeout = 10) {
    $body = json_encode($payload);
    if ($body === false) {
      return array(
        "ok" => false,
        "status" => 0,
        "body" => "",
        "message" => "Could not encode payload."
      );
    }

    if (function_exists("curl_init")) {
      $ch = curl_init($url);
      curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => array("Content-Type: application/json"),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => (int)$timeout,
        CURLOPT_FOLLOWLOCATION => true
      ));

      $response = curl_exec($ch);
      $error = curl_error($ch);
      $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      if ($response === false) {
        return array(
          "ok" => false,
          "status" => $code,
          "body" => "",
          "message" => $error !== "" ? $error : "cURL request failed."
        );
      }

      return array(
        "ok" => $code >= 200 && $code < 300,
        "status" => $code,
        "body" => (string)$response,
        "message" => $code >= 200 && $code < 300
          ? "Request completed."
          : "Remote endpoint returned HTTP " . $code . "."
      );
    }

    $context = stream_context_create(array(
      "http" => array(
        "method" => "POST",
        "header" => "Content-Type: application/json\r\n",
        "content" => $body,
        "timeout" => (int)$timeout,
        "ignore_errors" => true
      )
    ));

    $response = @file_get_contents($url, false, $context);
    $statusLine = isset($http_response_header[0]) ? (string)$http_response_header[0] : "";
    $code = 0;

    if (preg_match('/\s(\d{3})\s/', $statusLine, $matches)) {
      $code = (int)$matches[1];
    }

    return array(
      "ok" => $response !== false && $code >= 200 && $code < 300,
      "status" => $code,
      "body" => $response !== false ? (string)$response : "",
      "message" => $response !== false && $code >= 200 && $code < 300
        ? "Request completed."
        : ($code ? "Remote endpoint returned HTTP " . $code . "." : "Remote request failed.")
    );
  }
}
