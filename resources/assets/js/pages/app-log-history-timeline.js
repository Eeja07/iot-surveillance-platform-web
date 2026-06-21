'use strict';

import { Modal } from 'bootstrap';
// FIX: Import DataSet and Timeline directly from the package
import { DataSet, Timeline } from 'vis-timeline/standalone';

document.addEventListener('DOMContentLoaded', function () {
  const timelineContainer = document.getElementById('timeline-visualization');

  if (timelineContainer) {
    const dataUrl = timelineContainer.dataset.url;
    const imageModalEl = document.getElementById('imageModal');
    const imageModal = new Modal(imageModalEl);
    const fullImage = document.getElementById('full-image');
    const imageModalLabel = document.getElementById('imageModalLabel');

    // Fetch data from our new API endpoint
    fetch(dataUrl)
      .then(response => response.json())
      .then(items => {
        if (items.length === 0) {
          timelineContainer.innerHTML =
            '<div class="text-center p-5"><i class="ti ti-photo-off ti-lg text-muted mb-3"></i><h5 class="mb-1">Tidak Ada Rekaman</h5><p class="text-muted">Tidak ditemukan rekaman untuk tanggal ini.</p></div>';
          return;
        }

        // **MODIFICATION:** Transform items to show a checkmark icon instead of a point
        const timelineItems = items.map(item => {
          return {
            ...item,
            // Use HTML content to display a checkmark icon from Tabler Icons
            content: '<i class="ti ti-circle-check ti-lg text-success"></i>'
            // We no longer need the 'type' property
          };
        });

        // Use the imported DataSet class directly with the modified items
        const dataSet = new DataSet(timelineItems);

        // Configuration for the Timeline
        const options = {
          start: new Date(items[0].start).setHours(0, 0, 0, 0),
          end: new Date(items[0].start).setHours(23, 59, 59, 999),
          height: '400px',
          stack: false,
          zoomMin: 1000 * 60 * 5, // 5 minutes
          zoomMax: 1000 * 60 * 60 * 24, // 24 hours
          showCurrentTime: true,
          showMajorLabels: true,
          showMinorLabels: true
        };

        // Use the imported Timeline class directly
        const timeline = new Timeline(timelineContainer, dataSet, options);

        // Add event listener for click events on items
        // This logic remains the same and will work with the points
        timeline.on('select', function (properties) {
          if (properties.items.length > 0) {
            const itemId = properties.items[0];
            const clickedItem = dataSet.get(itemId);
            if (clickedItem && clickedItem.full_image_url) {
              fullImage.src = clickedItem.full_image_url;
              imageModalLabel.textContent = clickedItem.title;
              imageModal.show();
            }
          }
        });
      })
      .catch(error => {
        console.error('Error fetching timeline data:', error);
        timelineContainer.innerHTML = '<div class="text-center p-5"><h5>Gagal memuat data rekaman.</h5></div>';
      });
  }
});
