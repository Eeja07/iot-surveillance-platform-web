@extends('layouts/layoutMaster')

@section('title', 'Tambahkan Kamera Baru')

@section('content')
    <h4 class="mb-4">Tambahkan Perangkat Kamera</h4>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <h5 class="card-header">Sinkronkan Perangkat</h5>
                <div class="card-body">
                    <p>Hubungkan kamera ke akun Anda dengan mengetik <strong>Device ID</strong>, memindai <strong>Kode
                            QR</strong> secara langsung, atau mengunggah <strong>gambar Kode QR</strong>.</p>

                    {{-- Menampilkan pesan sukses dari controller --}}
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
                    @endif

                    {{-- Menampilkan semua pesan error validasi --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Area untuk pemindai QR (awalnya tersembunyi) --}}
                    <div id="qr-scanner-area" class="mb-3 text-center" style="display: none;">
                        <div id="qr-reader"
                            style="width: 100%; max-width: 500px; margin: auto; border: 1px solid #ddd; border-radius: 8px;">
                        </div>
                        <p id="qr-status" class="mt-2"></p>
                    </div>


                    <form action="{{ route('user.cameras.link.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        {{-- Input untuk Device ID manual --}}
                        <div class="mb-3">
                            <label for="device_id" class="form-label">Device ID</label>
                            <input type="text" class="form-control @error('device_id') is-invalid @enderror"
                                id="device_id" name="device_id" value="{{ old('device_id') }}"
                                placeholder="Ketik manual atau pindai/unggah QR Code">
                        </div>

                        {{-- Input BARU untuk upload gambar QR --}}
                        <div class="mb-3">
                            <label for="qr_image" class="form-label">Atau Unggah Gambar QR Code</label>
                            <input class="form-control @error('qr_image') is-invalid @enderror" type="file"
                                name="qr_image" id="qr_image" accept="image/*">
                        </div>

                        {{-- Tombol-tombol Aksi --}}
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="ti ti-link me-1"></i> Hubungkan Kamera
                            </button>
                            <button type="button" id="start-scan-btn" class="btn btn-info me-2">
                                <i class="ti ti-qrcode me-1"></i> Pindai Langsung
                            </button>
                            <a href="{{ route('user.dashboard') }}" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Memuat pustaka html5-qrcode dari CDN --}}
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startScanBtn = document.getElementById('start-scan-btn');
            const scannerArea = document.getElementById('qr-scanner-area');
            const deviceIdInput = document.getElementById('device_id');
            const qrStatus = document.getElementById('qr-status');
            let html5QrcodeScanner;

            if (startScanBtn) {
                startScanBtn.addEventListener('click', () => {
                    scannerArea.style.display = 'block';
                    qrStatus.innerText = 'Arahkan kamera ke Kode QR...';

                    if (!html5QrcodeScanner) {
                        html5QrcodeScanner = new Html5QrcodeScanner(
                            "qr-reader", {
                                fps: 10,
                                qrbox: {
                                    width: 250,
                                    height: 250
                                },
                                rememberLastUsedCamera: true
                            },
                            false
                        );
                    }
                    html5QrcodeScanner.render(
                        (decodedText, decodedResult) => {
                            deviceIdInput.value = decodedText;
                            qrStatus.innerHTML =
                                '<span class="text-success fw-bold">Pemindaian Berhasil!</span>';
                            html5QrcodeScanner.clear().catch(error => {
                                console.error("Gagal menghentikan scanner.", error);
                            });
                            scannerArea.style.display = 'none';
                        },
                        (error) => {
                            /* Abaikan error pemindaian */ }
                    );
                });
            }
        });
    </script>
@endpush
