@php
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts.blankLayout')

@section('title', 'Reset Password - Pages')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/pages-auth.js'])
@endsection

@section('content')

<div class="container-xxl">
<div class="authentication-wrapper authentication-basic container-p-y">
<div class="authentication-inner py-6">
<!-- Reset Password Card -->
<div class="card">
<div class="card-body">
<!-- Logo -->
<div class="app-brand justify-content-center mb-6">
<a href="{{ url('/') }}" class="app-brand-link">
<span class="app-brand-logo demo">@include('_partials.macros', ['height' => 20, 'withbg' => 'fill: #fff;'])</span>
<span class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
</a>
</div>
<!-- /Logo -->

      <h4 class="mb-1">Reset Password 📋</h4>
      <p class="mb-6">Please enter your new password to access your account</p>

      {{-- Error Validasi --}}
      @if ($errors->any())
      <div class="alert alert-danger mb-4">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
      @endif

      <form id="formAuthentication" class="mb-4" method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-6">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $request->email) }}" placeholder="Enter your email" required readonly>
        </div>

        <div class="mb-6 form-password-toggle">
          <label class="form-label" for="password">New Password</label>
          <div class="input-group input-group-merge">
            <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required autocomplete="new-password" autofocus />
            <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
          </div>
        </div>

        <div class="mb-6 form-password-toggle">
          <label class="form-label" for="password_confirmation">Confirm Password</label>
          <div class="input-group input-group-merge">
            <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required autocomplete="new-password" />
            <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
          </div>
        </div>

        <button class="btn btn-primary d-grid w-100 mb-6">
          Reset Password
        </button>
      </form>

    </div>
  </div>
  <!-- /Reset Password Card -->
</div>


</div>
</div>
@endsection
