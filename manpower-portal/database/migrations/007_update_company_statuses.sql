-- Migration 007: Update Company Verification Statuses
-- Database: manpower_portal_db
-- Organization: PRAYAS – The Automation Academy

ALTER TABLE `companies` 
    MODIFY COLUMN `verification_status` ENUM('Pending', 'Under Review', 'Verified', 'Active', 'Rejected', 'Suspended') 
    DEFAULT 'Pending';

