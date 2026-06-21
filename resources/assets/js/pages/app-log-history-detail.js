'use strict';

import { Fancybox } from '@fancyapps/ui';

$(function () {
  const container = $('#drilldown-container');
  const baseUrl = container.data('base-url');
  const breadcrumb = $('#breadcrumb');
  const backButton = $('#back-button');

  let historyStack = []; // To manage back button functionality

  const views = {
    hour: $('#hour-view'),
    minute: $('#minute-view'),
    gallery: $('#gallery-view')
  };

  function showView(viewName) {
    $('.drilldown-level').hide();
    views[viewName].show();
    backButton.toggle(historyStack.length > 0);
  }

  // MODIFIED: This function now populates a dropdown instead of a table
  function loadHourData() {
    $.ajax({
      url: baseUrl,
      success: function (response) {
        const hourSelect = $('#hour-select');
        hourSelect.empty(); // Clear existing options
        hourSelect.append('<option value="" selected disabled>Pilih satu jam...</option>');

        if (response.data && response.data.length > 0) {
          response.data.forEach(row => {
            const hourText = `Jam ${String(row.hour).padStart(2, '0')}:00 (${row.record_count} gambar)`;
            const option = `<option value="${row.hour}">${hourText}</option>`;
            hourSelect.append(option);
          });
        } else {
          hourSelect.prop('disabled', true);
          hourSelect.html('<option selected>Tidak ada rekaman ditemukan.</option>');
        }
      }
    });
  }

  function loadMinuteData(hour) {
    const url = `${baseUrl}/${hour}`;
    breadcrumb.text(breadcrumb.text().split(' > ')[0] + ` > Jam ${String(hour).padStart(2, '0')}:00`);

    $.ajax({
      url: url,
      success: function (response) {
        const tableBody = $('#minute-table-body');
        tableBody.empty();
        if (response.data && response.data.length > 0) {
          response.data.forEach(row => {
            const minuteText = `${String(hour).padStart(2, '0')}:${String(row.minute).padStart(2, '0')}`;
            const tableRow = `
                            <tr>
                                <td>${minuteText}</td>
                                <td>${row.record_count}</td>
                                <td><button class="btn btn-sm btn-primary view-gallery" data-hour="${hour}" data-minute="${row.minute}">Lihat Galeri</button></td>
                            </tr>
                        `;
            tableBody.append(tableRow);
          });
        } else {
          tableBody.append('<tr><td colspan="3" class="text-center">Tidak ada rekaman ditemukan.</td></tr>');
        }
      }
    });
  }

  function showGallery(hour, minute) {
    const url = `${baseUrl}/${hour}/${minute}`;
    const galleryContainer = $('#gallery-container');
    const galleryTitle = $('#gallery-title');

    galleryTitle.text(`Galeri Jam ${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`);
    breadcrumb.text(
      breadcrumb.text().split(' > ')[0] +
        ` > Jam ${String(hour).padStart(2, '0')}:00 > Menit ${String(minute).padStart(2, '0')}`
    );

    $.ajax({
      url: url,
      success: function (response) {
        galleryContainer.empty();
        if (response.data && response.data.length > 0) {
          response.data.forEach(image => {
            const item = `
                            <div class="gallery-item">
                                <a href="${image.url}" data-fancybox="gallery" data-caption="Waktu: ${image.time}">
                                    <img src="${image.url}" alt="Rekaman ${image.time}" class="img-fluid rounded" loading="lazy">
                                </a>
                                <div class="gallery-caption">${image.time}</div>
                            </div>`;
            galleryContainer.append(item);
          });
          Fancybox.bind('[data-fancybox="gallery"]', {});
        } else {
          galleryContainer.html('<p>Tidak ada gambar ditemukan.</p>');
        }
      }
    });
  }

  // --- Event Handlers ---
  // MODIFIED: Replaced table click handler with a dropdown change handler
  $('#hour-select').on('change', function () {
    const hour = $(this).val();
    if (hour) {
      // Ensure a valid hour is selected
      historyStack.push({ view: 'hour' });
      showView('minute');
      loadMinuteData(hour);
    }
  });

  $('#minute-table-body').on('click', '.view-gallery', function () {
    const hour = $(this).data('hour');
    const minute = $(this).data('minute');
    historyStack.push({ view: 'minute', hour: hour });
    showView('gallery');
    showGallery(hour, minute);
  });

  backButton.on('click', function () {
    const lastState = historyStack.pop();
    if (lastState) {
      showView(lastState.view);
      const currentBreadcrumb = breadcrumb.text().split(' > ');
      currentBreadcrumb.pop();
      breadcrumb.text(currentBreadcrumb.join(' > '));
    }
  });

  // --- Initial Load ---
  showView('hour');
  loadHourData();
});
