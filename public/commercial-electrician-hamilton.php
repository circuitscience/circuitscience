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
  <title>Commercial Electrician Hamilton | Circuit Science Inc.</title>
  <meta name="description" content="Commercial electrician in Hamilton, ON. Circuit Science Inc. provides maintenance, troubleshooting, lighting upgrades and tenant improvement work for businesses and facilities.">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="https://www.circuitscience.ca/commercial-electrician-hamilton">
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
        <p class="eyebrow">Commercial electrician Hamilton</p>
        <h1>Electrical support for Hamilton businesses that need practical solutions.</h1>
        <p class="hero-copy">Circuit Science Inc. helps businesses with maintenance, lighting modernizations, dedicated circuits, troubleshooting and project work that keeps operations moving without unnecessary disruption.</p>
        <div class="hero-actions">
          <a class="button" href="tel:+19056162987">Call Jerry</a>
          <a class="text-link" href="/#estimate">Request an estimate <span aria-hidden="true">→</span></a>
        </div>
      </div>
    </section>

    <section class="intro section">
      <div class="wrap split-heading">
        <div>
          <p class="eyebrow eyebrow--dark">Commercial electrical work</p>
          <h2>Responsive, experienced service for occupied spaces.</h2>
        </div>
        <div>
          <p>From tenant improvements and interior lighting to panel work and equipment connections, electrical issues in commercial settings often affect productivity and safety. A reliable contractor helps owners and managers solve the problem with minimal disruption.</p>
          <p>The approach is straightforward: evaluate the issue, explain the options and complete the work with the same care you would expect on a residential job.</p>
        </div>
      </div>
    </section>

    <section class="services section">
      <div class="wrap">
        <div class="section-heading">
          <p class="eyebrow eyebrow--dark">Hamilton service focus</p>
          <h2>Commercial electrical support built for practicality.</h2>
        </div>
        <div class="service-grid">
          <article class="service-card service-card--light">
            <span class="service-number">01</span>
            <h3>Maintenance</h3>
            <p>Routine troubleshooting and repairs to keep systems safe and operational.</p>
          </article>
          <article class="service-card service-card--navy">
            <span class="service-number">02</span>
            <h3>Lighting</h3>
            <p>Lighting upgrades, replacements and energy-conscious improvements that fit the facility.</p>
          </article>
          <article class="service-card service-card--amber">
            <span class="service-number">03</span>
            <h3>Tenant work</h3>
            <p>Electrical updates for renovations, fits and reconfigurations in occupied spaces.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="estimate section" id="estimate">
      <div class="wrap estimate-grid">
        <div class="estimate-copy">
          <p class="eyebrow">Request an estimate</p>
          <h2>Need an electrician in Hamilton?</h2>
          <p>Tell us what is happening and Jerry will follow up with the next step. We can help with both repairs and planned upgrades.</p>
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
          <label>Property type<select name="property"><option>Residential</option><option selected>Commercial</option><option>Healthcare</option></select></label>
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
