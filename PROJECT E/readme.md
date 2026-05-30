# PROJECT E | Advanced Dynamic Expense Management System

![Version](https://img.shields.io/badge/version-2.1.0-blue?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-00000F?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![License: MIT](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)

**PROJECT E** is a modern, full-stack financial tracking application built for individuals, freelancers, and small teams who demand complete control and flexibility over their daily expenses.

Unlike traditional, rigid tracking apps that force you into predefined workflows, PROJECT E introduces a highly flexible, **dynamic data architecture**. It acts as a "no-code" financial journal, empowering users to create infinite custom expense categories and inject dynamic data fields (like text, secondary currencies, dates, and times) on the fly — without ever touching the database schema.

Wrapped in a premium, dark-themed Glassmorphism interface and built with a privacy-first approach, PROJECT E ensures that your sensitive financial data remains 100% yours, free from third-party trackers.

---

## What's New in Version 2.1.0

### Security Overhaul (20+ Fixes)
* **HMAC-SHA256 ID Encoding** — Replaced XOR-based obfuscation with tamper-proof HMAC signing.
* **File + Database Rate Limiting** — Bypass-proof rate limiting with automatic DB fallback for shared hosting.
* **OTP Security** — 6-digit OTP with 2-minute expiry, 3-attempt limit, per-user cleanup, one-time use.
* **Session Regeneration** — Session ID rotated on every password change to prevent fixation attacks.
* **CSRF Protection** — All state-changing endpoints require CSRF tokens via headers.
* **Password Auto-Upgrade** — Legacy plaintext passwords automatically upgraded to bcrypt on login.
* **Account Deletion** — Password confirmation required before self-deletion.
* **Input Sanitization** — Non-UTF-8 encoding detection, recursive custom data sanitization.
* **XSS Prevention** — Centralized `escapeHtml()` with consistent output escaping.
* **SQL Injection Prevention** — All queries use PDO prepared statements.
* **Export Encryption** — AES-256 CryptoJS encryption for data exports (password-protected `.encrypted` files).

### UI/UX Improvements
* **User Notes** — Add, view, and edit section-specific notes (up to 1000 characters) directly from the dashboard. Notes are securely stored and integrated into the encrypted backup exports.
* **Refresh Button** — Animated spin icon on both user and admin dashboards. Mobile: inline with section name. Desktop: in controls row.
* **Section Actions** — Grouped edit/delete buttons with admin-style rounded-square icons.
* **Add Record Form** — Styled inputs matching the glassmorphic theme, 12-hour AM/PM time picker.
* **Change Password Modal** — Custom styled inputs with 8-character minimum validation.
* **Rename Section Popup** — Compact 380px width with themed inputs.
* **Admin Controls Row** — Unified month filter, sort dropdown, and refresh button below topbar.

### Infrastructure
* **InfinityFree Compatible** — Auto-generating APP_SECRET, DB charset fallback (utf8mb4 to utf8), mb_* function guards.
* **Google Apps Script Email** — OTP delivery via Google Apps Script with timeout handling and error recovery.
* **CI/CD Pipeline** — GitHub Actions auto-deploy to InfinityFree via FTP with secrets injection.
* **OTP Countdown Timer** — Real-time countdown showing OTP expiry with auto-close.

---

## Core Features

| Feature | Description |
|---------|-------------|
| **Dual Role-Based Portals** | Secure, isolated environments for Members (expense tracking) and Admins (platform oversight). |
| **Infinite Customization** | JSON-powered schema system allows users to create unlimited custom fields without code modifications. |
| **Real-Time Analytics** | Live budget tracking, expenditure calculations, and visual health indicators. |
| **Global Accessibility** | Support for 160+ currencies and instant UI translation via Google Translate API. |
| **Premium UI/UX** | Modern, responsive dark-themed frosted glass interface with interactive cursor-glow effects. |
| **Privacy & Security** | 15-minute auto-logout, IRCTC-style back-button logout, session management, bcrypt hashing. |
| **Data Export/Import** | AES-256 encrypted exports and JSON-based imports with full custom field support. |
| **OTP Password Reset** | Email-based OTP with 2-minute expiry, rate limiting, and one-time use. |

---

## Core Innovation: Dynamic JSON Schema

PROJECT E eliminates rigid database limitations through its dynamic field system:
1. **Frontend Creation:** Users define custom fields (text, currency, date, number, time) via an intuitive UI.
2. **Serialization:** JavaScript captures field metadata and input types.
3. **Backend Storage:** PHP securely stores data in a `JSON` column within the MySQL `expenses` table.
4. **Dynamic Rendering:** Upon retrieval, the frontend automatically reconstructs table headers and populates custom data accordingly.

---

## Technology Stack

* **Frontend:** HTML5, Modern CSS (Glassmorphism, Animations), Vanilla JavaScript.
* **Backend:** PHP 8+ (PDO for maximum database security).
* **Database:** MySQL / MariaDB (Relational + JSON column structures).
* **UI Libraries:** SweetAlert2, FontAwesome Icons, Flatpickr.
* **External APIs:** Google Translate API, Google Apps Script API (OTP).
* **Hosting:** InfinityFree (free hosting) with GitHub Actions CI/CD.

---

## Project Structure

```
PROJECT E/
├── index.php                        # Landing page & Portal selection
├── about.php                        # 'How To Use' documentation guide
├── 404.php                          # Custom 404 error page
├── .htaccess                        # URL rewriting & security headers
│
├── user/
│   ├── login.php                    # User login page
│   ├── register.php                 # User registration page
│   └── dashboard.php                # User expense tracking dashboard
│
├── admin/
│   ├── login.php                    # Admin login page
│   └── dashboard.php                # Admin monitoring panel
│
├── includes/
│   ├── config.php                   # App configuration & secrets loading
│   ├── db.php                       # PDO database connection (utf8mb4 fallback)
│   ├── security.php                 # Auth, rate limiting, HMAC, email, sanitization
│   ├── router.php                   # API request routing & CSRF verification
│   ├── header.php                   # Shared HTML head, meta tags, scripts
│   ├── footer.php                   # Shared footer
│   ├── .htaccess                    # Blocks direct access to handlers
│   └── handlers/
│       ├── auth_handlers.php        # Login, register, OTP, password reset
│       ├── record_handlers.php      # CRUD records, import/export
│       ├── category_handlers.php    # Section/category management
│       └── admin_handlers.php       # Admin stats, user management
│
├── assets/
│   ├── css/
│   │   ├── global.css               # Base styles, variables, resets
│   │   ├── user_dashboard.css       # User dashboard styles
│   │   ├── admin_dashboard.css      # Admin dashboard styles
│   │   ├── landing.css              # Landing page styles
│   │   └── auth.css                 # Login/register styles
│   └── js/
│       ├── input_validation.js      # Shared utilities (escapeHtml, validation)
│       └── forgot_password.js       # OTP reset flow with countdown timer
│
├── .github/
│   └── workflows/
│       └── main.yml                 # GitHub Actions CI/CD to InfinityFree
│
├── .gitignore                       # Excludes .sql, secrets, .env
└── database.sql                     # Full DB schema + seed data (not deployed)
```

---

## Security Architecture

| Layer | Implementation |
|-------|----------------|
| **Authentication** | bcrypt password hashing, session-based auth, auto-logout |
| **Authorization** | Role-based access (user/admin), session validation on every request |
| **CSRF** | Random tokens via `random_bytes(32)`, verified with `hash_equals()` |
| **XSS** | `escapeHtml()` on all output, `sanitize_input()` on all input |
| **SQL Injection** | PDO prepared statements exclusively, no raw queries |
| **Rate Limiting** | File-based with DB fallback, per-action + per-IP tracking |
| **Session Security** | httponly, samesite=Lax, secure flag on HTTPS, regeneration on password change |
| **ID Obfuscation** | HMAC-SHA256 signed tokens, tamper detection with 404 redirect |
| **OTP Security** | 6-digit, 2-min expiry, 3-attempt limit, one-time use, per-user cleanup |
| **Data Export** | AES-256 encryption, password-protected `.encrypted` files |
| **Headers** | CSP, X-Frame-Options DENY, X-Content-Type-Options, Referrer-Policy |

---

## Deployment (InfinityFree)

### Prerequisites
1. InfinityFree hosting account
2. MySQL database created
3. GitHub repository with the code

### GitHub Secrets Required
| Secret | Description |
|--------|-------------|
| `DB_HOST` | InfinityFree MySQL hostname |
| `DB_NAME` | Database name |
| `DB_USER` | Database username |
| `DB_PASS` | Database password |
| `APP_SECRET` | 64-character hex string for HMAC/encryption |
| `FTP_HOST` | InfinityFree FTP hostname |
| `FTP_USERNAME` | FTP username |
| `FTP_PASSWORD` | FTP password |


## Local Development

```bash
# Clone the repository
git clone https://github.com/anankush/project-e.git

# Place in XAMPP htdocs
# Create MySQL database 'expense_management'

# Set APP_SECRET (auto-generates on first run)
# Or set as environment variable

# Access via browser
https://expensemgmt.is-best.net

```

---

## Future Enhancements

* [ ] Multi-language native UI localization (replacing API-based translation)
* [ ] Export reports to PDF/CSV formats
* [ ] Recurring expense automation templates
* [ ] Graphical data visualization (Charts.js)
* [ ] Two-factor authentication (2FA)
* [ ] Multi-user team collaboration

---

## License & Support

This project is open-source and licensed under the **MIT License**. All rights reserved by **NAYAN**, the sole developer of PROJECT E.

**Support:** For issues, feature requests, or troubleshooting, please contact: support.nayan@gmail.com

---

Last Updated: May 2026
