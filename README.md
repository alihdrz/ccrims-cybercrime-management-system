# 🛡️ CCRIMS - Cyber Crime Reporting and Investigation Management System

[cite_start]A centralized, database-driven web application designed to handle the full lifecycle of cybercrime investigations[cite: 8, 101]. [cite_start]**CCRIMS** streamlines online complaint filing for citizens, provides case tracking for investigation officers, establishes a digital chain of custody with file-hash integrity for forensic experts, and ensures legal overhead with warrant tracking[cite: 9, 101, 104].

[cite_start]Developed as the final semester project for the **Database Systems** course at **Air University (Aerospace and Aviation Campus, Kamra)**[cite: 1, 8].

---

## 🚀 Key Features

- [cite_start]**Role-Based Access Control (RBAC):** Session-driven navigation and role-aware dashboards engineered for 4 distinct user groups: **Citizen, Investigation Officer, Forensic Expert, and Admin**[cite: 11, 96].
- [cite_start]**Tamper-Evident Evidence Management:** Supports digital evidence registry with **SHA-256 hash-based verification** to guarantee data integrity and an immutable chain of custody[cite: 19, 26].
- [cite_start]**Structured Forensic Reporting:** Enforces an absolute **1:1 relationship** between official case activity logs and expert forensic analysis entries[cite: 29, 48].
- [cite_start]**Legal Warrant Auditing:** Integrates structured management for tracking **Search Warrants, Arrest Warrants, Data Disclosure Orders, and Account Freezes** alongside court info and expiry dates[cite: 31, 32].
- [cite_start]**Realistic Data Benchmarking:** Pre-populated with **20+ sample records per table** representing localized, realistic Pakistani cybercrime test scenarios[cite: 104].

---

## 📊 Database Architecture & Normalization

[cite_start]The underlying relational database contains **13 well-structured tables**[cite: 10, 100]. [cite_start]The architecture was built through a rigorous normalization pipeline transitioning from a flat file Unnormalized Form (UNF) through 1NF, 2NF, and 3NF, ultimately conforming to **Boyce-Codd Normal Form (BCNF)** to eradicate data redundancy and preserve referential integrity[cite: 10, 51].

### Core Schema Highlights:
- [cite_start]**UUID Keys:** Employs `CHAR(36)` auto-generated UUID primary keys across entities for secure, decentralized indexing[cite: 86, 88].
- [cite_start]**Cascade Mechanisms:** Implements `ON DELETE CASCADE` constraints on weak entities (such as `CASE_RECORD`) to maintain absolute data relational consistency[cite: 43, 45, 86].
- [cite_start]**Candidate Key Constraints:** Restricts critical identifiers like `Email`, `NationalID`, `BadgeNumber`, and `FileHash` with `UNIQUE` keys[cite: 63, 68, 70, 71].

### Key Table Definitions Mapping:
- [cite_start]`CITIZEN` / `STAFF`: Account registries containing localized attributes and secure password mapping[cite: 87, 88].
- [cite_start]`COMPLAINT` / `CASE`: The direct pipeline connecting a citizen-filed incident report to an active, prioritized law enforcement task[cite: 87, 89, 90].
- [cite_start]`DIGITAL_EVIDENCE`: Holds critical parameters including `FileName`, `FileHash`, `FileSize`, and full `ChainOfCustody` narratives[cite: 91].
- [cite_start]`FORENSIC_REPORT`: Logs analytic expert findings, `ToolsUsed`, and research methodologies[cite: 92].
- [cite_start]`WARRANT`: Tracks structural courtroom mandates issued to active investigations[cite: 93].

---

## 🛠️ Technology Stack

[cite_start]The application is built as a lightweight, performant web suite deployed locally using a LAMP framework[cite: 94]:

- [cite_start]**Frontend:** HTML5, CSS3, Tailwind CSS, JavaScript [cite: 95]
- [cite_start]**Backend:** PHP 8.2 (Leveraging secure PDO connections for query execution) [cite: 96]
- [cite_start]**Database Backend:** MySQL / MariaDB 10.4 [cite: 97]
- [cite_start]**Server Ecosystem:** Apache 2.4 configured via XAMPP 
- [cite_start]**DB Operations Interface:** phpMyAdmin 

---

## ⚙️ Installation & Local Setup

To deploy and review the platform locally using XAMPP:

1. **Clone the Repository:**
   ```bash
   git clone [https://github.com/alihdrz/ccrims-cybercrime-management-system.git](https://github.com/alihdrz/ccrims-cybercrime-management-system.git)
