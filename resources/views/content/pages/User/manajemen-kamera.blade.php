@extends('layouts/layoutMaster')

@section('title', 'Manajemen Kamera Saya')

{{-- Vendor Styles untuk DataTables --}}
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection

{{-- Vendor Scripts untuk DataTables --}}
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

{{-- Page Scripts --}}
@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dataUrl = "{{ route('user.my-cameras.data') }}";

            var dt_basic = $('.datatables-basic').DataTable({
                processing: true,
                serverSide: true,
                ajax: dataUrl,
                columns: [{
                        data: 'id',
                        name: 'id',
                        visible: false
                    },
                    {
                        data: 'name',
                        name: 'name',
                        title: 'Nama Kamera'
                    },
                    {
                        data: 'device_id',
                        name: 'device_id',
                        title: 'Device ID'
                    },
                    {
                        data: 'is_active',
                        name: 'is_active',
                        title: 'Status',
                        render: function(data, type, full, meta) {
                            var status = {
                                1: {
                                    title: 'Aktif',
                                    class: 'bg-label-success'
                                },
                                0: {
                                    title: 'Nonaktif',
                                    class: 'bg-label-danger'
                                }
                            };
                            // [PERBAIKAN] Mengonversi nilai boolean (true/false) menjadi angka (1/0)
                            const statusKey = data ? 1 : 0;
                            return '<span class="badge ' + status[statusKey].class + '">' +
                                status[statusKey].title + '</span>';
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        title: 'Aksi',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                displayLength: 10,
                lengthMenu: [10, 25, 50],
                buttons: [{
                    text: '<i class="ti ti-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Tambah Kamera</span>',
                    className: 'create-new btn btn-primary',
                    action: function(e, dt, node, config) {
                        window.location.href =
                            "{{ route('user.cameras.link.create') }}";
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
            $('div.head-label').html('<h5 class="card-title mb-0">Manajemen Kamera Saya</h5>');

            // [BARU] Fungsi auto-refresh untuk DataTables
            setInterval(function() {
                dt_basic.ajax.reload(null, false); // 'false' menjaga pagination tetap di halaman saat ini
            }, 10000); // Reload data setiap 10 detik
        });
    </script>
@endsection

@section('content')

    {{-- TAMPILAN DAFTAR KAMERA (INDEX) --}}
    @if (!isset($view) || (isset($view) && $view == 'index'))
        <div class="card">
            <div class="card-datatable table-responsive pt-0">
                <table class="datatables-basic table">
                    {{-- Header dan Body tabel akan dibuat oleh JavaScript DataTables --}}
                </table>
            </div>
        </div>
    @endif

    {{-- TAMPILAN FORM EDIT KAMERA (EDIT) --}}
    @if (isset($view) && $view == 'edit')
        <h4 class="mb-4">Edit Detail Kamera</h4>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <h5 class="card-header">Detail Kamera</h5>
                    <div class="card-body">
                        <form action="{{ route('user.my-cameras.update', $camera->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label for="camera_name" class="form-label">Nama Kamera <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="camera_name" name="name"
                                    value="{{ old('name', $camera->name) }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="camera_description" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="camera_description" name="description" rows="3">{{ old('description', $camera->description) }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="ti ti-device-floppy me-1"></i> Simpan Perubahan
                            </button>
                            <a href="{{ route('user.my-cameras.index') }}" class="btn btn-secondary">Batal</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection
