'use strict';

// Import the main Calendar class and necessary plugins
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendar');

  // Check if the calendar element exists on the page
  if (calendarEl) {
    const dataUrl = calendarEl.dataset.url;

    const calendar = new Calendar(calendarEl, {
      plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
      initialView: 'dayGridMonth',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth' // Simplified for just day view
      },

      // Use the events function to fetch data manually and avoid 500 errors
      events: function (fetchInfo, successCallback, failureCallback) {
        fetch(dataUrl)
          .then(response => {
            if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
          })
          .then(json => {
            // Transform the data from our server into events for the calendar
            const events = json.data.map(eventData => {
              const actionHtml = eventData.action;
              const tempDiv = document.createElement('div');
              tempDiv.innerHTML = actionHtml;
              const link = tempDiv.querySelector('a');
              const url = link ? link.getAttribute('href') : '#';

              return {
                title: '✅ Ada Rekaman', // Display a checkmark
                start: eventData.date,
                url: url,
                allDay: true,
                backgroundColor: '#28a745', // Green color
                borderColor: '#28a745'
              };
            });
            successCallback(events); // Pass the events to the calendar
          })
          .catch(error => {
            console.error('Failed to load recording data:', error);
            failureCallback(error);
          });
      },

      // Handle what happens when a date/event is clicked
      eventClick: function (info) {
        info.jsEvent.preventDefault(); // Stop the browser from following the link immediately
        if (info.event.url && info.event.url !== '#') {
          window.location.href = info.event.url; // Go to the detail/timeline page
        }
      },
      locale: 'id' // Set language to Indonesian
    });

    // Render the calendar
    calendar.render();
  }
});
