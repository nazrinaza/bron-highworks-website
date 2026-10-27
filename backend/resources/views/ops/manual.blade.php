@extends('layout')
@section('title','Drone Operations Manual')
@section('intro')<p>The controlled operational reference is available separately from the project release workflow.</p>@endsection
@section('content')
<div class="panel manual-card"><p class="eyebrow">CONTROLLED DOCUMENT</p><h2>SERI N.S Complete Drone Operations Manual</h2><dl><dt>Document number</dt><dd>SNS-UAS-OM-001</dd><dt>Revision</dt><dd>02</dd><dt>Aircraft and activity</dt><dd>Joyance JTC30 façade glass and solar-panel cleaning</dd></dl><p>This manual covers planning, mobilisation, operation, cleaning, emergency response, maintenance and reporting. It does not itself grant authority to fly; every operation still requires valid approvals and site-specific controls.</p><a class="button" href="{{ route('ops.manual.download') }}">Download current manual</a></div>
@endsection
