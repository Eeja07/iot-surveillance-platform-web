@extends('layouts/layoutMaster')

@section('title', 'Log Aktivitas')

@section('content')
    <h4 class="mb-4">Log Aktivitas Sistem</h4>

    <div class="card">
        <h5 class="card-header">Catatan Aktivitas</h5>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Aksi</th>
                        <th>Deskripsi</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($activityLogs as $log)
                        <tr>
                            <td>
                                {{-- Cek jika log memiliki user, jika tidak tampilkan 'Sistem' --}}
                                <strong>{{ $log->user->name ?? 'Sistem' }}</strong>
                            </td>
                            <td><span class="badge bg-label-secondary">{{ $log->action }}</span></td>
                            <td>{{ $log->description }}</td>
                            <td>{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Tidak ada aktivitas yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($activityLogs->hasPages())
            <div class="card-footer">
                {{ $activityLogs->links() }}
            </div>
        @endif
    </div>
@endsection
