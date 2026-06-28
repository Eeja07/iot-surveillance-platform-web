{{-- @php
    use Illuminate\Support\Facades\Route;
@endphp

<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required
                autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout> --}}


@php
    $customizerHidden = 'customizer-hide';
@endphp

{{-- @extends('layouts/layoutMaster') --}}
@extends('layouts.blankLayout')


@section('title', 'Login - Pages')

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
                <!-- Login Card -->
                <div class="card shadow-xl rounded-2xl">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center flex-column mb-6">
                            <a href="{{ url('/') }}" class="app-brand-link d-flex flex-column align-items-center">
                                <span class="app-brand-logo demo mb-2">
                                    <img src="{{ asset(config('variables.logoUrl')) }}" height="48" alt="Logo">
                                </span>
                                <h3 class="app-brand-text demo text-heading fw-bold mb-0">
                                    {{ config('variables.templateName') }}
                                </h3>
                                <p class="text-muted fw-medium mt-1 mb-0" style="font-size: 0.85rem;">See More, Know Faster.</p>
                            </a>
                        </div>
                        <!-- /Logo -->

                        <p class="text-center text-muted mb-6" style="font-size: 0.9rem;">
                            Unified monitoring platform for telemetry, detection, firmware management, and real-time surveillance operations.
                        </p>

                        {{-- Session Status --}}
                        @if (session('status'))
                            <div class="alert alert-success mb-4">{{ session('status') }}</div>
                        @endif

                        {{-- Validation Errors --}}
                        @if ($errors->any())
                            <div class="alert alert-danger mb-4">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Login Form --}}
                        <form id="formAuthentication" class="mb-4" method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="mb-6">
                                <label for="email" class="form-label">Email or Username</label>
                                <input type="text" class="form-control" id="email" name="email"
                                    value="{{ old('email') }}" placeholder="Enter your email or username" required
                                    autofocus>
                            </div>

                            <div class="mb-6 form-password-toggle">
                                <label class="form-label" for="password">Password</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" class="form-control" name="password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="password" required />
                                    <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                                </div>
                            </div>

                            <div class="my-8">
                                <div class="d-flex justify-content-between">
                                    <div class="form-check mb-0 ms-2">
                                        <input class="form-check-input" type="checkbox" id="remember-me" name="remember"
                                            {{ old('remember') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="remember-me">Remember Me</label>
                                    </div>
                                    <a href="{{ route('password.request') }}">
                                        <p class="mb-0">Forgot Password?</p>
                                    </a>
                                </div>
                            </div>

                            <div class="mb-6">
                                <button class="btn btn-primary d-grid w-100" type="submit">Login</button>
                            </div>
                        </form>

                        <p class="text-center">
                            <span>New on our platform?</span>
                            <a href="{{ url('/register') }}">
                                <span>Create an account</span>
                            </a>
                        </p>

                        <div class="divider my-6">
                            <div class="divider-text">or</div>
                        </div>



                    </div>
                </div>
                <!-- /Login Card -->
            </div>
        </div>
    </div>
@endsection
