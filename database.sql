-- Almas Hospital Website Database Schema
-- MySQL 8.x compatible

CREATE DATABASE IF NOT EXISTS almas_hospital CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE almas_hospital;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Administrator','Content Creator','Content Approver') NOT NULL,
    assign_all_departments TINYINT(1) DEFAULT 0,
    phone VARCHAR(20) NULL,
    profile_image VARCHAR(255) NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Website content table
CREATE TABLE IF NOT EXISTS website_contents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_name VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    featured_image VARCHAR(255) NULL,
    status ENUM('Draft','Pending','Published') DEFAULT 'Draft',
    created_by INT NULL,
    updated_by INT NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Department sections (configurable per department)
CREATE TABLE IF NOT EXISTS department_sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT NOT NULL,
    section_key VARCHAR(50) NOT NULL,
    section_type ENUM('content','list','doctors') NOT NULL DEFAULT 'content',
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('Draft','Pending','Published') DEFAULT 'Draft',
    created_by INT NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Approval requests table
CREATE TABLE IF NOT EXISTS approval_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entity_type ENUM('website_content','department','department_facility','department_section','doctor','gallery','patient_story','career','branch','website_setting','blog') NOT NULL,
    entity_id INT NOT NULL,
    requested_by INT NULL,
    approved_by INT NULL,
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    comments TEXT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approval_date TIMESTAMP NULL,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_name VARCHAR(150) NOT NULL,
    description LONGTEXT NOT NULL,
    image VARCHAR(255) NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_by INT NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Department facilities table
CREATE TABLE IF NOT EXISTS department_facilities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT NOT NULL,
    facility_name VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- User department assignments
CREATE TABLE IF NOT EXISTS user_departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    department_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_dept (user_id, department_id)
) ENGINE=InnoDB;

-- Doctors table
CREATE TABLE IF NOT EXISTS doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT NULL,
    name VARCHAR(150) NOT NULL,
    designation VARCHAR(150) NULL,
    qualification VARCHAR(255) NOT NULL,
    specialization VARCHAR(255) NOT NULL,
    experience VARCHAR(100) NULL,
    profile LONGTEXT NULL,
    photo VARCHAR(255) NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_by INT NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(20) NOT NULL,
    department_id INT NULL,
    doctor_id INT NULL,
    appointment_date DATE NOT NULL,
    message TEXT NULL,
    status ENUM('Pending','Confirmed','Cancelled','Completed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Blogs table
CREATE TABLE IF NOT EXISTS blogs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    content TEXT NULL,
    posted_date DATE NULL,
    image VARCHAR(255) NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_by INT NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Patient stories table
CREATE TABLE IF NOT EXISTS patient_stories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_name VARCHAR(150) NOT NULL,
    video_url VARCHAR(500) NOT NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_by INT NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Careers table
CREATE TABLE IF NOT EXISTS careers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_title VARCHAR(255) NOT NULL,
    department VARCHAR(150) NULL,
    description LONGTEXT NOT NULL,
    qualification TEXT NULL,
    deadline DATE NULL,
    status ENUM('Open','Closed') DEFAULT 'Open',
    created_by INT NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Job applications table
CREATE TABLE IF NOT EXISTS job_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    career_id INT NULL,
    applicant_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    resume VARCHAR(255) NOT NULL,
    cover_letter TEXT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Health packages table
CREATE TABLE IF NOT EXISTS health_packages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    package_name VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    benefits TEXT NULL,
    image VARCHAR(255) NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_by INT NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Index for health packages
CREATE INDEX idx_health_packages_status ON health_packages(status);

-- Contact enquiries table
CREATE TABLE IF NOT EXISTS contact_enquiries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enquiry_type ENUM('General','International Patient','Home Care') NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(20) NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Pending','Resolved') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Branches table
CREATE TABLE IF NOT EXISTS branches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_name VARCHAR(150) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    google_map VARCHAR(500) NULL,
    image VARCHAR(255) NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_by INT NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Website settings table
CREATE TABLE IF NOT EXISTS website_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    website_name VARCHAR(255) NOT NULL,
    logo VARCHAR(255) NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    facebook VARCHAR(500) NULL,
    instagram VARCHAR(500) NULL,
    youtube VARCHAR(500) NULL,
    whatsapp VARCHAR(20) NULL,
    footer_text TEXT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Media library table
CREATE TABLE IF NOT EXISTS media_library (
    id INT PRIMARY KEY AUTO_INCREMENT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type ENUM('Image','Video','Document') NOT NULL,
    uploaded_by INT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Indexes for performance
CREATE INDEX idx_user_departments_user ON user_departments(user_id);
CREATE INDEX idx_user_departments_dept ON user_departments(department_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_website_contents_page ON website_contents(page_name);
CREATE INDEX idx_website_contents_status ON website_contents(status);
CREATE INDEX idx_approval_requests_status ON approval_requests(status);
CREATE INDEX idx_approval_requests_entity ON approval_requests(entity_type, entity_id);
CREATE INDEX idx_dept_sections_department ON department_sections(department_id);
CREATE INDEX idx_dept_sections_status ON department_sections(status);
CREATE INDEX idx_doctors_department ON doctors(department_id);
CREATE INDEX idx_appointments_date ON appointments(appointment_date);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_careers_status ON careers(status);
-- Indexes for blogs (formerly gallery)
CREATE INDEX idx_blog_status ON blogs(status);
CREATE INDEX idx_branches_status ON branches(status);

-- Insert default admin user (password: Admin@123)
-- Password hash generated for 'Admin@123'
INSERT INTO users (name, email, password, role, status) VALUES
('Super Administrator', 'admin@almas.com', '$2y$10$Oqc2Db9dq2GccI4snksjzuJn7iyfhqJY2FBXCQZ2H/kHZjLdfeAcy', 'Administrator', 'Active');

-- Insert default website settings
INSERT INTO website_settings (website_name, email, phone, address, footer_text) VALUES
('Almas Hospital', 'info@almas.com', '+91 1234567890', 'Almas Hospital, City Name, State, India', '© 2026 Almas Hospital. All rights reserved.');

-- =============================================
-- Department Unit-Based CMS Migration
-- =============================================
-- Run these statements ONCE to upgrade the schema.

-- 1. Migrate existing 'content' type to 'text' before ENUM change
UPDATE department_sections SET section_type = 'text' WHERE section_type = 'content';

-- 2. Expand section_type ENUM to support all layout types
ALTER TABLE department_sections
  MODIFY COLUMN section_type ENUM('text','image_text','text_image','list','gallery','cta','doctors') NOT NULL DEFAULT 'text';

-- 3. Add subtitle, button_text, button_url columns
ALTER TABLE department_sections
  ADD COLUMN subtitle TEXT NULL AFTER title,
  ADD COLUMN button_text VARCHAR(100) NULL AFTER subtitle,
  ADD COLUMN button_url VARCHAR(500) NULL AFTER button_text;

-- 4. Create department_faqs table (if not already created)
CREATE TABLE IF NOT EXISTS department_faqs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT NOT NULL,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. Add blog to approval_requests entity_type ENUM
ALTER TABLE approval_requests
  MODIFY COLUMN entity_type ENUM('website_content','department','department_facility','department_section','doctor','gallery','patient_story','career','branch','website_setting','department_faq','blog','home_care') NOT NULL;

-- =============================================
-- Home Care Module
-- =============================================
CREATE TABLE IF NOT EXISTS home_care (
    id INT PRIMARY KEY AUTO_INCREMENT,
    heading VARCHAR(255) NULL,
    description LONGTEXT NULL,
    image VARCHAR(255) NULL,
    list_items JSON NULL,
    additional_text LONGTEXT NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_by INT NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_home_care_status ON home_care(status);
