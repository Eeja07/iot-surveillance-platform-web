@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;
    $containerNav = $configData['contentLayout'] === 'compact' ? 'container-xxl' : 'container-fluid';
    $navbarDetached = $navbarDetached ?? '';
@endphp

<!-- Navbar -->
@if (isset($navbarDetached) && $navbarDetached == 'navbar-detached')
    <nav class="layout-navbar {{ $containerNav }} navbar navbar-expand-xl {{ $navbarDetached }} align-items-center bg-navbar-theme"
        id="layout-navbar">
@endif
@if (isset($navbarDetached) && $navbarDetached == '')
    <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
        <div class="{{ $containerNav }}">
@endif

<!--  Brand demo -->
@if (isset($navbarFull))
    <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
        <a href="{{ url('/') }}" class="app-brand-link">
            <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 20])</span>
            <span class="app-brand-text demo menu-text fw-bold">{{ config('variables.templateName') }}</span>
        </a>
    </div>
@endif

@if (!isset($navbarHideToggle))
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ? ' d-xl-none ' : '' }}">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="ti ti-menu-2 ti-md"></i>
        </a>
    </div>
@endif

<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

    @if ($configData['hasCustomizer'] == true)
        <!-- Style Switcher -->
        <div class="navbar-nav align-items-center">
            <div class="nav-item dropdown-style-switcher dropdown me-2 me-xl-0">
                <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
                    href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class='ti ti-md'></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-start dropdown-styles">
                    <li><a class="dropdown-item" href="javascript:void(0);" data-theme="light"><span class="align-middle"><i class='ti ti-sun ti-md me-3'></i>Light</span></a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-theme="dark"><span class="align-middle"><i class="ti ti-moon-stars ti-md me-3"></i>Dark</span></a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-theme="system"><span class="align-middle"><i class="ti ti-device-desktop-analytics ti-md me-3"></i>System</span></a></li>
                </ul>
            </div>
        </div>
    @endif

    <ul class="navbar-nav flex-row align-items-center ms-auto">

        <!-- Notification Dropdown / Toggle Button -->
        <li class="nav-item dropdown dropdown-notifications navbar-dropdown me-2 me-xl-1">
            <a class="nav-link dropdown-toggle hide-arrow btn btn-text-secondary btn-icon rounded-pill position-relative"
               id="navbar-notification-dropdown"
               href="javascript:void(0);"
               data-bs-toggle="dropdown"
               data-bs-auto-close="outside"
               aria-expanded="false"
               title="Pengaturan Notifikasi Pop-up">
                <i class="ti ti-bell ti-md notification-bell-icon"></i>
                <span class="badge bg-success badge-dot position-absolute top-0 end-0 mt-1 me-1 notification-badge-dot"></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end py-0 shadow" style="min-width: 290px;">
                <li class="dropdown-menu-header border-bottom">
                    <div class="dropdown-header d-flex align-items-center py-3">
                        <i class="ti ti-bell me-2 text-primary"></i>
                        <h6 class="mb-0 me-auto">Notifikasi Pop-up</h6>
                        <span class="badge bg-label-success ms-1 popup-notification-status-text">Aktif</span>
                    </div>
                </li>
                <li class="p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-check-label fw-medium cursor-pointer" for="navbarPopupNotificationSwitch">
                            Pop-up Toast Deteksi
                        </label>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input cursor-pointer popup-notification-toggle-input"
                                   type="checkbox"
                                   id="navbarPopupNotificationSwitch"
                                   onchange="window.setPopupNotifications(this.checked)"
                                   checked>
                        </div>
                    </div>
                    <small class="text-muted d-block mb-3" style="font-size: 0.8rem; line-height: 1.3;">
                        Tampilkan banner pop-up peringatan secara real-time saat terdeteksi orang di kamera.
                    </small>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.testPopupNotification()">
                            <i class="ti ti-bell-ringing me-1"></i> Uji Coba Pop-up
                        </button>
                    </div>
                </li>
            </ul>
        </li>

        <!-- User Profile Dropdown -->
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                <div class="btn btn-icon btn-text-secondary rounded-pill">
                    <i class="ti ti-user ti-md"></i>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item mt-0" href="{{ route('profile.edit') }}">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0">
                                    {{ Auth::check() ? Auth::user()->name : 'Guest' }}
                                </h6>
                                <small class="text-muted">
                                    @role('admin') Admin @else Pengguna @endrole
                                </small>
                            </div>
                        </div>
                    </a>
                </li>
                <li><div class="dropdown-divider my-1 mx-n2"></div></li>
                <li>
                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                        <i class="ti ti-user me-3 ti-md"></i><span class="align-middle">My Profile</span>
                    </a>
                </li>
                <li><div class="dropdown-divider my-1 mx-n2"></div></li>

                @if (Auth::check())
                    <li>
                        <div class="d-grid px-2 pt-2 pb-1">
                            <a class="btn btn-sm btn-danger d-flex" href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <small class="align-middle">Logout</small>
                                <i class="ti ti-logout ms-2 ti-14px"></i>
                            </a>
                        </div>
                    </li>
                    <form method="POST" id="logout-form" action="{{ route('logout') }}" style="display: none;">
                        @csrf
                    </form>
                @else
                    <li>
                        <div class="d-grid px-2 pt-2 pb-1">
                            <a class="btn btn-sm btn-primary d-flex"
                                href="{{ route('login') }}">
                                <small class="align-middle">Login</small>
                                <i class="ti ti-login ms-2 ti-14px"></i>
                            </a>
                        </div>
                    </li>
                @endif
            </ul>
        </li>
    </ul>
</div>

@if (!isset($navbarDetached))
    </div>
@endif
</nav>
