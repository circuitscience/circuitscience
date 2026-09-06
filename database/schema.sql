CREATE DATABASE IF NOT EXISTS jerrybil_csi;
USE jerrybil_csi;

CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(40) NULL,
    password_hash VARCHAR(255) NULL,
    preferred_contact ENUM('phone', 'text', 'email', 'any') DEFAULT 'any',
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS property_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    group_name ENUM('residential', 'rental', 'business', 'facility', 'other') NOT NULL,
    description TEXT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS customer_properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    property_type_id INT NULL,
    label VARCHAR(120) NULL,
    address_line1 VARCHAR(190) NOT NULL,
    address_line2 VARCHAR(190) NULL,
    city VARCHAR(100) NOT NULL,
    province VARCHAR(50) DEFAULT 'Ontario',
    postal_code VARCHAR(20) NULL,
    access_notes TEXT NULL,
    parking_notes TEXT NULL,
    gate_code VARCHAR(100) NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_customer_properties_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    CONSTRAINT fk_customer_properties_type FOREIGN KEY (property_type_id) REFERENCES property_types(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS service_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    icon VARCHAR(100) NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
);

INSERT INTO service_categories (name, slug, description, sort_order) VALUES
('Electrical', 'electrical', 'Outlets, switches, panels, lighting, fans, EV chargers, pools, and troubleshooting.', 10),
('Plumbing Fixtures', 'plumbing-fixtures', 'Faucets, toilets, shower heads, fixture leaks, replacements, and small repairs.', 20),
('Walls & Surfaces', 'walls-surfaces', 'Drywall repair, patching, painting, trim, baseboards, and touch-ups.', 30),
('Doors & Locks', 'doors-locks', 'Locksets, hinges, sticking doors, closers, weatherstripping, and smart locks.', 40),
('Smart Home & Cameras', 'smart-home-cameras', 'Video doorbells, cameras, smart switches, sensors, thermostats, smart devices.', 50),
('Lighting & Fans', 'lighting-fans', 'Indoor lighting, outdoor lighting, ceiling fans, bath fans, dimmers, timers, and controls.', 60),
('Hot Tubs & Pools', 'hot-tubs-pools', 'Hot tub electrical, pool equipment support, outdoor power, timers, and related repairs.', 70),
('General Repair', 'general-repair', 'Common home, rental, small business, and facility repairs.', 80),
('Not Sure', 'not-sure', 'The customer is not sure what category the issue belongs to.', 90);

INSERT INTO property_types (name, group_name, sort_order) VALUES
('Condo / Apartment', 'residential', 10),
('Townhouse', 'residential', 20),
('Detached / Semi-Detached', 'residential', 30),
('Rental Unit', 'rental', 40),
('Garage / Workshop', 'residential', 50),
('Salon / Barbershop', 'business', 60),
('Restaurant / Diner / Café', 'business', 70),
('Retail / Convenience Store', 'business', 80),
('Storage Facility', 'facility', 90),
('Office / Studio', 'business', 100),
('Medical / Wellness / Aesthetic Room', 'business', 110),
('Small Commercial Unit', 'facility', 120),
('Other Facility', 'other', 130);

CREATE TABLE IF NOT EXISTS task_scenario_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS task_scenarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_scenario_category_id INT NOT NULL,
    title VARCHAR(190) NOT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    description TEXT NULL,
    scope_summary VARCHAR(255) NULL,
    tools_required TEXT NULL,
    materials_required TEXT NULL,
    estimated_time_min_minutes INT NOT NULL DEFAULT 30,
    estimated_time_max_minutes INT NOT NULL DEFAULT 60,
    labour_hours_min DECIMAL(5,2) NULL,
    labour_hours_max DECIMAL(5,2) NULL,
    complexity ENUM('low', 'medium', 'high') DEFAULT 'medium',
    price_min DECIMAL(10,2) NULL,
    price_max DECIMAL(10,2) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_task_scenarios_category FOREIGN KEY (task_scenario_category_id) REFERENCES task_scenario_categories(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    property_id INT NULL,
    property_type_id INT NULL,
    request_source ENUM('public_site', 'customer_portal', 'admin_created') DEFAULT 'public_site',
    process_stage ENUM('general_inquiry', 'planning_stage', 'ready_to_book', 'emergency_urgent') NOT NULL,
    urgency ENUM('normal', 'soon', 'urgent', 'emergency') DEFAULT 'normal',
    title VARCHAR(190) NULL,
    description TEXT NULL,
    quote_readiness_score INT DEFAULT 0,
    quote_confidence ENUM('not_ready', 'low', 'medium', 'high', 'diagnostic_required', 'emergency_review') DEFAULT 'not_ready',
    quote_type ENUM('photo_quote', 'guided_estimate', 'diagnostic_first', 'project_review', 'emergency_review') DEFAULT 'guided_estimate',
    status ENUM('draft', 'submitted', 'reviewing', 'quoted', 'scheduled', 'completed', 'cancelled', 'declined') DEFAULT 'submitted',
    customer_notes TEXT NULL,
    admin_notes TEXT NULL,
    preferred_contact ENUM('phone', 'text', 'email', 'any') DEFAULT 'any',
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(40) NULL,
    address_line1 VARCHAR(190) NULL,
    address_line2 VARCHAR(190) NULL,
    city VARCHAR(100) NULL,
    province VARCHAR(50) DEFAULT 'Ontario',
    postal_code VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_service_requests_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    CONSTRAINT fk_service_requests_property FOREIGN KEY (property_id) REFERENCES customer_properties(id) ON DELETE SET NULL,
    CONSTRAINT fk_service_requests_property_type FOREIGN KEY (property_type_id) REFERENCES property_types(id) ON DELETE SET NULL
);