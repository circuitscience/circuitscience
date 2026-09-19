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
  <title>Electrician Burlington | Circuit Science Inc.</title>
  <meta name="description" content="Looking for a reliable electrician in Burlington, ON? Circuit Science Inc. provides residential, commercial and healthcare electrical work across Burlington and the West GTA.">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="https://www.circuitscience.ca/electrician-burlington">
  <link rel="stylesheet" href="styles.css">
  <script src="script.js" defer></script>
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
        <a href="/#services">Services</a>
        <a href="/#areas">Areas</a>
        <a class="button button--small" href="/#estimate">Request a quote</a>
      </nav>
    </div>
  </header>

  <main id="main">
    <section class="hero" style="min-height: 360px;">
      <div class="hero-media" aria-hidden="true"></div>
      <div class="hero-shade"></div>
      <div class="wrap hero-content">
        <p class="eyebrow">Electrician in Burlington, ON</p>
        <h1>Dependable electrical service for Burlington homes and businesses.</h1>
        <p class="hero-copy">Circuit Science Inc. helps homeowners, commercial properties and healthcare facilities with practical electrical repair, upgrades and troubleshooting across Burlington and nearby West GTA communities.</p>
        <div class="hero-actions">
          <a class="button" href="tel:+19056162987">Call Jerry</a>
          <a class="text-link" href="/#estimate">Request an estimate <span aria-hidden="true">→</span></a>
        </div>
      </div>
    </section>

    <section class="intro section">
      <div class="wrap split-heading">
        <div>
          <p class="eyebrow eyebrow--dark">Burlington electrical services</p>
          <h2>Clear advice and careful work from a licensed electrician.</h2>
        </div>
        <div>
          <p>Whether you are dealing with a failing circuit, an aging panel, a lighting upgrade, or a commercial electrical issue, the goal is the same: diagnose the problem correctly and complete the work efficiently.</p>
          <p>Our work is built around direct communication, safety, and practical solutions that match the needs of the property.</p>
        </div>
      </div>
    </section>

    <section class="services section">
      <div class="wrap">
        <div class="section-heading">
          <p class="eyebrow eyebrow--dark">What we do</p>
          <h2>Electrical repair and upgrades in Burlington.</h2>
        </div>
        <div class="service-grid">
          <article class="service-card service-card--light">
            <span class="service-number">01</span>
            <h3>Residential</h3>
            <p>Electrical troubleshooting, panel upgrades, lighting changes and rewiring for homes throughout Burlington.</p>
          </article>
          <article class="service-card service-card--navy">
            <span class="service-number">02</span>
            <h3>Commercial</h3>
            <p>Maintenance, tenant improvements and equipment support for offices, retail spaces and working facilities.</p>
          </article>
          <article class="service-card service-card--amber">
            <span class="service-number">03</span>
            <h3>Healthcare</h3>
            <p>Experienced electrical work for healthcare spaces where safety and reliability matter most.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="estimate section" id="estimate">
      <div class="wrap estimate-grid">
        <div class="estimate-copy">
          <p class="eyebrow">Request an estimate</p>
          <h2>Tell us about your Burlington project.</h2>
          <p>Send a few details and Jerry will respond within two business days. For active electrical hazards, call instead of using the form.</p>
          <div class="direct-contact">
            <a href="tel:+19056162987"><small>Call or text</small>905-616-2987</a>
            <a href="mailto:info@circuitscience.ca"><small>Email</small>info@circuitscience.ca</a>
          </div>
        </div>
        <form class="estimate-form" action="/contact.php" method="post">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
          <label class="website-field" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>
          <label>Full name<input name="name" autocomplete="name" required></label>
          <label>Phone<input name="phone" type="tel" autocomplete="tel" required></label>
          <label>Email<input name="email" type="email" autocomplete="email" required></label>
          <label>Property type<select name="property"><option selected>Residential</option><option>Commercial</option><option>Healthcare</option></select></label>
          <label>Preferred contact<select name="contact"><option>Phone</option><option>Text message</option><option>Email</option></select></label>
          <label>What do you need?<textarea name="details" rows="5" placeholder="Describe the issue or project, its location and preferred timing." required></textarea></label>
          <button class="button button--wide" type="submit">Send estimate request</button>
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
