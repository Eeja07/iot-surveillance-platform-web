@extends('layouts/layoutMaster')

@section('title', 'Riwayat Rekaman')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="mb-0">Riwayat Rekaman</h4>
            <p class="text-muted mb-0">Pilih perangkat kamera untuk melihat riwayat rekaman yang tersimpan.</p>
        </div>
    </div>

    <div class="card">
        <h5 class="card-header">Pilih Kamera</h5>
        <div class="list-group list-group-flush">
            @forelse($cameras as $camera)
                <a href="{{ route('log.history.explorer', $camera->id) }}"
                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-camera ti-lg me-3 text-primary"></i>
                        <div class="d-flex flex-column">
                            <h6 class="mb-0">{{ $camera->name }}</h6>
                            <small class="text-muted">Device ID:
                                {{ \Illuminate\Support\Str::limit($camera->device_id, 13) }}...</small>
                        </div>
                    </div>
                    <span class="badge bg-label-primary rounded-pill">Lihat Riwayat <i
                            class="ti ti-chevron-right ms-1 ti-xs"></i></span>
                </a>
            @empty
                <div class="list-group-item text-center">
                    <p class="text-muted my-3">Belum ada kamera yang terdaftar.</p>
                </div>
            @endforelse
        </div>
        @if ($cameras->hasPages())
            <div class="card-footer">
                {{ $cameras->links() }}
            </div>
        @endif
    </div>
@endsection
