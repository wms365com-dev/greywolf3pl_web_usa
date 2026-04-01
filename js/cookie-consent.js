// Simple cookie consent banner (analytics-only)
(function(){
  function qs(sel){ return document.querySelector(sel); }
  function show(){ var b=qs('#cookieBanner'); if(b) b.classList.add('show'); }
  function hide(){ var b=qs('#cookieBanner'); if(b) b.classList.remove('show'); }

  function setConsent(granted){
    localStorage.setItem('gw_cookie_consent', granted ? 'granted' : 'denied');
    window.dataLayer = window.dataLayer || [];
    function gtag(){ dataLayer.push(arguments); }

    if (granted) {
      gtag('consent', 'update', { 'analytics_storage': 'granted' });
      if (typeof window.loadGA4 === 'function') window.loadGA4();
    } else {
      gtag('consent', 'update', { 'analytics_storage': 'denied' });
    }
    hide();
  }

  document.addEventListener('DOMContentLoaded', function(){
    var choice = localStorage.getItem('gw_cookie_consent');
    if (!choice) show();
    if (choice === 'granted' && typeof window.loadGA4 === 'function') window.loadGA4();

    var accept = qs('#cookieAccept');
    var reject = qs('#cookieReject');
    if (accept) accept.addEventListener('click', function(){ setConsent(true); });
    if (reject) reject.addEventListener('click', function(){ setConsent(false); });
  });
})();
