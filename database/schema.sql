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
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS property_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    group_name ENUM('residential', 'rental', 'business', 'facility', 'other') NOT NULL,
    description TEXT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    icon VARCHAR(100) NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    city VARCHAR(120) NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT NOT NULL,
    approved TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estimate_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    email VARCHAR(190) NOT NULL,
    property_type ENUM('Residential', 'Commercial', 'Healthcare') NOT NULL,
    preferred_contact ENUM('Phone', 'Text message', 'Email') NOT NULL,
    community_rate ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
    details TEXT NOT NULL,
    source VARCHAR(80) NOT NULL DEFAULT 'public_site',
    status ENUM('new', 'reviewing', 'quoted', 'closed') NOT NULL DEFAULT 'new',
    archived TINYINT(1) NOT NULL DEFAULT 0,
    archived_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estimate_request_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estimate_request_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    stored_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(80) NOT NULL,
    file_size INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_estimate_request_attachments_request FOREIGN KEY (estimate_request_id) REFERENCES estimate_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    table_name VARCHAR(80) NOT NULL UNIQUE,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_code VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(180) NOT NULL,
    category VARCHAR(80) NOT NULL DEFAULT 'general',
    description TEXT NULL,
    labour_hours DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    labour_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    materials_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    disposal_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    recycling_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    travel_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    markup_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    profit_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    final_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_package_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_package_id INT NOT NULL,
    component_type ENUM('labour', 'material', 'disposal', 'recycling', 'travel', 'other') NOT NULL DEFAULT 'other',
    component_name VARCHAR(180) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    unit VARCHAR(30) NULL,
    unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_service_package_components_package FOREIGN KEY (service_package_id) REFERENCES service_packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estimates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(180) NOT NULL,
    customer_email VARCHAR(190) NULL,
    customer_phone VARCHAR(40) NULL,
    status ENUM('draft', 'sent', 'approved', 'closed') NOT NULL DEFAULT 'draft',
    notes TEXT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 13.00,
    tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estimate_line_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estimate_id INT NOT NULL,
    product_table VARCHAR(80) NOT NULL,
    product_id INT NOT NULL,
    item_number VARCHAR(80) NOT NULL,
    item_name VARCHAR(180) NOT NULL,
    item_manufacturer VARCHAR(120) NULL,
    item_dimensions VARCHAR(120) NULL,
    item_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 1,
    line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_estimate_line_items_estimate FOREIGN KEY (estimate_id) REFERENCES estimates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS general_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(180) NOT NULL,
    company_address_line_1 VARCHAR(190) NULL,
    company_address_line_2 VARCHAR(190) NULL,
    company_phone VARCHAR(40) NULL,
    company_email VARCHAR(190) NULL,
    company_www VARCHAR(190) NULL,
    company_logo VARCHAR(255) NULL,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 13.00,
    labour_rate_1 DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    labour_rate_2 DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    labour_rate_3 DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    contingency DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    markup DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    profit DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estimate_id INT NULL,
    customer_name VARCHAR(180) NOT NULL,
    customer_email VARCHAR(190) NULL,
    customer_phone VARCHAR(40) NULL,
    status ENUM('draft', 'sent', 'paid', 'partial', 'closed') NOT NULL DEFAULT 'draft',
    notes TEXT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 13.00,
    tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoices_estimate FOREIGN KEY (estimate_id) REFERENCES estimates(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoice_line_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    product_table VARCHAR(80) NOT NULL,
    product_id INT NOT NULL,
    item_number VARCHAR(80) NOT NULL,
    item_name VARCHAR(180) NOT NULL,
    item_manufacturer VARCHAR(120) NULL,
    item_dimensions VARCHAR(120) NULL,
    item_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 1,
    line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoice_line_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS material_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO material_categories (slug, name, is_active) VALUES
('electrical', 'Electrical', 1),
('plumbing', 'Plumbing', 1),
('drywall', 'Drywall', 1),
('paint', 'Paint', 1),
('flooring', 'Flooring', 1),
('windows_doors', 'Windows & Doors', 1),
('landscaping', 'Landscaping', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = VALUES(is_active);

CREATE TABLE IF NOT EXISTS material_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO material_units (code, name, is_active) VALUES
('foot', 'Foot', 1),
('meter', 'Metre', 1),
('sqft', 'Square foot', 1),
('sqmeters', 'Square metre', 1),
('single', 'Single item', 1),
('pkg_amt', 'Package amount', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = VALUES(is_active);

CREATE TABLE IF NOT EXISTS material_manufacturers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_material_manufacturers_name (name)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS job_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    category VARCHAR(50) NOT NULL,
    subcategory VARCHAR(100) NULL,
    description TEXT NULL,
    manufacturer VARCHAR(120) NULL,
    unit VARCHAR(30) NOT NULL,
    other_info TEXT NULL,
    image_url VARCHAR(500) NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_job_materials_name (name),
    KEY idx_job_materials_category (category),
    KEY idx_job_materials_unit (unit),
    CONSTRAINT fk_job_materials_category FOREIGN KEY (category) REFERENCES material_categories(slug) ON UPDATE CASCADE,
    CONSTRAINT fk_job_materials_unit FOREIGN KEY (unit) REFERENCES material_units(code) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
