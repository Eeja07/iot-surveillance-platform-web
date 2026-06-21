@extends('layouts/layoutMaster')

@section('title', 'Dashboard User')

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

    .group-header h5 {
        margin: 0;
        color: white;
        font-weight: 600;
    }

    .group-actions {
        display: flex;
        gap: 8px;
    }

    .group-filter-container {
        background: white;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .group-filter-label {
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
    }

    .camera-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .camera-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .toggle-icon {
        transition: transform 0.3s ease;
    }

    .toggle-icon.collapsed {
        transform: rotate(-90deg);
    }
</style>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Logika untuk update gambar ---
        function updateCameraFeed(cameraCard) {
            const imgElement = cameraCard.querySelector('.camera-feed-image');
            const timestampElement = cameraCard.querySelector('.camera-timestamp');
            const cameraId = imgElement.dataset.cameraId;

            if (!cameraId) return;

            fetch(`/api/cameras/${cameraId}/latest-image`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (imgElement.src !== data.image_url) {
                            imgElement.src = data.image_url;
                        }
                        if (timestampElement) {
                            timestampElement.textContent = 'Update: ' + data.captured_at;
                        }
                    }
                })
                .catch(error => console.error('Error fetching image for camera ' + cameraId + ':', error));
        }

        const allCameraCards = document.querySelectorAll('.camera-card');
        allCameraCards.forEach(updateCameraFeed);
        setInterval(() => {
            allCameraCards.forEach(updateCameraFeed);
        }, 5000);

        // --- Polling status kamera & Update Statistik ---
        function checkCameraStatuses() {
            fetch('/api/camera-statuses')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(statuses => {
                    let activeCount = 0; // Variabel penampung jumlah kamera aktif

                    for (const cameraId in statuses) {
                        const statusBadge = document.getElementById(`camera-status-${cameraId}`);
                        const info = statuses[cameraId];

                        // Menangani jika data berupa objek atau boolean langsung
                        const isActive = (typeof info === 'object') ? info.is_active : info;

                        if (isActive) activeCount++; // Tambahkan jika kamera aktif

                        if (statusBadge) {
                            if (isActive) {
                                statusBadge.classList.remove('bg-label-danger');
                                statusBadge.classList.add('bg-label-success');
                                statusBadge.textContent = 'Aktif';
                            } else {
                                statusBadge.classList.remove('bg-label-success');
                                statusBadge.classList.add('bg-label-danger');
                                statusBadge.textContent = 'Offline';
                            }
                        }
                    }

                    // UPDATE STATISTIK: Perbarui angka pada card statistik di atas
                    const counterEl = document.getElementById('active-camera-counter');
                    if (counterEl) {
                        counterEl.textContent = activeCount;
                    }
                })
                .catch(error => console.error('Error fetching camera statuses:', error));
        }

        // Jalankan saat load dan set interval (5 detik untuk responsivitas tinggi)
        checkCameraStatuses();
        setInterval(checkCameraStatuses, 5000);

        // --- Toggle collapse untuk grup ---
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(button => {
            button.addEventListener('click', function() {
                const icon = this.querySelector('.toggle-icon');
                if (icon) icon.classList.toggle('collapsed');
            });
        });

        // --- Auto-submit form filter ---
        const groupSelect = document.getElementById('groupFilter');
        if (groupSelect) {
            groupSelect.addEventListener('change', function() {
                this.form.submit();
            });
        }
    });
</script>
@endsection

@section('content')
{{-- Header Dashboard --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Dashboard Pemantauan Kamera</h4>
        <p class="mb-0">Selamat datang kembali, <strong>{{ auth()->user()->name ?? 'User' }}</strong>.</p>
    </div>

    <div>
        <a href="{{ route('user.camera-groups.index') }}" class="btn btn-outline-primary shadow-sm">
            <i class="ti ti-settings me-1"></i> Kelola Grup
        </a>
    </div>
</div>

{{-- Kartu Statistik --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-muted">Total Kamera</span>
                        <div class="d-flex align-items-end mt-2">
                            <h3 class="mb-0 me-2">{{ $totalCameras ?? 0 }}</h3>
                        </div>
                    </div>
                    <span class="badge bg-label-primary rounded p-2">
                        <i class="ti ti-camera ti-sm"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-muted">Kamera Aktif</span>
                        <div class="d-flex align-items-end mt-2">
                            {{-- Menambahkan ID agar sinkron dengan skrip auto-refresh --}}
                            <h3 class="mb-0 me-2 text-success" id="active-camera-counter">{{ $activeCameras ?? 0 }}</h3>
                        </div>
                    </div>
                    <span class="badge bg-label-success rounded p-2">
                        <i class="ti ti-video ti-sm"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Grup --}}
@if(count($groups ?? []) > 1)
<div class="group-filter-container border-0 shadow-sm">
    <form method="POST" action="{{ route('user.dashboard.groups') }}" id="groupFilterForm">
        @csrf
        <label class="group-filter-label">
            <i class="ti ti-filter me-1 text-primary"></i> Filter Berdasarkan Grup
        </label>
        <div class="row">
            <div class="col-md-6">
                <select name="group" id="groupFilter" class="form-select border-0 bg-light">
                    @foreach($groups as $group)
                        <option value="{{ $group }}" {{ ($currentGroup ?? '') == $group ? 'selected' : '' }}>
                            {{ $group }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
</div>
@endif

{{-- Judul Pratinjau --}}
<h5 class="mb-4 d-flex align-items-center">
    <i class="ti ti-video me-2 text-primary"></i>Pratinjau Kamera
    @if(($currentGroup ?? 'Semua Kamera') != 'Semua Kamera')
        <span class="badge bg-primary ms-2">{{ $currentGroup }}</span>
    @endif
</h5>

@php
    $groupedCameras = $cameras->groupBy(function($camera) {
        return $camera->group ? $camera->group->name : 'Tanpa Grup';
    });
    $showGroupHeaders = ($currentGroup ?? 'Semua Kamera') == 'Semua Kamera';
@endphp

@if($cameras->count() > 0)
    @foreach($groupedCameras as $groupName => $groupCameras)
        <div class="mb-5">
            @if($showGroupHeaders)
                <div class="group-header">
                    <h5>
                        <i class="ti ti-folder me-2"></i>
                        {{ $groupName }}
                        <span class="badge bg-white text-primary ms-2">{{ $groupCameras->count() }}</span>
                    </h5>
                    <div class="group-actions">
                        <button class="btn btn-sm btn-light" type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#group-{{ \Illuminate\Support\Str::slug($groupName) }}"
                                aria-expanded="true">
                            <i class="ti ti-chevron-down toggle-icon"></i>
                        </button>
                    </div>
                </div>
            @endif

            <div class="collapse show" id="group-{{ \Illuminate\Support\Str::slug($groupName) }}">
                <div class="row g-4">
                    @foreach($groupCameras as $camera)
                        <div class="col-md-6 col-lg-4 camera-card">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-0 pb-0">
                                    <h6 class="card-title mb-0 fw-bold">{{ $camera->name }}</h6>
                                    <span class="badge {{ $camera->is_active ? 'bg-label-success' : 'bg-label-danger' }}"
                                        id="camera-status-{{ $camera->id }}">
                                        {{ $camera->is_active ? 'Aktif' : 'Offline' }}
                                    </span>
                                </div>
                                <div class="card-body text-center pt-3">
                                    @php $latestImage = $camera; @endphp
                                    <div class="bg-dark rounded overflow-hidden" style="height: 180px;">
                                        <img class="img-fluid camera-feed-image h-100 w-100"
                                            style="object-fit: cover;"
                                            data-camera-id="{{ $camera->id }}"
                                            src="{{ $camera->latest_image_path ? asset('https://apiminio.miot-its.org/iot-cctv/' . $camera->latest_image_path) : 'https://placehold.co/600x400/293445/FFFFFF?text=No+Feed' }}"
                                            alt="Live feed untuk {{ $camera->name }}">
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-between align-items-center bg-transparent border-0 pt-0">
                                    <small class="text-muted camera-timestamp">
                                        {{ $camera->latest_image_at ? $camera->latest_image_at->diffForHumans() : 'Belum ada data' }}
                                    </small>
                                    <a href="{{ route('log.history.explorer', $camera->id) }}"
                                        class="btn btn-sm btn-outline-primary">Riwayat</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <i class="ti ti-camera-off text-muted mb-3" style="font-size: 3rem;"></i>
                <h5 class="mt-3 fw-bold">Tidak ada kamera ditemukan</h5>
                <p class="text-muted">
                    @if(($currentGroup ?? 'Semua Kamera') != 'Semua Kamera')
                        Grup "{{ $currentGroup }}" saat ini kosong.
                    @else
                        Silakan daftarkan kamera baru untuk memulai pemantauan.
                    @endif
                </p>
            </div>
        </div>
    </div>
@endif
@endsection
