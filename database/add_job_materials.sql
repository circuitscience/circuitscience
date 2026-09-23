USE jerrybil_csi;

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

-- CREATE TABLE IF NOT EXISTS does not add columns to an existing table.
-- This conditional statement makes the script safe to run on an existing catalog.
SET @has_material_subcategory = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'job_materials'
      AND column_name = 'subcategory'
);
SET @add_material_subcategory = IF(
    @has_material_subcategory = 0,
    'ALTER TABLE job_materials ADD COLUMN subcategory VARCHAR(100) NULL AFTER category',
    'SELECT 1'
);
PREPARE material_subcategory_statement FROM @add_material_subcategory;
EXECUTE material_subcategory_statement;
DEALLOCATE PREPARE material_subcategory_statement;

INSERT INTO material_manufacturers (name, is_active)
SELECT DISTINCT TRIM(manufacturer), 1
FROM job_materials
WHERE manufacturer IS NOT NULL
  AND TRIM(manufacturer) <> ''
ON DUPLICATE KEY UPDATE is_active = VALUES(is_active);
