-- ========================================================
-- Veterinary Care for Animal Medical Solution
-- Database: veterinary_care
-- Compatible with MySQL 8.0+ and MariaDB (XAMPP)
-- ========================================================

CREATE DATABASE IF NOT EXISTS `veterinary_care` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `veterinary_care`;

-- Disable foreign key checks for clean recreation
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `prescriptions`;
DROP TABLE IF EXISTS `medical_records`;
DROP TABLE IF EXISTS `vaccinations`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `veterinarian_availability`;
DROP TABLE IF EXISTS `animals`;
DROP TABLE IF EXISTS `veterinarian_profiles`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- 1. Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(30) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('owner', 'veterinarian', 'admin') NOT NULL,
  `account_status` ENUM('active', 'inactive', 'suspended', 'pending') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_role` (`role`),
  INDEX `idx_status` (`account_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Table structure for table `veterinarian_profiles`
-- --------------------------------------------------------
CREATE TABLE `veterinarian_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `qualification` VARCHAR(150) NOT NULL,
  `specialization` VARCHAR(150) NOT NULL,
  `license_number` VARCHAR(100) NOT NULL UNIQUE,
  `experience_years` INT NOT NULL DEFAULT 0,
  `bio` TEXT NULL,
  `consultation_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_vet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Table structure for table `animals`
-- --------------------------------------------------------
CREATE TABLE `animals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT NOT NULL,
  `animal_name` VARCHAR(100) NOT NULL,
  `species` VARCHAR(50) NOT NULL,
  `breed` VARCHAR(100) NULL,
  `gender` ENUM('male', 'female', 'unknown') NOT NULL DEFAULT 'unknown',
  `date_of_birth` DATE NULL,
  `age_years` INT NULL,
  `weight` DECIMAL(6,2) NULL,
  `color` VARCHAR(50) NULL,
  `identification_number` VARCHAR(100) NULL,
  `photo_path` VARCHAR(255) NULL,
  `allergies` TEXT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_animal_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_animal_species` (`species`),
  INDEX `idx_animal_owner` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Table structure for table `veterinarian_availability`
-- --------------------------------------------------------
CREATE TABLE `veterinarian_availability` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `veterinarian_id` INT NOT NULL,
  `available_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `slot_duration_minutes` INT NOT NULL DEFAULT 30,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_avail_vet` FOREIGN KEY (`veterinarian_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_avail_vet_date` (`veterinarian_id`, `available_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Table structure for table `appointments`
-- --------------------------------------------------------
CREATE TABLE `appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT NOT NULL,
  `veterinarian_id` INT NOT NULL,
  `animal_id` INT NOT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `symptoms` TEXT NOT NULL,
  `appointment_status` ENUM('pending', 'scheduled', 'completed', 'cancelled', 'rejected') NOT NULL DEFAULT 'pending',
  `veterinarian_notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_app_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_vet` FOREIGN KEY (`veterinarian_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  INDEX `idx_app_date_time` (`veterinarian_id`, `appointment_date`, `appointment_time`),
  INDEX `idx_app_status` (`appointment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Table structure for table `medical_records`
-- --------------------------------------------------------
CREATE TABLE `medical_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `animal_id` INT NOT NULL,
  `appointment_id` INT NULL,
  `veterinarian_id` INT NOT NULL,
  `visit_date` DATE NOT NULL,
  `symptoms` TEXT NOT NULL,
  `diagnosis` TEXT NOT NULL,
  `treatment_plan` TEXT NOT NULL,
  `clinical_notes` TEXT NULL,
  `follow_up_date` DATE NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_mr_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mr_app` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mr_vet` FOREIGN KEY (`veterinarian_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_mr_animal` (`animal_id`),
  INDEX `idx_mr_visit_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Table structure for table `prescriptions`
-- --------------------------------------------------------
CREATE TABLE `prescriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `medical_record_id` INT NOT NULL,
  `medicine_name` VARCHAR(150) NOT NULL,
  `dosage` VARCHAR(100) NOT NULL,
  `frequency` VARCHAR(100) NOT NULL,
  `duration` VARCHAR(100) NOT NULL,
  `instructions` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_rx_mr` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE,
  INDEX `idx_rx_mr` (`medical_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Table structure for table `vaccinations`
-- --------------------------------------------------------
CREATE TABLE `vaccinations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `animal_id` INT NOT NULL,
  `veterinarian_id` INT NOT NULL,
  `vaccine_name` VARCHAR(150) NOT NULL,
  `vaccination_date` DATE NOT NULL,
  `next_due_date` DATE NULL,
  `batch_number` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_vac_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vac_vet` FOREIGN KEY (`veterinarian_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_vac_animal` (`animal_id`),
  INDEX `idx_vac_due_date` (`next_due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. Table structure for table `contact_messages`
-- --------------------------------------------------------
CREATE TABLE `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('unread', 'read', 'replied') NOT NULL DEFAULT 'unread',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_contact_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 10. Table structure for table `activity_logs`
-- --------------------------------------------------------
CREATE TABLE `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_log_user` (`user_id`),
  INDEX `idx_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- SEED DEMONSTRATION DATA
-- Password for Admin: Admin@123
-- Password for Vets: Vet@123
-- Password for Owners: Owner@123
-- ========================================================

-- Insert Administrator
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `account_status`, `created_at`) VALUES
(1, 'System Administrator', 'admin@veterinary.com', '+1-555-0100', '$2y$10$fGwI.wyDoyQufOL/6FV0k.IRvGuD.586Y4Oh7gfOMCKY7Bx9OrRg2', 'admin', 'active', NOW());

-- Insert Veterinarians
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `account_status`, `created_at`) VALUES
(2, 'Dr. Sarah Jenkins', 'sarah.jenkins@veterinary.com', '+1-555-0101', '$2y$10$nWRxUGWee/9zOdYvkjcTf.uueIc38SEPJJGi1JFpX.FkaiibpBRLe', 'veterinarian', 'active', NOW()),
(3, 'Dr. Marcus Rodriguez', 'marcus.rodriguez@veterinary.com', '+1-555-0102', '$2y$10$nWRxUGWee/9zOdYvkjcTf.uueIc38SEPJJGi1JFpX.FkaiibpBRLe', 'veterinarian', 'active', NOW()),
(4, 'Dr. Priya Sharma', 'priya.sharma@veterinary.com', '+1-555-0103', '$2y$10$nWRxUGWee/9zOdYvkjcTf.uueIc38SEPJJGi1JFpX.FkaiibpBRLe', 'veterinarian', 'active', NOW()),
(5, 'Dr. James Wilson', 'james.wilson@veterinary.com', '+1-555-0104', '$2y$10$nWRxUGWee/9zOdYvkjcTf.uueIc38SEPJJGi1JFpX.FkaiibpBRLe', 'veterinarian', 'pending', NOW());

-- Insert Veterinarian Profiles
INSERT INTO `veterinarian_profiles` (`id`, `user_id`, `qualification`, `specialization`, `license_number`, `experience_years`, `bio`, `consultation_fee`, `approval_status`, `created_at`) VALUES
(1, 2, 'DVM, MSc Small Animal Clinical Sciences', 'Canine & Feline Medicine', 'VET-2024-8841', 9, 'Dedicated small animal clinician with expertise in internal medicine, preventative wellness, and surgical care for dogs and cats.', 45.00, 'approved', NOW()),
(2, 3, 'BVSc & AH, Certified Farm Animal Specialist', 'Livestock & Equine Health', 'VET-2023-5512', 12, 'Experienced rural veterinarian specializing in herd health management, bovine medicine, equine lameness, and ruminant care.', 60.00, 'approved', NOW()),
(3, 4, 'DVM, Board Certified Companion Animal Surgery', 'Avian & Small Animal Surgery', 'VET-2024-3298', 7, 'Specialist in avian care, small mammals, diagnostic imaging, and soft tissue veterinary surgery.', 50.00, 'approved', NOW()),
(4, 5, 'DVM, Wildlife & Exotic Medicine Specialist', 'Exotics & Wildlife Rehabilitation', 'VET-2025-9910', 4, 'Passionate exotic veterinarian focusing on reptiles, avian species, and specialized wildlife care.', 40.00, 'pending', NOW());

-- Insert Owners
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `account_status`, `created_at`) VALUES
(6, 'Alice Morgan', 'alice@example.com', '+1-555-0201', '$2y$10$cblKGBnngEeB4zvxnjqtSeK4qRai2AAqUD9BK2wtNnbYgOZ7tVm6C', 'owner', 'active', NOW()),
(7, 'David Smith', 'david@example.com', '+1-555-0202', '$2y$10$cblKGBnngEeB4zvxnjqtSeK4qRai2AAqUD9BK2wtNnbYgOZ7tVm6C', 'owner', 'active', NOW());

-- Insert Animals
INSERT INTO `animals` (`id`, `owner_id`, `animal_name`, `species`, `breed`, `gender`, `date_of_birth`, `age_years`, `weight`, `color`, `identification_number`, `allergies`, `notes`, `created_at`) VALUES
(1, 6, 'Buddy', 'Dog', 'Golden Retriever', 'male', '2021-04-10', 5, 31.50, 'Golden Honey', 'CHIP-US-998821', 'None known', 'Friendly and energetic, slight sensitive stomach on grain foods.', NOW()),
(2, 6, 'Luna', 'Cat', 'Persian', 'female', '2022-08-15', 3, 4.20, 'Pure White', 'CHIP-US-443312', 'Pollen', 'Quiet indoor cat, requires regular coat grooming.', NOW()),
(3, 7, 'Bella', 'Cow', 'Holstein Friesian', 'female', '2020-03-01', 6, 620.00, 'Black and White', 'TAG-COW-7801', 'None', 'High milk yield dairy cow, docile temperament.', NOW()),
(4, 7, 'Thunder', 'Horse', 'Thoroughbred', 'male', '2019-06-20', 7, 510.00, 'Dark Chestnut', 'PASSPORT-EQ-3021', 'Penicillin', 'Trained riding horse, responsive and healthy.', NOW()),
(5, 7, 'Daisy', 'Goat', 'Boer', 'female', '2023-01-12', 3, 48.00, 'White with Brown Head', 'TAG-GT-201', 'None', 'Active herd member, good appetite.', NOW());

-- Insert Veterinarian Availability (Upcoming schedule)
INSERT INTO `veterinarian_availability` (`veterinarian_id`, `available_date`, `start_time`, `end_time`, `slot_duration_minutes`, `is_available`) VALUES
(2, CURDATE(), '09:00:00', '17:00:00', 30, 1),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '17:00:00', 30, 1),
(2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:00:00', '17:00:00', 30, 1),
(2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:00:00', '17:00:00', 30, 1),
(3, CURDATE(), '10:00:00', '18:00:00', 45, 1),
(3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '18:00:00', 45, 1),
(3, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', '18:00:00', 45, 1),
(4, CURDATE(), '08:30:00', '16:30:00', 30, 1),
(4, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:30:00', '16:30:00', 30, 1);

-- Insert Sample Appointments
INSERT INTO `appointments` (`id`, `owner_id`, `veterinarian_id`, `animal_id`, `appointment_date`, `appointment_time`, `reason`, `symptoms`, `appointment_status`, `veterinarian_notes`, `created_at`) VALUES
(1, 6, 2, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '10:00:00', 'Annual Health Checkup & Vaccinations', 'Mild lethargy and occasional scratching of left ear.', 'completed', 'Animal examined. Ear canal cleaned, ear drops prescribed.', NOW()),
(2, 6, 2, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:00:00', 'Routine Wellness & Deworming', 'Hairball coughing noticed over last three days.', 'scheduled', 'Owner instructed to bring stool sample if possible.', NOW()),
(3, 7, 3, 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', 'Routine Herd Pregnancy & Lactation Check', 'Slight reduction in daily milk intake.', 'pending', NULL, NOW());

-- Insert Medical Records
INSERT INTO `medical_records` (`id`, `animal_id`, `appointment_id`, `veterinarian_id`, `visit_date`, `symptoms`, `diagnosis`, `treatment_plan`, `clinical_notes`, `follow_up_date`, `created_at`) VALUES
(1, 1, 1, 2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Head shaking, scratching left ear, mild erythema.', 'Mild Otitis Externa (Bacterial / Yeast)', 'Clean ears daily with antiseptic wash. Administer Otomax topical drops BID for 7 days. Recheck in 10 days.', 'Dog was calm and cooperative. Vitals normal: T=38.4C, HR=96bpm. Right ear clean.', DATE_ADD(CURDATE(), INTERVAL 5 DAY), NOW());

-- Insert Prescriptions
INSERT INTO `prescriptions` (`id`, `medical_record_id`, `medicine_name`, `dosage`, `frequency`, `duration`, `instructions`, `created_at`) VALUES
(1, 1, 'Otomax Ear Drops', '4 drops left ear', 'Twice daily', '7 days', 'Clean ear thoroughly before applying drops. Massage base of ear gently.', NOW()),
(2, 1, 'Amoxicillin Trihydrate (250mg)', '1 tablet', 'Twice daily with food', '5 days', 'Complete full course even if symptoms subside early.', NOW());

-- Insert Vaccinations
INSERT INTO `vaccinations` (`id`, `animal_id`, `veterinarian_id`, `vaccine_name`, `vaccination_date`, `next_due_date`, `batch_number`, `notes`, `created_at`) VALUES
(1, 1, 2, 'Rabies (Annual Booster)', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 360 DAY), 'RB-99214-A', 'Administered subcutaneous right shoulder. No adverse reaction observed.', NOW()),
(2, 1, 2, 'DHPP Canine Core Vaccine', DATE_SUB(CURDATE(), INTERVAL 180 DAY), DATE_ADD(CURDATE(), INTERVAL 185 DAY), 'DHPP-5510-C', 'Booster up to date.', NOW()),
(3, 2, 2, 'FVRCP Feline Core Vaccine', DATE_SUB(CURDATE(), INTERVAL 350 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'FVR-8820-K', 'Annual booster upcoming within 15 days.', NOW()),
(4, 3, 3, 'Bovine FMD (Foot & Mouth Disease)', DATE_SUB(CURDATE(), INTERVAL 90 DAY), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'FMD-BT-112', 'Standard herd vaccination schedule.', NOW()),
(5, 4, 3, 'Equine Tetanus & Influenza', DATE_SUB(CURDATE(), INTERVAL 360 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'EQ-TET-004', 'Overdue vaccination alert for annual tetanus booster.', NOW());

-- Insert Contact Messages
INSERT INTO `contact_messages` (`id`, `sender_name`, `email`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 'Michael Roberts', 'michael.r@example.com', 'Emergency farm visit inquiry', 'Do you provide on-site emergency visits for dairy cattle in the northern county area?', 'unread', NOW()),
(2, 'Emily Green', 'emily.g@example.com', 'Puppy vaccination schedule guidelines', 'I just adopted an 8-week-old rescue puppy and would love guidance on vaccination timing.', 'read', NOW());

-- Insert Activity Logs
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `created_at`) VALUES
(1, 1, 'SYSTEM_INITIALIZATION', 'system', 1, NOW()),
(2, 6, 'ANIMAL_REGISTERED', 'animal', 1, NOW()),
(3, 6, 'ANIMAL_REGISTERED', 'animal', 2, NOW()),
(4, 7, 'ANIMAL_REGISTERED', 'animal', 3, NOW()),
(5, 6, 'APPOINTMENT_BOOKED', 'appointment', 1, NOW()),
(6, 2, 'MEDICAL_RECORD_CREATED', 'medical_record', 1, NOW());
