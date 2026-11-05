<?php
$localIp = '192.168.29.91';
$apkPath = 'assets/apk/atom-mobile-latest.apk';
$apkUrl = "http://{$localIp}/my%20work/Atom/frontend/web/{$apkPath}";
$mobileAppUrl = "http://{$localIp}/my%20work/Atom/frontend/web/mobile_app.php";
$apkSize = file_exists(__DIR__ . '/' . $apkPath) ? round(filesize(__DIR__ . '/' . $apkPath) / 1024, 1) . ' KB' : '12.8 KB';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATOM Mobile Hub — Local APK Download & Mobile Testing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #080A0D;
            --bg-card: #11151C;
            --cyan-neon: #00F2FE;
            --purple-neon: #A855F7;
            --border-dim: #1E2838;
        }
        body {
            background-color: var(--bg-dark);
            color: #F8FAFC;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .hub-card {
            background: var(--bg-card);
            border: 1px solid var(--border-dim);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
            max-width: 540px;
            width: 100%;
            overflow: hidden;
        }
        .hub-header {
            background: #0C0F14;
            border-bottom: 1px solid var(--border-dim);
            padding: 20px;
            text-align: center;
        }
        .qr-box {
            background: #ffffff;
            padding: 12px;
            border-radius: 12px;
            display: inline-block;
            box-shadow: 0 4px 14px rgba(0,242,254,0.25);
        }
    </style>
</head>
<body>

    <div class="hub-card">
        <div class="hub-header">
            <div class="d-inline-flex align-items-center justify-content-center bg-dark border border-info rounded-circle mb-2" style="width: 54px; height: 54px;">
                <i class="bi bi-phone text-info fs-3"></i>
            </div>
            <h1 class="h4 fw-bold text-white mb-1">ATOM AI Mobile Hub</h1>
            <p class="small text-secondary mb-0">Local Android APK & Mobile Testing Portal</p>
        </div>

        <div class="p-4">
            <!-- QR Code Section -->
            <div class="text-center mb-4">
                <p class="small text-info fw-bold mb-2"><i class="bi bi-qr-code-scan"></i> Scan with Phone Camera on Local Wi-Fi:</p>
                <div class="qr-box">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($mobileAppUrl) ?>&color=080A0D" alt="Mobile App QR Code" width="180" height="180">
                </div>
                <div class="mt-2 small text-secondary">Connects directly to <code><?= htmlspecialchars($localIp) ?></code></div>
            </div>

            <!-- Download Buttons -->
            <div class="d-grid gap-2 mb-4">
                <a href="<?= htmlspecialchars($apkPath) ?>" download class="btn btn-info btn-lg fw-bold text-dark">
                    <i class="bi bi-android2 me-1"></i> Download Android APK (<?= $apkSize ?>)
                </a>
                <a href="mobile_app.php" class="btn btn-outline-light">
                    <i class="bi bi-phone-fill me-1 text-info"></i> Open Mobile Web App / PWA
                </a>
            </div>

            <!-- Instructions -->
            <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-dim);">
                <div class="small fw-bold text-white mb-2"><i class="bi bi-info-circle text-info"></i> Local Setup Steps:</div>
                <ol class="small text-secondary ps-3 mb-0" style="line-height: 1.6;">
                    <li>Ensure your mobile phone is connected to the same Wi-Fi network (<code>192.168.29.x</code>).</li>
                    <li>Download the APK file or scan the QR code above.</li>
                    <li>On Android, allow <em>"Install from Unknown Sources"</em> when prompted.</li>
                    <li>Open <strong>ATOM AI</strong> to access live chat, memory, and approvals!</li>
                </ol>
            </div>
        </div>

        <div class="p-3 bg-dark border-top border-secondary text-center small text-secondary">
            ATOM AI v1.0.0 &bull; IP: <code><?= htmlspecialchars($localIp) ?></code>
        </div>
    </div>

</body>
</html>

