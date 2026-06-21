resources/views/content/admin/roles/manage_roles.blade.php

@extends('layouts/layoutMaster')

@section('title', 'Manajemen Role')

@section('vendor-style')
    {{-- Ensure these paths are correct for DataTables --}}
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection

@section('vendor-script')
    {{-- Ensure these paths are correct for DataTables --}}
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTables only if the user list table is visible
            if (document.getElementById('role-list-table')) {
                $('.datatables-basic').DataTable({
                    order: [
                        [0, 'asc']
                    ],
                    dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                    buttons: [{
                        extend: 'collection',
                        className: 'btn btn-label-primary dropdown-toggle me-2',
                        text: '<i class="ti ti-file-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
                        buttons: [{
                            extend: 'print',
                            text: '<i class="ti ti-printer me-1" ></i>Cetak',
                            exportOptions: {
                                columns: [0,
                                    1] // Columns to be printed: Role Name, User Count
                            }
                        }, {
                            extend: 'csv',
                            text: '<i class="ti ti-file-text me-1" ></i>CSV',
                            exportOptions: {
                                columns: [0, 1]
                            }
                        }, {
                            extend: 'excel',
                            text: '<i class="ti ti-file-spreadsheet me-1"></i>Excel',
                            exportOptions: {
                                columns: [0, 1]
                            }
                        }, {
                            extend: 'pdf',
                            text: '<i class="ti ti-file-description me-1"></i>PDF',
                            exportOptions: {
                                columns: [0, 1]
                            }
                        }]
                    }],
                });
                $('div.head-label').html('<h5 class="card-title mb-0">Daftar Role</h5>');
            }
        });
    </script>
@endsection

@section('content')
    <h4 class="mb-4">Manajemen Role</h4>

    {{-- Success or Error Notifications --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Create Role Form Section --}}
    @if ($showCreateForm)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Tambah Role Baru</h5>
                <a href="{{ route('settings.roles.index') }}" class="btn btn-label-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('settings.roles.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Role</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" value="{{ old('name') }}" placeholder="Contoh: Admin, Perawat, Dokter"
                            required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- If you want to manage permissions when creating a role --}}
                    {{-- <div class="mb-3">
                        <label for="permissions" class="form-label">Permissions</label>
                        <select class="form-select @error('permissions') is-invalid @enderror" id="permissions" name="permissions[]" multiple>
                            @foreach ($permissions as $permission)
                                <option value="{{ $permission->name }}" {{ in_array($permission->name, old('permissions', [])) ? 'selected' : '' }}>
                                    {{ $permission->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('permissions')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Select one or more permissions for this role.</small>
                    </div> --}}
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">Simpan Role</button>
                        <a href="{{ route('settings.roles.index') }}" class="btn btn-label-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Edit Role Form Section --}}
    @if ($showEditForm && $editingRole)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit Role: {{ $editingRole->name }}</h5>
                <a href="{{ route('settings.roles.index') }}" class="btn btn-label-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('settings.roles.update', $editingRole->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Role</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" value="{{ old('name', $editingRole->name) }}"
                            placeholder="Contoh: Admin, Perawat, Dokter" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- If you want to manage permissions when editing a role --}}
                    {{-- <div class="mb-3">
                        <label for="permissions" class="form-label">Permissions</label>
                        <select class="form-select @error('permissions') is-invalid @enderror" id="permissions" name="permissions[]" multiple>
                            @foreach ($permissions as $permission)
                                <option value="{{ $permission->name }}" {{ in_array($permission->name, old('permissions', $rolePermissions)) ? 'selected' : '' }}>
                                    {{ $permission->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('permissions')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Select one or more permissions for this role.</small>
                    </div> --}}
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">Update Role</button>
                        <a href="{{ route('settings.roles.index') }}" class="btn btn-label-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Role List Section (always displayed unless a form is active) --}}
    @if (!$showCreateForm && !$showEditForm)
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">Daftar Role</h5>
            <a href="{{ route('settings.roles.index', ['action' => 'create']) }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> Tambah Role Baru
            </a>
        </div>

        <div class="card">
            <div class="card-datatable table-responsive pt-0">
                <table class="datatables-basic table" id="role-list-table"> {{-- Add ID for JS initialization --}}
                    <thead>
                        <tr>
                            <th>Nama Role</th>
                            <th>Jumlah User</th>
                            {{-- <th>Permissions</th> --}} {{-- If you want to display permissions --}}
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td>{{ $role->name }}</td>
                                <td>{{ $role->users()->count() }}</td> {{-- Count users with this role --}}
                                {{-- <td>
                                    @forelse ($role->permissions as $permission)
                                        <span class="badge bg-label-secondary me-1">{{ $permission->name }}</span>
                                    @empty
                                        <span class="badge bg-label-light">Tidak ada</span>
                                    @endforelse
                                </td> --}}
                                <td>
                                    <div class="d-inline-flex">
                                        <a href="{{ route('settings.roles.index', ['action' => 'edit', 'role_id' => $role->id]) }}"
                                            class="btn btn-sm btn-icon btn-info me-2" data-bs-toggle="tooltip"
                                            data-bs-placement="top" title="Edit">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                        <form action="{{ route('settings.roles.destroy', $role->id) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus role ini? User yang memiliki role ini mungkin akan kehilangan akses.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-icon btn-danger"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4">Tidak ada role ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
