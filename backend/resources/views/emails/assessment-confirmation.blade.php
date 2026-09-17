<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Assessment request received</title></head>
<body style="margin:0;background:#f2f3ef;color:#242627;font-family:Arial,sans-serif">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f2f3ef;padding:24px 12px"><tr><td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#fff;border-collapse:collapse">
<tr><td style="background:#cbdd29;padding:24px 32px;color:#080a0b"><strong style="font-size:25px;letter-spacing:1px">BRON HIGHWORKS</strong><br><span style="font-size:12px;font-weight:bold;letter-spacing:2px">HIGH ACCESS. SERIOUS CLEANING.</span></td></tr>
<tr><td style="padding:32px"><p style="margin-top:0;color:#667000;font-size:12px;font-weight:bold;letter-spacing:1px">REQUEST RECEIVED</p><h1 style="margin:0 0 18px;font-size:28px">Thank you, {{ $visit->name }}.</h1><p>We received your site assessment request and our team will contact you to confirm access and timing.</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="8" style="margin:24px 0;background:#f6f7f2;border-left:4px solid #cbdd29"><tr><td><strong>Reference</strong></td><td>{{ $visit->reference }}</td></tr><tr><td><strong>Service</strong></td><td>{{ $visit->service }}</td></tr><tr><td><strong>Site</strong></td><td>{{ $visit->address }}</td></tr><tr><td><strong>Preferred date</strong></td><td>{{ $visit->preferred_date?->format('d M Y') ?? 'Flexible' }}</td></tr></table>
<p>Your preferred date remains a request until BRON confirms the appointment.</p><p style="margin-bottom:0">Questions? Reply to this email or WhatsApp <a href="https://wa.me/60196522238" style="color:#536000">+60 19-652 2238</a>.</p></td></tr>
<tr><td style="padding:20px 32px;background:#242627;color:#f5f5f1;font-size:12px">BRON Highworks · 316-B, Lorong Kedah, Taman Melawati, 53100 Kuala Lumpur, Malaysia</td></tr>
</table></td></tr></table>
</body></html>
