@extends('layout')
@section('title','Drone pilots')
@section('intro')<p>Create, suspend and remove remote-pilot access. Deleted accounts remain attached to historical records.</p>@endsection
@section('content')
<details class="panel" open><summary><strong>Add remote pilot</strong></summary>
<form method="post" action="{{ route('ops.pilots.store') }}" class="grid top-gap">@csrf
<label>Name<input name="name" value="{{ old('name') }}" required></label><label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
<label>Phone<input name="phone" value="{{ old('phone') }}"></label><label>Licence / Flyer ID<input name="pilot_licence_no" value="{{ old('pilot_licence_no') }}"></label>
<label>Licence expiry<input type="date" name="pilot_licence_expires_at" value="{{ old('pilot_licence_expires_at') }}"></label><div></div>
<label>Password<input type="password" name="password" required autocomplete="new-password"><small>At least 12 characters with letters and numbers.</small></label><label>Confirm password<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
<div class="wide"><button>Create pilot account</button></div></form></details>
<div class="panel table-wrap top-gap"><table><thead><tr><th>Pilot</th><th>Licence</th><th>Assigned operations</th><th>Flight logs</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($pilots as $pilot)<tr><td><strong>{{ $pilot->name }}</strong><small>{{ $pilot->email }} · {{ $pilot->phone }}</small></td><td>{{ $pilot->pilot_licence_no ?: 'Not recorded' }}<small>{{ $pilot->pilot_licence_expires_at?->format('d M Y') }}</small></td><td>{{ $pilot->assigned_operations_count }}</td><td>{{ $pilot->flight_logs_count }}</td><td><span class="badge status-{{ $pilot->status }}">{{ ucfirst($pilot->status) }}</span></td><td><div class="inline-actions"><form method="post" action="{{ route('ops.pilots.status',$pilot) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $pilot->status==='active'?'suspended':'active' }}"><button class="secondary">{{ $pilot->status==='active'?'Suspend':'Activate' }}</button></form><form method="post" action="{{ route('ops.pilots.destroy',$pilot) }}" onsubmit="return confirm('Delete this pilot account? Historical records will be retained.')">@csrf @method('DELETE')<button class="danger">Delete</button></form></div></td></tr>
@empty<tr><td colspan="6">No pilot accounts.</td></tr>@endforelse
</tbody></table></div>
@endsection
