@extends('layout')
@section('title','Create drone operation')
@section('intro')<p>Create the project record and assign an active remote pilot. Planning documents can then be completed in order.</p>@endsection
@section('content')
<form class="panel" method="post" action="{{ route('ops.operations.store') }}">@csrf
<div class="grid">
<label>Operation title<input name="title" value="{{ old('title') }}" required placeholder="Example Tower façade cleaning"></label>
<label>Assigned remote pilot<select name="assigned_pilot_id"><option value="">Unassigned</option>@foreach($pilots as $pilot)<option value="{{ $pilot->id }}" @selected(old('assigned_pilot_id')==$pilot->id)>{{ $pilot->name }} · {{ $pilot->pilot_licence_no ?: 'licence not recorded' }}</option>@endforeach</select></label>
<label>Customer / company<input name="customer_name" value="{{ old('customer_name') }}" required></label>
<label>Customer email<input type="email" name="customer_email" value="{{ old('customer_email') }}"></label>
<label>Customer phone<input name="customer_phone" value="{{ old('customer_phone') }}"></label>
<label class="wide">Site address<textarea name="site_address" required>{{ old('site_address') }}</textarea></label>
<label>Scheduled start<input type="datetime-local" name="scheduled_start" value="{{ old('scheduled_start') }}"></label>
<label>Scheduled end<input type="datetime-local" name="scheduled_end" value="{{ old('scheduled_end') }}"></label>
</div><button>Create operation</button> <a class="button secondary" href="{{ route('ops.dashboard') }}">Cancel</a></form>
@endsection
