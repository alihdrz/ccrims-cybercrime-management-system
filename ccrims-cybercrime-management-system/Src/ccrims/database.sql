-- ============================================================
--  CCRIMS — Cyber Crime Reporting & Investigation Mgmt System
--  REDUCED SCHEMA — 13 Core Tables Only
--  Import: mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS ccrims CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ccrims;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS INVESTIGATION_LOG;
DROP TABLE IF EXISTS FORENSIC_REPORT;
DROP TABLE IF EXISTS CASE_RECORD;
DROP TABLE IF EXISTS DIGITAL_EVIDENCE;
DROP TABLE IF EXISTS WARRANT;
DROP TABLE IF EXISTS CASE_SUSPECT;
DROP TABLE IF EXISTS SUSPECT;
DROP TABLE IF EXISTS CASE_STAFF;
DROP TABLE IF EXISTS `CASE`;
DROP TABLE IF EXISTS COMPLAINT;
DROP TABLE IF EXISTS CYBER_CRIME_TYPE;
DROP TABLE IF EXISTS CITIZEN;
DROP TABLE IF EXISTS STAFF;
SET FOREIGN_KEY_CHECKS = 1;

-- ── 1. CITIZEN ───────────────────────────────────────────────
CREATE TABLE CITIZEN (
    CitizenID  CHAR(36)     NOT NULL DEFAULT (UUID()) PRIMARY KEY,
    Fname      VARCHAR(50)  NOT NULL,
    Lname      VARCHAR(50)  NOT NULL,
    Email      VARCHAR(150) NOT NULL UNIQUE,
    NationalID VARCHAR(50)  NOT NULL UNIQUE,
    Phone      VARCHAR(20),
    City       VARCHAR(80),
    Password   VARCHAR(255) NOT NULL
);

-- ── 2. STAFF ─────────────────────────────────────────────────
CREATE TABLE STAFF (
    StaffID     CHAR(36)     NOT NULL DEFAULT (UUID()) PRIMARY KEY,
    Fname       VARCHAR(50)  NOT NULL,
    Lname       VARCHAR(50)  NOT NULL,
    Role        ENUM('Officer','Analyst','Forensic Expert','Admin') NOT NULL,
    BadgeNumber VARCHAR(50)  NOT NULL UNIQUE,
    Department  VARCHAR(100),
    Email       VARCHAR(150) NOT NULL UNIQUE,
    Password    VARCHAR(255) NOT NULL
);

-- ── 3. CYBER_CRIME_TYPE ──────────────────────────────────────
CREATE TABLE CYBER_CRIME_TYPE (
    CrimeTypeID CHAR(36)     NOT NULL DEFAULT (UUID()) PRIMARY KEY,
    TypeName    VARCHAR(100) NOT NULL UNIQUE,
    Description TEXT
);

-- ── 4. COMPLAINT ─────────────────────────────────────────────
CREATE TABLE COMPLAINT (
    ComplaintID CHAR(36) NOT NULL DEFAULT (UUID()) PRIMARY KEY,
    Description TEXT     NOT NULL,
    LodgeDate   DATE     NOT NULL DEFAULT (CURRENT_DATE),
    Status      ENUM('Pending','Under Review','Resolved','Rejected') NOT NULL DEFAULT 'Pending',
    CitizenID   CHAR(36) NOT NULL,
    CrimeTypeID CHAR(36) NOT NULL,
    FOREIGN KEY (CitizenID)   REFERENCES CITIZEN(CitizenID),
    FOREIGN KEY (CrimeTypeID) REFERENCES CYBER_CRIME_TYPE(CrimeTypeID)
);

-- ── 5. CASE ──────────────────────────────────────────────────
CREATE TABLE `CASE` (
    CaseID       CHAR(36)    NOT NULL DEFAULT (UUID()) PRIMARY KEY,
    CaseNumber   VARCHAR(50) NOT NULL UNIQUE,
    Description  TEXT,
    Status       ENUM('Open','Active','Closed','Suspended') NOT NULL DEFAULT 'Open',
    Priority     ENUM('Low','Medium','High','Critical')     NOT NULL DEFAULT 'Medium',
    OpenDate     DATE        NOT NULL DEFAULT (CURRENT_DATE),
    CloseDate    DATE,
    Jurisdiction VARCHAR(150),
    ComplaintID  CHAR(36)    NOT NULL UNIQUE,
    FOREIGN KEY (ComplaintID) REFERENCES COMPLAINT(ComplaintID)
);

-- ── 6. CASE_STAFF ────────────────────────────────────────────
CREATE TABLE CASE_STAFF (
    CaseID         CHAR(36)     NOT NULL,
    StaffID        CHAR(36)     NOT NULL,
    AssignedDate   DATE         NOT NULL DEFAULT (CURRENT_DATE),
    AssignmentRole VARCHAR(100),
    PRIMARY KEY (CaseID, StaffID),
    FOREIGN KEY (CaseID)  REFERENCES `CASE`(CaseID)  ON DELETE CASCADE,
    FOREIGN KEY (StaffID) REFERENCES STAFF(StaffID)
);

-- ── 7. SUSPECT ───────────────────────────────────────────────
CREATE TABLE SUSPECT (
    SuspectID  CHAR(36)     NOT NULL DEFAULT (UUID()) PRIMARY KEY,
    Fname      VARCHAR(50)  NOT NULL,
    Lname      VARCHAR(50)  NOT NULL,
    Alias      VARCHAR(100),
    Phone      VARCHAR(20),
    City       VARCHAR(80),
    NationalID VARCHAR(50)  UNIQUE,
    Status     ENUM('Person of Interest','Suspect','Charged','Acquitted','Convicted') NOT NULL DEFAULT 'Person of Interest'
);

-- ── 8. CASE_SUSPECT ──────────────────────────────────────────
CREATE TABLE CASE_SUSPECT (
    CaseID          CHAR(36)     NOT NULL,
    SuspectID       CHAR(36)     NOT NULL,
    InvolvementType VARCHAR(100),
    PRIMARY KEY (CaseID, SuspectID),
    FOREIGN KEY (CaseID)    REFERENCES `CASE`(CaseID)     ON DELETE CASCADE,
    FOREIGN KEY (SuspectID) REFERENCES SUSPECT(SuspectID)
);

-- ── 9. DIGITAL_EVIDENCE ──────────────────────────────────────
CREATE TABLE DIGITAL_EVIDENCE (
    EvidenceID     CHAR(36)     NOT NULL DEFAULT (UUID()) PRIMARY KEY,
    EvidenceType   VARCHAR(100) NOT NULL,
    FileName       VARCHAR(255) NOT NULL,
    FileHash       VARCHAR(255) NOT NULL UNIQUE,
    FileSize       VARCHAR(50),
    CollectedDate  DATE         NOT NULL DEFAULT (CURRENT_DATE),
    ChainOfCustody TEXT,
    CaseID         CHAR(36)     NOT NULL,
    CollectedBy    CHAR(36)     NOT NULL,
    FOREIGN KEY (CaseID)      REFERENCES `CASE`(CaseID)  ON DELETE CASCADE,
    FOREIGN KEY (CollectedBy) REFERENCES STAFF(StaffID)
);

-- ── 10. CASE_RECORD ──────────────────────────────────────────
CREATE TABLE CASE_RECORD (
    RecordID   CHAR(36) NOT NULL DEFAULT (UUID()) PRIMARY KEY,
    RecordDate DATE     NOT NULL DEFAULT (CURRENT_DATE),
    Summary    TEXT     NOT NULL,
    CaseID     CHAR(36) NOT NULL,
    StaffID    CHAR(36) NOT NULL,
    FOREIGN KEY (CaseID)  REFERENCES `CASE`(CaseID)  ON DELETE CASCADE,
    FOREIGN KEY (StaffID) REFERENCES STAFF(StaffID)
);

-- ── 11. INVESTIGATION_LOG ────────────────────────────────────
CREATE TABLE INVESTIGATION_LOG (
    LogID        CHAR(36)     NOT NULL DEFAULT (UUID()) PRIMARY KEY,
    ActionType   VARCHAR(100) NOT NULL,
    ActionDetail TEXT,
    Timestamp    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    RecordID     CHAR(36)     NOT NULL,
    StaffID      CHAR(36)     NOT NULL,
    FOREIGN KEY (RecordID) REFERENCES CASE_RECORD(RecordID) ON DELETE CASCADE,
    FOREIGN KEY (StaffID)  REFERENCES STAFF(StaffID)
);

-- ── 12. FORENSIC_REPORT ──────────────────────────────────────
CREATE TABLE FORENSIC_REPORT (
    RecordID      CHAR(36)     NOT NULL PRIMARY KEY,
    ReportType    VARCHAR(100) NOT NULL,
    Findings      TEXT,
    ToolsUsed     TEXT,
    Methodology   TEXT,
    SubmittedDate DATE         NOT NULL DEFAULT (CURRENT_DATE),
    ExpertID      CHAR(36)     NOT NULL,
    FOREIGN KEY (RecordID) REFERENCES CASE_RECORD(RecordID) ON DELETE CASCADE,
    FOREIGN KEY (ExpertID) REFERENCES STAFF(StaffID)
);

-- ── 13. WARRANT ──────────────────────────────────────────────
CREATE TABLE WARRANT (
    WarrantID   CHAR(36)     NOT NULL DEFAULT (UUID()) PRIMARY KEY,
    WarrantType VARCHAR(100) NOT NULL,
    IssuedDate  DATE         NOT NULL,
    ExpiryDate  DATE,
    IssuedBy    VARCHAR(150) NOT NULL,
    Status      ENUM('Active','Expired','Executed','Revoked') NOT NULL DEFAULT 'Active',
    CaseID      CHAR(36)     NOT NULL,
    FOREIGN KEY (CaseID) REFERENCES `CASE`(CaseID) ON DELETE CASCADE
);

-- ============================================================
--  SEED DATA
-- ============================================================

-- Crime Types
INSERT INTO CYBER_CRIME_TYPE (CrimeTypeID, TypeName, Description) VALUES
  ('ct000001-0000-0000-0000-000000000001','Phishing',         'Fraudulent attempts to obtain sensitive info via email or fake websites.'),
  ('ct000001-0000-0000-0000-000000000002','Online Fraud',     'Deceptive schemes conducted via the internet for financial gain.'),
  ('ct000001-0000-0000-0000-000000000003','Identity Theft',   'Unauthorized use of another person''s personal information.'),
  ('ct000001-0000-0000-0000-000000000004','Cyber Harassment', 'Repeated use of electronic communications to harass or threaten.'),
  ('ct000001-0000-0000-0000-000000000005','Ransomware',       'Malware that encrypts files and demands payment for decryption.'),
  ('ct000001-0000-0000-0000-000000000006','Data Breach',      'Unauthorized access and exposure of confidential data.'),
  ('ct000001-0000-0000-0000-000000000007','Hacking',          'Unauthorized access to computer systems, networks, or digital accounts.'),
  ('ct000001-0000-0000-0000-000000000008','Online Blackmail',  'Threatening to release sensitive information unless demands are met.');

-- Staff  (password for all = "password123")
INSERT INTO STAFF (StaffID,Fname,Lname,Role,BadgeNumber,Department,Email,Password) VALUES
  ('st000001-0000-0000-0000-000000000001','Muhammad','Bilal',  'Admin',          'ADM-001','Administration', 'bilal@ccrims.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
  ('st000001-0000-0000-0000-000000000002','Bilal', 'Ahmed', 'Officer',        'OFF-101','Investigations',    'bilal.ahmed@ccrims.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
  ('st000001-0000-0000-0000-000000000003','Sara',  'Malik', 'Forensic Expert','FOR-201','Forensics',         'sara.malik@ccrims.gov',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
  ('st000001-0000-0000-0000-000000000004','Hassan','Raza',  'Analyst',        'ANL-301','Cyber Intelligence','hassan.raza@ccrims.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
  ('st000001-0000-0000-0000-000000000005','Zara',  'Hussain','Officer',       'OFF-102','Investigations',    'zara.hussain@ccrims.gov','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Citizens (password = "password123")
INSERT INTO CITIZEN (CitizenID,Fname,Lname,Email,NationalID,Phone,City,Password) VALUES
  ('ci000001-0000-0000-0000-000000000001','Ali',    'Zafar',   'ali.zafar@email.com',    '3520112345671','+923001234567','Lahore',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
  ('ci000001-0000-0000-0000-000000000002','Fatima', 'Siddiqui','fatima.s@email.com',     '3520112345672','+923019876543','Karachi',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
  ('ci000001-0000-0000-0000-000000000003','Usman',  'Tariq',   'usman.tariq@email.com',  '3520112345673','+923331122334','Islamabad', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
  ('ci000001-0000-0000-0000-000000000004','Ayesha', 'Malik',   'ayesha.malik@email.com', '3520112345674','+923001111004','Rawalpindi','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
  ('ci000001-0000-0000-0000-000000000005','Hassan', 'Raza',    'hassan.c@email.com',     '3520112345675','+923001111005','Lahore',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Complaints
INSERT INTO COMPLAINT (ComplaintID,Description,LodgeDate,Status,CitizenID,CrimeTypeID) VALUES
  ('cp000001-0000-0000-0000-000000000001','I received a fake email from HBL asking me to verify my account. I lost Rs 50,000 after clicking the link and entering details on a spoofed website.','2024-11-10','Under Review','ci000001-0000-0000-0000-000000000001','ct000001-0000-0000-0000-000000000001'),
  ('cp000001-0000-0000-0000-000000000002','Someone is using my identity to open mobile wallet accounts and take loans online. Three accounts were opened using my CNIC without my knowledge.','2024-11-15','Pending',     'ci000001-0000-0000-0000-000000000002','ct000001-0000-0000-0000-000000000003'),
  ('cp000001-0000-0000-0000-000000000003','My computer files were all encrypted overnight. I received a demand for 0.5 Bitcoin to get a decryption key. I cannot access any of my work files.','2024-11-20','Pending',     'ci000001-0000-0000-0000-000000000003','ct000001-0000-0000-0000-000000000005'),
  ('cp000001-0000-0000-0000-000000000004','A person on Instagram is threatening to share my private photos unless I pay Rs 70,000. They contacted me via a fake account.','2024-11-25','Pending',     'ci000001-0000-0000-0000-000000000004','ct000001-0000-0000-0000-000000000008'),
  ('cp000001-0000-0000-0000-000000000005','I paid Rs 30,000 to a fake online job recruitment website as a processing fee. The company never existed and the website is now offline.','2024-11-28','Resolved',    'ci000001-0000-0000-0000-000000000005','ct000001-0000-0000-0000-000000000002');

-- Cases
INSERT INTO `CASE` (CaseID,CaseNumber,Description,Status,Priority,OpenDate,Jurisdiction,ComplaintID) VALUES
  ('ca000001-0000-0000-0000-000000000001','CASE-2024-0001','Phishing attack targeting HBL customers via spoofed domain secure-hbl-pk.xyz. Victim lost Rs 50,000.','Active',  'High',    '2024-11-11','Federal',  'cp000001-0000-0000-0000-000000000001'),
  ('ca000001-0000-0000-0000-000000000002','CASE-2024-0002','Identity theft involving fraudulent mobile wallet and loan registrations using stolen CNIC.',          'Open',    'Medium',  '2024-11-16','Provincial','cp000001-0000-0000-0000-000000000002'),
  ('ca000001-0000-0000-0000-000000000003','CASE-2024-0003','Ransomware infection — suspected LockBit 3.0 variant. Full disk encryption of victim workstation.',    'Active',  'Critical','2024-11-21','Federal',  'cp000001-0000-0000-0000-000000000003'),
  ('ca000001-0000-0000-0000-000000000004','CASE-2024-0004','Online blackmail via intimate images. Suspect traced to Lahore residential area.',                     'Active',  'High',    '2024-11-26','Provincial','cp000001-0000-0000-0000-000000000004'),
  ('ca000001-0000-0000-0000-000000000005','CASE-2024-0005','Online job fraud. Fraudulent recruitment website collected Rs 30,000 from victim.',                    'Closed',  'Low',     '2024-11-29','Provincial','cp000001-0000-0000-0000-000000000005');

-- Case Staff Assignments
INSERT INTO CASE_STAFF (CaseID,StaffID,AssignedDate,AssignmentRole) VALUES
  ('ca000001-0000-0000-0000-000000000001','st000001-0000-0000-0000-000000000002','2024-11-11','Lead Investigator'),
  ('ca000001-0000-0000-0000-000000000001','st000001-0000-0000-0000-000000000003','2024-11-12','Forensic Expert'),
  ('ca000001-0000-0000-0000-000000000002','st000001-0000-0000-0000-000000000005','2024-11-16','Lead Investigator'),
  ('ca000001-0000-0000-0000-000000000003','st000001-0000-0000-0000-000000000004','2024-11-21','Analyst'),
  ('ca000001-0000-0000-0000-000000000003','st000001-0000-0000-0000-000000000003','2024-11-21','Forensic Expert'),
  ('ca000001-0000-0000-0000-000000000004','st000001-0000-0000-0000-000000000002','2024-11-26','Lead Investigator'),
  ('ca000001-0000-0000-0000-000000000005','st000001-0000-0000-0000-000000000005','2024-11-29','Lead Investigator');

-- Suspects
INSERT INTO SUSPECT (SuspectID,Fname,Lname,Alias,Phone,City,NationalID,Status) VALUES
  ('su000001-0000-0000-0000-000000000001','Kamran','Shah',   'KShark', '+923459876543','Lahore', '3520199999991','Suspect'),
  ('su000001-0000-0000-0000-000000000002','Unknown','Unknown','Phantom',NULL,           NULL,     NULL,           'Person of Interest'),
  ('su000001-0000-0000-0000-000000000003','Zeeshan','Asghar','ZCrack', '+923001000333','Karachi','3520199999993','Charged');

-- Case Suspect Links
INSERT INTO CASE_SUSPECT (CaseID,SuspectID,InvolvementType) VALUES
  ('ca000001-0000-0000-0000-000000000001','su000001-0000-0000-0000-000000000001','Primary Suspect'),
  ('ca000001-0000-0000-0000-000000000003','su000001-0000-0000-0000-000000000002','Unknown Actor'),
  ('ca000001-0000-0000-0000-000000000004','su000001-0000-0000-0000-000000000003','Primary Suspect');

-- Digital Evidence
INSERT INTO DIGITAL_EVIDENCE (EvidenceID,EvidenceType,FileName,FileHash,FileSize,CollectedDate,ChainOfCustody,CaseID,CollectedBy) VALUES
  ('ev000001-0000-0000-0000-000000000001','Email Header',  'phishing_email.eml','abc123def456abc123def456abc123def456abc123def456abc123def456ab12','12KB', '2024-11-12','Collected by Off. Bilal Ahmed on 2024-11-12. Stored in secure evidence locker.','ca000001-0000-0000-0000-000000000001','st000001-0000-0000-0000-000000000002'),
  ('ev000001-0000-0000-0000-000000000002','Malware Sample','ransomware.bin',    'def456abc123def456abc123def456abc123def456abc123def456abc123de12','2.4MB','2024-11-22','Collected by Expert Sara Malik on 2024-11-22. Isolated in sandbox environment.', 'ca000001-0000-0000-0000-000000000003','st000001-0000-0000-0000-000000000003'),
  ('ev000001-0000-0000-0000-000000000003','Screenshot',    'blackmail_msg.png', 'fff111aaa222fff111aaa222fff111aaa222fff111aaa222fff111aaa222ff11','340KB','2024-11-27','Collected by Off. Bilal Ahmed on 2024-11-27. Original preserved on write-blocked drive.','ca000001-0000-0000-0000-000000000004','st000001-0000-0000-0000-000000000002');

-- Case Records
INSERT INTO CASE_RECORD (RecordID,RecordDate,Summary,CaseID,StaffID) VALUES
  ('rc000001-0000-0000-0000-000000000001','2024-11-13','Initial investigation started. Email headers analyzed. Phishing domain identified as secure-hbl-pk.xyz registered 48 hours before the attack. Takedown request filed with registrar.','ca000001-0000-0000-0000-000000000001','st000001-0000-0000-0000-000000000002'),
  ('rc000001-0000-0000-0000-000000000002','2024-11-23','Malware sample extracted and identified as LockBit 3.0. Full disk image taken from victim laptop. Encryption keys partially recovered from memory dump.','ca000001-0000-0000-0000-000000000003','st000001-0000-0000-0000-000000000003'),
  ('rc000001-0000-0000-0000-000000000003','2024-11-27','Blackmailer account traced to a Lahore residential IP. Telecom subpoena filed. Suspect Zeeshan Asghar identified via billing records.','ca000001-0000-0000-0000-000000000004','st000001-0000-0000-0000-000000000002');

-- Investigation Logs
INSERT INTO INVESTIGATION_LOG (LogID,ActionType,ActionDetail,Timestamp,RecordID,StaffID) VALUES
  ('lg000001-0000-0000-0000-000000000001','Domain Analysis',  'Traced phishing domain to a foreign registrar in Panama. Domain now suspended. Hosting provider logs obtained.','2024-11-13 10:30:00','rc000001-0000-0000-0000-000000000001','st000001-0000-0000-0000-000000000002'),
  ('lg000001-0000-0000-0000-000000000002','Malware Analysis', 'Hash matched to known LockBit 3.0 signature. RDP brute force confirmed as attack vector. C2 server identified.','2024-11-23 14:00:00','rc000001-0000-0000-0000-000000000002','st000001-0000-0000-0000-000000000003'),
  ('lg000001-0000-0000-0000-000000000003','IP Trace',         'Blackmailer IP 203.215.x.x traced to Lahore residential area via PTCL subpoena. Physical surveillance requested.','2024-11-27 11:00:00','rc000001-0000-0000-0000-000000000003','st000001-0000-0000-0000-000000000002');

-- Forensic Report
INSERT INTO FORENSIC_REPORT (RecordID,ReportType,Findings,ToolsUsed,Methodology,SubmittedDate,ExpertID) VALUES
  ('rc000001-0000-0000-0000-000000000002','Malware Analysis Report','LockBit 3.0 confirmed. Encryption keys partially recovered from RAM. Attack vector: RDP brute force on port 3389. Attacker dwell time estimated at 6 hours before encryption triggered.','Autopsy, Volatility 3, VirusTotal, Wireshark','Static and dynamic malware analysis performed on isolated sandbox environment. Memory forensics conducted using Volatility 3 framework.','2024-11-25','st000001-0000-0000-0000-000000000003');

-- Warrants
INSERT INTO WARRANT (WarrantID,WarrantType,IssuedDate,ExpiryDate,IssuedBy,Status,CaseID) VALUES
  ('wr000001-0000-0000-0000-000000000001','Search Warrant', '2024-11-14','2024-12-14','Islamabad High Court','Active', 'ca000001-0000-0000-0000-000000000001'),
  ('wr000001-0000-0000-0000-000000000002','Arrest Warrant', '2024-11-28','2025-01-28','Lahore Sessions Court','Active','ca000001-0000-0000-0000-000000000004');
