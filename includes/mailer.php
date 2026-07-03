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

function get_monthly_summary_email_body($userName, $monthName, $totalSpent, $totalSaved, $expensesBreakdown = [], $savingsBreakdown = [], $currency = '₹') {
    $net = $totalSaved - $totalSpent;
    $netFormatted = number_format(abs($net), 2);
    $spentFormatted = number_format($totalSpent, 2);
    $savedFormatted = number_format($totalSaved, 2);

    if ($net >= 0) {
        $netColor = '#34d399'; // Emerald green
        $netSign = '+';
        $netBg = 'rgba(16, 185, 129, 0.08)';
        $netBorder = 'rgba(16, 185, 129, 0.2)';
        $netText = '<strong>Great! Stored amount was higher than outflow this month.</strong>';
        
        $outflowLabelStyle = 'font-weight:400;color:rgba(255,255,255,0.4);';
        $storedLabelStyle = 'font-weight:700;color:rgba(255,255,255,0.95);';
        $outflowValueWeight = '400';
        $storedValueWeight = '700';
    } else {
        $netColor = '#f87171'; // Red/Pink
        $netSign = '-';
        $netBg = 'rgba(239, 68, 68, 0.08)';
        $netBorder = 'rgba(239, 68, 68, 0.2)';
        $netText = '<strong>Notice: Outflow was higher than stored amount this month.</strong>';
        
        $outflowLabelStyle = 'font-weight:700;color:rgba(255,255,255,0.95);';
        $storedLabelStyle = 'font-weight:400;color:rgba(255,255,255,0.4);';
        $outflowValueWeight = '700';
        $storedValueWeight = '400';
    }

    // Build Expenses Breakdown Table Rows
    $expenseRows = '';
    if (!empty($expensesBreakdown)) {
        foreach ($expensesBreakdown as $row) {
            $expenseRows .= '<tr style="border-bottom:1px solid rgba(255,255,255,0.08);">';
            $expenseRows .= '  <td style="font-size:13px;color:rgba(255,255,255,0.85);padding:10px 8px;font-family:\'Inter\',sans-serif;">' . htmlspecialchars($row['category_name']) . '</td>';
            $expenseRows .= '  <td align="right" style="font-size:13px;font-weight:600;color:#f87171;padding:10px 8px;font-family:\'Inter\',sans-serif;">-' . $currency . number_format($row['spent'], 2) . '</td>';
            $expenseRows .= '</tr>';
        }
    } else {
        $expenseRows = '<tr><td colspan="2" style="font-size:12px;color:rgba(255,255,255,0.4);text-align:center;padding:15px;font-family:\'Inter\',sans-serif;">No activity logged this period.</td></tr>';
    }

    // Build Savings Breakdown Table Rows
    $savingsRows = '';
    if (!empty($savingsBreakdown)) {
        foreach ($savingsBreakdown as $row) {
            $val = floatval($row['net_saved']);
            $color = $val >= 0 ? '#34d399' : '#f87171';
            $sign = $val >= 0 ? '+' : '-';
            $savingsRows .= '<tr style="border-bottom:1px solid rgba(255,255,255,0.08);">';
            $savingsRows .= '  <td style="font-size:13px;color:rgba(255,255,255,0.85);padding:10px 8px;font-family:\'Inter\',sans-serif;">' . htmlspecialchars($row['goal_name']) . '</td>';
            $savingsRows .= '  <td align="right" style="font-size:13px;font-weight:600;color:' . $color . ';padding:10px 8px;font-family:\'Inter\',sans-serif;">' . $sign . $currency . number_format(abs($val), 2) . '</td>';
            $savingsRows .= '</tr>';
        }
    } else {
        $savingsRows = '<tr><td colspan="2" style="font-size:12px;color:rgba(255,255,255,0.4);text-align:center;padding:15px;font-family:\'Inter\',sans-serif;">No target activity this period.</td></tr>';
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
        <!-- Header Text Outside of the Card -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:480px;margin-bottom:15px;text-align:left;">
          <tr>
            <td style="padding:0 10px;">
              <h2 style="margin:0 0 8px 0;font-size:18px;font-weight:600;color:#ffffff;font-family:\'Outfit\',sans-serif;">Monthly Stats Overview</h2>
              <p style="margin:0;font-size:14px;color:rgba(255,255,255,0.8);line-height:1.5;font-family:\'Inter\',sans-serif;">
                Hello <strong>' . htmlspecialchars($userName) . '</strong>,<br>
                Here is your detailed statistics overview for the month of <strong>' . htmlspecialchars($monthName) . '</strong>.
              </p>
            </td>
          </tr>
        </table>

        <!-- Main Card Container -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:480px;background:rgba(20,14,50,0.65);border:1px solid rgba(139,92,246,0.3);border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.5);">
          <tr>
            <td style="padding:24px 24px 15px 24px;text-align:center;background:linear-gradient(135deg,#1e1b4b 0%,#0f172a 100%);border-bottom:1px solid rgba(139,92,246,0.15);">
              <div style="font-size:20px;font-weight:700;color:#a78bfa;letter-spacing:1px;font-family:\'Outfit\',sans-serif;">MONEY MANAGEMENT</div>
            </td>
          </tr>
          <tr>
            <td style="padding:24px;">
              
              <!-- Total Spent and Saved Cards -->
              <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:20px;">
                <tr>
                  <td style="padding:6px 0;">
                    <div style="padding:14px;background:rgba(239, 68, 68, 0.08);border:1px solid rgba(239, 68, 68, 0.2);border-radius:12px;">
                      <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;font-family:\'Inter\',sans-serif;' . $outflowLabelStyle . '">Total Outflow</div>
                      <div style="font-size:20px;color:#f87171;font-family:\'Outfit\',sans-serif;font-weight:' . $outflowValueWeight . ';">-' . $currency . $spentFormatted . '</div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td style="padding:6px 0;">
                    <div style="padding:14px;background:rgba(16, 185, 129, 0.08);border:1px solid rgba(16, 185, 129, 0.2);border-radius:12px;">
                      <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;font-family:\'Inter\',sans-serif;' . $storedLabelStyle . '">Total Stored</div>
                      <div style="font-size:20px;color:#34d399;font-family:\'Outfit\',sans-serif;font-weight:' . $storedValueWeight . ';">+' . $currency . $savedFormatted . '</div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td style="padding:6px 0;">
                    <div style="padding:14px;background:' . $netBg . ';border:1px solid ' . $netBorder . ';border-radius:12px;">
                      <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,0.5);margin-bottom:4px;font-family:\'Inter\',sans-serif;">Difference</div>
                      <div style="font-size:20px;font-weight:700;color:' . $netColor . ';font-family:\'Outfit\',sans-serif;">' . $netSign . $currency . $netFormatted . '</div>
                      <div style="font-size:12px;color:rgba(255,255,255,0.6);margin-top:6px;line-height:1.4;font-family:\'Inter\',sans-serif;">' . $netText . '</div>
                    </div>
                  </td>
                </tr>
              </table>

              <!-- Detailed Expenses Breakdown Table -->
              <h3 style="font-size:14px;font-weight:600;color:#a78bfa;margin:25px 0 10px 0;font-family:\'Outfit\',sans-serif;border-bottom:1px solid rgba(139,92,246,0.15);padding-bottom:6px;">📈 Outflow Breakdown</h3>
              <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin-bottom:20px;">
                <thead>
                  <tr style="background-color:rgba(139,92,246,0.1);border-bottom:1px solid rgba(139,92,246,0.25);">
                    <th align="left" style="font-size:11px;font-weight:600;color:#a78bfa;text-transform:uppercase;letter-spacing:1px;padding:8px;font-family:\'Inter\',sans-serif;">Section</th>
                    <th align="right" style="font-size:11px;font-weight:600;color:#a78bfa;text-transform:uppercase;letter-spacing:1px;padding:8px;font-family:\'Inter\',sans-serif;">Value</th>
                  </tr>
                </thead>
                <tbody>
                  ' . $expenseRows . '
                </tbody>
              </table>

              <!-- Detailed Savings Breakdown Table -->
              <h3 style="font-size:14px;font-weight:600;color:#a78bfa;margin:25px 0 10px 0;font-family:\'Outfit\',sans-serif;border-bottom:1px solid rgba(139,92,246,0.15);padding-bottom:6px;">🎯 Stored Goals Activity</h3>
              <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <thead>
                  <tr style="background-color:rgba(139,92,246,0.1);border-bottom:1px solid rgba(139,92,246,0.25);">
                    <th align="left" style="font-size:11px;font-weight:600;color:#a78bfa;text-transform:uppercase;letter-spacing:1px;padding:8px;font-family:\'Inter\',sans-serif;">Target</th>
                    <th align="right" style="font-size:11px;font-weight:600;color:#a78bfa;text-transform:uppercase;letter-spacing:1px;padding:8px;font-family:\'Inter\',sans-serif;">Value</th>
                  </tr>
                </thead>
                <tbody>
                  ' . $savingsRows . '
                </tbody>
              </table>

              <div style="text-align:center;margin:30px 0 10px 0;font-size:12px;color:rgba(255,255,255,0.4);font-family:\'Inter\',sans-serif;">
                You can view full details on your dashboard.
              </div>
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

