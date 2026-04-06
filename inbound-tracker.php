<?php
require_once __DIR__ . "/appointment-lib.php";

$selectedDate = isset($_GET["date"]) ? gw_app_clean($_GET["date"]) : date("Y-m-d");
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $selectedDate)) {
  $selectedDate = date("Y-m-d");
}

$appointments = gw_app_load_appointments_read_locked();
gw_app_sort_by_start($appointments);

$selectedRows = array();
$confirmedCount = 0;
$pendingCount = 0;

foreach ($appointments as $appointment) {
  if (($appointment["appointment_date"] ?? "") !== $selectedDate) {
    continue;
  }

  $selectedRows[] = $appointment;
  if (($appointment["status"] ?? "") === "confirmed") {
    $confirmedCount += 1;
  }
  if (($appointment["status"] ?? "") === "pending_after_hours") {
    $pendingCount += 1;
  }
}

$slotCounts = gw_app_daily_slot_counts($appointments, $selectedDate);
$fullSlotCount = 0;
foreach ($slotCounts as $slot) {
  if ((int)$slot["count"] >= 3) {
    $fullSlotCount += 1;
  }
}

$selectedLabel = date("l, F j, Y", strtotime($selectedDate));
$previousDate = date("Y-m-d", strtotime($selectedDate . " -1 day"));
$nextDate = date("Y-m-d", strtotime($selectedDate . " +1 day"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inbound Tracker | Grey Wolf 3PL</title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="description" content="Internal inbound tracker for Grey Wolf dock appointments.">
  <link rel="canonical" href="https://www.greywolf3pl.com/inbound-tracker.php">
  <link rel="stylesheet" href="style.css?v=20260324-2">
  <link rel="icon" type="image/png" href="favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <style>
    .tracker-page {
      background: linear-gradient(180deg, #08131d 0%, #0f1d2b 26%, #eef3f7 26%, #f6f8fb 100%);
      color: #102131;
      min-height: 100vh;
    }

    .tracker-hero {
      color: #eef4fb;
      padding: 6rem 0 3.5rem;
      position: relative;
      overflow: hidden;
    }

    .tracker-hero::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        radial-gradient(circle at top right, rgba(240, 161, 74, 0.16), transparent 24%),
        radial-gradient(circle at left center, rgba(87, 119, 150, 0.22), transparent 30%);
      pointer-events: none;
    }

    .tracker-hero .container,
    .tracker-section .container {
      width: min(1180px, calc(100% - 2rem));
      position: relative;
      z-index: 1;
    }

    .tracker-hero-layout {
      display: grid;
      grid-template-columns: minmax(0, 1.1fr) minmax(280px, 0.9fr);
      gap: 1.25rem;
      align-items: start;
    }

    .tracker-hero-copy h1 {
      color: #f7fafc;
      font-family: 'Poppins', sans-serif;
      font-size: clamp(2.4rem, 5vw, 4.2rem);
      line-height: 0.98;
      letter-spacing: -0.05em;
      margin-bottom: 1rem;
    }

    .tracker-hero-copy p,
    .tracker-side-card p {
      color: #bfd0df;
      font-size: 1.02rem;
      line-height: 1.7;
    }

    .tracker-eyebrow {
      color: #f0b36e;
      font-size: 0.8rem;
      font-weight: 800;
      letter-spacing: 0.16em;
      text-transform: uppercase;
    }

    .tracker-side-card,
    .tracker-summary-card,
    .tracker-slot,
    .tracker-table-shell {
      background: rgba(255, 255, 255, 0.74);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(16, 33, 49, 0.08);
      border-radius: 26px;
      box-shadow: 0 18px 46px rgba(12, 26, 39, 0.1);
    }

    .tracker-side-card {
      padding: 1.4rem;
    }

    .tracker-side-card strong {
      color: #f0b36e;
      display: block;
      font-size: 0.82rem;
      letter-spacing: 0.12em;
      margin-bottom: 0.55rem;
      text-transform: uppercase;
    }

    .tracker-side-card h2 {
      color: #f7fafc;
      font-family: 'Poppins', sans-serif;
      font-size: 1.7rem;
      line-height: 1.05;
      margin-bottom: 0.85rem;
    }

    .tracker-side-card {
      background: rgba(10, 18, 28, 0.82);
      border-color: rgba(255, 255, 255, 0.08);
    }

    .tracker-side-meta {
      display: grid;
      gap: 0.7rem;
      margin-top: 1rem;
    }

    .tracker-side-meta span {
      align-items: center;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 18px;
      color: #edf3fa;
      display: flex;
      gap: 0.75rem;
      padding: 0.85rem 1rem;
    }

    .tracker-section {
      padding: 0 0 5rem;
    }

    .tracker-toolbar {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 1rem;
      margin-top: -1.6rem;
      position: relative;
      z-index: 2;
    }

    .tracker-toolbar form,
    .tracker-toolbar .tracker-toolbar-links {
      align-items: center;
      display: flex;
      flex-wrap: wrap;
      gap: 0.7rem;
    }

    .tracker-toolbar label {
      color: #233447;
      font-weight: 700;
    }

    .tracker-toolbar input[type="date"] {
      background: #ffffff;
      border: 1px solid rgba(16, 33, 49, 0.12);
      border-radius: 999px;
      color: #102131;
      font: inherit;
      padding: 0.9rem 1rem;
    }

    .tracker-toolbar .btn,
    .tracker-toolbar a {
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      padding: 0.95rem 1.2rem;
      text-decoration: none;
    }

    .tracker-toolbar .btn,
    .tracker-toolbar .tracker-link-primary {
      background: linear-gradient(135deg, #d86a2d 0%, #f0a14a 100%);
      color: #08131d;
    }

    .tracker-link-secondary {
      background: rgba(16, 33, 49, 0.06);
      border: 1px solid rgba(16, 33, 49, 0.08);
      color: #102131;
    }

    .tracker-summary {
      display: grid;
      gap: 1rem;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      margin: 1.5rem 0;
    }

    .tracker-summary-card {
      padding: 1.3rem;
    }

    .tracker-summary-card span {
      color: #6a7b8b;
      display: block;
      font-size: 0.8rem;
      font-weight: 800;
      letter-spacing: 0.12em;
      margin-bottom: 0.55rem;
      text-transform: uppercase;
    }

    .tracker-summary-card strong {
      color: #102131;
      display: block;
      font-family: 'Poppins', sans-serif;
      font-size: 2rem;
      line-height: 1;
      margin-bottom: 0.35rem;
    }

    .tracker-summary-card p {
      color: #5a6978;
      margin: 0;
    }

    .tracker-slot-grid {
      display: grid;
      gap: 0.85rem;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      margin-bottom: 1.5rem;
    }

    .tracker-slot {
      padding: 1rem;
    }

    .tracker-slot strong {
      color: #102131;
      display: block;
      font-size: 1rem;
      margin-bottom: 0.35rem;
    }

    .tracker-slot span {
      color: #5b6b7a;
      font-size: 0.94rem;
    }

    .tracker-slot.is-full {
      border-color: rgba(220, 92, 76, 0.34);
      box-shadow: 0 14px 28px rgba(220, 92, 76, 0.12);
    }

    .tracker-slot.is-busy {
      border-color: rgba(240, 161, 74, 0.34);
      box-shadow: 0 14px 28px rgba(240, 161, 74, 0.12);
    }

    .tracker-table-shell {
      overflow: hidden;
      padding: 1.15rem;
    }

    .tracker-table-shell h2 {
      color: #102131;
      font-family: 'Poppins', sans-serif;
      font-size: 1.8rem;
      line-height: 1.08;
      margin-bottom: 0.35rem;
    }

    .tracker-table-shell p {
      color: #5a6b79;
      margin-bottom: 1rem;
    }

    .tracker-table {
      width: 100%;
      border-collapse: collapse;
    }

    .tracker-table th,
    .tracker-table td {
      border-bottom: 1px solid rgba(16, 33, 49, 0.08);
      padding: 0.9rem 0.7rem;
      text-align: left;
      vertical-align: top;
    }

    .tracker-table th {
      color: #67798a;
      font-size: 0.82rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .tracker-table td {
      color: #152738;
    }

    .tracker-pill {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      font-size: 0.78rem;
      font-weight: 800;
      letter-spacing: 0.06em;
      padding: 0.42rem 0.7rem;
      text-transform: uppercase;
    }

    .tracker-pill.is-confirmed {
      background: rgba(71, 143, 95, 0.14);
      color: #2d7a49;
    }

    .tracker-pill.is-pending {
      background: rgba(240, 161, 74, 0.16);
      color: #a65f1e;
    }

    .tracker-empty {
      background: rgba(255, 255, 255, 0.7);
      border: 1px dashed rgba(16, 33, 49, 0.14);
      border-radius: 24px;
      color: #506272;
      padding: 1.4rem;
      text-align: center;
    }

    @media (max-width: 980px) {
      .tracker-hero {
        padding-top: 5rem;
      }

      .tracker-hero-layout,
      .tracker-summary {
        grid-template-columns: 1fr;
      }

      .tracker-table-shell {
        overflow-x: auto;
      }

      .tracker-table {
        min-width: 980px;
      }
    }
  </style>
</head>
<body class="tracker-page brand-page">
  <header>
    <div class="container">
      <a class="logo site-brand" href="index.html" aria-label="Grey Wolf 3PL home">
        <img class="brand-mark" src="logo_wolf_invert.png" alt="Grey Wolf 3PL logo">
      </a>
      <button class="menu-toggle" type="button" aria-expanded="false" aria-label="Toggle navigation">
        <i class="fas fa-bars"></i>
      </button>
      <nav>
        <ul>
          <li>
            <a href="index.html#services">Services</a>
            <ul>
              <li><a href="warehousing.html">Warehousing &amp; Storage</a></li>
              <li><a href="ecommerce.html">Ecommerce Fulfillment</a></li>
              <li><a href="shipping.html">Shipping &amp; Parcel Management</a></li>
              <li><a href="container.html">Container Stuffing &amp; De-Stuffing</a></li>
              <li><a href="crossdocking.html">Cross-Docking &amp; Transloading</a></li>
              <li><a href="drayage.html">Ontario Drayage</a></li>
              <li><a href="copacking.html">Co-Packing &amp; Value-Add</a></li>
              <li><a href="inventory.html">Inventory Visibility &amp; Returns</a></li>
              <li><a href="compliance.html">Compliance &amp; Quality</a></li>
              <li><a href="crossborder.html">Cross-Border Logistics</a></li>
            </ul>
          </li>
          <li><a href="index.html#why-grey-wolf">About</a></li>
          <li>
            <a href="guide.html">Resources</a>
            <ul>
              <li><a href="guide.html">3PL Guide</a></li>
              <li><a href="faq.html">FAQ</a></li>
              <li><a href="mississauga.html">Mississauga 3PL</a></li>
              <li><a href="international.html">International</a></li>
              <li><a href="tracking.html">Tracking</a></li>
              <li><a href="delivery-appointment.html">Delivery Appointments</a></li>
              <li><a href="sitemap.html">Site Map</a></li>
            </ul>
          </li>
          <li><a href="driver-help.html">Driver Help</a></li>
          <li><a href="index.html#contact">Contact</a></li>
          <li><a href="tel:+14164518894" class="call-btn">Call 416-451-8894</a></li>
          <li><a href="delivery-appointment.html" class="cta-btn">Book a dock</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main>
    <section class="tracker-hero">
      <div class="container tracker-hero-layout">
        <div class="tracker-hero-copy">
          <p class="tracker-eyebrow">Inbound operations tracker</p>
          <h1>See dock load, pending after-hours requests, and the dayâ€™s inbound schedule.</h1>
          <p>This tracker helps Grey Wolf operations keep appointments visible and avoid overbooking across all 3 dock doors. It is intended as an internal coordination view.</p>
        </div>
        <div class="tracker-side-card">
          <strong>Selected day</strong>
          <h2><?php echo gw_app_h($selectedLabel); ?></h2>
          <p>Confirmed standard appointments are assigned a dock door automatically. After-hours requests stay pending until the fee review is confirmed.</p>
          <div class="tracker-side-meta">
            <span><i class="fas fa-warehouse"></i> 3 dock doors available</span>
            <span><i class="fas fa-clock"></i> Standard hours: Monday-Friday, 8:30 AM to 4:00 PM</span>
            <span><i class="fas fa-envelope"></i> Notifications route to info@greywolf3pl.com</span>
          </div>
        </div>
      </div>
    </section>

    <section class="tracker-section">
      <div class="container">
        <div class="tracker-toolbar">
          <form action="inbound-tracker.php" method="get">
            <label for="tracker-date">View date</label>
            <input id="tracker-date" name="date" type="date" value="<?php echo gw_app_h($selectedDate); ?>">
            <button class="btn" type="submit">Load day</button>
          </form>
          <div class="tracker-toolbar-links">
            <a class="tracker-link-secondary" href="inbound-tracker.php?date=<?php echo gw_app_h($previousDate); ?>">Previous day</a>
            <a class="tracker-link-secondary" href="inbound-tracker.php?date=<?php echo gw_app_h($nextDate); ?>">Next day</a>
            <a class="tracker-link-primary" href="delivery-appointment.html">Book appointment</a>
          </div>
        </div>

        <div class="tracker-summary">
          <div class="tracker-summary-card">
            <span>Total inbounds</span>
            <strong><?php echo count($selectedRows); ?></strong>
            <p>Appointments on <?php echo gw_app_h($selectedLabel); ?></p>
          </div>
          <div class="tracker-summary-card">
            <span>Confirmed</span>
            <strong><?php echo $confirmedCount; ?></strong>
            <p>Dock doors assigned automatically where capacity allowed</p>
          </div>
          <div class="tracker-summary-card">
            <span>Pending after-hours</span>
            <strong><?php echo $pendingCount; ?></strong>
            <p>Requests waiting on extra-fee confirmation</p>
          </div>
          <div class="tracker-summary-card">
            <span>Full standard slots</span>
            <strong><?php echo $fullSlotCount; ?></strong>
            <p>30-minute windows already using all 3 dock doors</p>
          </div>
        </div>

        <div class="tracker-table-shell" style="margin-bottom:1.25rem;">
          <h2>Standard-hours dock load</h2>
          <p>Each tile shows how many of the 3 dock doors are already occupied in that 30-minute window.</p>
          <div class="tracker-slot-grid">
            <?php foreach ($slotCounts as $slot): ?>
              <?php
                $slotClass = "tracker-slot";
                if ((int)$slot["count"] >= 3) {
                  $slotClass .= " is-full";
                } elseif ((int)$slot["count"] > 0) {
                  $slotClass .= " is-busy";
                }
              ?>
              <div class="<?php echo $slotClass; ?>">
                <strong><?php echo gw_app_h($slot["label"]); ?></strong>
                <span><?php echo (int)$slot["count"]; ?> booked / <?php echo (int)$slot["remaining"]; ?> open</span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="tracker-table-shell">
          <h2>Inbound appointments</h2>
          <p>Use this list to check confirmed dock assignments, carrier details, references, and any pending after-hours bookings that still need confirmation.</p>

          <?php if (empty($selectedRows)): ?>
            <div class="tracker-empty">No inbound appointments are scheduled for <?php echo gw_app_h($selectedLabel); ?> yet.</div>
          <?php else: ?>
            <table class="tracker-table">
              <thead>
                <tr>
                  <th>Status</th>
                  <th>Window</th>
                  <th>Dock</th>
                  <th>Carrier</th>
                  <th>Load</th>
                  <th>Reference</th>
                  <th>Company</th>
                  <th>Contact</th>
                  <th>Pallets</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($selectedRows as $row): ?>
                  <?php
                    $rowStart = gw_app_row_start($row);
                    $rowEnd = gw_app_row_end($row);
                    $statusClass = ($row["status"] ?? "") === "confirmed" ? "tracker-pill is-confirmed" : "tracker-pill is-pending";
                  ?>
                  <tr>
                    <td><span class="<?php echo $statusClass; ?>"><?php echo gw_app_h(gw_app_status_label($row["status"] ?? "")); ?></span></td>
                    <td><?php echo $rowStart ? gw_app_h(gw_app_time_label($rowStart)) : ""; ?><?php echo $rowEnd ? " - " . gw_app_h(gw_app_time_label($rowEnd)) : ""; ?></td>
                    <td><?php echo !empty($row["dock_door"]) ? "Door " . gw_app_h($row["dock_door"]) : "TBD"; ?></td>
                    <td>
                      <strong><?php echo gw_app_h($row["carrier_name"] ?? ""); ?></strong><br>
                      <span><?php echo gw_app_h($row["equipment_id"] !== "" ? $row["equipment_id"] : "No equipment ID"); ?></span>
                    </td>
                    <td><?php echo gw_app_h($row["load_type"] ?? ""); ?><br><span><?php echo gw_app_h($row["unload_type"] !== "" ? $row["unload_type"] : "Unload type not set"); ?></span></td>
                    <td>
                      <strong><?php echo gw_app_h($row["reference_number"] ?? ""); ?></strong><br>
                      <span><?php echo gw_app_h($row["secondary_reference"] !== "" ? $row["secondary_reference"] : "No secondary ref"); ?></span>
                    </td>
                    <td><?php echo gw_app_h($row["company_name"] ?? ""); ?></td>
                    <td>
                      <strong><?php echo gw_app_h($row["contact_name"] ?? ""); ?></strong><br>
                      <a href="mailto:<?php echo gw_app_h($row["contact_email"] ?? ""); ?>"><?php echo gw_app_h($row["contact_email"] ?? ""); ?></a><br>
                      <a href="tel:<?php echo gw_app_h(preg_replace("/[^0-9+]/", "", $row["contact_phone"] ?? "")); ?>"><?php echo gw_app_h($row["contact_phone"] ?? ""); ?></a>
                    </td>
                    <td><?php echo gw_app_h($row["pallet_count"] !== "" ? $row["pallet_count"] : "â€”"); ?><br><span><?php echo gw_app_h($row["piece_count"] !== "" ? $row["piece_count"] . " pcs" : ""); ?></span></td>
                    <td><?php echo nl2br(gw_app_h($row["notes"] !== "" ? $row["notes"] : "â€”")); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <div class="container footer-layout">
      <div>
        <p class="footer-brand">Grey Wolf 3PL &amp; Logistics Inc</p>
        <p class="footer-copy">Dock scheduling, inbound visibility and delivery coordination from Mississauga, Ontario.</p>
      </div>
      <div class="footer-links">
        <a href="delivery-appointment.html">Book a dock</a>
        <a href="warehousing.html">Warehousing</a>
        <a href="tracking.html">Tracking</a>
        <a href="privacy.html">Privacy</a>
        <a href="cookie-policy.html">Cookies</a>
      </div>
    </div>
  </footer>

  <script src="js/site.js?v=20260405-1"></script>
</body>
</html>
