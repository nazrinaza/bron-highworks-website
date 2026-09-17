@extends('layout')
@section('title','Staff sign-in')
@section('intro')<p>Manage site visits and project documents.</p>@endsection
@section('content')<form class="panel narrow" action="{{ route('login.store') }}" method="post">@csrf<label>Email<input name="email" type="email" autocomplete="username" value="{{ old('email') }}" required></label><label>Password<input name="password" type="password" autocomplete="current-password" required></label><button>Sign in →</button><p class="muted">Staff accounts are provisioned by the BRON administrator.</p></form>@endsection