<div align="center">
  <img width="250" alt="image" src="https://github.com/user-attachments/assets/34c9e165-2349-4914-978a-ab55f7c10917" />

[![Drupal](https://img.shields.io/badge/Drupal-10+-%230678BE?logo=drupal)](https://www.drupal.org)
[![Maintenance](https://img.shields.io/badge/Maintained%3F-Yes-green.svg)](https://github.com/your-org/appointment-booking-system/graphs/commit-activity)
[![PHP](https://img.shields.io/badge/PHP-8.3+-%23777BB4?logo=php)](https://php.net/)
</div>

# Booking system :

🆘 the module is not finished yet.

A comprehensive appointment booking system for Drupal that enables users to book, manage, and track appointments with advisers across multiple agencies.

## 🗓️ Features

- **Multi-step Booking Process**: Intuitive interface for selecting agencies, advisers, and time slots
- **Custom Entity Types**: Appointment and Agency entities with configurable fields
- **Role-based Access Control**: Different permissions for users, advisers, and administrators
- **Email Notifications**: Confirmation, modification, and cancellation emails
- **Administrative Dashboard**: Comprehensive management interface for appointments
- **Mobile Responsive**: Works across all device sizes
- **Multilingual Support**: French and English included by default
- **Export Appointments**: Export appointments to CSV format using Batch API

## 📑 Entities Used

#### For Appointments:

- **Title**
- **Date and Time**
- **Agency Reference**
- **Adviser Reference**
- **Customer Information:**
  - **first_name**
  - **last_name**
  - **Email**
  - **Phone**
- **Status:**
  - ⏳ *Pending*
  - ✅ *Confirmed*
  - ❌ *Cancelled*
- **Notes**


#### Agencies
- **Name**
- **Address**
- **Contact Information**
- **Operating Hours**


#### Advisers (User Fields)
- **Agency Reference**
- **Working Hours**
- **Specializations**

---

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

### Edit agency :

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/575446e0-03e2-40aa-b44c-99c3be07cfe9" />

### Agencies list :

<img width="1437" alt="image" src="https://github.com/user-attachments/assets/cff0cdb8-f86f-454a-9699-09232e51aba9" />


### Add advisor :
Navigate to `/admin/people/create`

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/f97b7151-d289-4fb9-be4c-1b79e9ba4400" />

### Taxonomy :
Navigate to `/admin/structure/taxonomy/manage/appointment_type/overview`

<img width="1438" alt="image" src="https://github.com/user-attachments/assets/27dcd181-3352-4483-bc6d-2d367d362967" />

---

### 📅 Booking form :
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

<img width="990" alt="image" src="https://github.com/user-attachments/assets/819241b7-a8b3-4e0a-b3fb-907befab0321" />

#### Customer confirmation email :
I used Mailhog for email testing.

```bash
docker run --rm --name mailhog -p 8025:8025 -p 1025:1025 mailhog/mailhog
```

<img width="1439" alt="image" src="https://github.com/user-attachments/assets/453fe02f-0aaf-4570-8b48-7c6dd0799832" />

#### Adviser email info :
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/766abff6-ac09-4d25-89c2-8c6c8a905a41" />

---

### Appointments list:
Navigate to `/admin/structure/appointments`

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/e0a1828c-4d33-4ed1-8660-9cedcc2b4ea0" />

To optimize the export process and prevent memory limit errors, we utilized Drupal's Batch API to export data in chunks of 100 records at a time.
![image](https://github.com/user-attachments/assets/8a248c93-bfdd-4325-a7bc-2013b0a9ae83)

---

### search by phone :
Navigate to `/appointments/search`

<img width="970" alt="image" src="https://github.com/user-attachments/assets/08497498-8586-4349-bf6c-70ab8c81596c" />
<img width="970" alt="image" src="https://github.com/user-attachments/assets/a3669228-e29c-4cd6-825a-273d313c40da" />

<img width="1440" alt="image" src="https://github.com/user-attachments/assets/c8e39ab6-9571-4695-823f-96d453461a93" />
<img width="970" alt="image" src="https://github.com/user-attachments/assets/939439ab-11b7-470b-9f4d-53a2a196d9c4" />

<img width="970" alt="image" src="https://github.com/user-attachments/assets/f86f1a8d-8cc9-42d6-b6be-40e88365094e" />
<img width="970" alt="image" src="https://github.com/user-attachments/assets/19c844fc-b11f-4b08-83a1-4d0a3429f689" />
<img width="970" alt="image" src="https://github.com/user-attachments/assets/7c69ff8d-b0ad-47bf-98f3-bcd2456094d9" />

---

## 🔧 Contribute & Customize
Feel free to **modify** this module to suit your specific needs. Contributions and improvements are always welcome! 🚀
