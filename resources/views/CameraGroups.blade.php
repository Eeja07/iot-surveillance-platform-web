@extends('layouts/layoutMaster')

@section('title', 'Manajemen Grup Kamera')

@section('vendor-style')
<style>
.group-card {
    transition: all 0.3s ease;
    border-left: 4px solid #667eea;
}

.group-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.camera-badge {
    display: inline-flex;
    align-items: center;
    margin: 4px;
    padding: 6px 10px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    max-width: 100%;
}

.camera-badge span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
}

.camera-badge:hover {
    background: #e9ecef;
}

.ungrouped-area {
    background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
    border-radius: 8px;
    padding: 20px;
    min-height: 150px;
}

.group-header-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    border-radius: 8px 8px 0 0;
}

.min-w-0 {
    min-width: 0;
}
</style>
@endsection

@section('content')
{{-- Header Halaman --}}
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="mb-1">
            <i class="ti ti-folders me-2"></i>Manajemen Grup Kamera
        </h4>
        <p class="mb-0 text-muted">Kelola dan atur kamera ke dalam grup untuk kemudahan monitoring</p>
    </div>
    <div class="align-self-end align-self-md-center">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
            <i class="ti ti-plus me-1"></i> <span class="d-none d-sm-inline">Buat Grup Baru</span>
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-check me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Kamera yang Belum Dikelompokkan --}}
@if($ungroupedCameras->count() > 0)
<div class="card mb-4">
    <div class="card-body ungrouped-area">
        <h5 class="mb-3">
            <i class="ti ti-alert-circle me-2"></i>
            Kamera Tanpa Grup ({{ $ungroupedCameras->count() }})
        </h5>
        <div class="d-flex flex-wrap gap-2">
            @foreach($ungroupedCameras as $camera)
            <div class="camera-badge" title="{{ $camera->name }}">
                <i class="ti ti-camera me-2 flex-shrink-0"></i>
                <span>{{ $camera->name }}</span>
                <button type="button"
                        class="btn btn-sm btn-primary ms-2 flex-shrink-0"
                        data-bs-toggle="modal"
                        data-bs-target="#assignCameraModal"
                        data-camera-id="{{ $camera->id }}"
                        data-camera-name="{{ $camera->name }}">
                    <i class="ti ti-arrow-right" style="font-size: 0.8rem;"></i>
                </button>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Daftar Grup --}}
<div class="row g-4">
    @forelse($groups as $group)
    <div class="col-md-6 col-xl-4">
        <div class="card group-card h-100 {{ $group->cameras->count() === 0 ? 'border-warning' : '' }}">
            <div class="group-header-custom d-flex justify-content-between align-items-center"
                 style="{{ $group->cameras->count() === 0 ? 'background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);' : '' }}">
                <div class="min-w-0 me-2">
                    <h5 class="mb-0 text-white text-truncate" title="{{ $group->name }}">
                        <i class="ti ti-folder me-2"></i>{{ $group->name }}
                    </h5>
                    <small class="text-white-50">{{ $group->cameras->count() }} Kamera</small>
                </div>
                <div class="dropdown flex-shrink-0">
                    <button class="btn btn-sm btn-light bg-white bg-opacity-25 border-0 text-white" type="button" data-bs-toggle="dropdown">
                        <i class="ti ti-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="#"
                               data-bs-toggle="modal"
                               data-bs-target="#editGroupModal"
                               data-group-id="{{ $group->id }}"
                               data-group-name="{{ $group->name }}">
                                <i class="ti ti-edit me-2"></i>Edit Nama
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="#"
                               onclick="event.preventDefault(); if(confirm('Yakin ingin menghapus grup ini?')) document.getElementById('delete-group-{{ $group->id }}').submit();">
                                <i class="ti ti-trash me-2"></i>Hapus Grup
                            </a>
                            <form id="delete-group-{{ $group->id }}"
                                  action="{{ route('admin.camera-groups.destroy', $group->id) }}"
                                  method="POST"
                                  class="d-none">
                                @csrf
                                @method('DELETE')
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-2">
                    @forelse($group->cameras as $camera)
                    <div class="d-flex justify-content-between align-items-center p-2 border rounded">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <i class="ti ti-camera text-primary flex-shrink-0"></i>
                            <span class="text-truncate fw-medium" title="{{ $camera->name }}">{{ $camera->name }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <button type="button"
                                    class="btn btn-icon btn-sm btn-outline-danger"
                                    onclick="event.preventDefault(); if(confirm('Keluarkan kamera ini?')) document.getElementById('remove-camera-{{ $camera->id }}').submit();">
                                <i class="ti ti-x"></i>
                            </button>
                            <form id="remove-camera-{{ $camera->id }}"
                                  action="{{ route('admin.camera-groups.remove') }}"
                                  method="POST"
                                  class="d-none">
                                @csrf
                                <input type="hidden" name="camera_id" value="{{ $camera->id }}">
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="alert alert-warning mb-0 p-2 small">
                        <i class="ti ti-alert-circle me-1"></i>Grup ini masih kosong.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <h5 class="mb-1">Belum Ada Grup</h5>
                <p class="text-muted mb-4">Buat grup baru untuk mulai mengorganisir kamera Anda</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                    <i class="ti ti-plus me-1"></i> Buat Grup Pertama
                </button>
            </div>
        </div>
    </div>
    @endforelse
</div>

{{-- Modal: Buat Grup Baru --}}
<div class="modal fade" id="createGroupModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.camera-groups.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Buat Grup Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label">Nama Grup <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="group_name" placeholder="Contoh: Lantai 1" required>
                    </div>
                    @if($ungroupedCameras->count() > 0)
                    <div class="mb-3">
                        <label class="form-label">Pilih Kamera (Opsional)</label>
                        <div class="border rounded" style="max-height: 200px; overflow-y: auto;">
                            <div class="list-group list-group-flush">
                                @foreach($ungroupedCameras as $camera)
                                <label class="list-group-item d-flex align-items-center">
                                    <input class="form-check-input me-3" type="checkbox" name="camera_ids[]" value="{{ $camera->id }}">
                                    <span>{{ $camera->name }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Edit Nama Grup --}}
<div class="modal fade" id="editGroupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="" method="POST" id="editGroupForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Nama Grup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Grup Baru</label>
                        <input type="text" class="form-control" id="new_group_name" name="new_group_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Assign Kamera ke Grup --}}
<div class="modal fade" id="assignCameraModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.camera-groups.assign') }}" method="POST">
                @csrf
                <input type="hidden" name="camera_id" id="assign_camera_id">
                <div class="modal-header">
                    <h5 class="modal-title">Pindahkan Kamera</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kamera Terpilih</label>
                        <input type="text" class="form-control" id="assign_camera_name" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pilih Grup Tujuan</label>
                        <select class="form-select" name="group_id" required>
                            <option value="">-- Pilih Grup --</option>
                            @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.getElementById('editGroupModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const groupId = button.getAttribute('data-group-id');
    const groupName = button.getAttribute('data-group-name');

    document.getElementById('new_group_name').value = groupName;
    const form = document.getElementById('editGroupForm');

    let updateUrl = "{{ route('admin.camera-groups.update', 'ID_PLACEHOLDER') }}";
    form.action = updateUrl.replace('ID_PLACEHOLDER', groupId);
});

document.getElementById('assignCameraModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const cameraId = button.getAttribute('data-camera-id');
    const cameraName = button.getAttribute('data-camera-name');

    document.getElementById('assign_camera_id').value = cameraId;
    document.getElementById('assign_camera_name').value = cameraName;
});
</script>
@endsection
