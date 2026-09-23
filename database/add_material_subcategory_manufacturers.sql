USE jerrybil_csi;

CREATE TABLE IF NOT EXISTS material_manufacturers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_material_manufacturers_name (name)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

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
