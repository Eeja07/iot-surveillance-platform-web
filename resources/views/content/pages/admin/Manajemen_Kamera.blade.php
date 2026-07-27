@extends('layouts/layoutMaster')

@section('title', 'Manajemen Kamera')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
    ])
@endsection

@section('page-script')
    @if(!isset($view) || $view == 'index')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var dt_basic = $('.datatables-basic').DataTable({
                processing: true,
                order: [[0, 'desc']],
                dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                displayLength: 10,
                lengthMenu: [10, 25, 50],
                buttons: [{
                    text: '<i class="ti ti-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Daftarkan Kamera Baru</span>',
                    className: 'create-new btn btn-primary',
                    action: function(e, dt, node, config) {
                        window.location.href = "{{ route('admin.cameras.create') }}";
                    }
                }],
                language: {
                    search: "Cari:",
                    lengthMenu: "_MENU_",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ kamera",
                    paginate: {
                        next: "Berikutnya",
                        previous: "Sebelumnya"
                    }
                }
            });
            $('div.head-label').html('<h5 class="card-title mb-0">Daftar Kamera Terdaftar</h5>');
        });
    </script>
    @endif
@endsection

@section('content')
    {{-- Toast Notification for Success or Error --}}
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <span class="alert-icon text-success me-2">
                <i class="ti ti-check ti-xs"></i>
            </span>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <span class="alert-icon text-danger me-2">
                <i class="ti ti-ban ti-xs"></i>
            </span>
            {{ session('error') }}
        </div>
    @endif

    @if(session('newCamera'))
        @php $newCam = session('newCamera'); @endphp
        <div class="card border border-success mb-4 shadow-sm">
            <div class="card-header bg-label-success d-flex justify-content-between align-items-center">
                <h5 class="card-title text-success mb-0">
                    <i class="ti ti-circle-check me-2"></i> Kredensial Kamera Baru Berhasil Dibuat
                </h5>
                <span class="badge bg-success">Simpan Kredensial Ini</span>
            </div>
            <div class="card-body pt-3">
                <div class="alert alert-warning mb-3" role="alert">
                    <i class="ti ti-alert-triangle me-1"></i> <strong>Perhatian:</strong> Password MQTT hanya ditampilkan <strong>SEKALI INI SAJA</strong> dan tidak akan dapat dilihat kembali setelah Anda meninggalkan halaman ini.
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Device ID (UUID)</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-white" value="{{ $newCam->device_id }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('{{ $newCam->device_id }}'); alert('Device ID disalin!');">
                                <i class="ti ti-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">API Key</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-white" value="{{ $newCam->api_key }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('{{ $newCam->api_key }}'); alert('API Key disalin!');">
                                <i class="ti ti-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">MQTT Username</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-white" value="{{ $newCam->mqtt_username }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('{{ $newCam->mqtt_username }}'); alert('MQTT Username disalin!');">
                                <i class="ti ti-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">MQTT Password</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-white border-success fw-bold text-success" value="{{ $newCam->mqtt_password }}" readonly>
                            <button class="btn btn-outline-success" type="button" onclick="navigator.clipboard.writeText('{{ $newCam->mqtt_password }}'); alert('MQTT Password disalin!');">
                                <i class="ti ti-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">WebSocket Channel ID</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-white" value="{{ $newCam->websocket_channel_id }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('{{ $newCam->websocket_channel_id }}'); alert('WebSocket Channel ID disalin!');">
                                <i class="ti ti-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 1. INDEX VIEW --}}
    @if(!isset($view) || $view == 'index')
        <div class="card">
            <div class="card-datatable table-responsive pt-0">
                <table class="datatables-basic table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Kamera</th>
                            <th>Device ID</th>
                            <th>Group</th>
                            <th>Status Heartbeat</th>
                            <th>Status Admin</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cameras as $camera)
                            <tr>
                                <td>{{ $camera->id }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $camera->name }}</span>
                                    @if($camera->description)
                                        <br><small class="text-muted">{{ Str::limit($camera->description, 50) }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $camera->device_id }}</code></td>
                                <td>
                                    @if($camera->group)
                                        <span class="badge bg-label-info">{{ $camera->group->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($camera->is_online)
                                        <span class="badge bg-label-success">Online</span>
                                    @else
                                        <span class="badge bg-label-danger">Offline</span>
                                    @endif
                                </td>
                                <td>
                                    @if($camera->admin_enabled)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-inline-block text-nowrap">
                                        <a href="{{ route('admin.cameras.edit', $camera->id) }}" class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect waves-light" title="Edit & Kredensial">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="{{ route('admin.cameras.qrcode', $camera->id) }}" class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect waves-light" title="Unduh QR Code">
                                            <i class="ti ti-qrcode"></i>
                                        </a>
                                        <form action="{{ route('admin.cameras.destroy', $camera->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kamera ini? Semua data histori dan media akan ikut terhapus secara permanen.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-icon btn-text-danger rounded-pill waves-effect waves-light" title="Hapus">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-end">
                {{ $cameras->links() }}
            </div>
        </div>
    @endif

    {{-- 2. CREATE VIEW --}}
    @if(isset($view) && $view == 'create')
        @php
            $groups = \App\Models\CameraGroup::where('user_id', auth()->id())->get();
        @endphp
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Daftarkan Kamera Baru</h5>
                        <a href="{{ route('admin.cameras.index') }}" class="btn btn-sm btn-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.cameras.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama Kamera <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: Kamera Depan Rumah" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Deskripsi lokasi atau fungsi kamera (opsional)">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="group_id" class="form-label">Grup Kamera</label>
                                <select class="form-select @error('group_id') is-invalid @enderror" id="group_id" name="group_id">
                                    <option value="">-- Tanpa Grup (Ungrouped) --</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                                    @endforeach
                                </select>
                                @error('group_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="ti ti-plus me-1"></i> Daftarkan Kamera
                                </button>
                                <a href="{{ route('admin.cameras.index') }}" class="btn btn-label-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 3. EDIT VIEW --}}
    @if(isset($view) && $view == 'edit')
        @php
            $groups = \App\Models\CameraGroup::where('user_id', auth()->id())->get();
        @endphp
        <div class="row">
            {{-- Form Edit Utama --}}
            <div class="col-md-7">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Edit Detail Kamera</h5>
                        <a href="{{ route('admin.cameras.index') }}" class="btn btn-sm btn-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.cameras.update', $camera->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama Kamera <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $camera->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $camera->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="group_id" class="form-label">Grup Kamera</label>
                                <select class="form-select @error('group_id') is-invalid @enderror" id="group_id" name="group_id">
                                    <option value="">-- Tanpa Grup (Ungrouped) --</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ old('group_id', $camera->group_id) == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                                    @endforeach
                                </select>
                                @error('group_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-4">
                                <label class="form-label d-block">Status Administratif</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="admin_enabled" value="0">
                                    <input class="form-check-input" type="checkbox" id="admin_enabled" name="admin_enabled" value="1" {{ old('admin_enabled', $camera->admin_enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="admin_enabled">Aktifkan Kamera (Mengizinkan koneksi dan penerimaan data)</label>
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="ti ti-device-floppy me-1"></i> Simpan Perubahan
                                </button>
                                <a href="{{ route('admin.cameras.index') }}" class="btn btn-label-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Integrasi & Kredensial Kamera --}}
            <div class="col-md-5">
                <div class="card mb-4">
                    <h5 class="card-header bg-label-primary">Integrasi & Kredensial ESP32</h5>
                    <div class="card-body pt-3">
                        <div class="alert alert-info" role="alert">
                            Gunakan kredensial berikut untuk melakukan konfigurasi firmware ESP32-CAM Anda agar dapat terhubung ke sistem.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Device ID (UUID)</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light" value="{{ $camera->device_id }}" readonly id="cred-device-id">
                                <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText('{{ $camera->device_id }}'); alert('Device ID disalin!');">
                                    <i class="ti ti-copy"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">API Key (Web Webhook)</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light" value="{{ $camera->api_key }}" readonly id="cred-api-key">
                                <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText('{{ $camera->api_key }}'); alert('API Key disalin!');">
                                    <i class="ti ti-copy"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">MQTT Username</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light" value="{{ $camera->mqtt_username }}" readonly id="cred-mqtt-user">
                                <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText('{{ $camera->mqtt_username }}'); alert('MQTT Username disalin!');">
                                    <i class="ti ti-copy"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">MQTT Password</label>
                            <div class="input-group">
                                @if(session('newCamera') && session('newCamera')->id == $camera->id)
                                    <input type="text" class="form-control bg-light text-success fw-bold" value="{{ session('newCamera')->mqtt_password }}" readonly id="cred-mqtt-pass">
                                    <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText('{{ session('newCamera')->mqtt_password }}'); alert('MQTT Password disalin!');">
                                        <i class="ti ti-copy"></i>
                                    </button>
                                @else
                                    <input type="password" class="form-control bg-light" value="••••••••••••••••" readonly disabled id="cred-mqtt-pass">
                                    <span class="input-group-text text-muted"><i class="ti ti-lock"></i></span>
                                @endif
                            </div>
                            @if(!session('newCamera') || session('newCamera')->id != $camera->id)
                                <small class="text-muted">Password disembunyikan demi keamanan (hanya tampil saat baru dibuat).</small>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">WebSocket Channel ID</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light" value="{{ $camera->websocket_channel_id }}" readonly id="cred-ws-channel">
                                <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText('{{ $camera->websocket_channel_id }}'); alert('WebSocket Channel ID disalin!');">
                                    <i class="ti ti-copy"></i>
                                </button>
                            </div>
                        </div>

                        <div class="text-center my-4 p-3 border rounded bg-light">
                            <h6 class="mb-2">Device ID QR Code</h6>
                            <div class="d-flex justify-content-center mb-3">
                                {!! QrCode::size(150)->generate($camera->device_id) !!}
                            </div>
                            <a href="{{ route('admin.cameras.qrcode', $camera->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-download me-1"></i> Unduh QR Code (SVG)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
