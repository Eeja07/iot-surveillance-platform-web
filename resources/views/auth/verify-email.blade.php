@php
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts.blankLayout')

@section('title', 'Verify Email - Pages')

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
<!-- Verify Email Card -->
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
<h4 class="mb-1">Verify your email ✉️</h4>
<p class="text-start mb-6">
Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?
</p>

      @if (session('status') == 'verification-link-sent')
      <div class="alert alert-success mb-4" role="alert">
        {{ __('A new verification link has been sent to the email address you provided during registration.') }}
      </div>
      @endif

      <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="btn btn-primary d-grid w-100 mb-4" type="submit">
          Resend Verification Email
        </button>
      </form>

      <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <button type="submit" class="btn btn-link text-muted text-decoration-none">
          <i class="ti ti-logout me-1"></i> Log Out
        </button>
      </form>
    </div>
  </div>
  <!-- /Verify Email Card -->
</div>


</div>
</div>
@endsection
