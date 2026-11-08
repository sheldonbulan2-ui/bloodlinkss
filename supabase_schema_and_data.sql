-- ==================================================================
-- BloodLink Supabase (PostgreSQL) Database Initialization Script
-- Generated for Supabase SQL Editor
-- ==================================================================

-- Enable pgcrypto for UUIDs & cryptographic functions if needed
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ------------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "users" (
    "id" SERIAL PRIMARY KEY,
    "username" varchar(80) NOT NULL,
    "email" varchar(160),
    "password_hash" varchar(255) NOT NULL,
    "role" VARCHAR(100) NOT NULL,
    "status" VARCHAR(100) NOT NULL DEFAULT 'active',
    "email_verified" BOOLEAN NOT NULL DEFAULT TRUE,
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "profile_image" varchar(500),
    "hospital_id" INTEGER,
    "patient_profile_image" varchar(500),
    "donor_profile_image" varchar(500)
);

-- Seed data for users (18 rows)
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (1, 'superadmin', 'superadmin@bloodlink.local', '$2y$10$xNTvqYsFjdAkZqM7af0ttO4loGDldQKwJ1XsfPvEMe/n2NzAwxkoC', 'superadmin', 'active', 1, '2026-09-30 02:55:21', '2026-09-30 04:03:17', NULL, NULL, NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (2, 'admin', 'admin@bloodlink.local', '$2y$10$IyKCFd7X.cKJit3tE1xmPuQIEr8GDsy/EwToMo9SCtmEZ0LkdqJPK', 'admin', 'active', 1, '2026-09-30 02:55:21', '2026-10-12 12:28:29', NULL, 1, NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (3, 'staff', 'staff@bloodlink.local', '$2y$10$ZO7pOmDKgQPoVEhF2sTAkO0VFAL6dDug.dK5M02COe8meK4TMgG22', 'staff', 'active', 1, '2026-09-30 02:55:21', '2026-10-12 12:28:29', NULL, 1, NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (4, 'patient', 'patient@example.com', '$2y$10$pU3PJrIwVzOl44Lyb/5AuO33xFJcb2xdUpoWY5133H30gSQJXz68S', 'patient', 'active', 1, '2026-09-30 02:55:21', '2026-09-30 04:03:17', NULL, NULL, NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (5, 'donor', 'donor@example.com', '$2y$10$GKFUfO4IiOFlAQ.4X2bmAe/B1w4O3cC4j.YotTZuo4hEneTibmEpO', 'donor', 'active', 1, '2026-09-30 02:55:21', '2026-10-12 23:20:39', 'https://bloodlink-dzh8.onrender.com/public/uploads/doc_20261012_130821_f2c553bd80da.jpg', NULL, NULL, 'https://bloodlink-dzh8.onrender.com/public/uploads/doc_20261012_232039_b05e23b9f0cc.jpg') ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (6, 'sherlon', 'sheldonbulan88@gmail.com', '$2y$10$FsxnTWZbFVnRkoEJhYjswe50d487BR5VkZH2shpnmODUOt/qUaorW', 'patient', 'active', 1, '2026-09-30 02:55:21', '2026-09-30 05:32:43', NULL, NULL, NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (30002, 'sheldon', 'sheldonbulan2@gmail.com', '$2y$10$PueYgnuZ1M5jd1bhwpgvDOCYgYnZWNtNQMiqx7C.TKbwQr7H45IVe', 'patient', 'active', 1, '2026-09-30 04:03:55', '2026-10-12 23:19:28', 'https://bloodlink-dzh8.onrender.com/public/uploads/doc_20261012_130632_128ca2f41d52.jpg', NULL, 'https://bloodlink-dzh8.onrender.com/public/uploads/doc_20261012_231927_2a2ca9b293ef.jpg', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (60002, 'sheldonph', 'sheldonbulan88@gmail.com', '$2y$10$0PPoY7nQq6ZVToY/B.z8OOk0gQaOiKxFwflxuHFdmHSrxAFop7eA6', 'donor', 'active', 1, '2026-09-30 05:06:10', '2026-09-30 06:59:19', NULL, NULL, NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (90002, 'Ries', 'aris@gmail.com', '$2y$10$Rdysj5Vvh2RkDarvVZBouu//6LVuVYUITmb.Bpn1u3QnYG.4ASEv2', 'donor', 'active', 1, '2026-09-30 12:28:27', '2026-09-30 12:28:27', NULL, NULL, NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (90003, 'Aries', 'aristotlecorpuz1@gmail.com', '$2y$10$XgSGCPynb1elj8m9VraW6.crHJsYEJNNp5q4cJs5GM7aTNLH5uSfW', 'donor', 'active', 1, '2026-09-30 12:29:48', '2026-09-30 12:29:48', NULL, NULL, NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (120002, 'Philip12', 'rachu44phil@gmail.com', '$2y$10$3Wc4wICZiGUu16fqBn64pO1Y.RQ1QRB4Ksvu6bSs96UUkQ1kZRfE6', 'patient', 'active', 1, '2026-09-30 12:50:50', '2026-09-30 12:56:06', NULL, NULL, NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (1242184, 'Ristot', 'ris@gmail.com', '$2y$10$2FIwx6q7LWBjztQaQXm0l.16AAuFDC8Fe2FsQrZsRvNkV4Jt/QVf6', 'donor', 'active', 1, '2026-10-02 05:42:01', '2026-10-02 05:42:01', NULL, NULL, NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (1242185, 'Ristotle', 'ris162636@gmail.com', '$2y$10$i7Y8EQ8CXJixMIN6p/CDfeLSlLHeebXcXxq/BsRR1gB2gAyx9GPwy', 'donor', 'active', 1, '2026-10-02 05:43:19', '2026-10-02 05:43:19', NULL, NULL, NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (1272184, 'addy', 'djangel815@gmail.com', '$2y$10$i1UxSewUH4Feaq6NVRUOpOvUaWbHOAlQEWL3JPXJj9A623TfgicJu', 'donor', 'active', 1, '2026-10-08 05:30:13', '2026-10-08 05:30:13', NULL, NULL, NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (1302184, 'Philmanzano', 'philipmanzano27@gmail.com', '$2y$10$WZXUK9whWjwhfTmMLA6y4.OaHYg2ax0RVvE7Qi/aTERGVsgD.GOIi', 'donor', 'active', '0', '2026-10-13 01:33:43', '2026-10-13 01:35:12', NULL, NULL, NULL, 'https://bloodlink-dzh8.onrender.com/public/uploads/doc_20261013_013511_e1080d7592eb.jpg') ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (1332184, 'donortest2026', 'testdonor2026@gmail.com', '$2y$10$UPfm/wOhfuh.bRamK3n2m.bSaarQPdUGxB7bFLx9AKslSvKiFxBVu', 'donor', 'active', '0', '2026-10-14 12:28:48', '2026-10-14 12:28:48', NULL, NULL, NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (1362184, 'shhaah', 'sheldonbulan1234@gmail.com', '$2y$10$iS7qZCUfUp3vp.jjZxApYOxgUVGkuv.D4D4vQr05zOLT1qu031Aou', 'patient', 'active', '0', '2026-11-07 09:07:17', '2026-11-07 09:07:17', NULL, NULL, NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "users" ("id", "username", "email", "password_hash", "role", "status", "email_verified", "created_at", "updated_at", "profile_image", "hospital_id", "patient_profile_image", "donor_profile_image") VALUES (1392184, 'MP1234', 'manzanophilip745@gmail.com', '$2y$10$IHd77hqB/kkC9xw4nPmcwem.ZPl/NXjQURTR0PMMhm4sfHiincZhu', 'donor', 'active', '0', '2026-11-08 00:27:06', '2026-11-08 00:27:06', NULL, NULL, NULL, NULL) ON CONFLICT DO NOTHING;

-- ------------------------------------------------------------------
-- Table: patients
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "patients" (
    "id" SERIAL PRIMARY KEY,
    "user_id" INTEGER NOT NULL,
    "patient_code" varchar(30) NOT NULL,
    "first_name" varchar(80) NOT NULL,
    "middle_name" varchar(80),
    "last_name" varchar(80) NOT NULL,
    "age" SMALLINT,
    "sex" VARCHAR(100),
    "contact_number" varchar(30) NOT NULL,
    "address" varchar(255),
    "blood_type_needed" VARCHAR(100),
    "hospital" varchar(160) NOT NULL,
    "medical_notes" TEXT,
    "status" VARCHAR(100) NOT NULL DEFAULT 'active',
    "profile_image" varchar(500)
);

-- Seed data for patients (6 rows)
INSERT INTO "patients" ("id", "user_id", "patient_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type_needed", "hospital", "medical_notes", "status", "profile_image") VALUES (1, 4, 'PAT-0001', 'Juan', 'Dela', 'Cruz', 45, 'Male', '09189876543', 'Brgy. Sto. Domingo, San Mariano, Isabela', 'AB+', 'San Mariano District Hospital', 'Hypertension', 'active', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "patients" ("id", "user_id", "patient_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type_needed", "hospital", "medical_notes", "status", "profile_image") VALUES (2, 6, 'PAT-F66AB89F', 'Sherlon', NULL, 'Bulan', 19, 'Male', '09925710218', 'Andabuen Benito Soliven', 'AB+', 'Andabuen Benito Soliven', NULL, 'active', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "patients" ("id", "user_id", "patient_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type_needed", "hospital", "medical_notes", "status", "profile_image") VALUES (30001, 30002, 'PAT-00001', 'Sheldon', 'f', 'Bulan', 20, 'Male', '09922093577', 'andabuen benito soliven', 'A+', 'dfafada', 'sfsdfad', 'active', 'https://bloodlink-dzh8.onrender.com/public/uploads/doc_20261012_231927_2a2ca9b293ef.jpg') ON CONFLICT DO NOTHING;
INSERT INTO "patients" ("id", "user_id", "patient_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type_needed", "hospital", "medical_notes", "status", "profile_image") VALUES (60001, 120002, 'PAT-00002', 'Philip', NULL, 'Manzano', 21, 'Male', '09557009279', 'Danipa Benito Soliven Isabela', 'A+', 'Danipa Benito Soliven Isabela', NULL, 'active', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "patients" ("id", "user_id", "patient_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type_needed", "hospital", "medical_notes", "status", "profile_image") VALUES (90001, 5, 'PAT-00003', 'Maria', 'Lopez', 'Santos', 28, 'Female', '09171234567', 'Brgy. San Isidro, San Mariano, Isabela', 'O+', 'Community / General', 'Self-registered requestor', 'active', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "patients" ("id", "user_id", "patient_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type_needed", "hospital", "medical_notes", "status", "profile_image") VALUES (120001, 1362184, 'PAT-00004', 'Hshah', 'Babasb', 'Baba', 32, 'Male', '09922093577', 'peowowlw, Abra, Santiago City, Isabela', 'AB+', 'peowowlw, Abra, Santiago City, Isabela', NULL, 'active', NULL) ON CONFLICT DO NOTHING;

-- ------------------------------------------------------------------
-- Table: donors
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "donors" (
    "id" SERIAL PRIMARY KEY,
    "user_id" INTEGER NOT NULL,
    "donor_code" varchar(30) NOT NULL,
    "first_name" varchar(80) NOT NULL,
    "middle_name" varchar(80),
    "last_name" varchar(80) NOT NULL,
    "age" SMALLINT,
    "sex" VARCHAR(100),
    "contact_number" varchar(30) NOT NULL,
    "address" varchar(255),
    "blood_type" VARCHAR(100) NOT NULL,
    "email" varchar(160) NOT NULL,
    "date_registered" DATE NOT NULL DEFAULT CURRENT_DATE,
    "last_donation_date" DATE,
    "status" VARCHAR(100) NOT NULL DEFAULT 'active',
    "profile_image" varchar(500)
);

-- Seed data for donors (12 rows)
INSERT INTO "donors" ("id", "user_id", "donor_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type", "email", "date_registered", "last_donation_date", "status", "profile_image") VALUES (1, 5, 'DON-0001', 'Maria', 'Lopez', 'Santos', 28, 'Female', '09171234567', 'Brgy. San Isidro, San Mariano, Isabela', 'O+', 'donor@example.com', '2026-09-30', NULL, 'active', 'https://bloodlink-dzh8.onrender.com/public/uploads/doc_20261012_232039_b05e23b9f0cc.jpg') ON CONFLICT DO NOTHING;
INSERT INTO "donors" ("id", "user_id", "donor_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type", "email", "date_registered", "last_donation_date", "status", "profile_image") VALUES (30001, 60002, 'DON-0B910E36', 'Sheldon', NULL, 'Bulan', 20, 'Male', '09104558714', 'Andabuen Bsi', 'AB+', 'sheldonbulan88@gmail.com', '2026-09-30', NULL, 'active', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "donors" ("id", "user_id", "donor_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type", "email", "date_registered", "last_donation_date", "status", "profile_image") VALUES (60001, 90002, 'DON-00037', 'Ries', NULL, 'Corpuz', 20, 'Male', '09123456789', 'Benito Soliven, Isabela', 'O-', 'aris@gmail.com', '2026-09-30', NULL, 'active', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "donors" ("id", "user_id", "donor_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type", "email", "date_registered", "last_donation_date", "status", "profile_image") VALUES (60002, 90003, 'DON-00038', 'Ries', NULL, 'Corpuz', 20, 'Male', '09123456789', 'Benito Soliven, Isabela', 'O-', 'aristotlecorpuz1@gmail.com', '2026-09-30', NULL, 'active', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "donors" ("id", "user_id", "donor_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type", "email", "date_registered", "last_donation_date", "status", "profile_image") VALUES (90001, 1242184, 'DON-00039', 'ris', NULL, 'totle', 20, 'Male', '09987654321', 'Benito Soliven', 'O-', 'ris@gmail.com', '2026-10-02', NULL, 'active', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "donors" ("id", "user_id", "donor_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type", "email", "date_registered", "last_donation_date", "status", "profile_image") VALUES (90002, 1242185, 'DON-00040', 'ries', NULL, 'totle', 20, 'Male', '09123456789', 'Soliven', 'O-', 'ris162636@gmail.com', '2026-10-02', NULL, 'active', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "donors" ("id", "user_id", "donor_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type", "email", "date_registered", "last_donation_date", "status", "profile_image") VALUES (120001, 1272184, 'DON-00041', 'Dj Angel', NULL, 'Danguilan', 22, 'Male', '09165410874', 'San mariano', 'O+', 'djangel815@gmail.com', '2026-10-08', NULL, 'active', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "donors" ("id", "user_id", "donor_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type", "email", "date_registered", "last_donation_date", "status", "profile_image") VALUES (150001, 1302184, 'DON-00042', 'Philip', 'Seroma', 'Manzano', 22, 'Male', '09367495753', 'purok 3, Danipa, Benito Soliven, Isabela', 'A-', 'philipmanzano27@gmail.com', '2026-10-13', NULL, 'active', 'https://bloodlink-dzh8.onrender.com/public/uploads/doc_20261013_013511_e1080d7592eb.jpg') ON CONFLICT DO NOTHING;
INSERT INTO "donors" ("id", "user_id", "donor_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type", "email", "date_registered", "last_donation_date", "status", "profile_image") VALUES (180001, 30002, 'DON-00043', 'Sheldon', 'f', 'Bulan', 20, 'Male', '09922093577', 'andabuen benito soliven', 'A+', 'sheldonbulan2@gmail.com', '2026-10-13', NULL, 'active', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "donors" ("id", "user_id", "donor_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type", "email", "date_registered", "last_donation_date", "status", "profile_image") VALUES (210001, 1332184, 'DON-00044', 'Juan', NULL, 'Dela Cruz', 25, 'Male', '09921234567', 'Diliman, Quezon City, Metro Manila (NCR)', 'O+', 'testdonor2026@gmail.com', '2026-10-14', NULL, 'active', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "donors" ("id", "user_id", "donor_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type", "email", "date_registered", "last_donation_date", "status", "profile_image") VALUES (240001, 4, 'DON-00045', 'Juan', 'Dela', 'Cruz', 45, 'Male', '09189876543', 'Brgy. Sto. Domingo, San Mariano, Isabela', 'AB+', 'patient@example.com', '2026-11-07', NULL, 'active', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "donors" ("id", "user_id", "donor_code", "first_name", "middle_name", "last_name", "age", "sex", "contact_number", "address", "blood_type", "email", "date_registered", "last_donation_date", "status", "profile_image") VALUES (270001, 1392184, 'DON-00046', 'Hdhdjd', 'You', 'Qwerty', 25, 'Male', '09557009729', 'purok 1, Balagan, San Mariano, Isabela', 'A+', 'manzanophilip745@gmail.com', '2026-11-08', NULL, 'active', NULL) ON CONFLICT DO NOTHING;

-- ------------------------------------------------------------------
-- Table: blood_locations
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "blood_locations" (
    "id" SERIAL PRIMARY KEY,
    "location_name" varchar(160) NOT NULL,
    "address" varchar(255),
    "latitude" decimal(10,7) NOT NULL,
    "longitude" decimal(10,7) NOT NULL,
    "contact_number" varchar(30) NOT NULL,
    "available_status" VARCHAR(100) NOT NULL DEFAULT 'Active',
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "admin_id" INTEGER,
    "created_by" INTEGER
);

-- Seed data for blood_locations (3 rows)
INSERT INTO "blood_locations" ("id", "location_name", "address", "latitude", "longitude", "contact_number", "available_status", "created_at", "updated_at", "admin_id", "created_by") VALUES (1, 'San Mariano Blood Center', 'San Mariano, Isabela', 17.0831000, 122.1894000, '(078) 305-1111', 'Active', '2026-09-30 02:55:28', '2026-09-30 02:55:28', NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "blood_locations" ("id", "location_name", "address", "latitude", "longitude", "contact_number", "available_status", "created_at", "updated_at", "admin_id", "created_by") VALUES (2, 'Cagayan Valley Medical Center', 'Tuguegarao City, Cagayan', 17.6132000, 121.7270000, '(078) 304-1810', 'Active', '2026-09-30 02:55:28', '2026-09-30 02:55:28', NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO "blood_locations" ("id", "location_name", "address", "latitude", "longitude", "contact_number", "available_status", "created_at", "updated_at", "admin_id", "created_by") VALUES (3, 'Isabela Doctors General Hospital', 'Ilagan City, Isabela', 17.1458000, 121.8906000, '(078) 624-2115', 'Active', '2026-09-30 02:55:28', '2026-09-30 02:55:28', NULL, NULL) ON CONFLICT DO NOTHING;

-- ------------------------------------------------------------------
-- Table: blood_inventory
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "blood_inventory" (
    "id" SERIAL PRIMARY KEY,
    "blood_type" VARCHAR(100) NOT NULL,
    "component_type" varchar(64) NOT NULL DEFAULT 'Whole Blood',
    "blood_units" INTEGER NOT NULL DEFAULT '0',
    "location_id" INTEGER NOT NULL,
    "expiration_date" DATE NOT NULL,
    "status" VARCHAR(100) NOT NULL DEFAULT 'Available',
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Seed data for blood_inventory (16 rows)
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (1, 'O+', 'Whole Blood', 14, 1, '2026-11-11', 'Available', '2026-09-30 02:55:28', '2026-10-22 02:39:47') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (2, 'O-', 'Whole Blood', 5, 1, '2026-11-09', 'Available', '2026-09-30 02:55:28', '2026-09-30 02:55:28') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (3, 'A+', 'Whole Blood', 10, 1, '2026-11-07', 'Available', '2026-09-30 02:55:28', '2026-09-30 02:55:28') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (4, 'A-', 'Whole Blood', 4, 1, '2026-11-04', 'Expired', '2026-09-30 02:55:28', '2026-11-07 02:42:00') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (5, 'B+', 'Whole Blood', 8, 1, '2026-11-10', 'Available', '2026-09-30 02:55:28', '2026-09-30 02:55:28') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (6, 'B-', 'Whole Blood', 3, 1, '2026-11-02', 'Expired', '2026-09-30 02:55:28', '2026-11-07 02:42:00') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (7, 'AB+', 'Whole Blood', 6, 1, '2026-11-06', 'Expired', '2026-09-30 02:55:28', '2026-11-07 02:42:00') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (8, 'AB-', 'Whole Blood', 2, 1, '2026-10-30', 'Expired', '2026-09-30 02:55:28', '2026-10-31 04:23:22') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (9, 'O+', 'Whole Blood', 18, 2, '2026-11-09', 'Available', '2026-09-30 02:55:28', '2026-09-30 07:53:03') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (10, 'A+', 'Whole Blood', 9, 2, '2026-11-04', 'Expired', '2026-09-30 02:55:28', '2026-11-07 02:42:00') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (11, 'B+', 'Whole Blood', 10, 2, '2026-11-07', 'Available', '2026-09-30 02:55:28', '2026-09-30 02:55:28') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (12, 'AB+', 'Whole Blood', 8, 3, '2026-11-05', 'Expired', '2026-09-30 02:55:28', '2026-11-07 02:42:00') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (30001, 'O+', 'Whole Blood', 1, 2, '2026-11-04', 'Expired', '2026-09-30 08:00:12', '2026-11-07 02:42:00') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (60001, 'AB+', 'Whole Blood', 1, 2, '2026-11-04', 'Expired', '2026-09-30 08:39:38', '2026-11-07 02:42:00') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (90001, 'A+', 'Whole Blood', 1, 1, '2026-11-17', 'Available', '2026-10-13 01:24:46', '2026-10-13 01:24:46') ON CONFLICT DO NOTHING;
INSERT INTO "blood_inventory" ("id", "blood_type", "component_type", "blood_units", "location_id", "expiration_date", "status", "created_at", "updated_at") VALUES (90002, 'O+', 'Whole Blood', 2, 1, '2026-11-17', 'Available', '2026-10-13 01:31:20', '2026-10-13 01:33:16') ON CONFLICT DO NOTHING;

-- ------------------------------------------------------------------
-- Table: blood_fee_schedule
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "blood_fee_schedule" (
    "id" SERIAL PRIMARY KEY,
    "component_name" varchar(100) NOT NULL,
    "doh_approved_fee" decimal(10,2) NOT NULL,
    "testing_fee" decimal(10,2) NOT NULL DEFAULT '0.00',
    "crossmatching_fee" decimal(10,2) NOT NULL DEFAULT '0.00',
    "storage_fee" decimal(10,2) NOT NULL DEFAULT '0.00',
    "total_fee" decimal(10,2) NOT NULL,
    "legal_basis" varchar(255) DEFAULT 'DOH AO No. 2015-0045 / R.A. 7719 (National Voluntary Blood Services Act)',
    "notes" TEXT,
    "is_active" BOOLEAN NOT NULL DEFAULT TRUE,
    "updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed data for blood_fee_schedule (5 rows)
INSERT INTO "blood_fee_schedule" ("id", "component_name", "doh_approved_fee", "testing_fee", "crossmatching_fee", "storage_fee", "total_fee", "legal_basis", "notes", "is_active", "updated_at") VALUES (1, 'Whole Blood', 1800.00, 300.00, '0.00', '0.00', 2100.00, 'DOH AO No. 2015-0045 / R.A. 7719 (National Voluntary Blood Services Act)', 'Covers testing for 5 Transfusion-Transmissible Infections (HIV, HBV, HCV, Syphilis, Malaria) and blood bag consumables. The blood itself is 100% free under Philippine Law.', 1, '2026-11-07 04:06:24') ON CONFLICT DO NOTHING;
INSERT INTO "blood_fee_schedule" ("id", "component_name", "doh_approved_fee", "testing_fee", "crossmatching_fee", "storage_fee", "total_fee", "legal_basis", "notes", "is_active", "updated_at") VALUES (2, 'Packed Red Blood Cells (PRBC)', 2000.00, 300.00, '0.00', '0.00', 2300.00, 'DOH AO No. 2015-0045 / R.A. 7719 (National Voluntary Blood Services Act)', 'Component preparation and separation, preservation, and mandatory infectious disease screening.', 1, '2026-11-07 04:06:24') ON CONFLICT DO NOTHING;
INSERT INTO "blood_fee_schedule" ("id", "component_name", "doh_approved_fee", "testing_fee", "crossmatching_fee", "storage_fee", "total_fee", "legal_basis", "notes", "is_active", "updated_at") VALUES (3, 'Platelets (Platelet Concentrate)', 1200.00, 300.00, '0.00', '0.00', 1500.00, 'DOH AO No. 2015-0045 / R.A. 7719 (National Voluntary Blood Services Act)', 'Agitation storage at 20-24°C, bacterial screening, and apheresis/centrifugation processing.', 1, '2026-11-07 04:06:24') ON CONFLICT DO NOTHING;
INSERT INTO "blood_fee_schedule" ("id", "component_name", "doh_approved_fee", "testing_fee", "crossmatching_fee", "storage_fee", "total_fee", "legal_basis", "notes", "is_active", "updated_at") VALUES (4, 'Fresh Frozen Plasma (FFP)', 1200.00, 300.00, '0.00', '0.00', 1500.00, 'DOH AO No. 2015-0045 / R.A. 7719 (National Voluntary Blood Services Act)', 'Flash freezing within 8 hours of collection at -18°C or colder.', 1, '2026-11-07 04:06:24') ON CONFLICT DO NOTHING;
INSERT INTO "blood_fee_schedule" ("id", "component_name", "doh_approved_fee", "testing_fee", "crossmatching_fee", "storage_fee", "total_fee", "legal_basis", "notes", "is_active", "updated_at") VALUES (5, 'Cryoprecipitate', 1200.00, 300.00, '0.00', '0.00', 1500.00, 'DOH AO No. 2015-0045 / R.A. 7719 (National Voluntary Blood Services Act)', 'Cold-insoluble portion of plasma rich in Factor VIII and Fibrinogen.', 1, '2026-11-07 04:06:24') ON CONFLICT DO NOTHING;

-- ------------------------------------------------------------------
-- Table: blood_requests
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "blood_requests" (
    "id" SERIAL PRIMARY KEY,
    "patient_id" INTEGER,
    "patient_name" varchar(255),
    "patient_dob" DATE,
    "patient_age" SMALLINT,
    "patient_mrn" varchar(64),
    "patient_sex" VARCHAR(100),
    "ward_room" varchar(64),
    "clinical_indication" varchar(255),
    "blood_type" VARCHAR(100) NOT NULL,
    "component_type" varchar(64) NOT NULL DEFAULT 'Whole Blood',
    "units_needed" INTEGER NOT NULL,
    "needed_datetime" TIMESTAMP,
    "request_date" DATE NOT NULL DEFAULT CURRENT_DATE,
    "priority" VARCHAR(100) NOT NULL DEFAULT 'Normal',
    "urgency_level" VARCHAR(100) NOT NULL DEFAULT 'Routine',
    "reason" varchar(500) NOT NULL,
    "special_requirements" TEXT,
    "requester_name" varchar(160),
    "requester_relationship" varchar(80),
    "requester_contact" varchar(50),
    "requester_email" varchar(160),
    "physician_name" varchar(160),
    "physician_license_no" varchar(80),
    "physician_signature" TEXT,
    "physician_signed_at" TIMESTAMP,
    "location" varchar(255),
    "contact_number" varchar(50),
    "medical_document_url" varchar(255),
    "status" VARCHAR(100) NOT NULL DEFAULT 'Pending',
    "document_status" varchar(64) NOT NULL DEFAULT 'Pending',
    "reviewed_by" INTEGER,
    "reviewed_at" TIMESTAMP,
    "units_confirmed" INTEGER,
    "component_confirmed" varchar(100),
    "compatibility_notes" TEXT,
    "correction_notes" TEXT,
    "final_status" varchar(64) NOT NULL DEFAULT 'Pending',
    "staff_signature_id" varchar(80),
    "inventory_deducted" BOOLEAN NOT NULL DEFAULT FALSE,
    "rejection_reason" varchar(255),
    "approved_at" TIMESTAMP,
    "processed_by" INTEGER,
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "group_id" varchar(64),
    "target_hospital_id" INTEGER,
    "hospital_department" varchar(120),
    "facility_address" varchar(255),
    "facility_contact" varchar(50),
    "request_ref_no" varchar(64),
    "approved_by_hospital" varchar(160)
);

-- Seed data for blood_requests (3 rows)
INSERT INTO "blood_requests" ("id", "patient_id", "patient_name", "patient_dob", "patient_age", "patient_mrn", "patient_sex", "ward_room", "clinical_indication", "blood_type", "component_type", "units_needed", "needed_datetime", "request_date", "priority", "urgency_level", "reason", "special_requirements", "requester_name", "requester_relationship", "requester_contact", "requester_email", "physician_name", "physician_license_no", "physician_signature", "physician_signed_at", "location", "contact_number", "medical_document_url", "status", "document_status", "reviewed_by", "reviewed_at", "units_confirmed", "component_confirmed", "compatibility_notes", "correction_notes", "final_status", "staff_signature_id", "inventory_deducted", "rejection_reason", "approved_at", "processed_by", "created_at", "group_id", "target_hospital_id", "hospital_department", "facility_address", "facility_contact", "request_ref_no", "approved_by_hospital") VALUES (1, 30001, 'sheldon', NULL, NULL, '', NULL, '', 'jwjwjw', 'O+', 'Whole Blood (commonly used for surgeries)', 1, NULL, '2026-10-30', 'Urgent', 'Urgent', 'jwjwjw', '', 'sheldon', 'Self / Mobile Request', '09922093577', '', '', '', '', NULL, 'San Mariano Blood Center', '09922093577', 'https://bloodlink-q334.onrender.com/public/uploads/doc_20261030_154041_1fbd534c0f47d6a6d459.jpg', 'Pending', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, '0', NULL, NULL, NULL, '2026-10-30 15:40:26', NULL, NULL, '', '', '', 'REQ-20261030-9E6465', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "blood_requests" ("id", "patient_id", "patient_name", "patient_dob", "patient_age", "patient_mrn", "patient_sex", "ward_room", "clinical_indication", "blood_type", "component_type", "units_needed", "needed_datetime", "request_date", "priority", "urgency_level", "reason", "special_requirements", "requester_name", "requester_relationship", "requester_contact", "requester_email", "physician_name", "physician_license_no", "physician_signature", "physician_signed_at", "location", "contact_number", "medical_document_url", "status", "document_status", "reviewed_by", "reviewed_at", "units_confirmed", "component_confirmed", "compatibility_notes", "correction_notes", "final_status", "staff_signature_id", "inventory_deducted", "rejection_reason", "approved_at", "processed_by", "created_at", "group_id", "target_hospital_id", "hospital_department", "facility_address", "facility_contact", "request_ref_no", "approved_by_hospital") VALUES (30001, 30001, 'sheldon', NULL, NULL, '', NULL, '', 'jdjdjs', 'O+', 'Whole Blood (commonly used for surgeries)', 1, NULL, '2026-10-31', 'Normal', 'Routine', 'jdjdjs', '', 'sheldon', 'Self / Mobile Request', '', '', '', '', '', NULL, 'San Mariano Blood Center', '', 'https://bloodlink-q334.onrender.com/public/uploads/doc_20261031_030639_c825e0e1dd53ff1e41a5.jpg', 'Pending', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, '0', NULL, NULL, NULL, '2026-10-31 03:06:40', NULL, NULL, '', '', '', 'REQ-20261031-2975E4', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "blood_requests" ("id", "patient_id", "patient_name", "patient_dob", "patient_age", "patient_mrn", "patient_sex", "ward_room", "clinical_indication", "blood_type", "component_type", "units_needed", "needed_datetime", "request_date", "priority", "urgency_level", "reason", "special_requirements", "requester_name", "requester_relationship", "requester_contact", "requester_email", "physician_name", "physician_license_no", "physician_signature", "physician_signed_at", "location", "contact_number", "medical_document_url", "status", "document_status", "reviewed_by", "reviewed_at", "units_confirmed", "component_confirmed", "compatibility_notes", "correction_notes", "final_status", "staff_signature_id", "inventory_deducted", "rejection_reason", "approved_at", "processed_by", "created_at", "group_id", "target_hospital_id", "hospital_department", "facility_address", "facility_contact", "request_ref_no", "approved_by_hospital") VALUES (60001, 30001, 'sheldon', NULL, NULL, '', NULL, '', 'jakalawk', 'O+', 'Whole Blood (commonly used for surgeries)', 1, NULL, '2026-11-07', 'Normal', 'Routine', 'jakalawk', '', 'sheldon', 'Self / Mobile Request', 131681010, '', '', '', '', NULL, 'San Mariano Blood Center', 131681010, 'https://bloodlink-q334.onrender.com/public/uploads/doc_20261107_034946_bd3071e10ca941c92d8a.jpg', 'Pending', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, '0', NULL, NULL, NULL, '2026-11-07 03:49:47', NULL, NULL, '', '', '', 'REQ-20261107-0D0FD4', NULL) ON CONFLICT DO NOTHING;

-- ------------------------------------------------------------------
-- Table: donations
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "donations" (
    "id" SERIAL PRIMARY KEY,
    "donor_id" INTEGER NOT NULL,
    "blood_type" VARCHAR(100) NOT NULL,
    "donation_date" DATE NOT NULL,
    "donation_time" varchar(20),
    "blood_units" INTEGER NOT NULL,
    "donation_location" varchar(255) NOT NULL,
    "notes" varchar(500),
    "reference_code" varchar(50),
    "status" VARCHAR(100) NOT NULL DEFAULT 'Pending',
    "recorded_by" INTEGER,
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "group_id" varchar(64),
    "target_hospital_id" INTEGER,
    "approved_by_hospital" varchar(160)
);

-- ------------------------------------------------------------------
-- Table: donor_screenings
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "donor_screenings" (
    "id" SERIAL PRIMARY KEY,
    "donor_id" INTEGER NOT NULL,
    "user_id" INTEGER NOT NULL,
    "component_type" varchar(50) NOT NULL DEFAULT 'Whole Blood',
    "weight_kg" decimal(5,2) NOT NULL,
    "hemoglobin_level" decimal(4,1),
    "blood_pressure" varchar(20),
    "pulse_rate" INTEGER,
    "passed_screening" BOOLEAN NOT NULL DEFAULT FALSE,
    "deferral_reason" varchar(255),
    "deferral_until" DATE,
    "screening_answers" TEXT,
    "screened_by" INTEGER,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------------
-- Table: feedbacks
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "feedbacks" (
    "id" SERIAL PRIMARY KEY,
    "user_id" INTEGER NOT NULL,
    "role" varchar(20) NOT NULL,
    "rating" INTEGER NOT NULL DEFAULT '5',
    "category" varchar(80) NOT NULL DEFAULT 'General Experience',
    "message" TEXT NOT NULL,
    "suggestion" TEXT,
    "status" varchar(20) NOT NULL DEFAULT 'New',
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed data for feedbacks (1 rows)
INSERT INTO "feedbacks" ("id", "user_id", "role", "rating", "category", "message", "suggestion", "status", "created_at") VALUES (1, 1302184, 'donor', 5, 'Blood Donation Process', 'It''s hard to learn the process of how to donating blood', '', 'New', '2026-10-13 01:38:47') ON CONFLICT DO NOTHING;

-- ------------------------------------------------------------------
-- Table: announcements
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "announcements" (
    "id" SERIAL PRIMARY KEY,
    "title" varchar(255) NOT NULL,
    "message" TEXT NOT NULL,
    "category" varchar(60) NOT NULL DEFAULT 'General',
    "target_audience" varchar(30) NOT NULL DEFAULT 'all',
    "is_urgent" BOOLEAN NOT NULL DEFAULT FALSE,
    "hospital_id" INTEGER,
    "hospital_name" varchar(160),
    "created_by" INTEGER NOT NULL,
    "created_by_name" varchar(120),
    "status" varchar(20) NOT NULL DEFAULT 'active',
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------------
-- Table: app_versions
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "app_versions" (
    "id" SERIAL PRIMARY KEY,
    "version_name" varchar(50) NOT NULL,
    "version_code" INTEGER NOT NULL,
    "release_notes" TEXT NOT NULL,
    "apk_file_url" varchar(255) NOT NULL,
    "file_size_bytes" BIGINT DEFAULT '0',
    "is_mandatory" BOOLEAN DEFAULT FALSE,
    "is_active" BOOLEAN DEFAULT TRUE,
    "uploaded_by" INTEGER,
    "uploaded_by_name" varchar(120),
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed data for app_versions (1 rows)
INSERT INTO "app_versions" ("id", "version_name", "version_code", "release_notes", "apk_file_url", "file_size_bytes", "is_mandatory", "is_active", "uploaded_by", "uploaded_by_name", "created_at") VALUES (1, '1.0.0', 1, 'Initial BloodLink mobile release with blood donation scheduling, patient requisition, and announcements.', '/public/download/Bloodlink_apk v1.0.apk', 10741814, '0', 1, NULL, 'System Superadmin', '2026-10-30 14:24:01') ON CONFLICT DO NOTHING;

-- ------------------------------------------------------------------
-- Table: notifications
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "notifications" (
    "id" SERIAL PRIMARY KEY,
    "user_id" INTEGER NOT NULL,
    "title" varchar(160) NOT NULL,
    "message" TEXT NOT NULL,
    "status" VARCHAR(100) NOT NULL DEFAULT 'unread',
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Seed data for notifications (16 rows)
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (120001, 120002, 'Blood Request Approved', 'Your request for 1 unit(s) of A+ was approved. Blood will be released from Cagayan Valley Medical Center.', 'unread', '2026-09-30 13:02:51') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (150001, 30002, 'Blood Request Approved', 'Your request for 2 unit(s) of A+ was approved. Blood will be released from Cagayan Valley Medical Center.', 'unread', '2026-10-02 11:32:15') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (180001, 30002, 'Blood Request Completed', 'Your request for 2 unit(s) of A+ has been completed. Get well soon!', 'unread', '2026-10-13 01:22:58') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (180002, 1272184, 'Donation Verified', 'Your donation of 1 unit(s) of A+ was verified. Thank you for saving lives!', 'unread', '2026-10-13 01:24:46') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (180003, 1272184, 'Donation Completed 🎉', 'Your blood donation of 1 unit(s) of A+ has been successfully completed! Thank you for saving lives. Your 56-day recovery cooldown is now active.', 'unread', '2026-10-13 01:26:58') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (180004, 5, 'Donation Verified', 'Your donation of 1 unit(s) of O+ was verified. Thank you for saving lives!', 'unread', '2026-10-13 01:31:20') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (180005, 60002, 'Donation Rejected', 'Your donation of 1 unit(s) of O- on 2026-10-03 was not approved.', 'unread', '2026-10-13 01:32:02') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (180006, 5, 'Donation Verified', 'Your donation of 1 unit(s) of O+ was verified. Thank you for saving lives!', 'unread', '2026-10-13 01:33:16') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (180007, 5, 'Donation Completed 🎉', 'Your blood donation of 1 unit(s) of O+ has been successfully completed! Thank you for saving lives. Your 56-day recovery cooldown is now active.', 'unread', '2026-10-13 01:33:44') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (210001, 120002, 'Blood Request Completed', 'Your request for 1 unit(s) of A+ has been completed. Get well soon!', 'unread', '2026-10-13 03:03:39') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (210002, 30002, 'Blood Request Approved', 'Your request for 1 unit(s) of O+ was approved by Cagayan Valley Medical Center. Blood will be released from Cagayan Valley Medical Center. Other hospitals in your broadcast have been automatically closed.', 'unread', '2026-10-13 03:03:59') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (210003, 30002, 'Blood Request Completed', 'Your request for 1 unit(s) of O+ has been completed. Get well soon!', 'unread', '2026-10-13 03:04:14') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (240001, 1, 'New Blood Request', 'Patient PAT-00001 requested 1 unit(s) of Whole Blood (A+) for Sheldon Bulan. Broadcasted to 1 hospital(s). Priority: Normal', 'unread', '2026-10-13 03:36:33') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (240002, 2, 'New Blood Request', 'Patient PAT-00001 requested 1 unit(s) of Whole Blood (A+) for Sheldon Bulan. Broadcasted to 1 hospital(s). Priority: Normal', 'read', '2026-10-13 03:36:33') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (240003, 3, 'New Blood Request', 'Patient PAT-00001 requested 1 unit(s) of Whole Blood (A+) for Sheldon Bulan. Broadcasted to 1 hospital(s). Priority: Normal', 'read', '2026-10-13 03:36:33') ON CONFLICT DO NOTHING;
INSERT INTO "notifications" ("id", "user_id", "title", "message", "status", "created_at") VALUES (270001, 30002, 'Blood Request Approved', 'Your request for 1 unit(s) of O+ was approved by San Mariano Blood Center. Blood will be released from San Mariano Blood Center. Other hospitals in your broadcast have been automatically closed.', 'unread', '2026-10-22 02:39:47') ON CONFLICT DO NOTHING;

-- ------------------------------------------------------------------
-- Table: activity_logs
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "activity_logs" (
    "id" SERIAL PRIMARY KEY,
    "user_id" INTEGER,
    "action" varchar(100) NOT NULL,
    "description" varchar(500) NOT NULL,
    "ip_address" varchar(45),
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Seed data for activity_logs (142 rows)
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (1, 30002, 'Register', 'sheldon registered as patient', NULL, '2026-09-30 04:03:55') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (2, 30002, 'OTP Sent', 'Verification code emailed', NULL, '2026-09-30 04:03:56') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (3, 30002, 'Email Verified', 'Email verification completed', NULL, '2026-09-30 04:04:30') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (4, 30002, 'Logout', 'sheldon logged out', NULL, '2026-09-30 04:04:43') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (5, 30002, 'Login', 'sheldon logged in', NULL, '2026-09-30 04:04:47') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (6, 30002, 'Login', 'sheldon logged in', NULL, '2026-09-30 04:08:19') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (7, 30002, 'Logout', 'sheldon logged out', NULL, '2026-09-30 04:08:26') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (8, 30002, 'Logout', 'sheldon logged out', NULL, '2026-09-30 04:08:26') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (9, 30002, 'Logout', 'sheldon logged out', NULL, '2026-09-30 04:08:26') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (10, 30002, 'Logout', 'sheldon logged out', NULL, '2026-09-30 04:08:26') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (11, 30002, 'Logout', 'sheldon logged out', NULL, '2026-09-30 04:08:26') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (12, 30002, 'Logout', 'sheldon logged out', NULL, '2026-09-30 04:08:26') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (2000001, 2, 'Login', 'admin logged in', NULL, '2026-09-30 04:15:20') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (2000002, 2, 'Logout', 'admin logged out', NULL, '2026-09-30 04:15:47') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (2000003, 3, 'Login', 'staff logged in', NULL, '2026-09-30 04:16:10') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (2000004, 3, 'Logout', 'staff logged out', NULL, '2026-09-30 04:16:24') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (2000005, 30002, 'Login', 'sheldon logged in', NULL, '2026-09-30 04:16:28') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (2000006, 30002, 'Logout', 'sheldon logged out', NULL, '2026-09-30 04:16:39') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (4000001, 30002, 'Login', 'sheldon logged in', NULL, '2026-09-30 04:36:21') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (6000001, 3, 'Login', 'staff logged in', NULL, '2026-09-30 05:02:57') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (6000002, 3, 'Login', 'staff logged in', NULL, '2026-09-30 05:02:58') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (6000003, 30002, 'Login', 'sheldon logged in', NULL, '2026-09-30 05:03:05') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (6000004, 30002, 'Logout', 'sheldon logged out', NULL, '2026-09-30 05:03:11') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (6000005, 3, 'Login', 'staff logged in', NULL, '2026-09-30 05:03:20') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (6000006, 3, 'Request Approved', 'Request #2: 1 unit(s) of O+ from Cagayan Valley Medical Center', NULL, '2026-09-30 05:03:50') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (6000007, 3, 'Logout', 'staff logged out', NULL, '2026-09-30 05:04:06') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (6000008, 30002, 'Login', 'sheldon logged in', NULL, '2026-09-30 05:04:13') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (8000001, 6, 'Password Reset', 'Reset code emailed', NULL, '2026-09-30 05:46:12') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (8000002, 6, 'Password Reset', 'Reset code emailed', NULL, '2026-09-30 05:50:06') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (8000003, 6, 'Password Reset', 'Reset code emailed', NULL, '2026-09-30 05:50:49') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (10000001, 2, 'Login', 'admin logged in', NULL, '2026-09-30 07:00:47') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (10000002, 2, 'Logout', 'admin logged out', NULL, '2026-09-30 07:02:23') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (10000003, 2, 'Logout', 'admin logged out', NULL, '2026-09-30 07:02:23') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (10000004, 3, 'Login', 'staff logged in', NULL, '2026-09-30 07:02:29') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (12000001, 3, 'Login', 'staff logged in', NULL, '2026-09-30 07:48:08') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (12000002, 3, 'Donation Rejected', 'Rejected donation #30001', NULL, '2026-09-30 07:49:42') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (12000003, 3, 'Request Approved', 'Request #30001: 1 unit(s) of O+ from Cagayan Valley Medical Center', NULL, '2026-09-30 07:53:03') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (12000004, 3, 'Request Rejected', 'Request #1 rejected', NULL, '2026-09-30 07:53:39') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (14000001, 3, 'Login', 'staff logged in', NULL, '2026-09-30 07:59:58') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (14000002, 3, 'Donation Verified', '1 unit(s) of O+ added to Cagayan Valley Medical Center', NULL, '2026-09-30 08:00:12') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (16000001, 3, 'Login', 'staff logged in', NULL, '2026-09-30 08:39:10') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (16000002, 3, 'Donation Verified', '1 unit(s) of AB+ added to Cagayan Valley Medical Center', NULL, '2026-09-30 08:39:38') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (16000003, 3, 'Donation Verified', '1 unit(s) of O+ added to Cagayan Valley Medical Center', NULL, '2026-09-30 08:39:42') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (16000004, 3, 'Donation Completed', 'Completed donation #90001 for donor #30001', NULL, '2026-09-30 08:39:46') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (18000001, 3, 'Login', 'staff logged in', NULL, '2026-09-30 11:01:50') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (18000002, 5, 'Login', 'donor logged in', NULL, '2026-09-30 11:09:39') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (18000003, 3, 'Login', 'staff logged in', NULL, '2026-09-30 11:09:40') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (18000004, 4, 'Login', 'patient logged in', NULL, '2026-09-30 11:09:42') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (18000005, 2, 'Login', 'admin logged in', NULL, '2026-09-30 11:09:42') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (20000001, 2, 'Login', 'admin logged in', NULL, '2026-09-30 13:02:04') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (20000002, 2, 'Login', 'admin logged in', NULL, '2026-09-30 13:02:31') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (20000003, 2, 'Request Approved', 'Request #90001: 1 unit(s) of A+ from Cagayan Valley Medical Center', NULL, '2026-09-30 13:02:51') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (22000001, 2, 'Login', 'admin logged in', NULL, '2026-10-01 12:15:50') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (24000001, 2, 'Request Approved', 'Request #120001: 2 unit(s) of A+ from Cagayan Valley Medical Center', NULL, '2026-10-02 11:32:15') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (24030001, 2, 'Login', 'admin logged in', NULL, '2026-10-04 00:22:24') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (26030001, 2, 'Login', 'admin logged in', NULL, '2026-10-12 07:05:42') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (28030001, 3, 'Login', 'staff logged in', NULL, '2026-10-12 07:48:58') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (30030001, 1, 'Login', 'superadmin logged in', NULL, '2026-10-12 12:28:26') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (30030002, 1, 'Logout', 'superadmin logged out', NULL, '2026-10-12 12:30:20') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (30030003, 3, 'Login', 'staff logged in', NULL, '2026-10-12 12:30:46') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (32030001, 3, 'Logout', 'staff logged out', NULL, '2026-10-12 12:58:22') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (32030002, 1, 'Login', 'superadmin logged in', NULL, '2026-10-12 13:02:24') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (34030001, 1, 'Login', 'superadmin logged in', NULL, '2026-10-12 14:23:13') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (34030002, 1, 'Logout', 'superadmin logged out', NULL, '2026-10-12 14:23:45') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (34030003, 3, 'Login', 'staff logged in', NULL, '2026-10-12 14:24:07') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (34030004, 3, 'Logout', 'staff logged out', NULL, '2026-10-12 14:27:53') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (34030005, 2, 'Login', 'admin logged in', NULL, '2026-10-12 14:28:06') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (34030006, 2, 'Logout', 'admin logged out', NULL, '2026-10-12 14:28:21') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (34030007, 1, 'Login', 'superadmin logged in', NULL, '2026-10-12 14:28:29') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (36030001, 1, 'Logout', 'superadmin logged out', NULL, '2026-10-12 14:39:41') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (36030002, 5, 'Login', 'donor logged in', NULL, '2026-10-12 14:40:10') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (36030003, 5, 'Logout', 'donor logged out', NULL, '2026-10-12 14:40:15') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (36030004, 30002, 'Login', 'sheldon logged in', NULL, '2026-10-12 14:41:31') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (36030005, 30002, 'Logout', 'sheldon logged out', NULL, '2026-10-12 14:41:36') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (36030006, 30002, 'Login', 'sheldon logged in', NULL, '2026-10-12 14:42:00') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (38030001, 3, 'Login', 'staff logged in', NULL, '2026-10-13 01:22:22') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (38030002, 3, 'Request Completed', 'Request #120001 completed for Sheldon Bulan (2 unit(s) of A+ deducted from hospital inventory)', NULL, '2026-10-13 01:22:58') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (38030003, 3, 'Donation Verified', '1 unit(s) of A+ added to San Mariano Blood Center', NULL, '2026-10-13 01:24:46') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (38030004, 3, 'Donation Completed', 'Completed donation #210001 for donor #120001', NULL, '2026-10-13 01:26:58') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (38030005, 3, 'Donation Verified', '1 unit(s) of O+ added to San Mariano Blood Center', NULL, '2026-10-13 01:31:20') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (38030006, 3, 'Donation Rejected', 'Rejected donation #180001', NULL, '2026-10-13 01:32:02') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (38030007, 3, 'Donation Verified', '1 unit(s) of O+ added to San Mariano Blood Center', NULL, '2026-10-13 01:33:16') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (38030008, 3, 'Donation Completed', 'Completed donation #300003 for donor #1', NULL, '2026-10-13 01:33:44') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (38030009, 3, 'Logout', 'staff logged out', NULL, '2026-10-13 01:38:27') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (38030010, 1, 'Login', 'superadmin logged in', NULL, '2026-10-13 01:38:41') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030001, 30002, 'Login', 'sheldon logged in', NULL, '2026-10-13 02:44:34') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030002, 30002, 'Logout', 'sheldon logged out', NULL, '2026-10-13 02:45:06') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030003, 2, 'Login', 'admin logged in', NULL, '2026-10-13 02:45:44') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030004, 2, 'Logout', 'admin logged out', NULL, '2026-10-13 02:46:01') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030005, 3, 'Login', 'staff logged in', NULL, '2026-10-13 02:46:38') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030006, 3, 'Logout', 'staff logged out', NULL, '2026-10-13 02:47:22') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030007, 30002, 'Login', 'sheldon logged in', NULL, '2026-10-13 02:48:12') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030008, 30002, 'Logout', 'sheldon logged out', NULL, '2026-10-13 02:49:38') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030009, 5, 'Login', 'donor logged in', NULL, '2026-10-13 02:51:35') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030010, 30002, 'Login', 'sheldon logged in', NULL, '2026-10-13 02:54:24') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030011, 30002, 'Login', 'sheldon logged in', NULL, '2026-10-13 02:55:56') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030012, 30002, 'Logout', 'sheldon logged out', NULL, '2026-10-13 02:56:09') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030013, 2, 'Login', 'admin logged in', NULL, '2026-10-13 02:59:25') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030014, 2, 'Logout', 'admin logged out', NULL, '2026-10-13 03:02:59') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030015, 1, 'Login', 'superadmin logged in', NULL, '2026-10-13 03:03:03') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030016, 1, 'Request Completed', 'Request #90001 completed for Philip Manzano (1 unit(s) of A+ deducted from hospital inventory)', NULL, '2026-10-13 03:03:39') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030017, 1, 'Request Approved', 'Request #150001: 1 unit(s) of O+ from Cagayan Valley Medical Center', NULL, '2026-10-13 03:03:59') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030018, 1, 'Request Completed', 'Request #150001 completed for Sheldon Bulan (1 unit(s) of O+ deducted from hospital inventory)', NULL, '2026-10-13 03:04:14') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (40030019, 1, 'Logout', 'superadmin logged out', NULL, '2026-10-13 03:06:16') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (42030001, 5, 'Login', 'donor logged in', NULL, '2026-10-13 03:28:58') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (42030002, 5, 'Logout', 'donor logged out', NULL, '2026-10-13 03:29:19') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (42030003, 30002, 'Login', 'sheldon logged in', NULL, '2026-10-13 03:29:40') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (42030004, 30002, 'Login', 'sheldon logged in', NULL, '2026-10-13 03:36:15') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (42030005, 30002, 'Request Submitted', 'PAT-00001 requested 1 unit(s) of Whole Blood (A+) for Sheldon Bulan (Sent to 1 hospital(s))', NULL, '2026-10-13 03:36:33') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (42030006, 30002, 'Logout', 'sheldon logged out', NULL, '2026-10-13 03:36:45') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (42030007, 5, 'Login', 'donor logged in', NULL, '2026-10-13 03:36:58') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (42030008, 5, 'Logout', 'donor logged out', NULL, '2026-10-13 03:37:53') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (44030001, 2, 'Login', 'admin logged in', NULL, '2026-10-13 03:59:36') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (44030002, 1, 'Login', 'superadmin logged in', NULL, '2026-10-13 04:03:19') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (46030001, 30002, 'Login', 'sheldon logged in', NULL, '2026-10-14 10:55:11') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (48030001, 30002, 'Login', 'sheldon logged in', NULL, '2026-10-14 12:21:05') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (48030002, 30002, 'Logout', 'sheldon logged out', NULL, '2026-10-14 12:24:10') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (48030003, 1332184, 'Register', 'donortest2026 registered as donor', NULL, '2026-10-14 12:28:48') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (48030004, 1332184, 'OTP Sent', 'Verification code emailed', NULL, '2026-10-14 12:28:49') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (48030005, 30002, 'Login', 'sheldon logged in', NULL, '2026-10-14 12:30:23') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (50030001, 3, 'Login', 'staff logged in', NULL, '2026-10-22 02:38:52') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (50030002, 3, 'Request Approved', 'Request #270001: 1 unit(s) of O+ from San Mariano Blood Center', NULL, '2026-10-22 02:39:47') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (52030001, 2, 'Login', 'admin logged in', NULL, '2026-10-22 03:29:08') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (54030001, 3, 'Login', 'staff logged in', NULL, '2026-10-30 13:26:40') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (56030001, 3, 'Login', 'staff logged in', NULL, '2026-10-30 13:58:01') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (58030001, 1, 'Login', 'superadmin logged in', NULL, '2026-10-31 04:23:20') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (58030002, 1, 'Logout', 'superadmin logged out', NULL, '2026-10-31 04:40:55') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (58030003, 2, 'Login', 'admin logged in', NULL, '2026-10-31 04:41:21') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (58030004, 2, 'Logout', 'admin logged out', NULL, '2026-10-31 04:44:59') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (58030005, 3, 'Login', 'staff logged in', NULL, '2026-10-31 04:45:05') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (58030006, 3, 'Login', 'staff logged in', NULL, '2026-10-31 04:46:47') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (60030001, 2, 'Login', 'admin logged in', NULL, '2026-11-03 12:32:01') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (60030002, 2, 'Login', 'admin logged in', NULL, '2026-11-03 12:32:41') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (60030003, 3, 'Login', 'staff logged in', NULL, '2026-11-03 12:33:12') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (62030001, 2, 'Login', 'admin logged in', NULL, '2026-11-04 05:50:54') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (64030001, 1, 'Login', 'superadmin logged in', NULL, '2026-11-07 02:41:59') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (64030002, 1, 'Login', 'superadmin logged in', NULL, '2026-11-07 02:44:23') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (66030001, 1, 'Login', 'superadmin logged in', NULL, '2026-11-07 02:51:22') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (66030002, 1, 'Login', 'superadmin logged in', NULL, '2026-11-07 02:53:33') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (68030001, 5, 'Login', 'donor logged in', NULL, '2026-11-07 03:31:40') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (68030002, 5, 'Login', 'donor logged in', NULL, '2026-11-07 03:33:46') ON CONFLICT DO NOTHING;
INSERT INTO "activity_logs" ("id", "user_id", "action", "description", "ip_address", "created_at") VALUES (68030003, 4, 'Login', 'patient logged in', NULL, '2026-11-07 03:34:37') ON CONFLICT DO NOTHING;

-- ------------------------------------------------------------------
-- Table: email_verification_otps
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "email_verification_otps" (
    "id" SERIAL PRIMARY KEY,
    "user_id" INTEGER NOT NULL,
    "email" varchar(160) NOT NULL,
    "otp_hash" TEXT NOT NULL,
    "expires_at" TIMESTAMP NOT NULL,
    "attempts" SMALLINT NOT NULL DEFAULT '0',
    "resend_count" SMALLINT NOT NULL DEFAULT '0',
    "last_sent_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "used_at" TIMESTAMP
);

-- Seed data for email_verification_otps (8 rows)
INSERT INTO "email_verification_otps" ("id", "user_id", "email", "otp_hash", "expires_at", "attempts", "resend_count", "last_sent_at", "created_at", "used_at") VALUES (1, 30002, 'sheldonbulan2@gmail.com', '39ff11896d9f6609de3cf659c203af70a368d6359a13578bb7f6d339749401f6', '2026-10-02 11:33:30', '0', '0', '2026-10-02 11:28:30', '2026-09-30 04:03:55', '2026-10-02 11:29:01') ON CONFLICT DO NOTHING;
INSERT INTO "email_verification_otps" ("id", "user_id", "email", "otp_hash", "expires_at", "attempts", "resend_count", "last_sent_at", "created_at", "used_at") VALUES (30001, 6, 'sheldonbulan88@gmail.com', '3b6ce0afc65f7c84fd2a383cc96b04f7eb818947b835c8185e0bce28ddff2a93', '2026-09-30 06:32:31', '0', '0', '2026-09-30 06:27:31', '2026-09-30 05:46:11', '2026-09-30 06:27:52') ON CONFLICT DO NOTHING;
INSERT INTO "email_verification_otps" ("id", "user_id", "email", "otp_hash", "expires_at", "attempts", "resend_count", "last_sent_at", "created_at", "used_at") VALUES (1000089, 60002, 'sheldonbulan88@gmail.com', '25e57e72ba6484fd167ed682ee10b4d977f72d905364ab4170283da743b0d12f', '2026-09-30 07:03:17', '0', '0', '2026-09-30 06:58:17', '2026-09-30 06:58:15', '2026-09-30 06:59:01') ON CONFLICT DO NOTHING;
INSERT INTO "email_verification_otps" ("id", "user_id", "email", "otp_hash", "expires_at", "attempts", "resend_count", "last_sent_at", "created_at", "used_at") VALUES (1163841, 120002, 'rachu44phil@gmail.com', 'f5884272c0cc994932512d88b6cb03fe3ca183c2736c2f16aafc906ce557da75', '2026-09-30 13:00:16', '0', '0', '2026-09-30 12:55:16', '2026-09-30 12:55:16', '2026-09-30 12:55:50') ON CONFLICT DO NOTHING;
INSERT INTO "email_verification_otps" ("id", "user_id", "email", "otp_hash", "expires_at", "attempts", "resend_count", "last_sent_at", "created_at", "used_at") VALUES (1223841, 1302184, 'philipmanzano27@gmail.com', 'f496e3dee00b9131dd4c1e559aed8a1d392a528d483f93954c16d32b1fe8df7b', '2026-10-13 01:38:43', '0', '0', '2026-10-13 01:33:43', '2026-10-13 01:33:43', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "email_verification_otps" ("id", "user_id", "email", "otp_hash", "expires_at", "attempts", "resend_count", "last_sent_at", "created_at", "used_at") VALUES (1253841, 1332184, 'testdonor2026@gmail.com', '8a427b1aa8cca1de9771cfec15fe1a37be30130d351220bbbe5d4d129914a31f', '2026-10-14 12:34:01', '0', '0', '2026-10-14 12:29:01', '2026-10-14 12:28:48', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "email_verification_otps" ("id", "user_id", "email", "otp_hash", "expires_at", "attempts", "resend_count", "last_sent_at", "created_at", "used_at") VALUES (1283841, 1362184, 'sheldonbulan1234@gmail.com', 'f3ac06176fd53bb23fdded928043ec305f72c46fb744ccb4df31923349d398ab', '2026-11-07 09:12:17', '0', '0', '2026-11-07 09:07:17', '2026-11-07 09:07:17', NULL) ON CONFLICT DO NOTHING;
INSERT INTO "email_verification_otps" ("id", "user_id", "email", "otp_hash", "expires_at", "attempts", "resend_count", "last_sent_at", "created_at", "used_at") VALUES (1313841, 1392184, 'manzanophilip745@gmail.com', '50624f4d84a70361b5fcd06841bde7d59c3fb8b1bb198244457bd7f9a9832cf6', '2026-11-08 00:32:06', '0', '0', '2026-11-08 00:27:06', '2026-11-08 00:27:06', NULL) ON CONFLICT DO NOTHING;

-- ------------------------------------------------------------------
-- Table: permissions
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "permissions" (
    "role" VARCHAR(100) NOT NULL,
    "module" varchar(40) NOT NULL,
    "can_access" BOOLEAN NOT NULL DEFAULT FALSE
);

-- Seed data for permissions (20 rows)
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('staff', 'donations', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('staff', 'donors', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('staff', 'inventory', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('staff', 'locations', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('staff', 'logs', '0') ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('staff', 'patients', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('staff', 'reports', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('staff', 'requests', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('staff', 'settings', '0') ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('staff', 'users', '0') ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('admin', 'donations', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('admin', 'donors', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('admin', 'inventory', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('admin', 'locations', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('admin', 'logs', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('admin', 'patients', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('admin', 'reports', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('admin', 'requests', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('admin', 'settings', 1) ON CONFLICT DO NOTHING;
INSERT INTO "permissions" ("role", "module", "can_access") VALUES ('admin', 'users', 1) ON CONFLICT DO NOTHING;

-- ------------------------------------------------------------------
-- Table: settings
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS "settings" (
    "setting_key" varchar(80) NOT NULL,
    "setting_value" varchar(255) NOT NULL
);

