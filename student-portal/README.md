# 🎓 PRAYAS Student Recruitment Portal

A complete web-based **Student Recruitment and Candidate Assessment Portal** developed for **PRAYAS – The Automation Academy**.

The portal manages the complete candidate recruitment lifecycle — from student registration and profile creation to resume management, aptitude assessment, profile photograph capture, video introduction, candidate summary, and final recruitment tracking.

The system includes two major modules:

* **Student Portal** — for candidates to complete the recruitment process step by step.
* **Admin Panel** — for administrators to review, assess, and manage candidate information and recruitment records.

---

## 🚀 Key Features

### 👨‍🎓 Student Module

* Student Registration
* Secure Login and Authentication
* Step-by-Step Recruitment Process
* Personal Profile Management
* Contact and Address Details
* Education Details
* Technical Skills
* Work Experience / Fresher Details
* Certifications
* Academic and Personal Projects
* Languages Known
* Hobbies and Interests
* Family Details
* Government Identity Information
* Resume Builder / Resume Upload
* Aptitude Assessment
* Aptitude Test Result
* Profile Photograph Capture
* Video Introduction Recording
* Candidate Summary
* Recruitment Progress Tracking
* Student Dashboard

---

## 👤 Candidate Profile Management

The candidate profile stores detailed personal and recruitment-related information.

### Personal Information

* Full Name
* Email Address
* Mobile Number
* Alternate / Emergency Contact Number
* Date of Birth
* Age
* Gender
* Nationality
* Marital Status
* Religion
* Place of Birth
* State / UT of Domicile

### Address Information

* Current Address
* Permanent Address
* Correspondence Address
* City
* State
* PIN Code

Support is included for maintaining a separate correspondence address when it differs from the permanent/current address.

---

## 🪪 Government Identity Information

The portal supports candidate identity information required during recruitment.

Supported identity document types include:

* Aadhaar Card
* PAN Card
* Voter ID
* Passport
* Driving License
* Other Government-Issued Photo Identity Cards

Candidate identity details can include:

* Identity Document Type
* Identity Document Number
* Aadhaar Details
* PAN Details
* Optional Document Upload
* PWBD ID Number, where applicable

Sensitive candidate information is intended to be accessible only through authorized recruitment workflows.

---

## 🎓 Education Details

Candidates can maintain their academic qualifications and education history.

Information may include:

* Qualification
* Course / Degree
* Specialization
* Institute / College
* Board / University
* Passing Year
* Percentage / CGPA
* Additional Academic Information

Multiple education records can be maintained for each candidate.

---

## 💻 Technical Skills

Candidates can add and manage their technical skills.

Examples include:

* Programming Languages
* Web Technologies
* Database Technologies
* Automation Technologies
* PLC
* SCADA
* HMI
* Robotics
* IoT / IIoT
* Industry 4.0 Technologies
* Other Technical Skills

Multiple skills can be associated with a candidate profile.

---

## 💼 Work Experience / Fresher Details

The portal supports both:

* Fresher Candidates
* Experienced Candidates

Experienced candidates can provide:

* Company / Organization Name
* Job Role / Designation
* Employment Duration
* Work Responsibilities
* Relevant Experience

Candidates selecting the **Fresher** option are not required to provide previous work experience.

---

## 🏆 Certifications

Candidates can add professional and academic certifications.

Certification information may include:

* Certification Name
* Issuing Organization
* Year / Date
* Related Technology or Skill

---

## 📂 Projects

Candidates can add academic, personal, internship, or professional projects.

Project information may include:

* Project Title
* Project Description
* Technologies Used
* Candidate Role
* Project Duration

---

## 🌐 Languages Known

Candidates can add multiple languages and specify their proficiency.

Supported proficiency options include:

* Read
* Write
* Speak

Each language can have an independent proficiency selection.

---

## 🎯 Hobbies & Interests

Candidates can provide information about:

* Hobbies
* Personal Interests
* Technical Interests
* Extracurricular Activities

This information is included in the candidate profile and summary.

---

## 👨‍👩‍👧 Family Details

The candidate profile supports family information.

### Father Details

* Father Name
* Age
* Qualification
* Occupation
* Annual / Monthly Income Information

### Mother Details

* Mother Name
* Age
* Qualification
* Occupation
* Annual / Monthly Income Information

Family information is displayed as a dedicated section in the candidate summary.

---

## 📄 Resume Management

The portal supports candidate resume management.

Features include:

* Resume Builder
* Resume Upload
* Education Information
* Technical Skills
* Previous Experience / Fresher Status
* Certifications
* Projects
* Hobbies & Interests
* Resume Storage
* Resume Access from Admin Panel

Candidate resume information can be stored in the database and associated with the candidate profile.

Uploaded/generated resume files are stored in:

```text
uploads/resumes/
```

---

## 📝 Aptitude Test Module

The portal contains an integrated aptitude assessment system.

### Features

* Multiple Choice Questions (MCQ)
* Technical Questions
* Automation-Based Questions
* Multiple Question Sets
* Random / Assigned Question Sets
* Timer-Based Questions
* Approximately 30 Seconds Per Question
* Candidate Answer Tracking
* Automatic Result Calculation
* Test Attempt Management
* Result Storage
* Result Display

The aptitude system can generate different question sets for candidates and maintain test attempts and answers in the database.

---

## 📸 Profile Photograph Capture

Candidates can capture their profile photograph directly using the device camera.

### Features

* Browser-Based Camera Access
* Camera Permission Handling
* Live Camera Preview
* Face Detection Support
* Face Visibility Validation
* Photograph Capture Validation
* Capture Button Control Based on Validation
* Photograph Preview
* Secure Photograph Storage
* Admin Photograph Preview

The photograph validation process can ensure:

* Candidate face is properly visible
* Sufficient face visibility
* Proper lighting
* No obstructed face
* Suitable photograph quality

Captured photographs are stored in:

```text
uploads/photos/
```

---

## 🎥 Video Introduction Module

Candidates can record a video introduction directly from the browser.

### Features

* Camera Access
* Microphone Access
* Video and Audio Recording
* MediaRecorder API Integration
* Recording Controls
* Video Preview Before Submission
* Video Upload
* Secure Video Storage
* Admin Video Playback

The system uses browser media APIs to capture both video and audio.

Recorded videos are stored in:

```text
uploads/videos/
```

---

## 📋 Candidate Summary

After completing the required recruitment steps, candidates can review their complete information on the **Candidate Summary** page.

The summary may include:

* Personal Details
* Contact Information
* Address Details
* Nationality
* Marital Status
* Religion
* Place of Birth
* State / UT of Domicile
* Government Identity Information
* Aadhaar / PAN Information
* Education
* Technical Skills
* Experience / Fresher Status
* Certifications
* Projects
* Languages
* Hobbies & Interests
* Profile Photograph
* Resume
* Aptitude Test Result
* Video Introduction Status
* Family Details

Candidates can review their information before proceeding to the final dashboard/recruitment stage.

A declaration can also be included to confirm that the information submitted by the candidate is accurate.

---

## 🛠️ Admin Panel

A dedicated Admin Panel allows authorized administrators to manage and review candidates.

### Admin Features

* Secure Admin Login
* Admin Dashboard
* View Registered Candidates
* Search / Review Candidate Records
* View Complete Candidate Profile
* View Personal Details
* View Contact and Address Information
* View Education Details
* View Technical Skills
* View Work Experience
* View Certifications
* View Projects
* View Languages
* View Hobbies & Interests
* View Family Details
* View Government Identity Information
* View Candidate Photograph
* View / Access Candidate Resume
* Play Candidate Video Introduction
* View Aptitude Test Results
* View Candidate Summary
* Track Recruitment Progress
* Manage Recruitment Information

The Admin Panel provides a centralized interface for reviewing the complete candidate recruitment record.

---

## 🔄 Candidate Recruitment Flow

```text
Student Registration
        ↓
Login
        ↓
Complete Personal Profile
        ↓
Education Details
        ↓
Technical Skills
        ↓
Experience / Fresher Details
        ↓
Certifications & Projects
        ↓
Languages
        ↓
Hobbies & Interests
        ↓
Family / Additional Details
        ↓
Resume Builder / Resume Upload
        ↓
Aptitude Test
        ↓
View Aptitude Result
        ↓
Capture Profile Photograph
        ↓
Record Video Introduction
        ↓
Candidate Summary
        ↓
Student Dashboard
        ↓
Recruitment Progress Tracking
```

---

## 🔐 Authentication & Recruitment Progress

The portal maintains candidate authentication and recruitment progress.

The system can track fields/status such as:

```text
current_step
first_login
profile_completed
aptitude_completed
video_uploaded
dashboard_unlocked
```

This allows candidates to be redirected to the correct recruitment step when they log in.

---

## 🗄️ Database

The application uses **MySQL** with **PDO (PHP Data Objects)** for secure database communication.

Major database tables may include:

```text
users
student_profile
education
experience
skills
languages
student_languages
resume
aptitude_questions
aptitude_options
exam_attempt
exam_answers
exam_result
video_upload
notifications
admin
settings
```

Additional tables or fields can be used for:

* Certifications
* Projects
* Hobbies
* Family Details
* Government Identity Details
* Candidate Documents
* Recruitment Progress

---

## 💻 Technologies Used

### Frontend

* HTML5
* CSS3
* JavaScript
* Bootstrap
* Responsive Web Design

### Backend

* PHP
* Core PHP
* PDO (PHP Data Objects)

### Database

* MySQL

### Development Environment

* XAMPP
* Apache Server
* phpMyAdmin
* Visual Studio Code

### Version Control

* Git
* GitHub

### Browser APIs

* MediaDevices API
* MediaRecorder API
* Camera API
* Microphone API

### Additional Technologies / Libraries

* Composer
* PHPMailer
* MediaPipe / Face Detection Integration
* DOMPDF, where applicable for resume/PDF generation

---

## 📁 Project Structure

```text
student-portal/
│
├── admin/
│   ├── Admin dashboard
│   ├── Candidate management
│   ├── Candidate details
│   ├── Resume viewing
│   ├── Photograph viewing
│   ├── Video preview
│   └── Aptitude result management
│
├── aptitude/
│   ├── Aptitude test
│   ├── Question management
│   ├── Test attempts
│   └── Results
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── includes/
│   └── Common PHP files
│
├── student/
│   ├── Profile
│   ├── Education
│   ├── Skills
│   ├── Experience
│   ├── Resume
│   ├── Photograph
│   ├── Video Introduction
│   ├── Candidate Summary
│   └── Dashboard
│
├── uploads/
│   ├── photos/
│   ├── resumes/
│   ├── videos/
│   └── documents/
│
├── mail/
│   └── Email-related files
│
├── vendor/
│   └── Composer dependencies
│
├── composer.json
├── composer.lock
├── create-password.php
├── dashboard.php
├── db.php
├── index.php
├── login.php
├── logout.php
├── register.php
└── README.md
```

> Note: The exact folder structure may vary as new modules and features are added during development.

---

## 🔒 Security Features

The system is designed with security considerations including:

* Session-Based Authentication
* Protected Student Pages
* Protected Admin Pages
* PDO Prepared Statements
* Secure Database Queries
* User Authentication Validation
* File Upload Validation
* Camera and Microphone Permission Handling
* Controlled Access to Candidate Information
* Recruitment Step Validation

Additional production security should include:

* Password hashing using `password_hash()` and `password_verify()`
* CSRF protection
* Strict server-side file validation
* MIME type validation
* Upload size restrictions
* Secure session cookie settings
* Role-based access control
* Protection of sensitive identity information
* HTTPS in production

---

## 📱 Responsive Design

The portal is designed to support multiple screen sizes using:

* Bootstrap
* Responsive CSS
* Flexible Layouts
* Mobile-Friendly Forms
* Responsive Candidate Dashboard
* Responsive Admin Panel

---

## 🎯 Project Objective

The main objective of the **PRAYAS Student Recruitment Portal** is to digitize and simplify the complete recruitment and candidate assessment process.

It provides a centralized platform where candidates can:

* Create and maintain their recruitment profile
* Submit academic and professional information
* Upload or build their resume
* Complete aptitude assessments
* Capture a verified profile photograph
* Record a video introduction
* Review their complete candidate summary
* Track recruitment progress

At the same time, administrators can efficiently review candidate profiles, assessments, documents, photographs, resumes, and video introductions from a centralized Admin Panel.

---

## 🔮 Future Enhancements

Future versions of the portal may include:

* Email OTP Verification
* Automated Email Notifications
* Candidate Registration ID Generation
* Advanced Role-Based Access Control
* Candidate Search and Filtering
* Candidate Shortlisting
* Interview Scheduling
* Interview Status Tracking
* Recruiter / HR Login
* Automated Recruitment Status Emails
* Candidate Data Export
* Excel / CSV Reports
* Analytics Dashboard
* Placement Tracking
* Certificate Generation
* Advanced Resume Generation
* Cloud File Storage
* REST API Integration
* Improved Face Verification
* AI-Assisted Candidate Screening
* Automated Resume Parsing

---

## ⚙️ Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
```

### 2. Move Project to XAMPP

Place the project folder inside:

```text
C:\xampp\htdocs\
```

Example:

```text
C:\xampp\htdocs\student-portal\
```

### 3. Start XAMPP

Start:

```text
Apache
MySQL
```

### 4. Create Database

Open **phpMyAdmin** and create/import the required MySQL database.

Update the database configuration in the project according to your local environment.

Example:

```php
$host = "localhost";
$dbname = "your_database_name";
$username = "root";
$password = "";
```

### 5. Install Composer Dependencies

Run:

```bash
composer install
```

### 6. Configure Upload Directories

Ensure the required upload directories exist:

```text
uploads/photos/
uploads/resumes/
uploads/videos/
uploads/documents/
```

### 7. Run the Project

Open the project through localhost:

```text
localhost/student-portal/
```

---

## 🧑‍💻 Development Status

The project is currently under active development.

Recently implemented or enhanced modules include:

* Expanded Candidate Personal Profile
* Nationality and Marital Status
* Religion and Place of Birth
* State / UT of Domicile
* Government Identity Information
* Aadhaar / PAN Information
* Emergency / Alternate Contact
* Family Details
* Education Management
* Skills Management
* Experience / Fresher Logic
* Languages with Read / Write / Speak Options
* Hobbies & Interests
* Resume Builder and Resume Management
* Aptitude Test System
* Candidate Photograph Capture
* Face Detection / Photograph Validation
* Video Introduction Recording
* Camera and Microphone Integration
* Candidate Summary
* Admin Candidate Details
* Admin Photograph Preview
* Admin Resume Access
* Admin Video Preview
* Recruitment Progress Tracking
* Git / GitHub Version Control

The system continues to be improved with additional validation, security, UI/UX enhancements, and recruitment management functionality.

---

## 📌 Project

**PRAYAS Student Recruitment Portal**

Developed as a centralized digital recruitment and candidate assessment solution for **PRAYAS – The Automation Academy**.
