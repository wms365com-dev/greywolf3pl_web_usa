const SHEET_NAME = 'DrayageLeads';
const SPREADSHEET_ID = '1mlCS1PypAuSQU3ep9JlTeetg3nlRXQ76_tHQjGVGfTY';

const HEADERS = [
  'tracking_id',
  'status',
  'consent_follow_up',
  'step_reached',
  'created_at',
  'updated_at',
  'last_submitted_at',
  'source_page',
  'page_title',
  'referrer',
  'utm_source',
  'utm_medium',
  'utm_campaign',
  'company_name',
  'contact_name',
  'phone',
  'email',
  'load_type',
  'reference_number',
  'secondary_reference',
  'service_date',
  'service_needed',
  'container_size',
  'unit_count',
  'ship_from_address_1',
  'ship_from_unit',
  'ship_from_city',
  'ship_from_province',
  'ship_from_postal_code',
  'ship_to_address_1',
  'ship_to_unit',
  'ship_to_city',
  'ship_to_province',
  'ship_to_postal_code',
  'notes'
];

function doGet() {
  return jsonResponse({
    ok: true,
    sheet: SHEET_NAME,
    timestamp: new Date().toISOString()
  });
}

function doPost(e) {
  try {
    var payload = parsePayload_(e);
    if (!payload.tracking_id) {
      return jsonResponse({ ok: false, error: 'Missing tracking_id' });
    }

    var sheet = getLeadSheet_();
    var headerMap = getHeaderMap_(sheet);
    var rowIndex = findRowByTrackingId_(sheet, headerMap.tracking_id, payload.tracking_id);
    var existingValues = rowIndex ? sheet.getRange(rowIndex, 1, 1, HEADERS.length).getValues()[0] : [];
    var existingMap = rowToObject_(existingValues);
    var nowIso = new Date().toISOString();

    var record = {
      tracking_id: payload.tracking_id,
      status: payload.status || existingMap.status || 'draft',
      consent_follow_up: payload.consent_follow_up || existingMap.consent_follow_up || 'no',
      step_reached: payload.step_reached || existingMap.step_reached || '',
      created_at: existingMap.created_at || payload.updated_at || nowIso,
      updated_at: payload.updated_at || nowIso,
      last_submitted_at: existingMap.last_submitted_at || '',
      source_page: payload.source_page || existingMap.source_page || '',
      page_title: payload.page_title || existingMap.page_title || '',
      referrer: payload.referrer || existingMap.referrer || '',
      utm_source: payload.utm_source || existingMap.utm_source || '',
      utm_medium: payload.utm_medium || existingMap.utm_medium || '',
      utm_campaign: payload.utm_campaign || existingMap.utm_campaign || '',
      company_name: payload.company_name || existingMap.company_name || '',
      contact_name: payload.contact_name || existingMap.contact_name || '',
      phone: payload.phone || existingMap.phone || '',
      email: payload.email || existingMap.email || '',
      load_type: payload.load_type || existingMap.load_type || '',
      reference_number: payload.reference_number || existingMap.reference_number || '',
      secondary_reference: payload.secondary_reference || existingMap.secondary_reference || '',
      service_date: payload.service_date || existingMap.service_date || '',
      service_needed: payload.service_needed || existingMap.service_needed || '',
      container_size: payload.container_size || existingMap.container_size || '',
      unit_count: payload.unit_count || existingMap.unit_count || '',
      ship_from_address_1: payload.ship_from_address_1 || existingMap.ship_from_address_1 || '',
      ship_from_unit: payload.ship_from_unit || existingMap.ship_from_unit || '',
      ship_from_city: payload.ship_from_city || existingMap.ship_from_city || '',
      ship_from_province: payload.ship_from_province || existingMap.ship_from_province || '',
      ship_from_postal_code: payload.ship_from_postal_code || existingMap.ship_from_postal_code || '',
      ship_to_address_1: payload.ship_to_address_1 || existingMap.ship_to_address_1 || '',
      ship_to_unit: payload.ship_to_unit || existingMap.ship_to_unit || '',
      ship_to_city: payload.ship_to_city || existingMap.ship_to_city || '',
      ship_to_province: payload.ship_to_province || existingMap.ship_to_province || '',
      ship_to_postal_code: payload.ship_to_postal_code || existingMap.ship_to_postal_code || '',
      notes: payload.notes || existingMap.notes || ''
    };

    if (record.status === 'submitted') {
      record.last_submitted_at = nowIso;
    }

    if (record.status === 'opt_out') {
      record.consent_follow_up = 'no';
    }

    var row = HEADERS.map(function(header) {
      return sanitizeValue_(record[header] || '');
    });

    if (rowIndex) {
      sheet.getRange(rowIndex, 1, 1, row.length).setValues([row]);
    } else {
      sheet.appendRow(row);
      rowIndex = sheet.getLastRow();
    }

    return jsonResponse({
      ok: true,
      row: rowIndex,
      tracking_id: record.tracking_id,
      status: record.status
    });
  } catch (error) {
    return jsonResponse({
      ok: false,
      error: String(error)
    });
  }
}

function getLeadSheet_() {
  var spreadsheet = SPREADSHEET_ID
    ? SpreadsheetApp.openById(SPREADSHEET_ID)
    : SpreadsheetApp.getActiveSpreadsheet();
  var sheet = spreadsheet.getSheetByName(SHEET_NAME);

  if (!sheet) {
    sheet = spreadsheet.insertSheet(SHEET_NAME);
  }

  ensureHeaders_(sheet);
  return sheet;
}

function ensureHeaders_(sheet) {
  var lastColumn = Math.max(sheet.getLastColumn(), HEADERS.length);
  var existingHeaders = sheet.getRange(1, 1, 1, lastColumn).getValues()[0];
  var needsUpdate = false;

  HEADERS.forEach(function(header, index) {
    if (existingHeaders[index] !== header) {
      needsUpdate = true;
    }
  });

  if (needsUpdate) {
    sheet.getRange(1, 1, 1, HEADERS.length).setValues([HEADERS]);
    sheet.setFrozenRows(1);
  }
}

function getHeaderMap_(sheet) {
  var headers = sheet.getRange(1, 1, 1, HEADERS.length).getValues()[0];
  var map = {};

  headers.forEach(function(header, index) {
    map[header] = index + 1;
  });

  return map;
}

function findRowByTrackingId_(sheet, trackingIdColumn, trackingId) {
  var lastRow = sheet.getLastRow();
  if (lastRow < 2 || !trackingIdColumn) {
    return 0;
  }

  var values = sheet.getRange(2, trackingIdColumn, lastRow - 1, 1).getValues();
  for (var i = 0; i < values.length; i += 1) {
    if (String(values[i][0]).trim() === String(trackingId).trim()) {
      return i + 2;
    }
  }

  return 0;
}

function rowToObject_(row) {
  var object = {};
  HEADERS.forEach(function(header, index) {
    object[header] = row[index] || '';
  });
  return object;
}

function parsePayload_(e) {
  if (!e || !e.postData || !e.postData.contents) {
    throw new Error('No POST body received');
  }

  var raw = e.postData.contents;
  return JSON.parse(raw);
}

function sanitizeValue_(value) {
  return String(value).replace(/\r?\n/g, ' ').trim();
}

function jsonResponse(data) {
  return ContentService
    .createTextOutput(JSON.stringify(data))
    .setMimeType(ContentService.MimeType.JSON);
}
