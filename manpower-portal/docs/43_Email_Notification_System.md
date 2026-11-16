# 43 — Email Notification System & Delivery Architecture

## Document Identifier: SPEC-MAIL-2026-V1.0
**Project Title:** Industry Manpower Requirement Portal  
**Organization:** PRAYAS – The Automation Academy  
**Service Class:** [`NotificationService`](file:///c:/Manpower%20Portal/includes/notification_service.php)  
**Database Table:** `email_logs`  

---

## 1. Overview & Operational Principles

The **Email Notification System** provides responsive, branded, and auditable email communications to industrial employers, HR managers, and portal administrators.

### Core Operating Principles:
1. **Zero Silent Failures:** Every outbound email attempt is recorded in `email_logs` with delivery status (`QUEUED`, `SENT`, `FAILED`, or `SIMULATED`).
2. **Graceful Degradation:** If SMTP credentials are not configured or live mail dispatch is disabled (`mail_enabled=0`), the system automatically switches to simulation mode without interrupting user workflows or throwing fatal PHP exceptions.
3. **No Plaintext Passwords / PII:** No student Aadhaar numbers, personal phone numbers, or account passwords are ever injected into email bodies.
4. **Multipart Layouts:** Every notification generates both an accessible, mobile-optimized HTML layout and an RFC-compliant plain-text fallback.

---

## 2. Configuration & Platform Settings

Mail settings are stored securely in `system_settings` and managed by Super Admin via the administration panel:

| Setting Key | Default Value | Description |
| :--- | :--- | :--- |
| `mail_enabled` | `'0'` | Toggle email dispatch (`0` = In-App Only/Simulated, `1` = Live SMTP). |
| `mail_from_address` | `'noreply@prayas-academy.org'` | Default envelope from email address. |
| `mail_from_name` | `'PRAYAS – The Automation Academy'` | Organization display sender name. |
| `smtp_host` | `'localhost'` | Outbound SMTP server hostname. |
| `smtp_port` | `'1025'` | Outbound SMTP server port (e.g. 587, 465, 1025). |
| `smtp_secure` | `'tls'` | Transport security protocol (`tls`, `ssl`, or `none`). |

---

## 3. Supported Email Templates

All email templates incorporate official **PRAYAS – The Automation Academy** styling (Navy Blue `#0d2847`, Gold `#d4af37`, clean typography):

1. **`company_approval`:** Congratulatory notice to employer HR confirming official accreditation and inviting live requirement creation.
2. **`company_rejection`:** Constructive feedback detailing documentation non-compliance with evaluator rationale and re-submission instructions.
3. **`requirement_approval`:** Confirmation that technical manpower requisition was auto-approved and queued for candidate matching.
4. **`requirement_submission`:** Requisition receipt confirmation with review timeline.
5. **`requirement_correction`:** Bulleted summary of data defects requiring correction with direct deep link to the requisition wizard.
6. **`requirement_manual_review`:** Advisory notice explaining that high-volume or rush requisition is currently undergoing administrative review.

---

## 4. Email Delivery Audit Schema (`email_logs`)

```sql
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `recipient_email` VARCHAR(255) NOT NULL,
    `recipient_user_id` INT UNSIGNED NULL,
    `subject` VARCHAR(255) NOT NULL,
    `template_name` VARCHAR(100) NOT NULL,
    `body_html` MEDIUMTEXT NULL,
    `body_text` TEXT NULL,
    `delivery_status` ENUM('QUEUED', 'SENT', 'FAILED', 'SIMULATED') DEFAULT 'QUEUED',
    `error_message` TEXT NULL,
    `retry_count` INT UNSIGNED DEFAULT 0,
    `sent_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_email_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_email_recipient` (`recipient_email`),
    INDEX `idx_email_status` (`delivery_status`),
    INDEX `idx_email_template` (`template_name`),
    INDEX `idx_email_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

