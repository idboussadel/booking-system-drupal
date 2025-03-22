(function (Drupal) {
  'use strict';

  Drupal.behaviors.appointmentSelection = {
    attach: function (context, settings) {
      // Helper function to prevent attaching duplicate event listeners
      function processOnce(elements, processedClass, callback) {
        elements.forEach(function (element) {
          if (!element.classList.contains(processedClass)) {
            element.classList.add(processedClass);
            callback(element);
          }
        });
      }

      // Agency selection
      const agencyItems = context.querySelectorAll('.agency-item');
      processOnce(agencyItems, 'agency-selection-processed', function (item) {
        item.addEventListener('click', function () {
          const selectedAgencyInput = document.querySelector('.selected_agency');
          const selectedAgencyId = selectedAgencyInput ? selectedAgencyInput.value : '';
          const currentAgencyId = this.getAttribute('data-agency-id');

          // If the clicked item is already selected, deselect it
          if (selectedAgencyId === currentAgencyId) {
            this.classList.remove('selected');
            if (selectedAgencyInput) {
              selectedAgencyInput.value = ''; // Clear the selected value
            }
          } else {
            // Otherwise, select the clicked item
            document.querySelectorAll('.agency-item').forEach(function (el) {
              el.classList.remove('selected');
            });
            this.classList.add('selected');
            if (selectedAgencyInput) {
              selectedAgencyInput.value = currentAgencyId;
            }
          }
        });

        // Check if this agency is the pre-selected one
        const selectedAgencyInput = document.querySelector('.selected_agency');
        const selectedAgencyId = selectedAgencyInput ? selectedAgencyInput.value : '';
        if (item.getAttribute('data-agency-id') === selectedAgencyId) {
          item.classList.add('selected');
        }
      });

      // Type selection
      const typeItems = context.querySelectorAll('.type-item');
      processOnce(typeItems, 'type-selection-processed', function (item) {
        item.addEventListener('click', function () {
          const selectedTypeInput = document.querySelector('.selected_type');
          const selectedTypeId = selectedTypeInput ? selectedTypeInput.value : '';
          const currentTypeId = this.getAttribute('data-type-id');

          // If the clicked item is already selected, deselect it
          if (selectedTypeId === currentTypeId) {
            this.classList.remove('selected');
            if (selectedTypeInput) {
              selectedTypeInput.value = ''; // Clear the selected value
            }
          } else {
            // Otherwise, select the clicked item
            document.querySelectorAll('.type-item').forEach(function (el) {
              el.classList.remove('selected');
            });
            this.classList.add('selected');
            if (selectedTypeInput) {
              selectedTypeInput.value = currentTypeId;
            }
          }
        });

        // Check if this type is the pre-selected one
        const selectedTypeInput = document.querySelector('.selected_type');
        const selectedTypeId = selectedTypeInput ? selectedTypeInput.value : '';
        if (item.getAttribute('data-type-id') === selectedTypeId) {
          item.classList.add('selected');
        }
      });

      // Advisor selection
      const advisorItems = context.querySelectorAll('.advisor-item');
      processOnce(advisorItems, 'advisor-selection-processed', function (item) {
        item.addEventListener('click', function () {
          const selectedAdvisorInput = document.querySelector('.selected_advisor');
          const selectedAdvisorId = selectedAdvisorInput ? selectedAdvisorInput.value : '';
          const currentAdvisorId = this.getAttribute('data-advisor-id');

          // If the clicked item is already selected, deselect it
          if (selectedAdvisorId === currentAdvisorId) {
            this.classList.remove('selected');
            if (selectedAdvisorInput) {
              selectedAdvisorInput.value = ''; // Clear the selected value
            }
          } else {
            // Otherwise, select the clicked item
            document.querySelectorAll('.advisor-item').forEach(function (el) {
              el.classList.remove('selected');
            });
            this.classList.add('selected');
            if (selectedAdvisorInput) {
              selectedAdvisorInput.value = currentAdvisorId;
            }
          }
        });

        // Check if this advisor is the pre-selected one
        const selectedAdvisorInput = document.querySelector('.selected_advisor');
        const selectedAdvisorId = selectedAdvisorInput ? selectedAdvisorInput.value : '';
        if (item.getAttribute('data-advisor-id') === selectedAdvisorId) {
          item.classList.add('selected');
        }
      });
    }
  };

})(Drupal);
