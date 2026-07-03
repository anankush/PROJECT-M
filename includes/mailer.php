<?php
if (!defined('GOOGLE_SCRIPT_URL')) {
    define('GOOGLE_SCRIPT_URL', getenv('GOOGLE_SCRIPT_URL') ?: '');
}

function send_email($to, $subject, $body) {
    if (empty(GOOGLE_SCRIPT_URL)) {
        error_log('GOOGLE_SCRIPT_URL not configured. Email not sent to: ' . $to);
        return false;
    }
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => GOOGLE_SCRIPT_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_POSTFIELDS     => http_build_query(['email' => $to, 'subject' => $subject, 'body' => $body])
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($httpCode >= 200 && $httpCode < 400);
}

function get_otp_email_body($otp, $is_reset = false) {
    $title = $is_reset ? 'Password Reset OTP' : 'Registration OTP';
    $message = $is_reset ? 'Use the verification code below to reset your password.' : 'Use the verification code below to complete your registration.';
    return '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#030014;font-family:\'Outfit\',\'Inter\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;color:#ffffff;">
  <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#030014;padding:20px 0;">
    <tr>
      <td align="center">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:420px;background:rgba(20,14,50,0.65);border:1px solid rgba(139,92,246,0.3);border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.5);">
          <tr>
            <td style="padding:24px 24px 15px 24px;text-align:center;background:linear-gradient(135deg,#1e1b4b 0%,#0f172a 100%);border-bottom:1px solid rgba(139,92,246,0.15);">
              <div style="font-size:20px;font-weight:700;color:#a78bfa;letter-spacing:1px;font-family:\'Outfit\',sans-serif;">MONEY MANAGEMENT</div>
            </td>
          </tr>
          <tr>
            <td style="padding:24px;text-align:center;">
              <h2 style="margin:0 0 10px 0;font-size:18px;font-weight:600;color:#ffffff;font-family:\'Outfit\',sans-serif;">' . $title . '</h2>
              <p style="margin:0 0 20px 0;font-size:14px;color:rgba(255,255,255,0.7);line-height:1.5;font-family:\'Inter\',sans-serif;">' . $message . '</p>
              <div style="display:inline-block;margin:10px 0;padding:12px 30px;background:linear-gradient(135deg,rgba(139,92,246,0.2) 0%,rgba(59,130,246,0.2) 100%);border:1px solid rgba(139,92,246,0.4);border-radius:12px;">
                <span style="font-family:\'JetBrains Mono\',\'Courier New\',Courier,monospace;font-size:32px;font-weight:700;letter-spacing:6px;color:#ffffff;text-shadow:0 0 10px rgba(139,92,246,0.5);">' . $otp . '</span>
              </div>
              <p style="margin:20px 0 0 0;font-size:12px;color:rgba(255,255,255,0.4);font-family:\'Inter\',sans-serif;">This code will expire in <strong style="color:#ef4444;">2 minutes</strong>. If you did not request this, please ignore this email.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 24px;background-color:rgba(10,10,26,0.8);text-align:center;border-top:1px solid rgba(139,92,246,0.15);">
              <span style="font-size:11px;color:rgba(255,255,255,0.35);font-family:\'Inter\',sans-serif;">&copy; 2026 Money Management. Secured Portal.</span>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

function get_monthly_summary_email_body($userName, $monthName, $totalSpent, $totalSaved, $currency = '₹') {
    $net = $totalSaved - $totalSpent;
    $netFormatted = number_format(abs($net), 2);
    $spentFormatted = number_format($totalSpent, 2);
    $savedFormatted = number_format($totalSaved, 2);

    if ($net >= 0) {
        $netColor = '#34d399'; // Emerald green
        $netSign = '+';
        $netBg = 'rgba(16, 185, 129, 0.08)';
        $netBorder = 'rgba(16, 185, 129, 0.2)';
        $netText = 'Superb! You saved more than you spent this month. Keep up the great work!';
    } else {
        $netColor = '#f87171'; // Red/Pink
        $netSign = '-';
        $netBg = 'rgba(239, 68, 68, 0.08)';
        $netBorder = 'rgba(239, 68, 68, 0.2)';
        $netText = 'Caution: You spent more than you saved this month. Review your budget.';
    }

    return '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#030014;font-family:\'Outfit\',\'Inter\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;color:#ffffff;">
  <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#030014;padding:20px 0;">
    <tr>
      <td align="center">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:480px;background:rgba(20,14,50,0.65);border:1px solid rgba(139,92,246,0.3);border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.5);">
          <tr>
            <td style="padding:24px 24px 15px 24px;text-align:center;background:linear-gradient(135deg,#1e1b4b 0%,#0f172a 100%);border-bottom:1px solid rgba(139,92,246,0.15);">
              <div style="font-size:20px;font-weight:700;color:#a78bfa;letter-spacing:1px;font-family:\'Outfit\',sans-serif;">MONEY MANAGEMENT</div>
            </td>
          </tr>
          <tr>
            <td style="padding:24px;">
              <h2 style="margin:0 0 10px 0;font-size:18px;font-weight:600;color:#ffffff;font-family:\'Outfit\',sans-serif;text-align:center;">📊 Monthly Financial Report</h2>
              <p style="margin:0 0 20px 0;font-size:14px;color:rgba(255,255,255,0.8);line-height:1.5;font-family:\'Inter\',sans-serif;">
                Hello <strong>' . htmlspecialchars($userName) . '</strong>,<br>
                Here is your financial summary report for the month of <strong>' . htmlspecialchars($monthName) . '</strong>.
              </p>
              
              <!-- Financial Cards -->
              <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:16px;">
                <tr>
                  <td style="padding:8px 0;">
                    <div style="padding:16px;background:rgba(239, 68, 68, 0.08);border:1px solid rgba(239, 68, 68, 0.2);border-radius:12px;">
                      <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,0.5);margin-bottom:4px;font-family:\'Inter\',sans-serif;">Total Expenses</div>
                      <div style="font-size:22px;font-weight:700;color:#f87171;font-family:\'Outfit\',sans-serif;">-' . $currency . $spentFormatted . '</div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td style="padding:8px 0;">
                    <div style="padding:16px;background:rgba(16, 185, 129, 0.08);border:1px solid rgba(16, 185, 129, 0.2);border-radius:12px;">
                      <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,0.5);margin-bottom:4px;font-family:\'Inter\',sans-serif;">Total Savings</div>
                      <div style="font-size:22px;font-weight:700;color:#34d399;font-family:\'Outfit\',sans-serif;">+' . $currency . $savedFormatted . '</div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td style="padding:8px 0;">
                    <div style="padding:16px;background:' . $netBg . ';border:1px solid ' . $netBorder . ';border-radius:12px;">
                      <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,0.5);margin-bottom:4px;font-family:\'Inter\',sans-serif;">Net Cashflow</div>
                      <div style="font-size:22px;font-weight:700;color:' . $netColor . ';font-family:\'Outfit\',sans-serif;">' . $netSign . $currency . $netFormatted . '</div>
                      <div style="font-size:12px;color:rgba(255,255,255,0.6);margin-top:6px;line-height:1.4;font-family:\'Inter\',sans-serif;">' . $netText . '</div>
                    </div>
                  </td>
                </tr>
              </table>
              
              <div style="text-align:center;margin:24px 0 10px 0;font-size:13px;color:rgba(255,255,255,0.6);font-family:\'Inter\',sans-serif;line-height:1.5;">
                You can view full details on your Money Management dashboard:<br>
                <strong style="color:#a78bfa;">moneymgmt.is-best.net</strong>
              </div>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 24px;background-color:rgba(10,10,26,0.8);text-align:center;border-top:1px solid rgba(139,92,246,0.15);">
              <span style="font-size:11px;color:rgba(255,255,255,0.35);font-family:\'Inter\',sans-serif;">&copy; ' . date('Y') . ' Money Management. All rights reserved.</span>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

