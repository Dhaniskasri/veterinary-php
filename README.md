# Veterinary Care for Animal Medical Solution
### College-Level Full-Stack PHP & MySQL Web Application

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-teal.svg)](https://www.mysql.com/)
[![Frontend](https://img.shields.io/badge/Frontend-Bootstrap%205-purple.svg)](https://getbootstrap.com/)
[![Architecture](https://img.shields.io/badge/Architecture-PDO%20OOP%20%7C%20RBAC-success.svg)](#)

---

## 1. Project Overview

**Veterinary Care for Animal Medical Solution** is a complete, production-ready, college-level PHP web application developed to modernize animal healthcare delivery. The platform connects three primary stakeholders:
1. **Animal Owners**: Register companion pets (dogs, cats) and farm livestock (cows, buffaloes, goats, sheep, horses, poultry), book appointments with certified veterinarians without time-slot conflicts, access electronic medical records, view printable prescriptions, and track vaccination booster schedules.
2. **Veterinarians**: Manage clinical calendars, configure consultation slot durations (15–60 mins), accept/reject appointment requests, conduct examinations, record vitals and diagnoses, generate multi-drug digital prescriptions, administer vaccines, and set follow-up schedules.
3. **Administrators**: Oversee system telemetry, manage client accounts, verify and approve/reject veterinarian medical licenses, review all appointments and clinical records, manage inbound contact inquiries, generate filterable audit reports with Chart.js analytics, and maintain system settings.

> **Academic & Educational Disclaimer:**  
> This application is built strictly for academic and demonstration purposes. It demonstrates full-stack PHP backend logic, MySQL schema architecture, role-based access control, transaction isolation, and secure input sanitation. It does not replace hands-on veterinary hospital triage or emergency physical diagnosis.

---

## 2. Technology Stack

* **Server Environment:** Windows / XAMPP (Apache + MySQL + PHP 8+)
* **Backend:** Native Core PHP 8+ (No Laravel or external backend frameworks)
* **Database:** MySQL 8.0+ / MariaDB with InnoDB engine, Foreign Keys, and ACID Transactions
* **Database Access:** PHP PDO (Prepared Statements with parameter binding, emulation disabled)
* **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6+), Bootstrap 5.3.3
* **Typography & Icons:** Google Fonts (*Plus Jakarta Sans*, *Inter*), Bootstrap Icons
* **Data Visualization:** Chart.js 4.4+ (Dynamic doughnut and bar charts populated from database queries)
* **Security Layer:** 
  * Password hashing using Bcrypt (`password_hash` & `password_verify`)
  * CSRF (Cross-Site Request Forgery) cryptographic token validation on all state-changing requests
  * Anti-XSS output escaping with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`
  * IDOR (Insecure Direct Object Reference) record ownership validation
  * File upload MIME verification (`finfo`) and randomized non-executable file storage with `.htaccess` execution denial
  * Brute-force login throttling

---

## 3. Pre-Configured Demonstration Accounts

The SQL seed data comes pre-populated with ready-to-test demonstration accounts for all roles:

| Role | Email | Password | Details |
|---|---|---|---|
| **System Administrator** | `admin@veterinary.com` | `Admin@123` | Full administrative control & vet approval |
| **Veterinarian (Approved)** | `sarah.jenkins@veterinary.com` | `Vet@123` | Dr. Sarah Jenkins (Canine & Feline Medicine) |
| **Veterinarian (Approved)** | `marcus.rodriguez@veterinary.com` | `Vet@123` | Dr. Marcus Rodriguez (Livestock & Equine Health) |
| **Veterinarian (Pending)** | `james.wilson@veterinary.com` | `Vet@123` | Dr. James Wilson (Awaiting admin approval) |
| **Animal Owner** | `alice@example.com` | `Owner@123` | Alice Morgan (Owns Buddy - Dog, Luna - Cat) |
| **Animal Owner** | `david@example.com` | `Owner@123` | David Smith (Owns Bella - Cow, Thunder - Horse) |

---

## 4. XAMPP Local Installation Guide (Step-by-Step)

Follow these clear, beginner-friendly instructions to run the application on Windows:

### Step 1: Install XAMPP
* Download and install **XAMPP for Windows** (with PHP 8.0, 8.1, or 8.2) from [https://www.apachefriends.org/](https://www.apachefriends.org/).

### Step 2: Start Apache and MySQL
* Open the **XAMPP Control Panel**.
* Click the **Start** button next to **Apache**.
* Click the **Start** button next to **MySQL**.
* Both modules should show a green background indicating they are active.

### Step 3: Copy Project to XAMPP Document Root
* Copy the entire project folder into your XAMPP web directory:
  ```
  C:\xampp\htdocs\veterinary-care
  ```
  *(Or if your directory is named `veterinary`, place it at `C:\xampp\htdocs\veterinary`)*

### Step 4: Open phpMyAdmin
* Open your browser and navigate to:  
  [http://localhost/phpmyadmin](http://localhost/phpmyadmin)

### Step 5: Import the Database Schema
1. In phpMyAdmin, click on the **Import** tab at the top.
2. Click **Choose File** and browse to:  
   `database/veterinary_care.sql` (located inside the project folder).
3. Click the **Import** button at the bottom of the page.
4. The database `veterinary_care` and its 10 tables will be automatically created with demonstration data!

### Step 6: Configure Database Credentials
* Open `config/database.php` in Visual Studio Code.
* The script contains automatic password fallback detection (`'root'` or empty `''`):
  ```php
  define('DB_HOST', '127.0.0.1');
  define('DB_PORT', 3306);
  define('DB_NAME', 'veterinary_care');
  define('DB_USER', 'root');
  define('DB_PASS', 'root'); // Or change to '' if your XAMPP root password is blank
  ```
* Save the file if you modified credentials.

### Step 7: (Optional) Administrator Setup Script
* The project includes a dedicated administrator setup script. You can run it either:
  * **Via Web Browser:** Open [http://localhost/veterinary-care/scripts/create_admin.php](http://localhost/veterinary-care/scripts/create_admin.php)
  * **Via Terminal:** Run:
    ```bash
    php scripts/create_admin.php
    ```
* You can also immediately sign in with the default seeded administrator:  
  **`admin@veterinary.com` / `Admin@123`**

### Step 8: Open the Web Application
* Open your browser and navigate to:  
  [http://localhost/veterinary-care/](http://localhost/veterinary-care/)  
  *(or `http://localhost/veterinary/` depending on your folder name)*
* The homepage will open with real-time database metrics!

---

## 5. End-to-End Walkthrough & Testing Scenarios

Verify the application functionality across all 3 roles:

### Scenario A: Animal Owner Registration & Booking
1. Visit `http://localhost/veterinary-care/auth/register_owner.php`.
2. Register a new owner account and log in.
3. Go to **Register Animal** (`owner/animal_add.php`) and add an animal (e.g., "Max", Species: Dog, Breed: Labrador, Age: 2, Weight: 28kg). Upload a photo.
4. Go to **Book Appointment** (`owner/appointment_add.php`):
   * Select your animal.
   * Select an approved veterinarian (e.g., Dr. Sarah Jenkins).
   * Pick today's date or tomorrow.
   * Notice the **live available time slots** generated via AJAX! Select a slot (e.g., 10:00 AM).
   * Enter reason: "Routine Wellness & Ear Cleaning".
   * Submit booking.
5. View the appointment confirmation with status **Pending Confirmation**.

### Scenario B: Veterinarian Consultation & Digital Prescription
1. Log out, then log in as the veterinarian:  
   `sarah.jenkins@veterinary.com` / `Vet@123`.
2. On the **Veterinarian Dashboard**, see the appointment in the **Pending Requests** queue.
3. Click **Accept** to transition status to **Scheduled**.
4. Click **Consult** to open the clinical examination wizard (`veterinarian/medical_record_add.php`):
   * Enter presenting symptoms.
   * Enter primary diagnosis (e.g., "Mild Otitis Externa").
   * Enter treatment plan and advice.
   * Set a follow-up date (e.g., 7 days ahead).
   * In the **Prescribed Medications** table, click **Add Another Medicine** to dynamically create multi-row prescriptions!
   * Click **Finalize Consultation & Issue Rx**.
5. The digital prescription slip is automatically generated with a print button (`window.print()`).

### Scenario C: Vaccination Recording & Automated Reminders
1. As veterinarian, click **Record Vaccine** (`veterinarian/vaccination_add.php`).
2. Select an animal, pick vaccine name (e.g., "Rabies 3-Year Booster"), enter lot number, and set next booster due date within 20 days.
3. Submit the record.
4. Log back in as the animal owner:
   * View the **Vaccination Reminder Alert Banner** directly on the **Owner Dashboard**!
   * Check **Vaccination Tracker** (`owner/vaccinations.php`) to view protection status.

### Scenario D: Administrator Oversight & Vet Licensure Approval
1. Log in as administrator:  
   `admin@veterinary.com` / `Admin@123`.
2. View system telemetry, doughnut chart breakdown, and recent activity logs.
3. In the **Veterinarian Approval Queue**, notice pending applicants (e.g., Dr. James Wilson).
4. Review medical license and click **Approve**. The veterinarian can now log in and take bookings!
5. Navigate to **System Reports** (`admin/reports.php`) to test:
   * Appointments by Date Range
   * Status Breakdown Percentages
   * Species Distribution
   * Veterinarian Caseload & Revenue

---

## 6. Project Directory Structure

```
veterinary-care/
├── config/
│   ├── config.php                 # App constants, species definitions, upload rules
│   └── database.php               # PDO singleton provider with auto-fallback
├── includes/
│   ├── init.php                   # Core bootstrap & session security initialization
│   ├── auth.php                   # Authentication & role-based access control (RBAC)
│   ├── functions.php              # Global helpers (e(), url(), formatters, badges)
│   ├── csrf.php                   # Cryptographic CSRF token generation & validation
│   ├── header.php                 # Master HTML head, navigation bar, CDN styles
│   ├── footer.php                 # Master HTML footer, scripts, disclaimer
│   ├── alerts.php                 # Flash message UI alerts renderer
│   ├── owner_sidebar.php          # Owner portal sidebar navigation
│   ├── vet_sidebar.php            # Veterinarian portal sidebar navigation
│   └── admin_sidebar.php          # Administrator portal sidebar navigation
├── auth/
│   ├── login.php                  # Unified login for owner, vet, and admin
│   ├── register_owner.php         # Animal owner registration
│   ├── register_vet.php           # Veterinarian registration with license info
│   └── logout.php                 # POST logout action with session invalidation
├── owner/
│   ├── dashboard.php              # Owner dashboard with live stats & reminder alerts
│   ├── profile.php                # Owner profile and password update
│   ├── animals.php                # Owner's registered animals grid & filter
│   ├── animal_add.php             # Add new animal form with photo upload
│   ├── animal_edit.php            # Edit animal vitals and details
│   ├── animal_view.php            # Comprehensive animal health profile (tabs)
│   ├── appointments.php           # Owner's appointments list with status filters
│   ├── appointment_add.php        # Interactive booking wizard with AJAX slots
│   ├── appointment_view.php       # Appointment details & cancellation action
│   ├── medical_history.php        # Medical history across all owner animals
│   ├── prescription_view.php      # Printable official prescription slip
│   ├── vaccinations.php           # Vaccination immunization records & due dates
│   └── followups.php              # Follow-up milestones schedule
├── veterinarian/
│   ├── dashboard.php              # Vet dashboard with today's schedule & pending queue
│   ├── profile.php                # Vet credentials, license, specialization & fee
│   ├── appointments.php           # Assigned appointment requests manager
│   ├── appointment_view.php       # Appointment status manager & pre-visit notes
│   ├── my_patients.php            # Directory of treated animals
│   ├── medical_history.php        # Master clinical records authored by vet
│   ├── medical_record_add.php     # Consultation form with dynamic multi-row Rx
│   ├── medical_record_edit.php    # Update clinical consultation record
│   ├── prescription_add.php       # Add extra medications to existing record
│   ├── vaccination_add.php        # Record vaccination administered
│   └── availability.php           # Manage working hours, dates, slot duration
├── admin/
│   ├── dashboard.php              # Admin master dashboard with Chart.js analytics
│   ├── owners.php                 # Manage owner accounts & toggle status
│   ├── veterinarians.php          # Approve/reject veterinarians & toggle status
│   ├── animals.php                # System-wide animals directory
│   ├── appointments.php           # System-wide appointments manager
│   ├── medical_records.php        # Medical records oversight
│   ├── vaccinations.php           # System-wide vaccination records
│   ├── contact_messages.php       # Inbound contact inquiries from public form
│   ├── reports.php                # Filterable reports (date, status, species, vet, vac)
│   └── settings.php               # Clinic settings & server health telemetry
├── actions/
│   ├── animal_save.php            # Animal create/update action
│   ├── appointment_create.php     # Transactional booking & conflict detection
│   ├── appointment_update.php     # Role-based status transition controller
│   ├── availability_save.php      # Save veterinarian availability slots
│   ├── contact_submit.php         # Public contact form submission handler
│   ├── get_slots.php              # Dynamic AJAX slot generator API
│   ├── medical_record_save.php    # Save consultation record & prescriptions
│   ├── prescription_save.php      # Save individual prescription item
│   ├── profile_update.php         # Profile & password updater
│   ├── vaccination_save.php       # Save vaccination entry
│   └── veterinarian_approval.php  # Approve/reject vet or toggle status
├── assets/
│   ├── css/
│   │   ├── style.css              # Custom veterinary healthcare theme tokens
│   │   └── dashboard.css          # Portal layouts, sidebars, stat widgets
│   └── js/
│       ├── main.js                # Mobile toggles, alert dismiss, AJAX slot fetcher
│       └── validation.js          # Client-side validation & dynamic prescription rows
├── uploads/
│   └── animals/                   # Secure animal images directory
│       └── .htaccess              # Script execution denial protection
├── database/
│   └── veterinary_care.sql        # Full MySQL database schema + demonstration seeds
├── scripts/
│   └── create_admin.php           # Secure CLI & Web administrator setup utility
├── index.php                      # Public homepage
├── about.php                      # Public about us
├── services.php                   # Public veterinary services
├── veterinarians.php              # Public veterinarian directory & search
├── contact.php                    # Public contact page with database recording
└── README.md                      # Comprehensive installation & testing manual
```

---

## 7. Troubleshooting Common Issues

### Issue 1: Apache Port Conflict (Port 80 / 443 in use)
* **Symptom:** Apache fails to start in XAMPP with an error like `Port 80 in use by PID 4 (SYSTEM)`.
* **Solution:**
  1. In XAMPP Control Panel, click **Config** next to Apache -> **httpd.conf**.
  2. Search for `Listen 80` and change to `Listen 8080`.
  3. Search for `ServerName localhost:80` and change to `ServerName localhost:8080`.
  4. Access the site via: `http://localhost:8080/veterinary-care/`.

### Issue 2: MySQL Connection Refused / Access Denied
* **Symptom:** `SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'`.
* **Solution:**
  * Open `config/database.php`.
  * Set `DB_PASS` to `''` (standard XAMPP default) or `'root'` (if a password was configured).
  * Ensure MySQL service is running in XAMPP Control Panel.

### Issue 3: Database Import Errors in phpMyAdmin
* **Symptom:** `Max execution time exceeded` or `Table already exists`.
* **Solution:**
  * In phpMyAdmin, click on the `veterinary_care` database (or create it), then import `database/veterinary_care.sql`.
  * The SQL script includes `DROP TABLE IF EXISTS` in safe foreign-key order to ensure clean re-imports.

### Issue 4: File Upload Permission Issues
* **Symptom:** Uploaded animal photos do not appear or error `Failed to move uploaded file`.
* **Solution:**
  * Ensure the directory `uploads/animals/` exists.
  * On Windows, make sure the folder is not marked "Read-only" in folder properties.

### Issue 5: Blank Page or 500 Server Error
* **Symptom:** HTTP Error 500 when accessing pages.
* **Solution:**
  * Verify that PHP 8.0+ is installed (PHP 7 does not support match expressions or named arguments).
  * Check Apache error log in XAMPP: `C:\xampp\apache\logs\error.log`.

---

## 8. License & Academic Attribution
Developed as an academic demonstration project for college-level web development coursework adhering to clean code standards, OWASP security principles, and robust database architecture.
