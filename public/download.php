<?php
if (isset($_GET['dl']) || (isset($_GET['action']) && $_GET['action'] === 'download')) {
    $apkPath = __DIR__ . '/download/Bloodlink_v1.0.8.apk';
    if (!file_exists($apkPath)) {
        $apkPath = __DIR__ . '/download/Bloodlink_apk_v1.0.apk';
    }
    if (file_exists($apkPath)) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.android.package-archive');
        header('Content-Disposition: attachment; filename="Bloodlink_apk v1.0.apk"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($apkPath));
        readfile($apkPath);
        exit;
    }
}
require __DIR__ . '/../php/guard.php';
publicPage();

// Calculate base domain for QR codes
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'bloodlinkss.onrender.com';
$baseUrl = $scheme . '://' . $host;
$androidTargetUrl = $baseUrl . '/public/download.php?dl=1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Download BloodLink Mobile App — Android APK Direct</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="manifest" href="manifest.json">
<meta name="mobile-web-app-capable" content="yes">
<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
<style>
  .guide-container {
    max-width: 820px;
    margin: 0 auto;
    padding: 24px 16px 60px;
  }
  .download-hero {
    text-align: center;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #0f172a;
    padding: 38px 24px;
    border-radius: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    margin-bottom: 28px;
  }
  .download-hero h1 {
    font-size: 28px;
    font-weight: 800;
    margin: 0 0 10px 0;
    letter-spacing: -0.5px;
    color: #0f172a;
  }
  .download-hero p {
    font-size: 15px;
    color: #64748b;
    max-width: 620px;
    margin: 0 auto 20px;
    line-height: 1.6;
  }
  .os-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 28px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    position: relative;
    border-top: 5px solid #16a34a;
    margin-bottom: 28px;
    color: #0f172a;
  }
  .os-badge {
    position: absolute;
    top: -14px;
    right: 24px;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  }
  .badge-android { background: #16a34a; color: #fff; }
  .step-card {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    padding: 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 12px;
  }
  .step-num {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 14px;
    flex-shrink: 0;
  }
  .num-android { background: #16a34a; }
  .step-content h4 {
    margin: 0 0 4px 0;
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
  }
  .step-content p {
    margin: 0;
    font-size: 13.5px;
    color: #64748b;
    line-height: 1.5;
  }
  .step-tag {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    margin-top: 6px;
  }
  .tag-android { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
  .faq-box {
    border-bottom: 1px solid #e2e8f0;
    padding: 14px 0;
  }
  .faq-box:last-child { border-bottom: none; }
  .faq-box b { color: #0f172a; font-size: 14px; display: block; margin-bottom: 4px; }
  .faq-box p { color: #64748b; font-size: 13px; margin: 0; line-height: 1.5; }
  .qr-box {
    display: inline-block;
    padding: 10px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 8px;
  }

  @media (max-width: 600px) {
    .download-hero { padding: 26px 16px; }
    .download-hero h1 { font-size: 22px; }
    .download-hero p { font-size: 13.5px; }
    .os-card { padding: 20px 16px; }
    .step-card { flex-direction: column; gap: 8px; }
  }
</style>
</head>
<body style="background: #f8fafc; min-height: 100vh; color: #0f172a;">
<div class="guide-container">
  <!-- Nav -->
  <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
    <a href="index.php" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; color:#0f172a; font-weight:700; font-size:16px;">
      <img src="images/logo.png" alt="BloodLink" style="width:32px; height:32px; border-radius:6px;">
      <span>BloodLink</span>
    </a>
    <div style="display:flex; gap:8px;">
      <a href="index.php" class="btn ghost slim">← Home</a>
      <a href="login.php" class="btn slim">Log In</a>
      <a href="register.php" class="btn slim" style="background:#c0152e;">Register</a>
    </div>
  </header>

  <!-- Hero -->
  <div class="download-hero">
    <div style="display:inline-block; background:rgba(255,255,255,0.2); padding:6px 16px; border-radius:20px; font-size:12px; font-weight:700; margin-bottom:12px; letter-spacing:0.5px;">
      🤖 OFFICIAL ANDROID APPLICATION &middot; v1.0
    </div>
    <h1>Get the BloodLink Android App</h1>
    <p>Access blood donations, urgent patient requisitions, and real-time blood stock inventory directly from your Android phone.</p>
    
    <div>
      <a href="download.php?dl=1" class="btn" style="background:#059669; color:#fff; font-size:15px; font-weight:800; padding:12px 28px; border-radius:12px; text-decoration:none; display:inline-flex; align-items:center; gap:10px; box-shadow:0 6px 20px rgba(5,150,105,0.35);">
        ⬇️ Download Android APK (Direct)
      </a>
    </div>
  </div>

  <!-- Android Package Card -->
  <div class="os-card" id="android-card">
    <span class="os-badge badge-android">🤖 Android</span>
    <div>
      <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
        <div style="width:48px; height:48px; border-radius:12px; background:#ecfdf5; display:flex; align-items:center; justify-content:center; font-size:26px;">
          🤖
        </div>
        <div>
          <h3 style="font-size:19px; font-weight:800; color:#111827; margin:0;">Official Android Application</h3>
          <span style="font-size:12px; color:#059669; font-weight:700;">Direct Native APK &middot; Clean Build</span>
        </div>
      </div>

      <p style="font-size:14px; color:#4b5563; line-height:1.6; margin-bottom:18px;">
        Works seamlessly on all modern Android devices (Samsung, Xiaomi, Oppo, Vivo, Realme, Google Pixel, OnePlus, Infinix, Tecno, etc.).
      </p>

      <!-- Android QR Code -->
      <div style="text-align:center; margin-bottom:20px;">
        <div class="qr-box">
          <img src="images/qr_android_apk.png?v=<?= time() ?>" alt="Scan to download Android APK" style="width:160px; height:160px; display:block; border-radius:8px;" onerror="this.onerror=null; this.src='https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=<?= urlencode($androidTargetUrl) ?>';">
        </div>
        <div style="font-size:12px; color:#6b7280; margin-top:6px; font-weight:500;">
          📷 Scan with your Android camera to download directly
        </div>
      </div>

      <!-- Android Specs -->
      <div style="background:#f9fafb; border:1px solid #f3f4f6; border-radius:12px; padding:16px; font-size:13px; line-height:1.8; color:#374151; margin-bottom:20px;">
        <div><b>Package Name:</b> <code>Bloodlink_apk v1.0.apk</code></div>
        <div><b>File Size:</b> ~10.2 MB</div>
        <div><b>Android Requirement:</b> Android 7.0+ (Nougat through Android 15)</div>
        <div><b>Security &amp; Integrity:</b> Verified Clean &amp; Active</div>
      </div>
    </div>

    <div>
      <a href="download.php?dl=1" class="btn" style="width:100%; text-align:center; box-sizing:border-box; background:#059669; color:#fff; text-decoration:none; font-weight:800; padding:14px; border-radius:12px; display:block; box-shadow:0 4px 14px rgba(5,150,105,0.25); font-size:15px;">
        ⬇️ Download Android APK (v1.0)
      </a>
    </div>
  </div>

  <!-- Installation Steps -->
  <div style="background:#fff; border-radius:18px; border:1px solid #e5e7eb; padding:28px 24px; box-shadow:0 4px 16px rgba(0,0,0,0.04);">
    <div style="border-bottom:2px solid #f3f4f6; padding-bottom:12px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
      <div>
        <h2 style="font-size:20px; font-weight:800; color:#065f46; margin:0 0 4px 0;">
          🤖 4 Simple Steps to Install on Android
        </h2>
        <p style="font-size:13.5px; color:#6b7280; margin:0;">
          Guide to installing the official BloodLink APK on your phone:
        </p>
      </div>
      <a href="download.php?dl=1" class="btn slim" style="background:#059669; color:#fff;">⬇️ Download APK</a>
    </div>

    <div class="step-card">
      <div class="step-num num-android">1</div>
      <div class="step-content">
        <h4>Download the APK File</h4>
        <p>Tap <b>Download Android APK</b> above or scan the QR code using your Android camera.</p>
        <span class="step-tag tag-android">Step 1: Download</span>
      </div>
    </div>

    <div class="step-card">
      <div class="step-num num-android">2</div>
      <div class="step-content">
        <h4>Confirm Download Prompt</h4>
        <p>If your browser shows <i>"File might be harmful"</i>, tap <b>Download anyway</b>. This is a standard security prompt for all direct APK downloads outside Google Play.</p>
        <span class="step-tag tag-android">Step 2: Confirm</span>
      </div>
    </div>

    <div class="step-card">
      <div class="step-num num-android">3</div>
      <div class="step-content">
        <h4>Enable "Install Unknown Apps" (If Prompted)</h4>
        <p>Open the downloaded file. If your phone asks for permission, tap <b>Settings</b> and toggle ON <b>Allow from this source</b>.</p>
        <span class="step-tag tag-android">Step 3: Permission</span>
      </div>
    </div>

    <div class="step-card">
      <div class="step-num num-android">4</div>
      <div class="step-content">
        <h4>Install &amp; Log In</h4>
        <p>Tap <b>Install</b>, then tap <b>Open</b>. Log in using your registered Donor or Patient account to start using BloodLink.</p>
        <span class="step-tag tag-android">Step 4: Launch</span>
      </div>
    </div>
  </div>

  <!-- FAQs -->
  <div style="background:#fff; border-radius:18px; border:1px solid #e5e7eb; padding:28px 24px; box-shadow:0 4px 16px rgba(0,0,0,0.04); margin-top:28px;">
    <h3 style="font-size:17px; font-weight:700; color:#111827; margin-bottom:14px;">❓ Frequently Asked Questions (FAQs)</h3>
    <div class="faq-box">
      <b>Who should use the Android App?</b>
      <p>All blood donors and patient requesters are designed to use the Android app for streamlined blood requests, eligibility tracking, and instant hospital announcements.</p>
    </div>
    <div class="faq-box">
      <b>Is my profile image kept separate between Donor and Patient?</b>
      <p>Yes. The latest update completely isolates Donor and Patient avatars so your profile pictures never cross over or replace each other.</p>
    </div>
    <div class="faq-box">
      <b>Where do I receive urgent hospital alerts?</b>
      <p>Hospital staff and admins publish announcements directly to the <b>Announcements</b> tab in the Android app.</p>
    </div>
  </div>

</div>
</body>
</html>
