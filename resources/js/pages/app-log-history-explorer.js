// 1. Import library yang dibutuhkan dari node_modules
import { Fancybox } from '@fancyapps/ui';
import flatpickr from 'flatpickr';

// 2. Inisialisasi Fancybox untuk galeri gambar
Fancybox.bind('[data-fancybox="gallery"]', {
  // Opsi kustom untuk Fancybox bisa ditambahkan di sini
});

// 3. Pindahkan semua logika dari file Blade ke sini
document.addEventListener('DOMContentLoaded', function () {
  const filterDateInput = document.getElementById('filter-date');
  if (filterDateInput) {
    // Inisialisasi Flatpickr (kalender) dengan tanggal yang tersedia
    flatpickr(filterDateInput, {
      dateFormat: 'Y-m-d',
      altInput: true,
      altFormat: 'd F Y',
      enable: window.allAvailableDates // Mengambil data tanggal dari Blade
    });

    const hourSelect = document.getElementById('filter-hour');
    const minuteSelect = document.getElementById('filter-minute');

    // Mengambil data filter awal dari Blade
    const currentHour = window.explorerFilters.hour || '';
    const currentMinute = window.explorerFilters.minute || '';

    function updateMinuteOptions() {
      const selectedHour = hourSelect.value;
      const availableMinutesForHour = window.availableTimes[selectedHour] || [];

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

    document.getElementById('filter-go-btn').addEventListener('click', function () {
      const date = filterDateInput.value;
      const hour = hourSelect.value;
      const minute = minuteSelect.value;
      if (!date) {
        alert('Silakan pilih tanggal terlebih dahulu.');
        return;
      }
      let finalUrl = `${window.explorerBaseUrl}/${date}`;
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
