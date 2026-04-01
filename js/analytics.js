// Grey Wolf Analytics + Event Tracking
// Replace G-WJ744VRBSD with your GA4 Measurement ID
window.GW_ANALYTICS = {
  GA4_ID: "G-WJ744VRBSD",
  ENABLED: true
};

window.dataLayer = window.dataLayer || [];
function gtag(){ dataLayer.push(arguments); }

// Consent defaults (do not store/track until user opts in)
gtag('consent', 'default', {
  'ad_storage': 'denied',
  'analytics_storage': 'denied',
  'ad_user_data': 'denied',
  'ad_personalization': 'denied'
});

window.loadGA4 = function(){
  if (!window.GW_ANALYTICS.ENABLED) return;
  if (window.__ga4_loaded) return;
  window.__ga4_loaded = true;

  var s = document.createElement('script');
  s.async = true;
  s.src = 'https://www.googletagmanager.com/gtag/js?id=' + window.GW_ANALYTICS.GA4_ID;
  document.head.appendChild(s);

  gtag('js', new Date());
  gtag('config', window.GW_ANALYTICS.GA4_ID, { 'anonymize_ip': true });
};

window.bindGWEventTracking = function(){
  document.addEventListener('click', function(e){
    var el = e.target.closest('[data-track]');
    if (!el) return;
    var name = el.getAttribute('data-track') || 'click';
    var label = el.getAttribute('href') || (el.textContent || '').trim().slice(0, 80);
    try {
      gtag('event', name, { 'event_category': 'engagement', 'event_label': label });
    } catch(err){}
  });
};

document.addEventListener('DOMContentLoaded', function(){
  window.bindGWEventTracking();
});
