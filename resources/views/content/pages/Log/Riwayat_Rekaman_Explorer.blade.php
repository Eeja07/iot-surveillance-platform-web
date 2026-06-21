@extends('layouts/layoutMaster')

@section('title', 'Riwayat Explorer - ' . $camera->name)

{{-- [PERBAIKAN] Menggunakan CDN untuk memuat CSS library --}}
@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
@endsection

@section('page-style')
    {{-- CSS Kustom untuk tampilan folder dan galeri --}}
    <style>
        .folder-item,
        .gallery-item {
            border: 1px solid #dbdade;
            border-radius: 0.375rem;
            padding: 1.25rem;
            transition: all 0.2s ease-in-out;
            background-color: #fff;
        }

        .folder-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            border-color: var(--bs-primary);
        }

        .folder-icon {
            font-size: 2.5rem;
            color: #b9b8c3;
        }

        .folder-item:hover .folder-icon {
            color: var(--bs-primary);
        }

        .gallery-item img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 0.25rem;
            cursor: pointer;
        }

        .breadcrumb-item a {
            color: var(--bs-primary);
        }

        .breadcrumb-item.active {
            color: #6f6b7d;
        }

        .delete-form {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        /* [BARU] CSS untuk memberikan tanda/marker pada tanggal di kalender */
        .flatpickr-day.has-records {
            background: #e9defb;
            border-color: #e9defb;
            color: #7367f0;
            font-weight: bold;
        }

        .flatpickr-day.today.has-records {
            background: #7367f0;
            border-color: #7367f0;
            color: #fff;
        }
    </style>
@endsection

@section('content')
    {{-- Header Halaman dan Breadcrumbs --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('log.history.index') }}"><i class="ti ti-home"></i></a>
                    </li>
                    @foreach ($breadcrumbs as $index => $breadcrumb)
                        @if ($breadcrumb['url'])
                            <li class="breadcrumb-item">
                                <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['name'] }}</a>
                            </li>
                        @else
                            <li class="breadcrumb-item active">{{ $breadcrumb['name'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
            <p class="text-muted mb-0 mt-1">Jelajahi rekaman yang tersimpan untuk kamera {{ $camera->name }}.</p>
        </div>
        <a href="{{ route('log.history.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left me-1"></i>
            <span class="align-middle">Kembali ke Pilih Kamera</span>
        </a>
    </div>

    {{-- Kartu Filter Cepat --}}
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Navigasi Cepat</h5>
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="filter-date" class="form-label">Tanggal</label>
                    <input type="text" id="filter-date" class="form-control" placeholder="YYYY-MM-DD"
                        value="{{ $filter['date'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label for="filter-hour" class="form-label">Jam</label>
                    <select id="filter-hour" class="form-select">
                        <option value="">-- Pilih Jam --</option>
                        @for ($h = 0; $h < 24; $h++)
                            @php $hourKey = str_pad($h, 2, '0', STR_PAD_LEFT); @endphp
                            <option value="{{ $hourKey }}" {{ !isset($availableTimes[$hourKey]) ? 'disabled' : '' }}>
                                {{ $hourKey }}:00 {{ isset($availableTimes[$hourKey]) ? '✔' : '' }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter-minute" class="form-label">Menit</label>
                    <select id="filter-minute" class="form-select">
                        <option value="">-- Pilih Menit --</option>
                        @for ($m = 0; $m < 60; $m++)
                            <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}" disabled>
                                {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="filter-go-btn" class="btn btn-primary w-100">
                        <i class="ti ti-player-play me-1"></i>Search
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tampilan Konten Dinamis --}}
    <div class="card">
        <div class="card-body">
            @if (count($items) > 0)
                <h5 class="card-title mb-4">
                    @if ($level === 'date')
                        Pilih Tanggal Rekaman
                    @elseif ($level === 'hour')
                        Pilih Jam Rekaman
                    @elseif ($level === 'minute')
                        Pilih Menit Rekaman
                    @elseif ($level === 'chunk')
                        Pilih Grup Rekaman
                    @else
                        Galeri Gambar ({{ count($items) }} gambar)
                    @endif
                </h5>
                <div class="row g-4">
                    @if ($level === 'gallery')
                        @foreach ($items as $image)
                            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                <div class="gallery-item text-center">
                                    <a href="{{ $image['url'] }}" data-fancybox="gallery"
                                        data-caption="Waktu Rekaman: {{ $image['time'] }}">
                                        <img src="{{ $image['url'] }}" alt="Rekaman {{ $image['name'] }}">
                                    </a>
                                    <small class="d-block mt-2 text-muted">{{ $image['time'] }}</small>
                                </div>
                            </div>
                        @endforeach
                    @else
                        @foreach ($items as $item)
                            <div class="col-12 col-md-6 col-lg-4">
                                <a href="{{ $item['url'] }}" class="text-decoration-none">
                                    <div class="folder-item d-flex align-items-center gap-3 position-relative">
                                        <i class="ti ti-folder folder-icon"></i>
                                        <div>
                                            <h6 class="mb-0">{{ $item['name'] }}</h6>
                                            <small class="text-muted">{{ $item['count'] }} rekaman</small>
                                        </div>
                                        @if ($level === 'date')
                                            <form action="{{ route('log.history.destroy.folder', $camera->id) }}"
                                                method="POST" class="delete-form"
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus semua {{ $item['count'] }} rekaman untuk tanggal {{ $item['name'] }}? Tindakan ini tidak dapat diurungkan.');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="date" value="{{ $item['raw_date'] }}">
                                                <button type="submit"
                                                    class="btn btn-sm btn-icon btn-text-danger rounded-pill">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    @endif
                </div>
                @if ($level === 'date' && $items->hasPages())
                    <div class="mt-4">
                        {{ $items->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="ti ti-photo-off ti-lg text-muted mb-3"></i>
                    <p class="text-muted">Tidak ada rekaman yang ditemukan di sini.</p>
                    @if (count($breadcrumbs) > 1)
                        <a href="{{ $breadcrumbs[count($breadcrumbs) - 2]['url'] }}" class="btn btn-primary mt-2">
                            <i class="ti ti-arrow-left me-1"></i> Kembali
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection

{{-- [PERBAIKAN] Menggunakan CDN untuk memuat JS library --}}
@section('vendor-script')
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endsection

{{-- [PERBAIKAN] Memindahkan semua logika JS ke dalam Blade file --}}
@section('page-script')
    <script>
        // Mengirimkan data dari PHP ke JavaScript
        const allAvailableDates = @json($allAvailableDates ?? []);
        const availableTimes = @json($availableTimes ?? []);
        const explorerFilters = @json($filter ?? []);
        const explorerBaseUrl = "{{ route('log.history.explorer', $camera->id) }}";

        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi Fancybox
            Fancybox.bind('[data-fancybox="gallery"]', {});

            // Inisialisasi Flatpickr dan logika filter
            const filterDateInput = document.getElementById('filter-date');
            if (filterDateInput) {
                // [BARU] Fungsi terpisah untuk menandai tanggal, agar bisa dipanggil ulang
                function markAvailableDays(instance) {
                    const dayElements = instance.days.childNodes;
                    dayElements.forEach(function(dayElem) {
                        const y = dayElem.dateObj.getFullYear();
                        const m = String(dayElem.dateObj.getMonth() + 1).padStart(2, '0');
                        const d = String(dayElem.dateObj.getDate()).padStart(2, '0');
                        const dateString = `${y}-${m}-${d}`;

                        if (allAvailableDates.includes(dateString)) {
                            dayElem.classList.add("has-records");
                        }
                    });
                }

                flatpickr(filterDateInput, {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd F Y',
                    // [PERBAIKAN] Menggunakan hook onReady dan onMonthChange untuk memastikan penanda selalu ada
                    onReady: function(selectedDates, dateStr, instance) {
                        markAvailableDays(instance);
                    },
                    onMonthChange: function(selectedDates, dateStr, instance) {
                        // Diberi sedikit delay agar kalender selesai menggambar ulang sebelum ditandai
                        setTimeout(() => {
                            markAvailableDays(instance);
                        }, 200);
                    },
                });

                const hourSelect = document.getElementById('filter-hour');
                const minuteSelect = document.getElementById('filter-minute');
                const currentHour = explorerFilters.hour || '';
                const currentMinute = explorerFilters.minute || '';

                function updateMinuteOptions() {
                    const selectedHour = hourSelect.value;
                    const availableMinutesForHour = availableTimes[selectedHour] || [];

                    if (!availableMinutesForHour.includes(minuteSelect.value)) {
                        minuteSelect.value = '';
                    }

                    for (const option of minuteSelect.options) {
                        if (option.value === '') continue;
                        if (availableMinutesForHour.includes(option.value)) {
                            option.disabled = false;
                            option.textContent = `${option.value} ✔`;
                        } else {
                            option.disabled = true;
                            option.textContent = option.value;
                        }
                    }
                }

                if (currentHour) {
                    hourSelect.value = currentHour;
                    updateMinuteOptions();
                    if (currentMinute) {
                        minuteSelect.value = currentMinute;
                    }
                }

                hourSelect.addEventListener('change', updateMinuteOptions);

                document.getElementById('filter-go-btn').addEventListener('click', function() {
                    const date = filterDateInput.value;
                    const hour = hourSelect.value;
                    const minute = minuteSelect.value;
                    if (!date) {
                        alert('Silakan pilih tanggal terlebih dahulu.');
                        return;
                    }
                    let finalUrl = `${explorerBaseUrl}/${date}`;
                    if (hour) {
                        finalUrl += `/${hour}`;
                    }
                    if (hour && minute) {
                        finalUrl += `/${minute}`;
                    }
                    window.location.href = finalUrl;
                });
            }
        });
    </script>
@endsection
