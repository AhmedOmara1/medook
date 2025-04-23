-- SQL to add token column to appointments table
USE medook_db;
ALTER TABLE appointments ADD COLUMN token VARCHAR(64) DEFAULT NULL; 