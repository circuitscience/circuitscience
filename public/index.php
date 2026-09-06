<?php
declare(strict_types=1);
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$formState = $_GET['form'] ?? '';

$reviews = [];
try {
  require_once __DIR__ . '/../app/Core/db.php';
  $pdo = getPDO();
  $stmt = $pdo->query('SELECT name, city, rating, review, created_at FROM reviews WHERE approved = 1 ORDER BY created_at DESC LIMIT 6');
  $reviews = $stmt->fetchAll() ?: [];
} catch (Throwable $e) {
  $reviews = [];
}
?>
<!doctype html>
<html lang="en-CA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Circuit Science Inc. | Electrician in West GTA, Hamilton & Niagara</title>
  <meta name="description" content="Residential, commercial and healthcare electrical services in the West GTA, Hamilton and Niagara. Licensed electrician with 40+ years of experience. ECRA/ESA 7007167.">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="https://www.circuitscience.ca/">
  <meta name="theme-color" content="#0b1724">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Circuit Science Inc. | Electrician in West GTA, Hamilton & Niagara">
  <meta property="og:description" content="Residential, commercial and healthcare electrical services in the West GTA, Hamilton and Niagara. Licensed electrician with 40+ years of experience.">
  <meta property="og:url" content="https://www.circuitscience.ca/">
  <meta property="og:image" content="https://www.circuitscience.ca/assets/og-image.png">
  <meta property="og:image:alt" content="Circuit Science Inc. electrical services in Ontario">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Circuit Science Inc. | Electrical services in Ontario">
  <meta name="twitter:description" content="Electrical repairs, upgrades and commercial work across the West GTA, Hamilton and Niagara.">
  <meta name="twitter:image" content="https://www.circuitscience.ca/assets/og-image.png">
  <link rel="stylesheet" href="styles.css">
  <script src="script.js" defer></script>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Electrician",
    "name": "Circuit Science Inc.",
    "url": "https://www.circuitscience.ca/",
    "telephone": "+1-905-616-2987",
    "email": "info@circuitscience.ca",
    "description": "Residential, commercial and healthcare electrical contractor serving the West GTA, Hamilton and Niagara regions.",
    "areaServed": [
      "West GTA",
      "Burlington",
      "Oakville",
      "Hamilton",
      "Grimsby",
      "St. Catharines",
      "Niagara Region"
    ],
    "priceRange": "$$",
    "foundingDate": "2011",
    "keywords": [
      "electrician Burlington",
      "commercial electrician Hamilton",
      "electrical panel upgrade Oakville",
      "healthcare electrical contractor Niagara",
      "Ontario electrical services"
    ],
    "address": {
      "@type": "PostalAddress",
      "addressRegion": "ON",
      "addressCountry": "CA"
    },
    "sameAs": []
  }
  </script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>

  <div class="service-strip">
    <div class="wrap service-strip__inner">
      <span>Free estimates within 20 km</span>
      <span>Free telephone troubleshooting and consultation</span>
      <a href="tel:+19056162987">Call 905-616-2987</a>
    </div>
  </div>

  <header class="site-header" id="top">
    <div class="wrap nav-wrap">
      <a class="brand" href="#top" aria-label="Circuit Science Inc. home">
        <svg class="brand-mark" viewBox="0 0 44 44" aria-hidden="true">
          <path d="M25 2 9 25h11l-2 17 17-25H24z"/>
        </svg>
        <span><strong>Circuit Science</strong><small>Inc.</small></span>
      </a>
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
        <span></span><span></span><span></span><span class="sr-only">Open menu</span>
      </button>
      <nav id="primary-nav" aria-label="Primary navigation">
        <a href="#services">Services</a>
        <a href="#about">Experience</a>
        <a href="#areas">Service area</a>
        <a class="button button--small" href="#estimate">Request an estimate</a>
      </nav>
    </div>
  </header>

  <main id="main">
    <section class="hero">
      <div class="hero-media" aria-hidden="true"></div>
      <div class="hero-shade"></div>
      <div class="wrap hero-content">
        <p class="eyebrow">Master electrician · ECRA/ESA 7007167</p>
        <h1>Electrical work backed by <em>40+ years</em> of experience.</h1>
        <p class="hero-copy">Practical, careful electrical service for homes, businesses and healthcare facilities across the West GTA, Hamilton and Niagara.</p>
        <div class="hero-actions">
          <a class="button" href="tel:+19056162987">Call Jerry</a>
          <a class="text-link" href="#estimate">Describe your project <span aria-hidden="true">→</span></a>
        </div>
        <ul class="trust-list" aria-label="Service benefits">
          <li>Licensed & insured</li>
          <li>Small jobs welcome</li>
          <li>Residential & commercial</li>
        </ul>
      </div>
    </section>

    <section class="intro section">
      <div class="wrap split-heading">
        <div>
          <p class="eyebrow eyebrow--dark">Straight answers. Sound work.</p>
          <h2>You deal directly with the electrician doing the work.</h2>
        </div>
        <div>
          <p>Circuit Science is a small Ontario electrical contractor built around experience, accountability and sensible solutions. There is no oversized sales team and no pressure to replace what can be safely repaired.</p>
          <p>Whether the job is a troublesome circuit, a service upgrade or a facility-wide lighting project, the goal is the same: understand the problem, explain the options and complete the work properly.</p>
        </div>
      </div>
    </section>

    <section class="services section" id="services">
      <div class="wrap">
        <div class="section-heading">
          <p class="eyebrow eyebrow--dark">What we do</p>
          <h2>Electrical service for the places people live and work.</h2>
        </div>
        <div class="service-grid">
          <article class="service-card service-card--light">
            <span class="service-number">01</span>
            <h3>Residential</h3>
            <p>Repairs, upgrades and new installations completed with care for your home and your schedule.</p>
            <ul>
              <li>Troubleshooting and repairs</li>
              <li>Panels and service upgrades</li>
              <li>EV-charger circuits</li>
              <li>Lighting, fans and smart devices</li>
              <li>Renovations and rewiring</li>
              <li>Hot tubs, pools and outdoor power</li>
            </ul>
          </article>
          <article class="service-card service-card--navy">
            <span class="service-number">02</span>
            <h3>Commercial</h3>
            <p>Responsive electrical support that helps facilities operate safely and keeps disruptions under control.</p>
            <ul>
              <li>Maintenance and troubleshooting</li>
              <li>LED lighting conversions</li>
              <li>Tenant improvements</li>
              <li>Panels and dedicated circuits</li>
              <li>Exterior and security lighting</li>
              <li>Equipment connections</li>
            </ul>
          </article>
          <article class="service-card service-card--amber">
            <span class="service-number">03</span>
            <h3>Healthcare</h3>
            <p>Experienced work in environments where safety, continuity and consideration for occupants matter.</p>
            <ul>
              <li>Long-term-care facilities</li>
              <li>Patient-room lighting</li>
              <li>Medical and aesthetic equipment</li>
              <li>Lighting modernization</li>
              <li>Preventive maintenance</li>
              <li>Repairs in occupied facilities</li>
            </ul>
          </article>
        </div>
      </div>
    </section>

    <section class="experience section" id="about">
      <div class="wrap experience-grid">
        <div class="experience-stat">
          <strong>40+</strong>
          <span>years of electrical experience</span>
        </div>
        <div class="experience-copy">
          <p class="eyebrow">Meet your electrician</p>
          <h2>Experience changes how a problem is seen.</h2>
          <p>Jerry Bilous is a Master Electrician with more than four decades of hands-on experience across residential, commercial and healthcare work. That history brings context to unusual faults, older systems and projects where the simplest-looking answer is not always the right one.</p>
          <p>Circuit Science uses small, efficient crews and purchases materials for the work at hand. The result is personal accountability, lower overhead and advice based on what the project actually needs.</p>
          <a class="text-link text-link--light" href="#estimate">Talk through your project <span aria-hidden="true">→</span></a>
        </div>
      </div>
    </section>

    <?php if ($reviews): ?>
    <section class="reviews section" aria-labelledby="reviews-title">
      <div class="wrap">
        <div class="section-heading">
          <p class="eyebrow eyebrow--dark">What clients say</p>
          <h2 id="reviews-title">Kind words from homeowners and businesses.</h2>
        </div>
        <div class="service-grid">
          <?php foreach ($reviews as $review): ?>
            <article class="service-card service-card--light">
              <span class="service-number">★</span>
              <h3><?php echo htmlspecialchars($review['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p><strong><?php echo htmlspecialchars($review['city'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
              <p>
                <?php for ($i = 0; $i < (int) $review['rating']; $i++): ?>★<?php endfor; ?>
              </p>
              <p>“<?php echo htmlspecialchars($review['review'], ENT_QUOTES, 'UTF-8'); ?>”</p>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <section class="process section">
      <div class="wrap">
        <div class="section-heading section-heading--compact">
          <p class="eyebrow eyebrow--dark">A straightforward process</p>
          <h2>Start with the problem—not a sales pitch.</h2>
        </div>
        <ol class="process-grid">
          <li><span>1</span><h3>Tell us what is happening</h3><p>Call or send a short description. Photographs often help establish the next step.</p></li>
          <li><span>2</span><h3>Understand your options</h3><p>We will discuss likely causes, safety concerns and whether a site visit is needed.</p></li>
          <li><span>3</span><h3>Approve the work</h3><p>You receive a clear scope or estimate before larger work moves ahead.</p></li>
        </ol>
      </div>
    </section>

    <section class="areas section" id="areas">
      <div class="wrap areas-grid">
        <div>
          <p class="eyebrow eyebrow--dark">Service area</p>
          <h2>West GTA, Hamilton and Niagara.</h2>
          <p>Based between major service regions, Circuit Science supports homeowners, businesses and facilities throughout the western Greater Toronto Area and the Niagara corridor.</p>
        </div>
        <div class="area-list" aria-label="Primary service areas">
          <a href="electrician-burlington.php">Burlington</a>
          <a href="electrician-oakville.php">Oakville</a>
          <a href="commercial-electrician-hamilton.php">Hamilton</a>
          <a href="electrician-grimsby.php">Grimsby</a>
          <a href="electrician-st-catharines.php">St. Catharines</a>
          <a href="electrical-panel-upgrade-niagara.php">Niagara Region</a>
        </div>
      </div>
    </section>

    <section class="property-care" aria-labelledby="property-care-title">
      <div class="wrap property-care__inner">
        <div class="property-care__label"><span>Electrical plus property repair</span></div>
        <div class="property-care__copy">
          <h2 id="property-care-title">And while we’re there…</h2>
          <p>Some projects need more than an electrician. Circuit Science can also take care of practical handyman and property-maintenance work—without bringing another contractor into the job.</p>
          <ul aria-label="Property maintenance services">
            <li>Drywall repairs</li><li>Painting &amp; touch-ups</li><li>Trim, doors &amp; locksets</li>
            <li>Fixture mounting</li><li>Minor plumbing repairs</li><li>Furniture assembly</li>
          </ul>
        </div>
      </div>
    </section>

    <section class="estimate section" id="estimate">
      <div class="wrap estimate-grid">
        <div class="estimate-copy">
          <p class="eyebrow">Request an estimate</p>
          <h2>What can we help you solve?</h2>
          <p>Send the basic details and Jerry will respond within two business days. If there is an immediate electrical hazard, call rather than using this form.</p>
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
          <label>Photographs or documents <span class="optional">Optional</span><input name="attachments[]" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" multiple></label>
          <p class="field-hint">Up to three JPG, PNG, WebP or PDF files; 5 MB each.</p>
          <label>Preferred contact
            <select name="contact">
              <option>Phone</option><option>Text message</option><option>Email</option>
            </select>
          </label>
          <label class="private-rate"><input type="checkbox" name="community_rate" value="Yes"><span>I would like to ask privately about reduced pricing.</span></label>
          <button class="button button--wide" type="submit">Send estimate request</button>
          <p class="form-note">Your information is used only to respond to this request.</p>
          <p class="form-status" role="status" aria-live="polite"><?php
            if ($formState === 'sent') echo 'Thank you. Your request has been sent to Jerry.';
            if ($formState === 'error') echo 'We could not send your request. Please call 905-616-2987.';
          ?></p>
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
