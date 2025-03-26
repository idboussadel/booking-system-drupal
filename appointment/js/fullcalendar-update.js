(function () {
  Drupal.behaviors.appointment = {
    attach: function (context, settings) {
      const calendarEl = document.getElementById('fullcalendar');
      if (calendarEl && !calendarEl.hasAttribute('data-processed')) {
        calendarEl.setAttribute('data-processed', 'true');

        const initCalendar = function () {
          try {
            if (typeof FullCalendar === 'undefined') {
              setTimeout(function () {
                initCalendar();
              }, 100);
              return;
            }

            // Check if appointment data exists in settings
            if (!settings.appointment) {
              console.error('Appointment settings not found');
              return;
            }

            const workingHours = settings.appointment.working_hours || {};
            const existingAppointments = settings.appointment.existing_appointments || [];
            const defaultStart = settings.appointment.default_start_date;
            const defaultEnd = settings.appointment.default_end_date;

            const businessHours = [
              ...(workingHours.agency || []),
              ...(workingHours.advisor || [])
            ];

            // Generate unavailable hours for the advisor
            const unavailableEvents = generateUnavailableEvents(workingHours.agency, workingHours.advisor, existingAppointments, settings.appointment);
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
              initialDate: defaultStart ? new Date(defaultStart) : null,
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
                return {html: `<div class="unavailable-event">${arg.event.title}</div>`};
              },
            });

            calendar.render();

            // If default dates are provided, select them
            if (defaultStart && defaultEnd) {
              calendar.select(new Date(defaultStart), new Date(defaultEnd));
            }
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
  function generateUnavailableEvents(agencyHours, advisorHours, existingAppointments,appointmentSettings) {
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

    if (existingAppointments && existingAppointments.length > 0) {
      existingAppointments.forEach(appointment => {
        // Skip if this is the current appointment being edited
        if (appointmentSettings &&
          appointment.start === appointmentSettings.default_start_date &&
          appointment.end === appointmentSettings.default_end_date) {
          return;
        }

        unavailableEvents.push({
          start: appointment.start,
          end: appointment.end,
          title: appointment.title,
          color: 'white',
          textColor: '#2c3e50',
          borderColor: '#2c3e50',
        });
      });
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
