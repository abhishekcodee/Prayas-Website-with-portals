# Multi-Format Report Export Engine
## PRAYAS – The Automation Academy | Industry Manpower Requirement Portal

### 1. Export Capabilities
The PRAYAS Reporting System provides multi-format export capabilities across all active report views:
- **CSV Export:** Standard RFC 4180 compliant comma-separated values with UTF-8 encoding.
- **Excel (.xls) Export:** Structured HTML spreadsheet with PRAYAS branding headers and styled table rows.
- **Print / PDF Export:** Print-optimized CSS rules with page breaks, hidden navigation, and legible typography.

### 2. Formula Injection Protection (CSV Injection Defense)
To prevent malicious spreadsheet formula execution (e.g., Dynamic Data Exchange or CMD injection), all cells exported through the system are sanitized via `ReportController::sanitizeExportCell()`:
```php
public static function sanitizeExportCell(mixed $value): string
{
    if ($value === null) return '';
    $str = (string)$value;
    if ($str === '') return '';

    $firstChar = $str[0];
    if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $str;
    }

    return $str;
}
```
Any cell value starting with `=`, `+`, `-`, `@`, `\t`, or `\r` is prepended with a single quote (`'`), ensuring spreadsheet software parses the content strictly as text literals.
