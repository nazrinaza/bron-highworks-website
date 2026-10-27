@extends('layout')
@section('title',$operation->reference)
@section('intro')<p>{{ $operation->title }} · {{ $operation->customer_name }}</p>@endsection
@section('content')
<div class="ops-summary panel">
    <div><span class="badge status-{{ $operation->status }}">{{ str_replace('_',' ',ucfirst($operation->status)) }}</span><h2>{{ $operation->site_address }}</h2><p>{{ $operation->scheduled_start?->format('d M Y H:i') ?? 'Schedule pending' }} · Pilot: {{ $operation->assignedPilot?->name ?? 'Unassigned' }}</p></div>
    <div class="release-state {{ $operation->isReadyForRelease() ? 'ready' : '' }}"><strong>{{ $operation->isReadyForRelease() ? 'Release gate complete' : 'Planning incomplete' }}</strong><small>{{ $operation->completedWorkflowSteps() }}/5 workflow records complete</small></div>
</div>
<nav class="workflow-nav" aria-label="Operation workflow"><a href="#risk">1 Risk assessment</a><a href="#flight-plan">2 Flight plan</a><a href="#preflight">3 Preflight</a><a href="#authorisation">4 Authorisation</a><a href="#logbook">5 Pilot logbook</a></nav>
@can('manage-operations-users')
<details class="panel workflow-card"><summary><strong>Operation details and assignment</strong></summary>
<form method="post" action="{{ route('ops.operations.update',$operation) }}" class="grid top-gap">@csrf @method('PUT')
<label>Operation title<input name="title" value="{{ old('title',$operation->title) }}" required></label><label>Assigned pilot<select name="assigned_pilot_id"><option value="">Unassigned</option>@foreach($pilots as $pilot)<option value="{{ $pilot->id }}" @selected(old('assigned_pilot_id',$operation->assigned_pilot_id)==$pilot->id)>{{ $pilot->name }}</option>@endforeach</select></label>
<label>Customer / company<input name="customer_name" value="{{ old('customer_name',$operation->customer_name) }}" required></label><label>Customer email<input type="email" name="customer_email" value="{{ old('customer_email',$operation->customer_email) }}"></label>
<label>Customer phone<input name="customer_phone" value="{{ old('customer_phone',$operation->customer_phone) }}"></label><label class="wide">Site address<textarea name="site_address" required>{{ old('site_address',$operation->site_address) }}</textarea></label>
<label>Scheduled start<input type="datetime-local" name="scheduled_start" value="{{ old('scheduled_start',$operation->scheduled_start?->format('Y-m-d\TH:i')) }}"></label><label>Scheduled end<input type="datetime-local" name="scheduled_end" value="{{ old('scheduled_end',$operation->scheduled_end?->format('Y-m-d\TH:i')) }}"></label>
<div class="wide"><button>Save operation details</button></div></form>
<form method="post" action="{{ route('ops.operations.status',$operation) }}" class="filters">@csrf @method('PATCH')<label>Status<select name="status">@foreach(\App\Models\DroneOperation::STATUSES as $status)<option value="{{ $status }}" @selected($operation->status===$status)>{{ str_replace('_',' ',ucfirst($status)) }}</option>@endforeach</select></label><button>Update status</button></form></details>
@endcan
@include('ops.operations.partials.risk')
@include('ops.operations.partials.flight-plan')
@include('ops.operations.partials.preflight')
@include('ops.operations.partials.authorisation')
@include('ops.operations.partials.logbook')
@endsection
