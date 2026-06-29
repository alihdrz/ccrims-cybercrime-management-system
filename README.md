# 🛡️ CCRIMS - Cyber Crime Reporting and Investigation Management System

A centralized, database-driven web application designed to handle the full lifecycle of cybercrime investigations. **CCRIMS** streamlines online complaint filing for citizens, provides case tracking for investigation officers, establishes a digital chain of custody with file-hash integrity for forensic experts, and ensures legal overhead with warrant tracking.

Developed as the final semester project for the **Database Systems** course at **Air University (Aerospace and Aviation Campus, Kamra)**.

---

## 🚀 Key Features

- **Role-Based Access Control (RBAC):** Session-driven navigation and role-aware dashboards engineered for 4 distinct user groups: **Citizen, Investigation Officer, Forensic Expert, and Admin**.
- **Tamper-Evident Evidence Management:** Supports digital evidence registry with **SHA-256 hash-based verification** to guarantee data integrity and an immutable chain of custody.
- **Structured Forensic Reporting:** Enforces an absolute **1:1 relationship** between official case activity logs and expert forensic analysis entries.
- **Legal Warrant Auditing:** Integrates structured management for tracking **Search Warrants, Arrest Warrants, Data Disclosure Orders, and Account Freezes** alongside court info and expiry dates.
- **Realistic Data Benchmarking:** Pre-populated with **20+ sample records per table** representing localized, realistic Pakistani cybercrime test scenarios.

---

## 📊 Database Architecture & Normalization

The underlying relational database contains **13 well-structured tables**. The architecture was built through a rigorous normalization pipeline transitioning from a flat file Unnormalized Form (UNF) through 1NF, 2NF, and 3NF, ultimately conforming to **Boyce-Codd Normal Form (BCNF)** to eradicate data redundancy and preserve referential integrity.

### Core Schema Highlights:
- **UUID Keys:** Employs `CHAR(36)` auto-generated UUID primary keys across entities for secure, decentralized indexing.
- **Cascade Mechanisms:** Implements `ON DELETE CASCADE` constraints on weak entities (such as `CASE_RECORD`) to maintain absolute data relational consistency.
- **Candidate Key Constraints:** Restricts critical identifiers like `Email`, `NationalID`, `BadgeNumber`, and `FileHash` with `UNIQUE` keys.

### Key Table Definitions Mapping:
- `CITIZEN` / `STAFF`: Account registries containing localized attributes and secure password mapping.
- `COMPLAINT` / `CASE`: The direct pipeline connecting a citizen-filed incident report to an active, prioritized law enforcement task.
- `DIGITAL_EVIDENCE`: Holds critical parameters including `FileName`, `FileHash`, `FileSize`, and full `ChainOfCustody` narratives.
- `FORENSIC_REPORT`: Logs analytic expert findings, `ToolsUsed`, and research methodologies.
- `WARRANT`: Tracks structural courtroom mandates issued to active investigations.

---

## 🛠️ Technology Stack

The application is built as a lightweight, performant web suite deployed locally using a LAMP framework:

- **Frontend:** HTML5, CSS3, Tailwind CSS, JavaScript
- **Backend:** PHP 8.2 (Leveraging secure PDO connections for query execution)
- **Database Backend:** MySQL / MariaDB 10.4
- **Server Ecosystem:** Apache 2.4 configured via XAMPP
- **DB Operations Interface:** phpMyAdmin

---

## ⚙️ Installation & Local Setup

To deploy and review the platform locally using XAMPP:

1. **Clone the Repository:**
   ```bash
   git clone [https://github.com/alihdrz/ccrims-cybercrime-management-system.git](https://github.com/alihdrz/ccrims-cybercrime-management-system.git)
