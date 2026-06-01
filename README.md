# 📊 Project M — Money Management

[![Website](https://img.shields.io/badge/Website-Live-success.svg?style=for-the-badge&logo=google-chrome)](http://moneymgmt.is-best.net/)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777bb4.svg?style=for-the-badge&logo=php)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-MySQL-4479A1.svg?style=for-the-badge&logo=mysql)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg?style=for-the-badge)](#)
[![Security](https://img.shields.io/badge/Security-Prepared%20Statements-success.svg?style=for-the-badge&logo=securityscorecard)](https://owasp.org/)
[![LinkedIn](https://img.shields.io/badge/LinkedIn-Nayan-blue.svg?style=for-the-badge&logo=linkedin)](https://linkedin.com/in/itznayan)

**Project M** is a premium, proprietary personal finance command center designed to help users track expenses, set savings goals, and master their financial life with elegance. Featuring a custom glassmorphism user interface, the application delivers rich real-time analytics and detailed tracking metrics without compromising on security or performance.

🔗 **Live Website:** [moneymgmt.is-best.net](http://moneymgmt.is-best.net/)


---

## ✨ Key Modules & Features

### 📈 1. Interactive Dashboard
*   **Real-time Analytics:** Visual representation of monthly spending and savings progression using Chart.js.
*   **Quick Insights:** Get instant summaries of total expenses, savings balances, and budget alerts at a single glance.

### 💸 2. Expense & Budget Tracking
*   **Custom Categories:** Create and customize spending sections with absolute freedom.
*   **Target Budgets:** Set monthly limits per category and track utilization.
*   **Data Flex:** Support for custom metadata fields and fields on-demand.
*   **Data Portability:** Seamless JSON backup imports and exports.

### 🎯 3. Savings Goals
*   **Progress Visualization:** Watch progress bars fill up as you deposit money toward custom goals.
*   **Ledger History:** Detailed log of all deposits, withdrawals, and updates.
*   **Target Deadlines:** Keep track of critical dates and milestones.

### 🔒 4. Enterprise-Grade Security
*   **Prepared Statements:** Powered by PDO to fully eliminate SQL Injection risks.
*   **Cryptographic Passwords:** Industry-standard `bcrypt` hashing with timing-safe verification.
*   **CSRF Protection:** Tokens securing all forms and API endpoints.
*   **Session Management:** Auto-timeout, session fixation prevention, and secure cookie properties.

### 🛡️ 5. Email Security Shield
*   **100% Real-Time Dual-Layer Validation:** Blocks temporary/disposable burner emails (Yopmail, Mailinator, etc.) during user and admin registration on both front-end (ES6 Live Fetch) and back-end (PHP Guard) layers.
*   **Dual-Source Live Check:** Direct live browser checking via Kickbox Open API with automatic fallback to live GitHub raw community blocklist (30,000+ domains) with zero browser caching.
*   **DNS Self-Healing Checker:** Server-side MX record validation with a self-healing check (tests `gmail.com` MX lookup dynamically) to guarantee zero false-positives for genuine users under restricted hosting providers (e.g. InfinityFree).
*   **Security Event Logs:** Captures and logs intruder IP addresses, User-Agents, and bypass attempts in real-time.
*   **Admin Shield Hub:** Seamless tab integrated into the Admin Desktop Sidebar & Mobile Hamburger Menu featuring total blocked count metrics, last sync status, an instant manual `[Sync Live Blocklist]` action button, and a live domain search lookup tool.

---

## 🛠️ Tech Stack

*   **Backend:** PHP 8.0+
*   **Database:** MySQL (MariaDB)
*   **Frontend UI:** Vanilla CSS (Glassmorphism design system) & JavaScript (ES6)
*   **APIs & Data:** Kickbox Open API & GitHub Raw Disposable Email DB
*   **Visualization:** Chart.js, FontAwesome Icons & SweetAlert2

---

## 📂 Project Structure (High Level)

```text
├── admin/          # Admin administration dashboard & API handlers
├── api/            # API endpoints for data operations
├── assets/         # CSS styles, JS assets (including email_validator.js), and images
├── auth/           # Login, registration, and session scripts
├── dashboard/      # User dashboard view and analytics
├── database/       # SQL schemas and setup files
├── includes/       # Core functions (is_disposable_email), DB connections, and blocklist
└── index.php       # Main application landing page
```

---

## ✉️ Developer & Support

For inquiries or professional updates:

[![LinkedIn](https://img.shields.io/badge/LinkedIn-itznayan-blue?style=for-the-badge&logo=linkedin)](https://linkedin.com/in/itznayan)

---

Last Updated June 2026  
Made With Love ❤️  
**PROJECT M**  
*MONEY MANAGEMENT SYSTEM*  


