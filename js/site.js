(function () {
  function gtagSafe(eventName, payload) {
    try {
      if (typeof window.gtag === "function") {
        window.gtag("event", eventName, payload || {});
      }
    } catch (err) {}
  }

  function getCookie(name) {
    var cookieSource = document.cookie || "";
    if (!cookieSource) return "";

    var prefix = encodeURIComponent(name) + "=";
    var parts = cookieSource.split(";");

    for (var i = 0; i < parts.length; i += 1) {
      var part = parts[i].trim();
      if (part.indexOf(prefix) === 0) {
        return decodeURIComponent(part.slice(prefix.length));
      }
    }

    return "";
  }

  function setCookie(name, value, maxAgeSeconds) {
    var cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + "; path=/; samesite=Lax";

    if (typeof maxAgeSeconds === "number") {
      cookie += "; max-age=" + Math.max(0, Math.round(maxAgeSeconds));
    }

    if (window.location.protocol === "https:") {
      cookie += "; secure";
    }

    document.cookie = cookie;
  }

  function getCurrentPageName() {
    var path = (window.location.pathname || "").split("/").pop();
    return path || "index.html";
  }

  function normalizeBaseUrl(url) {
    if (!url) return "";
    return String(url).replace(/\/+$/, "");
  }

  function getPreferredSiteBase() {
    if (window.GW_SITE_URL) {
      return normalizeBaseUrl(window.GW_SITE_URL);
    }

    if (window.location.protocol === "file:") {
      return "https://www.greywolf3pl.com";
    }

    return normalizeBaseUrl(window.location.origin || "https://www.greywolf3pl.com");
  }

  function getPreferredApiBase() {
    if (window.GW_API_URL) {
      return normalizeBaseUrl(window.GW_API_URL);
    }

    var host = (window.location.hostname || "").toLowerCase();
    if (host && (host.indexOf(".up.railway.app") !== -1 || host === "api.greywolf3pl.com")) {
      return normalizeBaseUrl(window.location.origin);
    }

    return "https://api.greywolf3pl.com";
  }

  function resolveApiUrl(path) {
    var cleanPath = (path || "").trim();
    if (!cleanPath) return getPreferredApiBase() + "/";
    if (/^https?:\/\//i.test(cleanPath)) return cleanPath;
    return getPreferredApiBase() + "/" + cleanPath.replace(/^\/+/, "");
  }

  window.GWSite = window.GWSite || {};
  window.GWSite.siteBase = getPreferredSiteBase();
  window.GWSite.apiBase = getPreferredApiBase();
  window.GWSite.resolveApiUrl = resolveApiUrl;

  function shouldSkipReturningBanner() {
    var skippedPages = [
      "thank-you.html",
      "business-card.html",
      "retailers_manager.html",
      "readme.html",
      "seo-plan.html",
      "gwnewcustomer.html"
    ];

    return skippedPages.indexOf(getCurrentPageName().toLowerCase()) !== -1;
  }

  function applyDeviceClasses() {
    var body = document.body;
    if (!body) return;

    if (!body.classList.contains("home-page") &&
        !body.classList.contains("brand-page") &&
        !body.classList.contains("faq-page")) {
      body.classList.add("legacy-page");
    }

    body.classList.remove("device-mobile", "device-tablet", "device-desktop");

    var width = window.innerWidth || document.documentElement.clientWidth || 0;
    if (width <= 760) {
      body.classList.add("device-mobile");
      body.dataset.device = "mobile";
    } else if (width <= 980) {
      body.classList.add("device-tablet");
      body.dataset.device = "tablet";
    } else {
      body.classList.add("device-desktop");
      body.dataset.device = "desktop";
    }

    if (window.matchMedia && window.matchMedia("(pointer: coarse)").matches) {
      body.classList.add("touch-input");
    } else {
      body.classList.remove("touch-input");
    }
  }

  function wireMenus() {
    var menuToggle = document.querySelector(".menu-toggle");
    var navList = document.querySelector("nav > ul");
    if (!menuToggle || !navList || menuToggle.dataset.siteBound === "true") return;

    menuToggle.dataset.siteBound = "true";
    menuToggle.addEventListener("click", function () {
      var isOpen = navList.classList.toggle("open");
      menuToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
      document.body.classList.toggle("nav-open", isOpen);
      gtagSafe("menu_toggle", { event_category: "navigation", event_label: isOpen ? "open" : "close" });
    });

    document.addEventListener("click", function (event) {
      if (!navList.classList.contains("open")) return;
      if (event.target.closest("nav") || event.target.closest(".menu-toggle")) return;
      navList.classList.remove("open");
      menuToggle.setAttribute("aria-expanded", "false");
      document.body.classList.remove("nav-open");
    });

    window.addEventListener("resize", function () {
      var width = window.innerWidth || document.documentElement.clientWidth || 0;
      if (width > 980 && navList.classList.contains("open")) {
        navList.classList.remove("open");
        menuToggle.setAttribute("aria-expanded", "false");
        document.body.classList.remove("nav-open");
      }
    });
  }

  function normalizeLegacyShell() {
    var body = document.body;
    if (!body || !body.classList.contains("legacy-page")) return;

    var header = document.querySelector("header");
    var nav = header && header.querySelector("nav");
    var navList = nav && nav.querySelector("ul");
    var logo = header && header.querySelector(".logo");

    if (logo && logo.dataset.legacyUpgraded !== "true") {
      var replacement = document.createElement("a");
      replacement.href = "index.html";
      replacement.className = "logo site-brand";
      replacement.setAttribute("aria-label", "Grey Wolf 3PL home");
      replacement.innerHTML =
        '<img class="brand-mark" src="logo_wolf_invert.png" alt="Grey Wolf 3PL logo">';
      replacement.dataset.legacyUpgraded = "true";
      logo.parentNode.replaceChild(replacement, logo);
    }

    if (navList && navList.dataset.legacyUpgraded !== "true") {
      navList.innerHTML =
        '<li class="nav-item nav-item-services"><a href="index.html#services">Services</a>' +
          '<ul>' +
            '<li><a href="warehousing.html">Warehousing &amp; Storage</a></li>' +
            '<li><a href="ecommerce.html">Ecommerce Fulfillment</a></li>' +
            '<li><a href="shipping.html">Shipping &amp; Parcel Management</a></li>' +
            '<li><a href="container.html">Container Stuffing &amp; De-Stuffing</a></li>' +
            '<li><a href="crossdocking.html">Cross-Docking &amp; Transloading</a></li>' +
            '<li><a href="drayage.html">Ontario Drayage</a></li>' +
            '<li><a href="copacking.html">Co-Packing &amp; Value-Add</a></li>' +
            '<li><a href="inventory.html">Inventory Visibility &amp; Returns</a></li>' +
            '<li><a href="compliance.html">Compliance &amp; Quality</a></li>' +
            '<li><a href="crossborder.html">Cross-Border Logistics</a></li>' +
          '</ul>' +
        '</li>' +
        '<li class="nav-item"><a href="index.html#why-grey-wolf">Why Grey Wolf</a></li>' +
        '<li class="nav-item nav-item-resources"><a href="guide.html">Resources</a>' +
          '<ul>' +
            '<li><a href="guide.html">3PL Guide</a></li>' +
            '<li><a href="faq.html">FAQ</a></li>' +
            '<li><a href="mississauga.html">Mississauga 3PL</a></li>' +
            '<li><a href="international.html">International</a></li>' +
            '<li><a href="tracking.html">Tracking</a></li>' +
            '<li><a href="delivery-appointment.html">Delivery Appointments</a></li>' +
            '<li><a href="sitemap.html">Site Map</a></li>' +
          '</ul>' +
        '</li>' +
        '<li class="nav-item"><a href="driver-help.html">Driver Help</a></li>' +
        '<li class="nav-item"><a href="index.html#contact">Contact</a></li>' +
        '<li class="nav-item nav-action nav-action-call"><a href="tel:+14164518894" class="call-btn">Call 416-451-8894</a></li>' +
        '<li class="nav-item nav-action nav-action-quote"><a href="index.html#quote-form" class="cta-btn">Request a Quote</a></li>';
      navList.dataset.legacyUpgraded = "true";
    }

    if (header) {
      header.querySelectorAll(".header-flags").forEach(function (node) {
        node.parentNode.removeChild(node);
      });
    }

    var footerParagraph = document.querySelector("footer .container p");
    if (footerParagraph && footerParagraph.dataset.legacyUpgraded !== "true") {
      footerParagraph.innerHTML =
        '&copy; <span class="js-year"></span> Grey Wolf 3PL &amp; Logistics Inc. All rights reserved. ' +
        '&bull; <a href="sitemap.html">Site Map</a> ' +
        '&bull; <a href="privacy.html">Privacy</a> ' +
        '&bull; <a href="cookie-policy.html">Cookie Policy</a>';
      footerParagraph.dataset.legacyUpgraded = "true";
    }
  }

  function getPageMeta() {
    var file = getCurrentPageName().toLowerCase();
    var rawTitle = (document.title || "")
      .replace(/\s*\|\s*Grey Wolf.*$/i, "")
      .replace(/\s*[–-]\s*Grey Wolf.*$/i, "")
      .trim();
    var pageLabel = rawTitle || humanizeName(file.replace(".html", ""));
    var serviceFiles = {
      "warehousing.html": true,
      "ecommerce.html": true,
      "shipping.html": true,
      "container.html": true,
      "crossdocking.html": true,
      "drayage.html": true,
      "copacking.html": true,
      "inventory.html": true,
      "compliance.html": true,
      "crossborder.html": true,
      "fulfillment.html": true,
      "amazon-prep-mississauga.html": true,
      "canada-warehousing.html": true,
      "ecommerce-fulfillment-gta.html": true,
      "fba-ecommerce-canada.html": true,
      "gta-crossdocking.html": true,
      "gta-warehouse.html": true,
      "ltl-ftl-shipping-gta.html": true,
      "ontario-3pl.html": true,
      "pick-and-pack-gta.html": true,
      "toronto-warehousing.html": true,
      "usa-to-canada-warehouse.html": true,
      "warehouse-near-me.html": true,
      "warehouse-rental-gta.html": true
    };
    var resourceFiles = {
      "guide.html": true,
      "faq.html": true,
      "tracking.html": true,
      "delivery-appointment.html": true,
      "inbound-tracker.php": true,
      "international.html": true,
      "sitemap.html": true
    };
    var legalFiles = {
      "privacy.html": true,
      "cookie-policy.html": true
    };

    if (serviceFiles[file]) {
      return { file: file, label: pageLabel, section: "Services", sectionHref: "index.html#services" };
    }

    if (resourceFiles[file]) {
      return { file: file, label: pageLabel, section: "Resources", sectionHref: "guide.html" };
    }

    if (legalFiles[file]) {
      return { file: file, label: pageLabel, section: "Legal", sectionHref: "privacy.html" };
    }

    if (file === "driver-help.html") {
      return { file: file, label: pageLabel, section: "Driver Help", sectionHref: "driver-help.html" };
    }

    if (file === "mississauga.html" || file.indexOf("-warehouse.html") !== -1) {
      return { file: file, label: pageLabel, section: "Locations", sectionHref: "mississauga.html" };
    }

    return { file: file, label: pageLabel, section: "", sectionHref: "" };
  }

  function setCurrentNavState() {
    var links = document.querySelectorAll("header nav a");
    if (!links.length) return;

    links.forEach(function (link) {
      link.classList.remove("is-current");
      link.removeAttribute("aria-current");
    });

    var meta = getPageMeta();
    var currentFile = meta.file;

    links.forEach(function (link) {
      var href = (link.getAttribute("href") || "").toLowerCase();
      if (href === currentFile) {
        link.classList.add("is-current");
        link.setAttribute("aria-current", "page");
      }
    });

    var sectionSelector = "";
    if (meta.section === "Services") {
      sectionSelector = 'header nav > ul > li > a[href*="#services"]';
    } else if (meta.section === "Resources") {
      sectionSelector = 'header nav > ul > li > a[href="guide.html"]';
    } else if (meta.section === "Driver Help") {
      sectionSelector = 'header nav > ul > li > a[href="driver-help.html"]';
    } else if (meta.section === "Locations") {
      sectionSelector = 'header nav > ul > li > a[href="guide.html"]';
    }

    if (sectionSelector) {
      var sectionLink = document.querySelector(sectionSelector);
      if (sectionLink) {
        sectionLink.classList.add("is-current");
      }
    }
  }

  function injectBreadcrumbs() {
    var body = document.body;
    var main = document.querySelector("main");
    if (!body || !main || body.classList.contains("home-page") || body.classList.contains("no-breadcrumbs")) return;
    if (document.querySelector(".gw-breadcrumbs")) return;

    var meta = getPageMeta();
    var wrapper = document.createElement("nav");
    wrapper.className = "gw-breadcrumbs";
    wrapper.setAttribute("aria-label", "Breadcrumb");

    var crumbs = ['<a href="index.html">Home</a>'];
    if (meta.section && meta.sectionHref) {
      crumbs.push('<a href="' + meta.sectionHref + '">' + meta.section + '</a>');
    }
    crumbs.push('<span aria-current="page">' + meta.label + '</span>');

    wrapper.innerHTML = '<div class="container"><div class="gw-breadcrumbs-trail">' + crumbs.join('<span class="gw-breadcrumb-sep" aria-hidden="true">/</span>') + '</div></div>';
    main.parentNode.insertBefore(wrapper, main);
  }

  function syncFooterCopyright() {
    var year = String(new Date().getFullYear());

    document.querySelectorAll(".js-year").forEach(function (el) {
      el.textContent = year;
    });

    document.querySelectorAll("footer").forEach(function (footer) {
      var existingCopyright = footer.querySelector(".site-copyright");
      if (existingCopyright) {
        existingCopyright.querySelectorAll(".js-year").forEach(function (el) {
          el.textContent = year;
        });
        return;
      }

      var legacyNode = null;
      footer.querySelectorAll("p, div.footer").forEach(function (node) {
        if (legacyNode) return;
        var text = (node.textContent || "").replace(/\s+/g, " ").trim();
        if (!/Grey Wolf/i.test(text)) return;
        if (!/[©]|\bAll rights reserved\b|\b20\d{2}\b/.test(text)) return;
        legacyNode = node;
      });

      if (legacyNode) {
        var html = legacyNode.innerHTML || "";

        if (!/(?:&copy;|©)/i.test(html)) {
          html = "&copy; " + html.replace(/^\s*[.\-–—•]+\s*/, "");
        }

        html = html.replace(/(?:&copy;|©)\s*20\d{2}/i, '&copy; <span class="js-year"></span>');

        if (!/js-year/.test(html)) {
          html = html.replace(/(?:&copy;|©)/i, '&copy; <span class="js-year"></span>');
        }

        legacyNode.innerHTML = html;
        legacyNode.classList.add("site-copyright");
        legacyNode.querySelectorAll(".js-year").forEach(function (el) {
          el.textContent = year;
        });
        return;
      }

      var copyrightHtml = '&copy; <span class="js-year"></span> Grey Wolf 3PL &amp; Logistics Inc. All rights reserved.';
      var footerLayout = footer.querySelector(".footer-layout");

      if (footerLayout) {
        var metaRow = document.createElement("div");
        metaRow.className = "container footer-meta-row";
        metaRow.innerHTML = '<p class="site-copyright">' + copyrightHtml + "</p>";
        footer.appendChild(metaRow);
      } else {
        var host = footer.querySelector(".container") || footer;
        var line = document.createElement("p");
        line.className = "site-copyright";
        line.innerHTML = copyrightHtml;
        host.appendChild(line);
      }

      footer.querySelectorAll(".js-year").forEach(function (el) {
        el.textContent = year;
      });
    });
  }

  function ensureHiddenInput(form, name, value) {
    var input = form.querySelector('input[name="' + name + '"]');
    if (!input) {
      input = document.createElement("input");
      input.type = "hidden";
      input.name = name;
      form.appendChild(input);
    }
    input.value = value;
  }

  function humanizeName(name) {
    return (name || "")
      .replace(/[_-]+/g, " ")
      .replace(/([a-z])([A-Z])/g, "$1 $2")
      .replace(/\s+/g, " ")
      .replace(/^\w/, function (chr) { return chr.toUpperCase(); })
      .trim();
  }

  function getFieldLabel(form, field) {
    var labels = {
      company: "Company",
      company_name: "Company",
      full_name: "Full name",
      name: "Name",
      email: "Email",
      phone: "Phone",
      company_website: "Company website",
      orders_per_month: "Orders per month",
      number_of_skus: "Number of SKUs",
      pallets_storage: "Estimated pallets for storage",
      business_info: "Business details",
      service: "Primary service",
      startDate: "Preferred start date",
      product: "Product description",
      pallets: "Approx. pallets",
      palletSize: "Pallet size / weight",
      inboundFreq: "Inbound frequency",
      outboundFreq: "Outbound frequency",
      pickPack: "Pick and pack details",
      special: "Special requirements",
      notes: "Additional notes",
      lead_magnet: "Requested resource"
    };

    if (field.id) {
      var label = form.querySelector('label[for="' + field.id + '"]');
      if (label) {
        return (label.textContent || "").replace(/\*/g, "").trim();
      }
    }

    if (field.name && labels[field.name]) {
      return labels[field.name];
    }

    if (field.getAttribute("aria-label")) {
      return field.getAttribute("aria-label").trim();
    }

    if (field.placeholder) {
      return field.placeholder.replace(/\*/g, "").trim();
    }

    return humanizeName(field.name);
  }

  function getFieldValue(field) {
    if (!field) return "";

    if (field.tagName === "SELECT") {
      var selected = field.options[field.selectedIndex];
      return selected ? String(selected.text || "").trim() : "";
    }

    if (field.type === "checkbox" || field.type === "radio") {
      return field.checked ? "Yes" : "";
    }

    return String(field.value || "").trim();
  }

  function inferFormKind(form, action) {
    if (form.getAttribute("data-form-type") === "email_capture" || form.querySelector('[name="lead_magnet"]')) {
      return "checklist_request";
    }

    if (action.indexOf("quote-submit.php") !== -1 || form.querySelector('[name="product"]')) {
      return "quote_request";
    }

    return "service_lead";
  }

  function buildMailtoDraft(form) {
    var action = form.getAttribute("action") || "";
    var formKind = inferFormKind(form, action);
    var companyField = form.querySelector('[name="company"], [name="company_name"]');
    var company = getFieldValue(companyField);
    var subject = "Grey Wolf service inquiry";

    if (formKind === "quote_request") {
      subject = "Grey Wolf quote request";
    } else if (formKind === "checklist_request") {
      subject = "Grey Wolf checklist request";
    }

    if (company) {
      subject += " - " + company;
    }

    var lines = [];
    lines.push("Grey Wolf 3PL & Logistics Inc");
    lines.push("Form: " + humanizeName(formKind));
    lines.push("Page: " + (document.title || "Grey Wolf 3PL"));
    lines.push("URL: " + (window.location.href || ""));
    lines.push("");

    Array.prototype.forEach.call(form.elements, function (field) {
      var name = field.name || "";
      var type = (field.type || "").toLowerCase();
      var value = getFieldValue(field);

      if (!name || field.disabled) return;
      if (type === "submit" || type === "button" || type === "reset" || type === "image" || type === "file") return;
      if (name === "website" || name === "form_started_at" || name === "source_page" || name === "form_type") return;
      if (type === "hidden" && name !== "lead_magnet") return;
      if (!value) return;

      var label = getFieldLabel(form, field);
      if (!label) return;

      if (field.tagName === "TEXTAREA") {
        lines.push(label + ":");
        lines.push(value);
        lines.push("");
        return;
      }

      lines.push(label + ": " + value);
    });

    return "mailto:info@greywolf3pl.com?subject=" + encodeURIComponent(subject) + "&body=" + encodeURIComponent(lines.join("\n").trim());
  }

  function wireForms() {
    var forms = document.querySelectorAll("form.contact-form, form.lead-capture-form");
    forms.forEach(function (form, index) {
      var action = form.getAttribute("action") || "";
      var page = window.location.pathname || "/";

      ensureHiddenInput(form, "source_page", page);
      ensureHiddenInput(form, "website", "");

      if (!form.querySelector('input[name="form_started_at"]')) {
        ensureHiddenInput(form, "form_started_at", new Date().toISOString());
      }

      if (action.indexOf("lead-submit.php") !== -1) {
        ensureHiddenInput(form, "form_type", form.getAttribute("data-form-type") || "service_lead");
      } else if (action.indexOf("quote-submit.php") !== -1) {
        ensureHiddenInput(form, "source_page", page);
      }

      if (action === "quote-submit.php" || action === "lead-submit.php") {
        form.setAttribute("action", resolveApiUrl(action));
        action = form.getAttribute("action") || action;
      } else if (/^https:\/\/api\.greywolf3pl\.com\//i.test(action) && getPreferredApiBase() !== "https://api.greywolf3pl.com") {
        form.setAttribute("action", resolveApiUrl(action.replace(/^https:\/\/api\.greywolf3pl\.com\//i, "")));
        action = form.getAttribute("action") || action;
      }

      if (form.dataset.formBound === "true") return;
      form.dataset.formBound = "true";

      var started = false;
      form.addEventListener("focusin", function () {
        if (started) return;
        started = true;
        gtagSafe("form_start", {
          event_category: "lead_generation",
          event_label: form.getAttribute("data-form-type") || action || ("form_" + index)
        });
      });

      form.addEventListener("submit", function (event) {
        if (typeof form.reportValidity === "function" && !form.reportValidity()) {
          return;
        }

        gtagSafe("form_submit", {
          event_category: "lead_generation",
          event_label: form.getAttribute("data-form-type") || action || ("form_" + index)
        });
      });
    });
  }

  function wireClickTracking() {
    document.addEventListener("click", function (e) {
      var link = e.target.closest("a, button");
      if (!link) return;

      var label = (link.textContent || link.getAttribute("href") || "").trim().slice(0, 120);
      var href = link.getAttribute("href") || "";

      if (href.indexOf("tel:") === 0) {
        gtagSafe("phone_click", { event_category: "contact", event_label: href });
      } else if (href.indexOf("mailto:") === 0) {
        gtagSafe("email_click", { event_category: "contact", event_label: href });
      } else if (link.classList.contains("btn") || link.classList.contains("cta-btn") || link.classList.contains("call-btn")) {
        gtagSafe("cta_click", { event_category: "engagement", event_label: label });
      }
    });
  }

  function wireScrollDepth() {
    var fired = {};
    function checkDepth() {
      var doc = document.documentElement;
      var scrollTop = window.pageYOffset || doc.scrollTop || 0;
      var scrollHeight = Math.max(doc.scrollHeight - window.innerHeight, 1);
      var pct = Math.round((scrollTop / scrollHeight) * 100);

      [25, 50, 75].forEach(function (mark) {
        if (!fired[mark] && pct >= mark) {
          fired[mark] = true;
          gtagSafe("scroll_depth", { event_category: "engagement", event_label: String(mark), value: mark });
        }
      });
    }

    window.addEventListener("scroll", checkDepth, { passive: true });
    checkDepth();
  }

  function wireExpandableCards() {
    var toggles = document.querySelectorAll(".service-card-toggle");
    if (!toggles.length) return;

    toggles.forEach(function (toggle) {
      if (toggle.dataset.siteBound === "true") return;
      toggle.dataset.siteBound = "true";

      toggle.addEventListener("click", function () {
        var card = toggle.closest(".service-card");
        if (!card) return;

        document.querySelectorAll(".service-card.expanded").forEach(function (openCard) {
          if (openCard === card) return;
          openCard.classList.remove("expanded");
          var openToggle = openCard.querySelector(".service-card-toggle");
          if (openToggle) {
            openToggle.setAttribute("aria-expanded", "false");
          }
        });

        var isExpanded = card.classList.toggle("expanded");
        toggle.setAttribute("aria-expanded", isExpanded ? "true" : "false");
      });
    });
  }

  function injectLeadPopup() {
    var body = document.body;
    if (!body || body.classList.contains("no-lead-popup")) return;
    if (window.location.pathname.indexOf("thank-you") !== -1) return;
    if (document.querySelector(".gw-lead-popup")) return;

    var dismissed = localStorage.getItem("gw_lead_popup_dismissed");
    var submitted = localStorage.getItem("gw_lead_popup_submitted");
    if (dismissed === "true" || submitted === "true") return;

    var wrapper = document.createElement("div");
    wrapper.className = "gw-lead-popup";
    wrapper.innerHTML =
      '<div class="gw-lead-popup-card">' +
        '<button type="button" class="gw-lead-popup-close" aria-label="Close">×</button>' +
        '<p class="gw-lead-popup-kicker">Stay connected</p>' +
        '<h3>Get the 3PL quote prep checklist.</h3>' +
        '<p>Leave your email and we will send a simple checklist to help you prepare for pricing and onboarding.</p>' +
        '<form class="lead-capture-form" data-form-type="email_capture" action="lead-submit.php" method="post">' +
          '<input type="hidden" name="lead_magnet" value="3PL Quote Prep Checklist">' +
          '<div class="gw-lead-popup-grid">' +
            '<input type="text" name="company" placeholder="Company name">' +
            '<input type="email" name="email" placeholder="Work email" required>' +
          '</div>' +
          '<textarea name="notes" rows="3" placeholder="Optional: what kind of logistics support are you looking for?"></textarea>' +
          '<button type="submit" class="btn btn-primary">Send me the checklist</button>' +
        '</form>' +
      '</div>';

    body.appendChild(wrapper);
    wireForms();

    setTimeout(function () {
      wrapper.classList.add("show");
      gtagSafe("lead_popup_view", { event_category: "lead_generation", event_label: window.location.pathname || "/" });
    }, 6500);

    var closeBtn = wrapper.querySelector(".gw-lead-popup-close");
    if (closeBtn) {
      closeBtn.addEventListener("click", function () {
        wrapper.classList.remove("show");
        localStorage.setItem("gw_lead_popup_dismissed", "true");
        gtagSafe("lead_popup_close", { event_category: "lead_generation", event_label: window.location.pathname || "/" });
      });
    }

    var popupForm = wrapper.querySelector("form");
    if (popupForm) {
      popupForm.addEventListener("submit", function () {
        localStorage.setItem("gw_lead_popup_submitted", "true");
      });
    }
  }

  function injectWelcomeBackBanner() {
    var body = document.body;
    if (!body || shouldSkipReturningBanner()) return;
    if (document.querySelector(".gw-returning-banner")) return;

    var cookieName = "gw_returning_visitor";
    var dismissName = "gw_returning_banner_dismissed";
    var visitMaxAge = 60 * 60 * 24 * 90;
    var dismissMaxAge = 60 * 60 * 24 * 14;
    var isReturningVisitor = getCookie(cookieName) === "1";

    setCookie(cookieName, "1", visitMaxAge);

    if (!isReturningVisitor || getCookie(dismissName) === "1") {
      return;
    }

    var header = document.querySelector("header");
    var main = document.querySelector("main");
    var quoteHref = document.getElementById("quote-form") ? "#quote-form" : "index.html#quote-form";
    var banner = document.createElement("section");

    banner.className = "gw-returning-banner";
    banner.setAttribute("aria-label", "Welcome back message");
    banner.innerHTML =
      '<div class="container gw-returning-banner-inner">' +
        '<div class="gw-returning-banner-copy">' +
          '<p class="gw-returning-banner-kicker">Welcome back</p>' +
          '<strong>Glad to see you again.</strong>' +
          '<span>Need another quote or help with storage, fulfillment or shipping support?</span>' +
        '</div>' +
        '<div class="gw-returning-banner-actions">' +
          '<a href="' + quoteHref + '" class="gw-returning-banner-link">Request a quote</a>' +
          '<button type="button" class="gw-returning-banner-dismiss" aria-label="Dismiss welcome back message">Not now</button>' +
        '</div>' +
      '</div>';

    if (header && header.parentNode) {
      header.insertAdjacentElement("afterend", banner);
    } else if (main && main.parentNode) {
      main.parentNode.insertBefore(banner, main);
    } else {
      body.insertBefore(banner, body.firstChild);
    }

    window.requestAnimationFrame(function () {
      banner.classList.add("show");
    });

    gtagSafe("returning_banner_view", {
      event_category: "personalization",
      event_label: getCurrentPageName()
    });

    var dismissButton = banner.querySelector(".gw-returning-banner-dismiss");
    if (dismissButton) {
      dismissButton.addEventListener("click", function () {
        setCookie(dismissName, "1", dismissMaxAge);
        banner.classList.remove("show");
        window.setTimeout(function () {
          if (banner.parentNode) {
            banner.parentNode.removeChild(banner);
          }
        }, 220);
      });
    }

    var ctaLink = banner.querySelector(".gw-returning-banner-link");
    if (ctaLink) {
      ctaLink.addEventListener("click", function () {
        gtagSafe("returning_banner_cta", {
          event_category: "personalization",
          event_label: getCurrentPageName()
        });
      });
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.body.classList.add("motion-ready");
    applyDeviceClasses();
    normalizeLegacyShell();
    syncFooterCopyright();
    setCurrentNavState();
    injectBreadcrumbs();
    wireMenus();
    wireExpandableCards();
    injectLeadPopup();
    wireForms();
    wireClickTracking();
    wireScrollDepth();
    injectWelcomeBackBanner();
  });

  window.addEventListener("resize", function () {
    applyDeviceClasses();
  });
})();

