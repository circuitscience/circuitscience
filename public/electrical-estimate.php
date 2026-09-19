<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!doctype html>
<html lang="en-CA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Electrical Estimate Request | Circuit Science Inc.</title>
  <meta name="description" content="Request an estimate for electrical repairs, panel upgrades, commercial work and healthcare electrical service in the West GTA, Hamilton and Niagara.">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="https://www.circuitscience.ca/electrical-estimate">
  <link rel="stylesheet" href="styles.css">
  <script src="script.js" defer></script>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "ContactPage",
    "name": "Electrical Estimate Request",
    "url": "https://www.circuitscience.ca/electrical-estimate",
    "description": "Request an estimate for residential, commercial and healthcare electrical work in Ontario.",
    "mainEntity": {
      "@type": "LocalBusiness",
      "name": "Circuit Science Inc.",
      "telephone": "+1-905-616-2987",
      "email": "info@circuitscience.ca",
      "areaServed": ["West GTA", "Hamilton", "Niagara", "Burlington", "Oakville"]
    }
  }
  </script>
</head>
<body>
  <div class="service-strip">
    <div class="wrap service-strip__inner">
      <span>Free estimates within 20 km</span>
      <span>Free telephone troubleshooting and consultation</span>
      <a href="tel:+19056162987">Call 905-616-2987</a>
    </div>
  </div>

  <header class="site-header" id="top">
    <div class="wrap nav-wrap">
      <a class="brand" href="/" aria-label="Circuit Science Inc. home">
        <svg class="brand-mark" viewBox="0 0 44 44" aria-hidden="true"><path d="M25 2 9 25h11l-2 17 17-25H24z"/></svg>
        <span><strong>Circuit Science</strong><small>Inc.</small></span>
      </a>
      <nav id="primary-nav" aria-label="Primary navigation">
        <a href="/">Home</a>
        <a href="/electrical-faq">FAQ</a>
        <a href="/#services">Services</a>
        <a class="button button--small" href="/#estimate">Request a quote</a>
      </nav>
    </div>
  </header>

  <main id="main">
    <section class="estimate section" id="estimate">
      <div class="wrap estimate-grid">
        <div class="estimate-copy">
          <p class="eyebrow">Request an estimate</p>
          <h1>What can we help you solve?</h1>
          <p>Send a few details and Jerry will respond within two business days. For active electrical hazards, call instead of using the form.</p>
          <div class="direct-contact">
            <a href="tel:+19056162987"><small>Call or text</small>905-616-2987</a>
            <a href="mailto:info@circuitscience.ca"><small>Email</small>info@circuitscience.ca</a>
          </div>
          <aside class="care-note" aria-label="Reduced pricing information">
            <strong>Community Care Rate</strong>
            <p>Reduced labour pricing may be available for people living with disabilities, lower-income seniors and customers experiencing financial hardship. Eligibility is handled privately and respectfully.</p>
            <p>If the cost of an essential safety repair is preventing you from addressing it, please tell us. We may be able to help.</p>
          </aside>
        </div>

        <form class="estimate-form" id="estimate-form" action="contact.php" method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
          <label class="website-field" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>
          <div class="field-row">
            <label>Full name<input name="name" autocomplete="name" required></label>
            <label>Phone<input name="phone" type="tel" autocomplete="tel" required></label>
          </div>
          <label>Email<input name="email" type="email" autocomplete="email" required></label>
          <fieldset>
            <legend>Property type</legend>
            <div class="choice-row">
              <label><input type="radio" name="property" value="Residential" checked><span>Residential</span></label>
              <label><input type="radio" name="property" value="Commercial"><span>Commercial</span></label>
              <label><input type="radio" name="property" value="Healthcare"><span>Healthcare</span></label>
            </div>
          </fieldset>
          <label>What do you need?<textarea name="details" rows="5" placeholder="Describe the issue or project, its location and preferred timing." required></textarea></label>
          <p class="field-hint">You can include related property repairs in the same request.</p>
          <label>Photographs or documents <span class="optional">Optional</span><input name="attachments[]" type="file" accept="image/gif,image/jpeg,image/png,image/webp,application/pdf" multiple></label>
          <p class="field-hint">Up to three GIF, JPG, PNG, WebP or PDF files; 5 MB each.</p>
          <label>Preferred contact
            <select name="contact">
              <option>Phone</option><option>Text message</option><option>Email</option>
            </select>
          </label>
          <label class="private-rate"><input type="checkbox" name="community_rate" value="Yes"><span>I would like to ask privately about reduced pricing.</span></label>
          <button class="button button--wide" type="submit">Send estimate request</button>
          <p class="form-note">Your information is used only to respond to this request.</p>
        </form>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="wrap footer-grid">
      <div class="brand brand--footer">
        <svg class="brand-mark" viewBox="0 0 44 44" aria-hidden="true"><path d="M25 2 9 25h11l-2 17 17-25H24z"/></svg>
        <span><strong>Circuit Science</strong><small>Inc.</small></span>
      </div>
      <div><strong>Electrical contractor</strong><span>ECRA/ESA 7007167</span><span>West GTA · Hamilton · Niagara</span></div>
      <div><a href="tel:+19056162987">905-616-2987</a><a href="mailto:info@circuitscience.ca">info@circuitscience.ca</a></div>
    </div>
    <div class="wrap footer-bottom"><span>© 2011–2026 Circuit Science Inc.</span><a href="#top">Back to top ↑</a></div>
  </footer>
</body>
</html>
