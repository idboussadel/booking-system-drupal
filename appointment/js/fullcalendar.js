(function () {
  Drupal.behaviors.appointment = {
    attach: function (context, settings) {
      const calendarEl = document.getElementById('fullcalendar');
      if (calendarEl && !calendarEl.hasAttribute('data-processed')) {
        calendarEl.setAttribute('data-processed', 'true');

        // Attempt to load the calendar with a small delay to ensure library is ready
        const initCalendar = function () {
          try {
            if (typeof FullCalendar === 'undefined') {
              setTimeout(function () {
                initCalendar();
              }, 100);
              return;
            }

            const workingHours = settings.appointment.working_hours;
            const businessHours = [
              ...(workingHours.agency || []),
            ];
            console.log('businessHours:', businessHours);

            // Generate unavailable hours for the advisor
            const unavailableEvents = generateUnavailableEvents(workingHours.agency, workingHours.advisor);

            const calendar = new FullCalendar.Calendar(calendarEl, {
              initialView: 'timeGridWeek',
              headerToolbar: {
                left: 'prev,next',
                center: 'title',
                right: ''
              },
              titleFormat: {year: 'numeric', month: 'long', day: 'numeric'},
              firstDay: 1, // to start calendar from Monday
              height: 'auto',
              allDaySlot: false,
              slotMinTime: '08:00:00',
              slotMaxTime: '18:00:00',
              slotDuration: '00:30:00',
              selectable: true,
              businessHours: businessHours, // Highlight working hours
              eventOverlap: false,
              events: unavailableEvents, // Add unavailable hours as events
              selectAllow: function (selectInfo) {
                // Check if the selected time range is within the agency's working hours
                const start = selectInfo.start;
                const end = selectInfo.end;

                // Check if the selection spans more than one day
                if (start.getDate() !== end.getDate() || start.getMonth() !== end.getMonth() || start.getFullYear() !== end.getFullYear()) {
                  return false;
                }

                // Check if the selection is within the agency's working hours
                const isWithinWorkingHours = businessHours.some(hours => {
                  const dayMatch = hours.daysOfWeek.includes(start.getDay());
                  const timeMatch = start >= new Date(`${start.toDateString()} ${hours.startTime}`) &&
                    end <= new Date(`${end.toDateString()} ${hours.endTime}`);
                  return dayMatch && timeMatch;
                });

                return isWithinWorkingHours;
              },
              select: function (info) {
                const startDateTime = info.startStr;
                const endDateTime = info.endStr;
                document.getElementById('selected-start-date').value = startDateTime;
                document.getElementById('selected-end-date').value = endDateTime;
              },
              eventContent: function (arg) {
                // Customize the content of the unavailable events
                return {html: `<div class="unavailable-event">${arg.event.title}</div>`};
              },
            });

            calendar.render();
          } catch (error) {
            console.error('Error initializing calendar:', error);
            calendarEl.innerHTML = '<div class="messages messages--error">Error initializing calendar. Please check console for details.</div>';
          }
        };

        initCalendar();
      }
    }
  };

  /**
   * Generate unavailable events based on agency and advisor working hours.
   */
  function generateUnavailableEvents(agencyHours, advisorHours) {
    const unavailableEvents = [];

    if (!advisorHours || !agencyHours) return unavailableEvents;

    // Loop through each day of the week (0 = Sunday, 1 = Monday, etc.)
    for (let day = 1; day <= 7; day++) {
      const agencyDayHours = agencyHours.find(hours => hours.daysOfWeek.includes(day));
      const advisorDayHours = advisorHours.find(hours => hours.daysOfWeek.includes(day));

      if (!agencyDayHours || !advisorDayHours) continue;

      // Convert times to minutes for easier comparison
      const agencyStart = timeToMinutes(agencyDayHours.startTime);
      const agencyEnd = timeToMinutes(agencyDayHours.endTime);
      const advisorStart = timeToMinutes(advisorDayHours.startTime);
      const advisorEnd = timeToMinutes(advisorDayHours.endTime);

      // Calculate unavailable slots
      if (advisorStart > agencyStart) {
        unavailableEvents.push({
          daysOfWeek: [day],
          startTime: minutesToTime(agencyStart),
          endTime: minutesToTime(advisorStart),
          title: 'Advisor Indisponible',
          color: '#2c3e50',
        });
      }

      if (advisorEnd < agencyEnd) {
        unavailableEvents.push({
          daysOfWeek: [day],
          startTime: minutesToTime(advisorEnd),
          endTime: minutesToTime(agencyEnd),
          title: 'Advisor Indisponible',
          color: '#2c3e50',
        });
      }
    }

    return unavailableEvents;
  }

  /**
   * Convert time string (e.g., "08:30") to minutes since midnight.
   */
  function timeToMinutes(time) {
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
  }

  /**
   * Convert minutes since midnight to time string (e.g., "08:30").
   */
  function minutesToTime(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
  }
})();
