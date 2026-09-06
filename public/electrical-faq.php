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
  <title>Electrical FAQ | Circuit Science Inc.</title>
  <meta name="description" content="Electrical FAQ for homeowners and businesses in the West GTA, Hamilton and Niagara. Learn about common repairs, panel upgrades, troubleshooting and estimate requests.">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="https://www.circuitscience.ca/electrical-faq">
  <link rel="stylesheet" href="styles.css">
  <script src="script.js" defer></script>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
      {
        "@type": "Question",
        "name": "Do you handle residential electrical repairs?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Yes. Circuit Science provides residential electrical repairs, troubleshooting, panel work, lighting, EV circuit work and renovations across the West GTA, Hamilton and Niagara."
        }
      },
      {
        "@type": "Question",
        "name": "Can you help with commercial electrical work?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Yes. We support businesses with maintenance, lighting upgrades, tenant improvements, equipment connections and troubleshooting for occupied facilities."
        }
      },
      {
        "@type": "Question",
        "name": "What is your process for a quote?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Send us the basic details, location and your project description. Jerry will follow up to discuss the issue, possible causes and the next step, including whether a site visit is needed."
        }
      }
    ]
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
        <a href="/electrical-estimate">Estimate</a>
        <a href="/#services">Services</a>
        <a class="button button--small" href="/#estimate">Request a quote</a>
      </nav>
    </div>
  </header>

  <main id="main">
    <section class="intro section">
      <div class="wrap split-heading">
        <div>
          <p class="eyebrow eyebrow--dark">Electrical FAQ</p>
          <h1>Common questions about electrical service in Ontario.</h1>
        </div>
        <div>
          <p>Circuit Science helps homeowners, businesses and healthcare facilities with practical electrical answers. Below are some of the most common questions we hear from customers in the West GTA, Hamilton and Niagara.</p>
        </div>
      </div>
    </section>

    <section class="services section">
      <div class="wrap">
        <div class="service-grid">
          <article class="service-card service-card--light">
            <h3>Do you handle residential electrical repairs?</h3>
            <p>Yes. We handle troubleshooting, panel issues, lighting, fan installs, renovations and general electrical repair for homes.</p>
          </article>
          <article class="service-card service-card--navy">
            <h3>Can you help with commercial electrical work?</h3>
            <p>Yes. We support businesses with maintenance, lighting upgrades, dedicated circuits, tenant improvements and troubleshooting.</p>
          </article>
          <article class="service-card service-card--amber">
            <h3>How do I request an estimate?</h3>
            <p>Send the issue details, location and your preferred timing. Jerry will review the project and respond with the next step.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="estimate section">
      <div class="wrap estimate-grid">
        <div class="estimate-copy">
          <p class="eyebrow">Need help now?</p>
          <h2>Talk through the problem with Jerry.</h2>
          <p>For urgent issues, call rather than submitting a form. We can discuss safety concerns and whether a site visit is needed.</p>
          <div class="direct-contact">
            <a href="tel:+19056162987"><small>Call or text</small>905-616-2987</a>
            <a href="mailto:info@circuitscience.ca"><small>Email</small>info@circuitscience.ca</a>
          </div>
        </div>
        <div class="estimate-form">
          <a class="button button--wide" href="/electrical-estimate">Request an estimate</a>
        </div>
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
