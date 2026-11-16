-- Migration 017: Allow multiple accommodation options in manpower_requirements
ALTER TABLE `manpower_requirements` 
MODIFY COLUMN `accommodation_provided` VARCHAR(255) DEFAULT 'No';
