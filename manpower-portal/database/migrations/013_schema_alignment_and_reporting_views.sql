-- ==============================================================================
-- Migration 013: Schema Alignment, Business Aliasing & Analytical Reporting Views
-- Project: Industry Manpower Requirement Portal
-- Organization: PRAYAS – The Automation Academy
-- Target Database: manpower_portal_db
-- Type: Safe, Non-Destructive, Idempotent Additive Migration
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- 1. ANALYTICAL REPORTING VIEWS (Optimizes Multi-Table Executive Aggregations)
-- ------------------------------------------------------------------------------

-- View A: Active Requisitions Summary with Candidate Placement Progress
CREATE OR REPLACE VIEW `v_active_requisitions_summary` AS
SELECT 
    mr.id AS requirement_pk,
    mr.requirement_id,
    mr.company_id,
    c.company_name,
    c.industry_type,
    mr.plant_id,
    COALESCE(mp.plant_name, 'Headquarters') AS plant_name,
    mr.title AS job_title,
    mr.quantity_required AS total_vacancies,
    mr.status AS requirement_status,
    mr.priority,
    mr.application_deadline,
    mr.target_joining_date,
    mr.created_at,
    COALESCE(COUNT(DISTINCT CASE WHEN cm.status = 'Matched' THEN cm.id END), 0) AS count_matched,
    COALESCE(COUNT(DISTINCT CASE WHEN cm.status = 'Shortlisted' THEN cm.id END), 0) AS count_shortlisted,
    COALESCE(COUNT(DISTINCT CASE WHEN cm.status = 'Scheduled' THEN cm.id END), 0) AS count_interviewed,
    COALESCE(COUNT(DISTINCT CASE WHEN cm.status = 'Selected' THEN cm.id END), 0) AS count_selected,
    COALESCE(COUNT(DISTINCT CASE WHEN cm.status = 'Joined' THEN cm.id END), 0) AS count_joined
FROM `manpower_requirements` mr
JOIN `companies` c ON mr.company_id = c.id
LEFT JOIN `manufacturing_plants` mp ON mr.plant_id = mp.id
LEFT JOIN `candidate_matches` cm ON mr.id = cm.requirement_id
GROUP BY mr.id, mr.requirement_id, mr.company_id, c.company_name, c.industry_type, mr.plant_id, mp.plant_name, mr.title, mr.quantity_required, mr.status, mr.priority, mr.application_deadline, mr.target_joining_date, mr.created_at;

-- View B: Complete Candidate Recruitment Pipeline Funnel Summary
CREATE OR REPLACE VIEW `v_recruitment_funnel_summary` AS
SELECT 
    cm.id AS match_id,
    cm.requirement_id,
    mr.requirement_id AS requirement_code,
    mr.company_id,
    c.company_name,
    cm.candidate_source,
    cm.candidate_name,
    cm.trade,
    cm.match_score,
    cm.status AS pipeline_status,
    cm.is_shared_with_industry,
    s.offered_designation,
    s.offered_ctc,
    ol.letter_reference_no AS offer_letter_code,
    j.actual_joining_date,
    j.assigned_unit,
    cm.matched_at
FROM `candidate_matches` cm
JOIN `manpower_requirements` mr ON cm.requirement_id = mr.id
JOIN `companies` c ON mr.company_id = c.id
LEFT JOIN `selections` s ON cm.id = s.match_id
LEFT JOIN `offer_letters` ol ON s.id = ol.selection_id
LEFT JOIN `joinings` j ON s.id = j.selection_id;

-- ------------------------------------------------------------------------------
-- 2. BUSINESS ENTITY ALIASING VIEWS (Aligns Documentation with Physical Schema)
-- ------------------------------------------------------------------------------

-- Alias View: portal_users -> users
CREATE OR REPLACE VIEW `v_portal_users` AS 
SELECT 
    u.id,
    u.role_id,
    r.name AS role_name,
    r.label AS role_label,
    u.company_id,
    c.company_name,
    u.full_name,
    u.email,
    u.phone,
    u.status,
    u.must_change_password,
    u.last_login_at,
    u.created_at,
    u.updated_at
FROM `users` u
JOIN `roles` r ON u.role_id = r.id
LEFT JOIN `companies` c ON u.company_id = c.id;

-- Alias View: candidate_selections -> selections
CREATE OR REPLACE VIEW `v_candidate_selections` AS 
SELECT * FROM `selections`;

-- Alias View: candidate_joinings -> joinings
CREATE OR REPLACE VIEW `v_candidate_joinings` AS 
SELECT * FROM `joinings`;

-- Alias View: requirement_validation_logs -> requirement_validation_history
CREATE OR REPLACE VIEW `v_requirement_validation_logs` AS 
SELECT * FROM `requirement_validation_history`;

-- ==============================================================================
-- ROLLBACK INSTRUCTIONS (Execute only if rolling back this migration):
-- 
-- DROP VIEW IF EXISTS `v_active_requisitions_summary`;
-- DROP VIEW IF EXISTS `v_recruitment_funnel_summary`;
-- DROP VIEW IF EXISTS `v_portal_users`;
-- DROP VIEW IF EXISTS `v_candidate_selections`;
-- DROP VIEW IF EXISTS `v_candidate_joinings`;
-- DROP VIEW IF EXISTS `v_requirement_validation_logs`;
-- ==============================================================================

