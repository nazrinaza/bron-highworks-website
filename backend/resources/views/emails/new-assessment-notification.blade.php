<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>New site assessment</title></head>
<body style="margin:0;background:#f2f3ef;color:#242627;font-family:Arial,sans-serif">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f2f3ef;padding:24px 12px"><tr><td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#fff;border-collapse:collapse">
<tr><td style="background:#cbdd29;padding:24px 32px"><strong style="font-size:25px">BRON HIGHWORKS</strong></td></tr>
<tr><td style="padding:32px"><p style="margin-top:0;color:#667000;font-size:12px;font-weight:bold;letter-spacing:1px">NEW SITE ASSESSMENT</p><h1 style="margin:0 0 18px;font-size:28px">{{ $visit->company ?: $visit->name }}</h1>
<table role="presentation" width="100%" cellspacing="0" cellpadding="8" style="margin:20px 0;background:#f6f7f2"><tr><td><strong>Contact</strong></td><td>{{ $visit->name }}</td></tr><tr><td><strong>Email</strong></td><td><a href="mailto:{{ $visit->email }}">{{ $visit->email }}</a></td></tr><tr><td><strong>Phone</strong></td><td>{{ $visit->phone }}</td></tr><tr><td><strong>Service</strong></td><td>{{ $visit->service }}</td></tr><tr><td><strong>Site</strong></td><td>{{ $visit->address }}</td></tr><tr><td><strong>Preferred date</strong></td><td>{{ $visit->preferred_date?->format('d M Y') ?? 'Flexible' }}</td></tr>@if($visit->area)<tr><td><strong>Area</strong></td><td>{{ $visit->area }}</td></tr>@endif</table>
@if($visit->access_details)<h2 style="font-size:17px">Access details</h2><p style="white-space:pre-line">{{ $visit->access_details }}</p>@endif
@if($visit->requirements)<h2 style="font-size:17px">Requirements</h2><p style="white-space:pre-line">{{ $visit->requirements }}</p>@endif
<p><a href="{{ route('admin.visits.show', $visit) }}" style="display:inline-block;background:#242627;color:#fff;text-decoration:none;padding:13px 18px;font-weight:bold">Open in BRON Admin →</a></p><p style="margin-bottom:0;font-size:12px;color:#666">Reference: {{ $visit->reference }}</p></td></tr>
</table></td></tr></table>
</body></html>
