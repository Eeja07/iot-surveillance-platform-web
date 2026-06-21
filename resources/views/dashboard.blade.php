@extends('layouts/layoutMaster')

@section('title', 'Dashboard Admin')

@section('vendor-style')
<style>
    .group-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px;
        padding: 12px 20px;
        margin-bottom: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .group-header h5 { margin: 0; color: white; font-weight: 600; }
    .camera-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .camera-card:hover { transform: translateY(-4px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .toggle-icon { transition: transform 0.3s ease; }
    .toggle-icon.collapsed { transform: rotate(-90deg); }
</style>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Update Feed Kamera (Polling Gambar)
        function updateCameraFeed(cameraCard) {
            const imgElement = cameraCard.querySelector('.camera-feed-image');
            const timestampElement = cameraCard.querySelector('.camera-timestamp');
            const cameraId = imgElement.dataset.cameraId;

            if (!cameraId) return;

            fetch(`/api/cameras/${cameraId}/latest-image`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && imgElement.src !== data.image_url) {
                        imgElement.src = data.image_url;
                        if (timestampElement) timestampElement.textContent = 'Update: ' + data.captured_at;
                    }
                }).catch(e => console.error(e));
        }

        // 2. [BARU] Update Status Kamera & Statistik (Polling Status)
        function refreshStatuses() {
            fetch('/api/camera-statuses')
                .then(response => response.json())
                .then(data => {
                    let activeCount = 0;

                    Object.entries(data).forEach(([cameraId, info]) => {
                        const statusBadge = document.getElementById(`status-badge-${cameraId}`);
                        const isActive = (typeof info === 'object') ? info.is_active : info;

                        if (isActive) activeCount++;

                        if (statusBadge) {
                            if (isActive) {
                                statusBadge.className = 'badge bg-label-success';
                                statusBadge.textContent = 'Online';
                            } else {
                                statusBadge.className = 'badge bg-label-danger';
                                statusBadge.textContent = 'Offline';
                            }
                        }
                    });

                    // Update angka statistik di bagian atas
                    const activeCounterEl = document.getElementById('active-camera-counter');
                    if (activeCounterEl) {
                        activeCounterEl.textContent = activeCount;
                    }
                }).catch(e => console.error('Error refreshing statuses:', e));
        }

        const allCards = document.querySelectorAll('.camera-card');

        // Interval update gambar (5 detik)
        setInterval(() => allCards.forEach(updateCameraFeed), 5000);

        // Interval update status (5 detik)
        setInterval(refreshStatuses, 5000);
        refreshStatuses(); // Jalankan sekali saat start

        // Filter Auto-submit
        const filter = document.getElementById('groupFilter');
        if (filter) filter.addEventListener('change', () => filter.form.submit());
    });
</script>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Dashboard Pemantauan</h4>
            <p class="mb-0">Halo, <strong>{{ auth()->user()->name }}</strong>.</p>
        </div>
        @role('admin')
        <a href="{{ route('admin.camera-groups.index') }}" class="btn btn-primary">
            <i class="ti ti-settings me-1"></i> Kelola Grup
        </a>
        @endrole
    </div>

    {{-- Statistik Card --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>Total Kamera</span>
                            <h3 class="mb-0 mt-2">{{ $totalCameras ?? 0 }}</h3>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-camera ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>Kamera Aktif</span>
                            {{-- Tambahkan ID agar angka ini bisa di-update otomatis --}}
                            <h3 class="mb-0 mt-2 text-success" id="active-camera-counter">{{ $activeCameras ?? 0 }}</h3>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-video ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>Total Pengguna</span>
                            <h3 class="mb-0 mt-2">{{ $totalUsers ?? 0 }}</h3>
                        </div>
                        <span class="badge bg-label-secondary rounded p-2"><i class="ti ti-users ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Berdasarkan Nama Grup --}}
    @if(count($groups) > 1)
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" action="{{ url('/dashboard/groups') }}">
                @csrf
                <label class="form-label fw-bold"><i class="ti ti-filter me-1"></i> Pilih Grup</label>
                <div class="row">
                    <div class="col-md-4">
                        <select name="group" id="groupFilter" class="form-select">
                            @foreach($groups as $name)
                                <option value="{{ $name }}" {{ $currentGroup == $name ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    @php
        $groupedCameras = $cameras->groupBy(function($item) {
            return $item->group ? $item->group->name : 'Tanpa Grup';
        });
        $showHeaders = $currentGroup == 'Semua Kamera';
    @endphp

    @if($cameras->count() > 0)
        @foreach($groupedCameras as $groupName => $items)
            <div class="mb-5">
                @if($showHeaders)
                <div class="group-header">
                    <h5><i class="ti ti-folder me-2"></i>{{ $groupName }}</h5>
                    <span class="badge bg-white text-primary">{{ $items->count() }} Kamera</span>
                </div>
                @endif

                <div class="row g-4">
                    @foreach($items as $camera)
                        <div class="col-md-6 col-lg-4 camera-card">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">{{ $camera->name }}</h6>
                                    {{-- Tambahkan ID pada badge status --}}
                                    <span class="badge {{ $camera->is_active ? 'bg-label-success' : 'bg-label-danger' }}"
                                          id="status-badge-{{ $camera->id }}">
                                        {{ $camera->is_active ? 'Online' : 'Offline' }}
                                    </span>
                                </div>
                                <div class="card-body p-0 text-center bg-dark" style="overflow: hidden;">
                                    @php $latest = $camera; @endphp
                                    <img class="camera-feed-image"
                                         data-camera-id="{{ $camera->id }}"
                                         style="width: 100%; height: 200px; object-fit: cover;"
                                         src="{{ $camera->latest_image_path ? asset('https://apiminio.miot-its.org/iot-cctv/' . $camera->latest_image_path) : 'https://placehold.co/600x400?text=No+Feed' }}">
                                </div>
                                <div class="card-footer d-flex justify-content-between py-2">
                                    <small class="text-muted camera-timestamp">
                                        {{ $camera->latest_image_at ? $camera->latest_image_at->diffForHumans() : 'No Data' }}
                                    </small>
                                    <a href="{{ route('log.history.explorer', $camera->id) }}" class="btn btn-xs btn-primary">Detail</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @else
        <div class="alert alert-info text-center py-5">
            <i class="ti ti-camera-off d-block mb-3" style="font-size: 3rem;"></i>
            <h5>Tidak ada kamera ditemukan</h5>
            <p>Pilih grup lain atau tambahkan kamera baru.</p>
        </div>
    @endif
@endsection
