<div align="center">
  <img width="250" alt="image" src="https://github.com/user-attachments/assets/34c9e165-2349-4914-978a-ab55f7c10917" />

[![Drupal](https://img.shields.io/badge/Drupal-10+-%230678BE?logo=drupal)](https://www.drupal.org)
[![Maintenance](https://img.shields.io/badge/Maintained%3F-Yes-green.svg)](https://github.com/your-org/appointment-booking-system/graphs/commit-activity)
[![PHP](https://img.shields.io/badge/PHP-8.3+-%23777BB4?logo=php)](https://php.net/)
</div>

# Booking system :

🆘 the module is not finished yet.

A comprehensive appointment booking system for Drupal that enables users to book, manage, and track appointments with advisers across multiple agencies.

## Features

- **Multi-step Booking Process**: Intuitive interface for selecting agencies, advisers, and time slots
- **Custom Entity Types**: Appointment and Agency entities with configurable fields
- **Role-based Access Control**: Different permissions for users, advisers, and administrators
- **Email Notifications**: Confirmation, modification, and cancellation emails
- **Administrative Dashboard**: Comprehensive management interface for appointments
- **Mobile Responsive**: Works across all device sizes
- **Multilingual Support**: French and English included by default

## Installation

```bash
composer require 'drupal/office_hours:^1.23'
composer require 'drupal/fullcalendar:^3.0'
composer require 'drupal/symfony_mailer:^1.5'
```

Clone the repo in the custom folder :
```bash
git clone https://github.com/idboussadel/booking-system-drupal.git
```

Enable the module :
```bash
./vendor/bin/drush en appointment
```


<div align="center">
  <img width="658" alt="image" src="https://github.com/user-attachments/assets/9ad21853-c9db-46dd-b3d6-dcfe6df694b7" />
</div>

### Add agency :
Navigate to `/admin/structure/agency/add`

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/acc9cf1a-fe65-473e-b756-1e8638e11ad8" />

### Add advisor :
Navigate to `/admin/people/create`

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/f97b7151-d289-4fb9-be4c-1b79e9ba4400" />

### Taxonomy :
Navigate to `/admin/structure/taxonomy/manage/appointment_type/overview`

<img width="1438" alt="image" src="https://github.com/user-attachments/assets/27dcd181-3352-4483-bc6d-2d367d362967" />

### Agencies list:

<img width="1437" alt="image" src="https://github.com/user-attachments/assets/cff0cdb8-f86f-454a-9699-09232e51aba9" />

---

### Booking form :
Navigate to `/prendre-un-rendez-vous`

1. **Agency Selection**
   <img width="990" alt="image" src="https://github.com/user-attachments/assets/9458068b-e082-4518-84c1-f00ba404d93e" />

2. **Appointment Type Selection**
   <img width="990" alt="image" src="https://github.com/user-attachments/assets/9400eb81-cdd9-4883-b921-fda321adead3" />

3. **Adviser Selection** : The list of advisers is filtered based on the selected agency.
   <img width="990" alt="image" src="https://github.com/user-attachments/assets/25ca4115-2bae-42be-9837-77696d3cedb0" />

4. **Date and Time Selection**
<div align="center">
<img width="990" alt="image" src="https://github.com/user-attachments/assets/e8944fec-4347-4550-9ab0-d15c2acea410" />
</div>

5. **Personal Information**
   <img width="990" alt="image" src="https://github.com/user-attachments/assets/665faf82-9d69-4521-a5d6-c841eb9e5057" />

6. **Confirmation** : Review your booking details and confirm the appointment. confirmation emails are sent.
   <img width="990" alt="image" src="https://github.com/user-attachments/assets/ca947a30-9881-452a-abfa-24f80b81ed9e" />

7. **Success Page**
   <img width="990" alt="image" src="https://github.com/user-attachments/assets/547f4a81-aa4b-4175-8e06-a5626280e6a6" />

#### Customer confirmation email :
I used Mailhog for email testing.

<img width="1439" alt="image" src="https://github.com/user-attachments/assets/453fe02f-0aaf-4570-8b48-7c6dd0799832" />

#### Adviser email info :
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/766abff6-ac09-4d25-89c2-8c6c8a905a41" />

---

### Appointments list:
Navigate to `/admin/structure/appointments`

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/6295a8a4-3c7d-4a5d-a14e-2e877e225929" />

### search by phone :
Navigate to `/appointments/search`

<img width="1000" alt="image" src="https://github.com/user-attachments/assets/08497498-8586-4349-bf6c-70ab8c81596c" />

