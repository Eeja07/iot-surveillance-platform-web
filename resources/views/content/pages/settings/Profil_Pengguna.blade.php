@extends('layouts/layoutMaster')

@section('title', 'Manajemen Menu')

@section('vendor-style')
    {{-- Path untuk DataTables dan Select2 --}}
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    {{-- Path untuk DataTables dan Select2 --}}
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    <script>
        // Inisialisasi Select2 untuk dropdown
        $(function() {
            $('.select2').select2();
        });
    </script>
@endsection

@section('content')
    <h4 class="mb-4">Manajemen Menu</h4>

    {{-- Notifikasi Sukses atau Error --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Logika untuk menampilkan form Tambah atau Edit, atau Daftar Menu --}}
    {{-- Anda perlu menyesuaikan controller untuk mengirim variabel $showCreateForm dan $showEditForm --}}
    @php
        $action = request()->query('action');
        $showCreateForm = $action === 'create';
        $showEditForm = $action === 'edit';
    @endphp


    {{-- Bagian Form Tambah Menu --}}
    @if ($showCreateForm)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Tambah Item Menu Baru</h5>
                <a href="{{ route('admin.menus.index') }}" class="btn btn-label-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.menus.store') }}" method="POST">
                    @csrf
                    {{-- Nama Menu --}}
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Menu</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}"
                            placeholder="Contoh: Dashboard" required>
                    </div>
                    {{-- URL --}}
                    <div class="mb-3">
                        <label for="url" class="form-label">URL</label>
                        <input type="text" class="form-control" id="url" name="url"
                            value="{{ old('url', '#') }}" placeholder="Contoh: /dashboard atau #" required>
                    </div>
                    {{-- Ikon --}}
                    <div class="mb-3">
                        <label for="icon" class="form-label">Ikon (Opsional)</label>
                        <input type="text" class="form-control" id="icon" name="icon" value="{{ old('icon') }}"
                            placeholder="Contoh: ti ti-smart-home">
                    </div>
                    {{-- Menu Induk --}}
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Menu Induk (Opsional)</label>
                        <select class="form-select select2" id="parent_id" name="parent_id">
                            <option value="">-- Tidak Ada Induk --</option>
                            @foreach ($parentMenus as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Role --}}
                    <div class="mb-3">
                        <label for="roles" class="form-label">Role yang Diizinkan (Opsional)</label>
                        <select class="form-select select2" id="roles" name="roles[]" multiple>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Kosongkan jika menu bisa diakses semua role.</small>
                    </div>
                    {{-- Urutan --}}
                    <div class="mb-3">
                        <label for="order" class="form-label">Urutan</label>
                        <input type="number" class="form-control" id="order" name="order"
                            value="{{ old('order', 0) }}">
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">Simpan</button>
                        <a href="{{ route('admin.menus.index') }}" class="btn btn-label-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Bagian Form Edit Menu --}}
    @if ($showEditForm && isset($menu))
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit Item Menu: {{ $menu->name }}</h5>
                <a href="{{ route('admin.menus.index') }}" class="btn btn-label-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.menus.update', $menu->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    {{-- Nama Menu --}}
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Menu</label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="{{ old('name', $menu->name) }}" required>
                    </div>
                    {{-- URL --}}
                    <div class="mb-3">
                        <label for="url" class="form-label">URL</label>
                        <input type="text" class="form-control" id="url" name="url"
                            value="{{ old('url', $menu->url) }}" required>
                    </div>
                    {{-- Ikon --}}
                    <div class="mb-3">
                        <label for="icon" class="form-label">Ikon (Opsional)</label>
                        <input type="text" class="form-control" id="icon" name="icon"
                            value="{{ old('icon', $menu->icon) }}">
                    </div>
                    {{-- Menu Induk --}}
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Menu Induk (Opsional)</label>
                        <select class="form-select select2" id="parent_id" name="parent_id">
                            <option value="">-- Tidak Ada Induk --</option>
                            @foreach ($parentMenus as $item)
                                <option value="{{ $item->id }}"
                                    {{ old('parent_id', $menu->parent_id) == $item->id ? 'selected' : '' }}>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Role --}}
                    <div class="mb-3">
                        <label for="roles" class="form-label">Role yang Diizinkan (Opsional)</label>
                        <select class="form-select select2" id="roles" name="roles[]" multiple>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}"
                                    {{ in_array($role->id, old('roles', $menu->roles->pluck('id')->toArray())) ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Urutan --}}
                    <div class="mb-3">
                        <label for="order" class="form-label">Urutan</label>
                        <input type="number" class="form-control" id="order" name="order"
                            value="{{ old('order', $menu->order) }}">
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">Update</button>
                        <a href="{{ route('admin.menus.index') }}" class="btn btn-label-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Bagian Daftar Menu --}}
    @if (!$showCreateForm && !$showEditForm)
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Menu</h5>
                <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> Tambah Item Menu
                </a>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Urutan</th>
                            <th>Nama Menu</th>
                            <th>URL</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse ($menus as $menuItem)
                            {{-- Menu Induk --}}
                            <tr>
                                <td>{{ $menuItem->order }}</td>
                                <td><strong><i class="{{ $menuItem->icon }} me-2"></i>{{ $menuItem->name }}</strong>
                                </td>
                                <td>{{ $menuItem->url }}</td>
                                <td>
                                    @forelse ($menuItem->roles as $role)
                                        <span class="badge bg-label-secondary me-1">{{ $role->name }}</span>
                                    @empty
                                        <span class="badge bg-label-info">Semua</span>
                                    @endforelse
                                </td>
                                <td>
                                    <a href="{{ route('admin.menus.edit', $menuItem->id) }}"
                                        class="btn btn-sm btn-icon btn-info">
                                        <i class="ti ti-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.menus.destroy', $menuItem->id) }}" method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Anda yakin ingin menghapus menu ini? Submenu juga akan terhapus.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-icon btn-danger">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            {{-- Submenu --}}
                            @foreach ($menuItem->children as $child)
                                <tr>
                                    <td>{{ $child->order }}</td>
                                    <td class="ps-4"><i class="ti ti-arrow-right me-2"></i> {{ $child->name }}</td>
                                    <td>{{ $child->url }}</td>
                                    <td>
                                        @forelse ($child->roles as $role)
                                            <span class="badge bg-label-secondary me-1">{{ $role->name }}</span>
                                        @empty
                                            <span class="badge bg-label-info">Semua</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.menus.edit', $child->id) }}"
                                            class="btn btn-sm btn-icon btn-info">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.menus.destroy', $child->id) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Anda yakin ingin menghapus submenu ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-icon btn-danger">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Belum ada item menu. Silakan tambahkan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endsection
