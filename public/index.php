<?php require __DIR__ . '/../php/guard.php'; publicPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BloodLink — Blood Donation, Inventory & Availability Mapping</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="manifest" href="manifest.json">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="BloodLink">
<link rel="apple-touch-icon" href="images/logo.png">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  /* Interactive Blood Map & Search Modal */
  .blood-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.78);
    backdrop-filter: blur(6px);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    opacity: 0;
    transition: opacity 0.25s ease;
  }
  .blood-modal-overlay.open {
    display: flex;
    opacity: 1;
  }
  .blood-modal-container {
    background: #fff;
    width: 100%;
    max-width: 1060px;
    max-height: 92vh;
    border-radius: 20px;
    box-shadow: 0 25px 60px -15px rgba(0,0,0,0.4);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: modalSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
  }
  @keyframes modalSlideUp {
    from { transform: translateY(24px) scale(0.97); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
  }
  .blood-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 24px;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
  }
  .blood-modal-header h2 {
    font-size: 19px;
    font-weight: 800;
    color: #111827;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .blood-modal-body {
    padding: 20px 24px;
    overflow-y: auto;
    flex: 1;
  }
  .modal-chips-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-top: 12px;
  }
  .modal-chip {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
    padding: 5px 12px;
    border-radius: 18px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  .modal-chip:hover, .modal-chip.active {
    background: #c0152e;
    color: #fff;
    border-color: #c0152e;
    box-shadow: 0 2px 6px rgba(192, 21, 46, 0.25);
  }
</style>
</head>
<body>
<div class="hero-luminous">
  <!-- Red Ambient Glow Orbs -->
  <div class="hero-glow-orb hero-glow-1"></div>
  <div class="hero-glow-orb hero-glow-2"></div>
  <div class="hero-glow-orb hero-glow-3"></div>

  <!-- Subtle Floating Erythrocytes (Bokeh Red Blood Cells) -->
  <svg class="rbc-bokeh rbc-1" viewBox="0 0 100 60">
    <ellipse cx="50" cy="30" rx="46" ry="24" fill="url(#rbcGrad)" />
    <ellipse cx="50" cy="30" rx="24" ry="12" fill="#2d0309" opacity="0.6" />
  </svg>
  <svg class="rbc-bokeh rbc-2" viewBox="0 0 100 60">
    <ellipse cx="50" cy="30" rx="46" ry="24" fill="url(#rbcGrad)" />
    <ellipse cx="50" cy="30" rx="24" ry="12" fill="#2d0309" opacity="0.6" />
  </svg>
  <svg class="rbc-bokeh rbc-3" viewBox="0 0 100 60">
    <ellipse cx="50" cy="30" rx="46" ry="24" fill="url(#rbcGrad)" />
    <ellipse cx="50" cy="30" rx="24" ry="12" fill="#2d0309" opacity="0.6" />
  </svg>
  <svg class="rbc-bokeh rbc-4" viewBox="0 0 100 60">
    <ellipse cx="50" cy="30" rx="46" ry="24" fill="url(#rbcGrad)" />
    <ellipse cx="50" cy="30" rx="24" ry="12" fill="#2d0309" opacity="0.6" />
  </svg>

  <!-- SVG Global Gradients for Visuals -->
  <svg width="0" height="0" style="position:absolute; pointer-events:none;">
    <defs>
      <linearGradient id="rbcGrad" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#ff3355" stop-opacity="0.8" />
        <stop offset="100%" stop-color="#700918" stop-opacity="0.9" />
      </linearGradient>
    </defs>
  </svg>

  <!-- Top Navigation Bar -->
  <nav class="hero-nav">
    <div class="hero-nav-left">
      <a href="index.php" class="hero-nav-brand">
        <img src="images/logo.png" alt="BloodLink Logo" class="hero-nav-logo">
        <span class="hero-nav-title">BloodLink</span>
      </a>
      <span class="hero-nav-badge">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.6"><polyline points="20 6 9 17 4 12"></polyline></svg>
        Trusted &bull; Secure &bull; Life-Saving
      </span>
    </div>

    <div class="hero-nav-right">
      <a href="#" onclick="openMapModal('map'); return false;" class="hero-nav-link">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
        Live Map
      </a>
      <a href="download.php" target="_blank" class="hero-nav-link">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
        Mobile App
      </a>
      <a href="login.php" class="hero-portal-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        Portal Login &rarr;
      </a>
    </div>
  </nav>

  <!-- Hero Content Wrap -->
  <div class="hero-content-wrap">
    <!-- 3-Column Stage (Droplet on Left, Center Hero, Constellation on Right) -->
    <div class="hero-stage">
      <!-- Left Column: 3D Glowing Glass Blood Droplet with Cross & Orbital Rings -->
      <div class="hero-visual-left">
        <svg viewBox="0 0 260 320" class="glass-droplet-container">
          <defs>
            <radialGradient id="dropFluid" cx="42%" cy="56%" r="62%">
              <stop offset="0%" stop-color="#ff3b5c" stop-opacity="0.95" />
              <stop offset="40%" stop-color="#c0152e" stop-opacity="0.92" />
              <stop offset="80%" stop-color="#700918" stop-opacity="0.96" />
              <stop offset="100%" stop-color="#3d030c" stop-opacity="0.98" />
            </radialGradient>
            <radialGradient id="dropAura" cx="50%" cy="60%" r="50%">
              <stop offset="0%" stop-color="#ff1a40" stop-opacity="0.45" />
              <stop offset="100%" stop-color="#ff1a40" stop-opacity="0" />
            </radialGradient>
            <linearGradient id="specularRim" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#ffffff" stop-opacity="0.9" />
              <stop offset="25%" stop-color="#ffffff" stop-opacity="0.3" />
              <stop offset="70%" stop-color="#ff6b81" stop-opacity="0.15" />
              <stop offset="100%" stop-color="#ffffff" stop-opacity="0.5" />
            </linearGradient>
            <linearGradient id="glassGloss" x1="0%" y1="0%" x2="100%" y2="50%">
              <stop offset="0%" stop-color="#ffffff" stop-opacity="0.85" />
              <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
            </linearGradient>
            <linearGradient id="ringGrad1" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#ff3355" stop-opacity="0" />
              <stop offset="50%" stop-color="#ffffff" stop-opacity="0.85" />
              <stop offset="100%" stop-color="#ff3355" stop-opacity="0" />
            </linearGradient>
            <linearGradient id="ringGrad2" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#ff1a40" stop-opacity="0" />
              <stop offset="40%" stop-color="#ff4d6d" stop-opacity="0.75" />
              <stop offset="100%" stop-color="#ff1a40" stop-opacity="0" />
            </linearGradient>
            <filter id="crossGlow" x="-20%" y="-20%" width="140%" height="140%">
              <feGaussianBlur stdDeviation="3" result="blur" />
              <feComposite in="SourceGraphic" in2="blur" operator="over" />
            </filter>
          </defs>
          <!-- Ambient Glow Behind Drop -->
          <circle cx="130" cy="180" r="105" fill="url(#dropAura)" />
          <!-- Back Orbital Ring -->
          <ellipse cx="130" cy="185" rx="115" ry="42" transform="rotate(-24 130 185)" fill="none" stroke="url(#ringGrad2)" stroke-width="1.8" stroke-dasharray="160 80" />
          <!-- Main 3D Glass Drop Body -->
          <path d="M 130 35 C 130 35 210 135 210 195 C 210 240 174 275 130 275 C 86 275 50 240 50 195 C 50 135 130 35 130 35 Z" fill="url(#dropFluid)" stroke="url(#specularRim)" stroke-width="2.5" />
          <!-- Internal Liquid Depth Contour -->
          <path d="M 130 55 C 130 55 198 142 198 195 C 198 232 168 262 130 262 C 92 262 62 232 62 195 C 62 142 130 55 130 55 Z" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="1.2" />
          <!-- Center Glowing Medical Cross -->
          <g filter="url(#crossGlow)">
            <path d="M 121 162 H 139 V 178 H 155 V 194 H 139 V 210 H 121 V 194 H 105 V 178 H 121 Z" fill="#ffffff" />
          </g>
          <!-- Glass Specular Highlight Curve (Upper Left) -->
          <path d="M 128 50 C 128 50 82 125 78 175 C 75 210 92 235 92 235 C 92 235 76 200 80 170 C 85 130 126 54 128 50 Z" fill="url(#glassGloss)" opacity="0.85" />
          <!-- Glass Top Specular Dot -->
          <circle cx="130" cy="42" r="3" fill="#ffffff" opacity="0.9" />
          <!-- Front Orbital Ring -->
          <ellipse cx="130" cy="185" rx="122" ry="38" transform="rotate(32 130 185)" fill="none" stroke="url(#ringGrad1)" stroke-width="2" />
          <!-- Orbital Light Sparkle Nodes -->
          <circle cx="218" cy="154" r="3.5" fill="#ffffff" filter="url(#crossGlow)" />
          <circle cx="44" cy="212" r="2.5" fill="#ff758c" />
        </svg>
      </div>

      <!-- Center Column: EKG Badge, Headline, Subtext, Action Buttons -->
      <div class="hero-center-col">
        <!-- EKG Pulse Pill -->
        <div class="hero-ekg-pill">
          <svg class="hero-ekg-svg" viewBox="0 0 32 14">
            <path d="M 0 7 H 7 L 10 1 L 13 13 L 17 3 L 20 10 L 23 7 H 32" />
          </svg>
          Blood Donation. A Stronger Tomorrow.
        </div>

        <!-- Main Headline -->
        <h1 class="hero-headline">
          Find blood. Give blood.<br>
          <span class="hero-glow-title">Save lives.</span>
        </h1>

        <!-- Subtext Description -->
        <p class="hero-subtext">
          BloodLink connects Hospital Staff, Administrators, Donors, and Patients in one secure Philippine healthcare platform. Real-time inventory tracking, verified donor screening, and cold-chain compliance.
        </p>

        <!-- 3 Action Pills -->
        <div class="hero-actions-row">
          <a href="login.php" class="btn-hero-primary">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 3h6v6"></path><path d="M10 14L21 3"></path><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path></svg>
            Access Official Portal
          </a>
          <button type="button" onclick="openMapModal('search')" class="btn-hero-glass">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            Search Blood Inventory
          </button>
          <button type="button" onclick="openMapModal('map')" class="btn-hero-glass">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>
            Live Philippines Map
          </button>
        </div>
      </div>

      <!-- Right Column: Stylized Philippines Constellation Network Map -->
      <div class="hero-visual-right">
        <svg viewBox="0 0 260 340" class="ph-constellation-container">
          <defs>
            <radialGradient id="beaconGlow" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stop-color="#ff3355" stop-opacity="1" />
              <stop offset="40%" stop-color="#ff3355" stop-opacity="0.4" />
              <stop offset="100%" stop-color="#ff3355" stop-opacity="0" />
            </radialGradient>
            <filter id="starGlow" x="-50%" y="-50%" width="200%" height="200%">
              <feGaussianBlur stdDeviation="2" result="blur" />
              <feComposite in="SourceGraphic" in2="blur" operator="over" />
            </filter>
          </defs>

          <!-- Interconnecting Network Lines -->
          <g stroke="#ff3355" stroke-opacity="0.32" stroke-width="1.2" stroke-dasharray="2 3">
            <!-- Luzon lines -->
            <line x1="130" y1="52" x2="162" y2="78" />
            <line x1="162" y1="78" x2="152" y2="98" />
            <line x1="152" y1="98" x2="124" y2="114" />
            <line x1="124" y1="114" x2="116" y2="138" />
            <line x1="116" y1="138" x2="132" y2="162" />
            <line x1="132" y1="162" x2="158" y2="178" />

            <!-- Palawan line -->
            <line x1="116" y1="138" x2="74" y2="202" />
            <line x1="74" y1="202" x2="52" y2="236" />

            <!-- Visayas lines -->
            <line x1="132" y1="162" x2="148" y2="198" />
            <line x1="148" y1="198" x2="174" y2="204" />
            <line x1="174" y1="204" x2="188" y2="188" />
            <line x1="148" y1="198" x2="138" y2="218" />

            <!-- Mindanao lines -->
            <line x1="174" y1="204" x2="176" y2="252" />
            <line x1="176" y1="252" x2="152" y2="260" />
            <line x1="152" y1="260" x2="114" y2="284" />
            <line x1="176" y1="252" x2="188" y2="294" />
            <line x1="188" y1="294" x2="168" y2="318" />
            <line x1="152" y1="260" x2="168" y2="318" />
          </g>

          <!-- Constellation Cluster Nodes (Philippines Archipelago) -->
          <!-- Luzon -->
          <circle cx="130" cy="52" r="3" fill="#ff758c" />
          <!-- San Mariano District Hospital (Center Beacon) -->
          <circle cx="162" cy="78" r="16" fill="url(#beaconGlow)">
            <animate attributeName="r" values="8;22;8" dur="3s" repeatCount="indefinite" />
            <animate attributeName="opacity" values="0.8;0.1;0.8" dur="3s" repeatCount="indefinite" />
          </circle>
          <circle cx="162" cy="78" r="4.5" fill="#ffffff" filter="url(#starGlow)" />
          <circle cx="152" cy="98" r="3" fill="#ff4d6d" />
          <circle cx="124" cy="114" r="3.2" fill="#ff758c" />
          <circle cx="116" cy="138" r="3.8" fill="#ffffff" filter="url(#starGlow)" />
          <circle cx="132" cy="162" r="3" fill="#ff4d6d" />
          <circle cx="158" cy="178" r="3.2" fill="#ff758c" />

          <!-- Palawan -->
          <circle cx="74" cy="202" r="2.8" fill="#ff4d6d" />
          <circle cx="52" cy="236" r="3" fill="#ff758c" />

          <!-- Visayas -->
          <circle cx="148" cy="198" r="3.5" fill="#ffffff" filter="url(#starGlow)" />
          <circle cx="174" cy="204" r="4" fill="#ffffff" filter="url(#starGlow)" />
          <circle cx="188" cy="188" r="2.8" fill="#ff758c" />
          <circle cx="138" cy="218" r="2.8" fill="#ff4d6d" />

          <!-- Mindanao -->
          <circle cx="176" cy="252" r="3.5" fill="#ff4d6d" />
          <circle cx="152" cy="260" r="3.8" fill="#ffffff" filter="url(#starGlow)" />
          <circle cx="114" cy="284" r="3" fill="#ff758c" />
          <circle cx="188" cy="294" r="4" fill="#ffffff" filter="url(#starGlow)" />
          <circle cx="168" cy="318" r="3.2" fill="#ff4d6d" />

          <!-- Faint Background Star Dust -->
          <circle cx="198" cy="64" r="1.5" fill="#ffffff" opacity="0.5" />
          <circle cx="182" cy="132" r="1.5" fill="#ffffff" opacity="0.6" />
          <circle cx="94" cy="88" r="1.5" fill="#ffffff" opacity="0.4" />
          <circle cx="98" cy="274" r="1.5" fill="#ffffff" opacity="0.5" />
          <circle cx="214" cy="242" r="1.5" fill="#ffffff" opacity="0.6" />
        </svg>
      </div>
    </div>

    <!-- Floating Glass Card for Blood Donors & Patients -->
    <div class="hero-donor-card">
      <div class="hero-donor-left">
        <div class="hero-blood-bag-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
            <rect x="5" y="4" width="14" height="17" rx="3" fill="#ff1a40" fill-opacity="0.3" stroke="#ff3355" stroke-width="1.8"></rect>
            <circle cx="12" cy="13" r="3.5" fill="#ffffff" />
            <path d="M12 2v2M9 4h6" stroke="#ff3355" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </div>
        <div class="hero-donor-info">
          <h4>Blood Donors &amp; Patients</h4>
          <p>Step into a safer, more connected healthcare community. Donate or request blood with just a few clicks.</p>
        </div>
      </div>
      <div>
        <a href="download.php" target="_blank" class="btn-app-download">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          Download Android App (v1.0) &rsaquo;
        </a>
      </div>
    </div>

    <!-- Bottom Trust Bar -->
    <div class="hero-trust-bar">
      <span class="trust-item">
        <svg class="trust-icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
        Secure &amp; Encrypted
      </span>
      <span>&bull;</span>
      <span class="trust-item">
        <svg class="trust-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="4"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
        Partnered with PH Hospitals
      </span>
      <span>&bull;</span>
      <span class="trust-item">
        <svg class="trust-icon" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        For a Healthier Philippines
      </span>
    </div>
  </div>
</div>

<!-- INTERACTIVE BLOOD MAP & SEARCH MODAL VIEWER -->
<div id="blood-viewer-modal" class="blood-modal-overlay">
  <div class="blood-modal-container">
    <div class="blood-modal-header">
      <h2 id="modal-viewer-title">
        <span>📍</span> Live Blood Availability Map (Philippines)
      </h2>
      <div style="display:flex; align-items:center; gap:10px;">
        <a id="modal-fullscreen-link" href="map.php" target="_blank" class="btn ghost slim" style="font-size:12px; padding:6px 12px; text-decoration:none;">
          ↗️ Full Page
        </a>
        <button type="button" class="btn ghost slim" onclick="closeMapModal()" style="font-size:18px; line-height:1; padding:6px 12px; border-radius:8px; cursor:pointer;" aria-label="Close">
          &times;
        </button>
      </div>
    </div>

    <div class="blood-modal-body">
      <!-- Filter controls -->
      <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:14px; padding:16px 18px; margin-bottom:18px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <label for="modal-blood-filter" style="font-size:13.5px; font-weight:700; color:#1f2937;">
              Filter Blood Type:
            </label>
            <select id="modal-blood-filter" style="padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; font-weight:600; background:#fff; width:180px;">
              <option value="">All Types (Available Stock)</option>
              <option value="A+">A+ (A Positive)</option>
              <option value="A-">A- (A Negative)</option>
              <option value="B+">B+ (B Positive)</option>
              <option value="B-">B- (B Negative)</option>
              <option value="AB+">AB+ (AB Positive)</option>
              <option value="AB-">AB- (AB Negative)</option>
              <option value="O+">O+ (O Positive)</option>
              <option value="O-">O- (O Negative)</option>
            </select>
          </div>
          <div style="font-size:13px; color:#4b5563;">
            <span id="modal-centers-count" style="font-weight:700; color:#c0152e;">0</span> centers with verified stock
          </div>
        </div>

        <div class="modal-chips-bar">
          <span style="font-size:12px; font-weight:600; color:#6b7280;">Quick Select:</span>
          <button type="button" class="modal-chip active" data-type="">All</button>
          <button type="button" class="modal-chip" data-type="A+">A+</button>
          <button type="button" class="modal-chip" data-type="A-">A-</button>
          <button type="button" class="modal-chip" data-type="B+">B+</button>
          <button type="button" class="modal-chip" data-type="B-">B-</button>
          <button type="button" class="modal-chip" data-type="AB+">AB+</button>
          <button type="button" class="modal-chip" data-type="AB-">AB-</button>
          <button type="button" class="modal-chip" data-type="O+">O+</button>
          <button type="button" class="modal-chip" data-type="O-">O-</button>
        </div>
      </div>

      <!-- Leaflet Map Container -->
      <div style="position:relative; margin-bottom:20px;">
        <div id="modal-map" style="height:440px; border-radius:14px; border:2px solid #e5e7eb; overflow:hidden;"></div>
        <div style="position:absolute; bottom:12px; left:12px; z-index:1000; background:rgba(255,255,255,0.94); padding:6px 12px; border-radius:8px; font-size:11px; color:#374151; box-shadow:0 2px 8px rgba(0,0,0,0.12); border:1px solid #e5e7eb;">
          💡 <b>Tip:</b> Click any hospital pin to view details and proceed directly to Login or Register!
        </div>
      </div>

      <!-- Hospital Cards List -->
      <div>
        <h3 style="font-size:16px; font-weight:800; color:#111827; margin-bottom:12px;">
          🏥 Verified Partner Blood Centers
        </h3>
        <div id="modal-hospital-list" class="grid cols-3" style="gap:14px;"></div>
      </div>
    </div>
  </div>
</div>

<script src="js/api.js?v=<?= time() ?>"></script>
<script src="js/common.js?v=<?= time() ?>"></script>
<script src="js/map.js?v=<?= time() ?>"></script>
<script>
let allModalLocations = [];
let modalDataLoaded = false;

async function loadModalData() {
  if (modalDataLoaded && allModalLocations.length > 0) return;
  try {
    const data = await API.get('/api/blood/map');
    allModalLocations = data.locations || [];
    modalDataLoaded = true;
  } catch (err) {
    console.error('Failed to load blood locations', err);
  }
}

async function openMapModal(mode) {
  const modal = document.getElementById('blood-viewer-modal');
  const title = document.getElementById('modal-viewer-title');
  const fullLink = document.getElementById('modal-fullscreen-link');
  const filter = document.getElementById('modal-blood-filter');

  if (mode === 'search') {
    title.innerHTML = '<span>🔍</span> Live Blood Search & Hospital Availability';
    fullLink.href = 'search.php';
  } else {
    title.innerHTML = '<span>📍</span> Live Blood Availability Map (Philippines)';
    fullLink.href = 'map.php';
  }

  modal.classList.add('open');
  document.body.style.overflow = 'hidden';

  await loadModalData();
  await BloodMap.create('modal-map');

  renderModalAvailability();

  setTimeout(() => {
    if (BloodMap.map) BloodMap.map.invalidateSize();
    if (mode === 'search') filter.focus();
  }, 250);
}

function closeMapModal() {
  const modal = document.getElementById('blood-viewer-modal');
  modal.classList.remove('open');
  document.body.style.overflow = '';
}

// Close on backdrop click
document.getElementById('blood-viewer-modal').addEventListener('click', (e) => {
  if (e.target.id === 'blood-viewer-modal') closeMapModal();
});

// Close on Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeMapModal();
});

function renderModalAvailability() {
  const filterType = document.getElementById('modal-blood-filter').value;
  const listEl = document.getElementById('modal-hospital-list');
  const countEl = document.getElementById('modal-centers-count');

  // 100% Accuracy: Only show hospitals that currently have verified available units > 0
  const filtered = allModalLocations.filter(loc => {
    const validStocks = Object.entries(loc.blood || {}).filter(([bt, units]) => Number(units) > 0);
    if (filterType) {
      return Number(loc.blood[filterType] || 0) > 0;
    }
    return validStocks.length > 0;
  });

  if (countEl) countEl.textContent = filtered.length;
  BloodMap.render(filtered, filterType || null);

  // Update quick chip active states
  document.querySelectorAll('.modal-chip').forEach(btn => {
    if (btn.dataset.type === filterType) btn.classList.add('active');
    else btn.classList.remove('active');
  });

  if (filtered.length === 0) {
    listEl.innerHTML = `<div class="card" style="grid-column: 1 / -1; text-align:center; padding:32px 16px;">
      <h4 style="color:#6b7280; font-size:15px; margin-bottom:4px;">No Centers with Stock Available</h4>
      <p class="muted small">Currently, no blood center has ${filterType ? `<b>${UI.esc(filterType)}</b>` : 'available'} blood in stock. Please try another blood type or check back later.</p>
    </div>`;
    return;
  }

  listEl.innerHTML = filtered.map(loc => {
    const validChips = Object.entries(loc.blood || {})
      .filter(([bt, units]) => Number(units) > 0)
      .map(([bt, units]) => {
        const isHighlight = filterType === bt;
        return `<span class="chip" style="${isHighlight ? 'outline:2px solid var(--red); background:#c0152e; color:#fff;' : ''}">${UI.esc(bt)} &mdash; <b>${units}</b> unit(s)</span>`;
      })
      .join('');

    const locNameEnc = encodeURIComponent(loc.location_name || '');
    return `<div class="card" style="display:flex; flex-direction:column; justify-content:space-between; border-left:4px solid #c0152e; padding:14px;">
      <div>
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px; margin-bottom:4px;">
          <h4 style="margin:0; font-size:14.5px; font-weight:700; color:#111827;">🏥 ${UI.esc(loc.location_name)}</h4>
          <button type="button" class="btn ghost slim" onclick="modalFocusHospital(${loc.location_id})" style="font-size:10.5px; padding:2px 7px; flex-shrink:0;">
            📍 Focus
          </button>
        </div>
        <div class="small muted" style="margin-bottom:6px; font-size:11.5px; line-height:1.4;">
          📍 ${UI.esc(loc.address)}<br>📞 ${UI.esc(loc.contact_number || '-')}
        </div>
        <div class="blood-line" style="margin-bottom:10px;">${validChips}</div>
      </div>
      <div style="display:flex; gap:6px; margin-top:8px; border-top:1px solid #f3f4f6; padding-top:8px;">
        <a href="login.php?location_id=${loc.location_id}&location_name=${locNameEnc}" class="btn slim" style="flex:1; text-align:center; font-size:11px; text-decoration:none; padding:6px;">
          🔑 Log In
        </a>
        <a href="register.php?location_id=${loc.location_id}&location_name=${locNameEnc}" class="btn ghost slim" style="flex:1; text-align:center; font-size:11px; text-decoration:none; padding:6px;">
          📝 Register
        </a>
      </div>
    </div>`;
  }).join('');
}

function modalFocusHospital(locationId) {
  const mapContainer = document.getElementById('modal-map');
  if (mapContainer) {
    mapContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => {
      BloodMap.openLocation(locationId);
    }, 300);
  }
}

// Filter dropdown change
document.getElementById('modal-blood-filter').addEventListener('change', renderModalAvailability);

// Quick chips click
document.querySelectorAll('.modal-chip').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('modal-blood-filter').value = btn.dataset.type;
    renderModalAvailability();
  });
});
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('sw.js').catch(() => {});
  });
}
</script>
</body>
</html>
