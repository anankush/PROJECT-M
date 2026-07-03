# 📊 Project M — Money Management

[![Website](https://img.shields.io/badge/Website-Live-success.svg?style=for-the-badge&logo=google-chrome)](http://moneymgmt.is-best.net/) &nbsp; [![Version](https://img.shields.io/badge/Version-2.3.0-blue.svg?style=for-the-badge)](#) &nbsp; [![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777bb4.svg?style=for-the-badge&logo=php)](https://www.php.net/) &nbsp; [![Database](https://img.shields.io/badge/Database-MySQL-4479A1.svg?style=for-the-badge&logo=mysql)](https://www.mysql.com/) &nbsp; [![License](https://img.shields.io/badge/License-Proprietary-red.svg?style=for-the-badge)](#) &nbsp; [![Security](https://img.shields.io/badge/Security-Prepared%20Statements-success.svg?style=for-the-badge&logo=securityscorecard)](https://owasp.org/)

[![LinkedIn](https://img.shields.io/badge/LinkedIn-Nayan-blue.svg?style=for-the-badge&logo=linkedin)](https://linkedin.com/in/itznayan) &nbsp;&nbsp;&nbsp;&nbsp; [![Pentested By](https://img.shields.io/badge/Pentested%20By-0xLum3n-black.svg?style=for-the-badge&logo=github)](https://github.com/0xLum3n)

**Project M** is a premium, proprietary personal finance command center designed to help users track expenses, set savings goals, and master their financial life with elegance. Featuring a custom glassmorphism user interface, the application delivers rich real-time analytics and detailed tracking metrics without compromising on security or performance.

🔗 **Live Website:** [moneymgmt.is-best.net](http://moneymgmt.is-best.net/)

---

## ✨ Key Modules & Features

### 📈 1. Interactive Dashboard
*   **Real-time Analytics:** Visual representation of monthly spending and savings progression using Chart.js.
*   **Quick Insights:** Get instant summaries of total expenses, savings balances, net worth, and budget alerts.

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
*   **IDOR Prevention:** Cryptographic SHA-256 HMAC signature-verified ID tokens to prevent unauthorized parameter tampering.

### 🛡️ 5. Email Security Shield
*   **100% Real-Time Dual-Layer Validation:** Blocks temporary/disposable burner emails (Yopmail, Mailinator, etc.) during registration.
*   **Dual-Source Live Check:** Direct live browser checking via Kickbox Open API with automatic fallback to live GitHub raw community blocklist (30,000+ domains) with zero browser caching.
*   **DNS Self-Healing Checker:** Server-side MX record validation with a self-healing check to guarantee zero false-positives under restricted hosting environments.
*   **Security Event Logs:** Captures and logs intruder IP addresses, User-Agents, and bypass attempts in real-time.

### 🔔 6. Pure PHP Web Push Notifications (v2.2.0)
*   **Zero-Dependency Push Encryption:** Built using native PHP OpenSSL methods (ECDH key exchange, HKDF key derivation, and AES-128-GCM payload encryption) to deliver secure push notifications without any third-party library overhead.
*   **Granular Preferences:** Real-time settings modal toggles to enable or disable alerts for budget warnings (80%), budget limits exceeded (100%), savings goal milestones, account logins, and automatic monthly summaries.

### 🛑 7. Real-Time Admin Blocking & Cascading Deletion (v2.3.0)
*   **Instant Session Invalidation:** Dynamic session checking checks the user's status (`status = 'blocked'`) in real-time on every page load or automatic session ping, instantly logging them out.
*   **Transaction-Safe Cascade Deletion:** Administrators can permanently delete a user and clear all associated records (budgets, expenses, savings transactions, notes, push subscriptions, security logs, and resets) inside an error-resilient database transaction.
*   **Account Deletion Notice:** Instantly redirecting active sessions of deleted accounts to a security-flagged termination page with a clear warning: *"Suspicious account behaviour administrator deleted your account."*

### 📬 8. Automated Monthly Summary Reports (v2.4.0)
*   **Detailed Financial Digests:** Automatically compiles previous month's category-wise expenses (Outflow Breakdown) and goals-wise savings (Stored Goals Activity) into structured tables.
*   **Security & Anti-Spam Shield:** Completely link-free HTML email template using generic/statistical terminology (Outflow, Stored, Difference) to bypass mail service spam filters and deliver directly to the user's Inbox.
*   **Secure Virtual Trigger:** Triggered monthly on the 1st day at 6:00 AM IST via a headless Puppeteer browser running in GitHub Actions, verified by a custom HTTP header (`X-Cron-Secret`) to prevent URL parameter backdoors.

### 🤖 9. Znoda AI Assistant (Gemini AI Chatbot)
*   **High-Availability Failover Queue:** Dynamic traffic-balancing API pool utilizing multiple Google Gemini and Gemma models (Gemini 3.1 Flash Lite, Gemini 2.5 Flash, Gemma 4, etc.) with automatic failovers.
*   **Context-Grounded Financial Advice:** Performs step-by-step mathematical calculations (e.g. 50/30/20 rule, compound interest, emergency funds) to advise users based on shared details.
*   **Strict Security Boundaries:** Zero internal database access to protect sensitive account tables; restricts all technical disclosures about underlying PHP/SQL structures.

### 🛠️ 10. Admin Portal Command Center
*   **System Stats:** Real-time metrics for total/active users, total budget, spent, saved, and login attempts.
*   **User Directory:** View registration details, activity status, category usage, with quick Block/Unblock and Delete actions.
*   **Email Shield Hub:** Synced blocklist status, manual live update trigger, and dynamic search/lookup for domains.
*   **Security Audit Logs:** Live database logs capturing actions, IP addresses, and User-Agents for the last 30 days.

---

## 🛠️ Tech Stack

*   **Backend:** PHP 8.0+
*   **Database:** MySQL (MariaDB)
*   **Frontend UI:** Vanilla CSS (Glassmorphism design system) & JavaScript (ES6)
*   **APIs & Data:** Kickbox Open API & GitHub Raw Disposable Email DB
*   **AI Models:** Google Gemini & Gemma APIs
*   **Visualization:** Chart.js, FontAwesome Icons & SweetAlert2

---

## 📂 Project Structure (High Level)

```text
├── admin/          # Admin administration dashboard & API handlers
├── api/            # API endpoints for data operations
├── assets/         # CSS styles, JS assets, and images
├── auth/           # Login, registration, and session scripts
├── dashboard/      # User dashboard view and analytics
├── database/       # SQL schemas and setup files
├── includes/       # Core functions, DB connections, and blocklist
└── index.php       # Main application landing page
```

---

## ✉️ Developer, Support & Security Audits

For inquiries, professional updates, or security reports:

### 👨‍💻 Developer & Support
For networking, collaboration, or suggestions:

[![LinkedIn](https://img.shields.io/badge/LinkedIn-itznayan-blue.svg?style=for-the-badge&logo=linkedin)](https://linkedin.com/in/itznayan)

<br/>

### 🛡️ Security Audit & Pentesting
The security architecture of **Project M** has been independently audited, pentested, and hardened against vulnerabilities (including SQL Injection, Cross-Site Request Forgery, Cross-Site Scripting, Session Hijacking, Concurrent Logins, and Disposable Email Bypasses) by:

[![Pentested By](https://img.shields.io/badge/Pentested%20By-0xLum3n-black.svg?style=for-the-badge&logo=github)](https://github.com/0xLum3n)

---

Last Updated July 2026  
Made With Love ❤️  
**PROJECT M**  
*MONEY MANAGEMENT SYSTEM*  
