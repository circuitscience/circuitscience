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
  <title>Electrician Grimsby | Circuit Science Inc.</title>
  <meta name="description" content="Electrician in Grimsby, ON. Circuit Science Inc. provides residential and commercial electrical work, repairs and upgrades across Grimsby and the Niagara region.">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="https://www.circuitscience.ca/electrician-grimsby">
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
        <p class="eyebrow">Electrician in Grimsby</p>
        <h1>Electrical repair and upgrades for Grimsby homes and small businesses.</h1>
        <p class="hero-copy">Circuit Science supports Grimsby property owners with straightforward electrical service, from troubleshooting and safety repairs to upgrades that improve reliability and long-term performance.</p>
        <div class="hero-actions">
          <a class="button" href="tel:+19056162987">Call Jerry</a>
          <a class="text-link" href="/#estimate">Request an estimate <span aria-hidden="true">→</span></a>
        </div>
      </div>
    </section>

    <section class="estimate section" id="estimate">
      <div class="wrap estimate-grid">
        <div class="estimate-copy">
          <p class="eyebrow">Request an estimate</p>
          <h2>Need a Grimsby electrician?</h2>
          <p>Describe the issue and we can help determine the next step, whether it is a repair, upgrade or site inspection.</p>
          <div class="direct-contact">
            <a href="tel:+19056162987"><small>Call or text</small>905-616-2987</a>
            <a href="mailto:info@circuitscience.ca"><small>Email</small>info@circuitscience.ca</a>
          </div>
        </div>
        <form class="estimate-form" action="contact.php" method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
          <label>Full name<input name="name" autocomplete="name" required></label>
          <label>Phone<input name="phone" type="tel" autocomplete="tel" required></label>
          <label>Email<input name="email" type="email" autocomplete="email" required></label>
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
