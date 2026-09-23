<?php
require_once __DIR__ . '/../../app/Core/helpers.php';
require_once __DIR__ . '/../../app/Core/db.php';

session_start();

$dotenv = getDotEnv(__DIR__ . '/../../.env');
foreach ($dotenv as $key => $value) {
    if (!array_key_exists($key, $_ENV)) {
        $_ENV[$key] = $value;
    }
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: /index.php');
    exit;
}

$loggedIn = ($_SESSION['admin_logged_in'] ?? false) === true;
if (!$loggedIn) {
    header('Location: /admin/login.php');
    exit;
}

$pdo = null;
$reviews = [];
$requests = [];
$archivedRequests = [];
$generalInfo = [];
$dbError = '';
$jobMaterialsCount = 0;
$dashboardStats = [
    'active_requests' => 0,
    'archived_requests' => 0,
    'reviews' => 0,
    'categories' => 0,
    'catalog_items' => 0,
    'service_packages' => 0,
    'job_materials' => 0,
];

try {
    $pdo = getPDO();
} catch (Throwable $e) {
    $dbError = 'Database connection failed.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review']) && $pdo instanceof PDO) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
    $review = trim((string) ($_POST['review'] ?? ''));

    if ($name !== '' && $city !== '' && $review !== '') {
        $stmt = $pdo->prepare('INSERT INTO reviews (name, city, rating, review, approved, created_at) VALUES (:name, :city, :rating, :review, 1, NOW())');
        $stmt->execute([
            ':name' => $name,
            ':city' => $city,
            ':rating' => $rating,
            ':review' => $review,
        ]);
        $success = 'Review added successfully.';
    } else {
        $error = 'Name, city and review are required.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_reviewed']) && $pdo instanceof PDO) {
    $requestId = (int) ($_POST['request_id'] ?? 0);
    if ($requestId > 0) {
        $pdo->prepare('UPDATE estimate_requests SET status = :status, updated_at = NOW() WHERE id = :id')->execute([
            ':status' => 'reviewing',
            ':id' => $requestId,
        ]);
        $success = 'Request marked as reviewed.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_request']) && $pdo instanceof PDO) {
    $requestId = (int) ($_POST['request_id'] ?? 0);
    if ($requestId > 0) {
        $pdo->prepare('UPDATE estimate_requests SET archived = 1, archived_at = NOW(), updated_at = NOW() WHERE id = :request_id AND archived = 0')->execute([':request_id' => $requestId]);
        $success = 'Request archived.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_request']) && $pdo instanceof PDO) {
    $requestId = (int) ($_POST['request_id'] ?? 0);
    if ($requestId > 0) {
        $pdo->prepare('UPDATE estimate_requests SET archived = 0, archived_at = NULL, updated_at = NOW() WHERE id = :request_id')->execute([':request_id' => $requestId]);
        $success = 'Request restored to active queue.';
    }
}

try {
    $pdo = getPDO();
    $hasArchivedColumn = (bool) $pdo->query("SHOW COLUMNS FROM estimate_requests LIKE 'archived'")->fetch();
    $reviews = $pdo->query('SELECT id, name, city, rating, review, approved, created_at FROM reviews ORDER BY created_at DESC')->fetchAll();

    if ($hasArchivedColumn) {
        $requests = $pdo->query('SELECT * FROM estimate_requests WHERE archived = 0 ORDER BY created_at DESC')->fetchAll();
        $archivedRequests = $pdo->query('SELECT * FROM estimate_requests WHERE archived = 1 ORDER BY archived_at DESC, created_at DESC')->fetchAll();
    } else {
        $requests = $pdo->query('SELECT * FROM estimate_requests WHERE archived_at IS NULL OR archived_at = "0000-00-00 00:00:00" ORDER BY created_at DESC')->fetchAll();
        $archivedRequests = $pdo->query('SELECT * FROM estimate_requests WHERE archived_at IS NOT NULL AND archived_at <> "0000-00-00 00:00:00" ORDER BY archived_at DESC, created_at DESC')->fetchAll();
    }

    $generalInfo = $pdo->query('SELECT * FROM general_info ORDER BY id DESC LIMIT 1')->fetch();
    try {
        $jobMaterialsCount = (int) $pdo->query('SELECT COUNT(*) FROM job_materials WHERE is_active = 1')->fetchColumn();
    } catch (Throwable $ignored) {
        $jobMaterialsCount = 0;
    }

    $dashboardStats = [
        'active_requests' => count($requests),
        'archived_requests' => count($archivedRequests),
        'reviews' => count($reviews),
        'categories' => 0,
        'catalog_items' => 0,
        'service_packages' => 0,
        'job_materials' => 0,
    ];
} catch (Throwable $e) {
    $dbError = 'Database connection failed: ' . $e->getMessage();
}

if ($pdo instanceof PDO) {
    $productCategories = $pdo->query('SELECT id, name, table_name FROM product_categories WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
    $productTables = [];
    foreach ($productCategories as $category) {
        $tableName = (string) $category['table_name'];
        $productTables[$tableName] = (string) $category['name'];
    }

    $allProducts = [];
    foreach ($productTables as $tableName => $tableLabel) {
        try {
            $stmt = $pdo->query('SELECT id, item_number, item_name, item_manufacturer, item_dimensions, item_cost, item_quantity, inventory, item_image FROM ' . $tableName . ' WHERE is_active = 1 ORDER BY item_name ASC');
            foreach ($stmt->fetchAll() as $row) {
                $allProducts[] = ['table' => $tableName, 'label' => $tableLabel] + $row;
            }
        } catch (Throwable $ignored) {
            // category tables are created ad hoc when needed
        }
    }

    $categoryProducts = [];
    foreach ($productTables as $tableName => $tableLabel) {
        try {
            $categoryProducts[$tableName] = $pdo->query('SELECT * FROM ' . $tableName . ' WHERE is_active = 1 ORDER BY item_name ASC')->fetchAll();
        } catch (Throwable $ignored) {
            $categoryProducts[$tableName] = [];
        }
    }

    $dashboardStats = [
        'active_requests' => count($requests),
        'archived_requests' => count($archivedRequests),
        'reviews' => count($reviews),
        'categories' => count($productCategories),
        'catalog_items' => array_sum(array_map('count', $categoryProducts)),
        'service_packages' => (int) $pdo->query('SELECT COUNT(*) FROM service_packages WHERE is_active = 1')->fetchColumn(),
        'job_materials' => $jobMaterialsCount,
    ];

    $stmt = $pdo->query('SELECT id, package_code AS item_number, name AS item_name, NULL AS item_manufacturer, NULL AS item_dimensions, final_price AS item_cost, 1 AS item_quantity, NULL AS item_image, 0 AS inventory, category AS category, description FROM service_packages WHERE is_active = 1 ORDER BY category ASC, name ASC');
    foreach ($stmt->fetchAll() as $row) {
        $allProducts[] = ['table' => 'service_packages', 'label' => 'Service Package'] + $row;
    }

    try {
        $stmt = $pdo->query("SELECT id, CONCAT('MAT-', id) AS item_number, name AS item_name, manufacturer AS item_manufacturer, unit AS item_dimensions, price AS item_cost, 1 AS item_quantity, image_url AS item_image, 0 AS inventory, category, subcategory, description FROM job_materials WHERE is_active = 1 ORDER BY category, subcategory, name");
        foreach ($stmt->fetchAll() as $row) {
            $materialGroup = 'Job Material / ' . ucfirst(str_replace('_', ' ', (string) $row['category']));
            if (trim((string) ($row['subcategory'] ?? '')) !== '') {
                $materialGroup .= ' / ' . trim((string) $row['subcategory']);
            }
            $allProducts[] = ['table' => 'job_materials', 'label' => $materialGroup] + $row;
        }
    } catch (Throwable $ignored) {
        // The materials migration may not have been applied yet.
    }
} else {
    $productCategories = [];
    $productTables = [];
    $allProducts = [];
    $categoryProducts = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_general_info'])) {
    $companyName = trim((string) ($_POST['company_name'] ?? ''));
    $companyAddressLine1 = trim((string) ($_POST['company_address_line_1'] ?? ''));
    $companyAddressLine2 = trim((string) ($_POST['company_address_line_2'] ?? ''));
    $companyPhone = trim((string) ($_POST['company_phone'] ?? ''));
    $companyEmail = trim((string) ($_POST['company_email'] ?? ''));
    $companyWww = trim((string) ($_POST['company_www'] ?? ''));
    $companyLogo = trim((string) ($_POST['company_logo'] ?? ''));
    $taxRate = (float) ($_POST['tax_rate'] ?? 13.00);
    $labourRate1 = (float) ($_POST['labour_rate_1'] ?? 0.00);
    $labourRate2 = (float) ($_POST['labour_rate_2'] ?? 0.00);
    $labourRate3 = (float) ($_POST['labour_rate_3'] ?? 0.00);
    $contingency = (float) ($_POST['contingency'] ?? 0.00);
    $markup = (float) ($_POST['markup'] ?? 0.00);
    $profit = (float) ($_POST['profit'] ?? 0.00);
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if ($companyName === '') {
        $error = 'Company name is required.';
    } else {
        $data = [
            ':company_name' => $companyName,
            ':company_address_line_1' => $companyAddressLine1,
            ':company_address_line_2' => $companyAddressLine2,
            ':company_phone' => $companyPhone,
            ':company_email' => $companyEmail,
            ':company_www' => $companyWww,
            ':company_logo' => $companyLogo,
            ':tax_rate' => number_format($taxRate, 2, '.', ''),
            ':labour_rate_1' => number_format($labourRate1, 2, '.', ''),
            ':labour_rate_2' => number_format($labourRate2, 2, '.', ''),
            ':labour_rate_3' => number_format($labourRate3, 2, '.', ''),
            ':contingency' => number_format($contingency, 2, '.', ''),
            ':markup' => number_format($markup, 2, '.', ''),
            ':profit' => number_format($profit, 2, '.', ''),
            ':notes' => $notes,
        ];

        if ($generalInfo) {
            $stmt = $pdo->prepare('UPDATE general_info SET company_name = :company_name, company_address_line_1 = :company_address_line_1, company_address_line_2 = :company_address_line_2, company_phone = :company_phone, company_email = :company_email, company_www = :company_www, company_logo = :company_logo, tax_rate = :tax_rate, labour_rate_1 = :labour_rate_1, labour_rate_2 = :labour_rate_2, labour_rate_3 = :labour_rate_3, contingency = :contingency, markup = :markup, profit = :profit, notes = :notes, updated_at = NOW() WHERE id = :id');
            $data[':id'] = (int) $generalInfo['id'];
            $stmt->execute($data);
        } else {
            $stmt = $pdo->prepare('INSERT INTO general_info (company_name, company_address_line_1, company_address_line_2, company_phone, company_email, company_www, company_logo, tax_rate, labour_rate_1, labour_rate_2, labour_rate_3, contingency, markup, profit, notes) VALUES (:company_name, :company_address_line_1, :company_address_line_2, :company_phone, :company_email, :company_www, :company_logo, :tax_rate, :labour_rate_1, :labour_rate_2, :labour_rate_3, :contingency, :markup, :profit, :notes)');
            $stmt->execute($data);
        }

        $generalInfo = $pdo->query('SELECT * FROM general_info ORDER BY id DESC LIMIT 1')->fetch();
        $success = 'Business settings saved.';
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product']) && $pdo instanceof PDO) {
    $tableName = trim((string) ($_POST['product_table'] ?? ''));
    $productId = (int) ($_POST['product_id'] ?? 0);

    if ($tableName === '' || $productId <= 0 || !isset($productTables[$tableName])) {
        $error = 'Invalid product selected for deletion.';
    } else {
        $pdo->prepare('UPDATE ' . $tableName . ' SET is_active = 0, updated_at = NOW() WHERE id = :id')->execute([':id' => $productId]);
        $success = 'Product deleted from category.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product_edit']) && $pdo instanceof PDO) {
    $tableName = trim((string) ($_POST['product_table'] ?? ''));
    $productId = (int) ($_POST['product_id'] ?? 0);

    if ($tableName === '' || $productId <= 0 || !isset($productTables[$tableName])) {
        $error = 'Invalid product selected for editing.';
    } else {
        $itemNumber = trim((string) ($_POST['edit_item_number'] ?? ''));
        $itemName = trim((string) ($_POST['edit_item_name'] ?? ''));
        $itemManufacturer = trim((string) ($_POST['edit_item_manufacturer'] ?? ''));
        $itemDimensions = trim((string) ($_POST['edit_item_dimensions'] ?? ''));
        $itemCost = (float) ($_POST['edit_item_cost'] ?? 0);
        $quantity = max(0, (int) ($_POST['edit_item_quantity'] ?? 0));
        $inventory = max(0, (int) ($_POST['edit_inventory'] ?? 0));
        $unit = trim((string) ($_POST['edit_unit'] ?? 'ea'));
        $imageUrl = trim((string) ($_POST['edit_item_image'] ?? ''));
        $description = trim((string) ($_POST['edit_description'] ?? ''));

        if ($itemNumber === '' || $itemName === '') {
            $error = 'Item number and item name are required.';
        } else {
            $pdo->prepare('UPDATE ' . $tableName . ' SET item_number = :item_number, item_name = :item_name, item_manufacturer = :item_manufacturer, item_dimensions = :item_dimensions, item_cost = :item_cost, item_quantity = :item_quantity, inventory = :inventory, item_image = :item_image, description = :description, unit = :unit, updated_at = NOW() WHERE id = :id')->execute([
                ':item_number' => $itemNumber,
                ':item_name' => $itemName,
                ':item_manufacturer' => $itemManufacturer,
                ':item_dimensions' => $itemDimensions,
                ':item_cost' => number_format($itemCost, 2, '.', ''),
                ':item_quantity' => $quantity,
                ':inventory' => $inventory,
                ':item_image' => $imageUrl,
                ':description' => $description,
                ':unit' => $unit,
                ':id' => $productId,
            ]);
            $success = 'Product updated successfully.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product_category']) && $pdo instanceof PDO) {
    $categoryName = trim((string) ($_POST['category_name'] ?? ''));
    $categoryDescription = trim((string) ($_POST['category_description'] ?? ''));
    if ($categoryName === '') {
        $error = 'Product category name is required.';
    } else {
        $tableName = 'product_' . preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($categoryName)));
        $tableName = preg_replace('/_+/', '_', $tableName);
        $tableName = trim($tableName, '_');
        if ($tableName === '' || strlen($tableName) > 80) {
            $error = 'Category name produced an invalid table name.';
        } else {
            $pdo->exec('CREATE TABLE IF NOT EXISTS ' . $tableName . ' (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_number VARCHAR(80) NOT NULL,
                item_name VARCHAR(180) NOT NULL,
                item_manufacturer VARCHAR(120) NULL,
                item_dimensions VARCHAR(120) NULL,
                item_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                item_quantity INT NOT NULL DEFAULT 0,
                item_image VARCHAR(255) NULL,
                inventory INT NOT NULL DEFAULT 0,
                description TEXT NULL,
                unit VARCHAR(30) DEFAULT "ea",
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_' . $tableName . '_item_number (item_number)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');

            $stmt = $pdo->prepare('INSERT INTO product_categories (name, table_name, description, is_active) VALUES (:name, :table_name, :description, 1) ON DUPLICATE KEY UPDATE name = VALUES(name), table_name = VALUES(table_name), description = VALUES(description), is_active = 1');
            $stmt->execute([
                ':name' => $categoryName,
                ':table_name' => $tableName,
                ':description' => $categoryDescription,
            ]);

            $success = 'Product category created: ' . $categoryName . '.';
            $productCategories = $pdo->query('SELECT id, name, table_name FROM product_categories WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
            $productTables = [];
            foreach ($productCategories as $category) {
                $productTables[(string) $category['table_name']] = (string) $category['name'];
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service']) && $pdo instanceof PDO) {
    $packageCode = trim((string) ($_POST['service_package_code'] ?? ''));
    $packageName = trim((string) ($_POST['service_name'] ?? ''));
    $category = trim((string) ($_POST['service_category'] ?? 'general'));
    $description = trim((string) ($_POST['service_description'] ?? ''));
    $labourHours = (float) ($_POST['service_labour_hours'] ?? 0.00);
    $labourRate = (float) ($_POST['service_labour_rate'] ?? 0.00);
    $materialsCost = (float) ($_POST['service_materials_cost'] ?? 0.00);
    $disposalCost = (float) ($_POST['service_disposal_cost'] ?? 0.00);
    $recyclingCost = (float) ($_POST['service_recycling_cost'] ?? 0.00);
    $travelCost = (float) ($_POST['service_travel_cost'] ?? 0.00);
    $markupPct = (float) ($_POST['service_markup_pct'] ?? 0.00);
    $profitPct = (float) ($_POST['service_profit_pct'] ?? 0.00);
    $finalPrice = (float) ($_POST['service_final_price'] ?? 0.00);

    if ($packageCode === '' || $packageName === '') {
        $error = 'Service package code and name are required.';
    } else {
        $subtotal = ($labourHours * $labourRate) + $materialsCost + $disposalCost + $recyclingCost + $travelCost;
        $finalPrice = $finalPrice > 0 ? $finalPrice : $subtotal;

        $pdo->prepare('INSERT INTO service_packages (package_code, name, category, description, labour_hours, labour_rate, materials_cost, disposal_cost, recycling_cost, travel_cost, subtotal, markup_pct, profit_pct, final_price, is_active) VALUES (:package_code, :name, :category, :description, :labour_hours, :labour_rate, :materials_cost, :disposal_cost, :recycling_cost, :travel_cost, :subtotal, :markup_pct, :profit_pct, :final_price, 1)')->execute([
            ':package_code' => $packageCode,
            ':name' => $packageName,
            ':category' => $category,
            ':description' => $description,
            ':labour_hours' => number_format($labourHours, 2, '.', ''),
            ':labour_rate' => number_format($labourRate, 2, '.', ''),
            ':materials_cost' => number_format($materialsCost, 2, '.', ''),
            ':disposal_cost' => number_format($disposalCost, 2, '.', ''),
            ':recycling_cost' => number_format($recyclingCost, 2, '.', ''),
            ':travel_cost' => number_format($travelCost, 2, '.', ''),
            ':subtotal' => number_format($subtotal, 2, '.', ''),
            ':markup_pct' => number_format($markupPct, 2, '.', ''),
            ':profit_pct' => number_format($profitPct, 2, '.', ''),
            ':final_price' => number_format($finalPrice, 2, '.', ''),
        ]);
        $success = 'Service package added.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product']) && $pdo instanceof PDO) {
    $tableName = trim((string) ($_POST['product_category'] ?? ''));
    if ($tableName === '' || !isset($productTables[$tableName])) {
        $error = 'Product category is required.';
    } else {
        $itemNumber = trim((string) ($_POST['item_number'] ?? ''));
        $itemName = trim((string) ($_POST['item_name'] ?? ''));
        $itemManufacturer = trim((string) ($_POST['item_manufacturer'] ?? ''));
        $itemDimensions = trim((string) ($_POST['item_dimensions'] ?? ''));
        $itemCost = (float) ($_POST['item_cost'] ?? 0);
        $itemQuantity = max(0, (int) ($_POST['item_quantity'] ?? 0));
        $itemImage = trim((string) ($_POST['item_image'] ?? ''));
        $inventory = max(0, (int) ($_POST['inventory'] ?? 0));
        $description = trim((string) ($_POST['description'] ?? ''));
        $unit = trim((string) ($_POST['unit'] ?? 'ea'));

        if ($itemNumber === '' || $itemName === '') {
            $error = 'Item number and item name are required.';
        } else {
            $st = $pdo->prepare('INSERT INTO ' . $tableName . ' (item_number, item_name, item_manufacturer, item_dimensions, item_cost, item_quantity, item_image, inventory, description, unit, is_active) VALUES (:item_number, :item_name, :item_manufacturer, :item_dimensions, :item_cost, :item_quantity, :item_image, :inventory, :description, :unit, 1)');
            $st->execute([
                ':item_number' => $itemNumber,
                ':item_name' => $itemName,
                ':item_manufacturer' => $itemManufacturer,
                ':item_dimensions' => $itemDimensions,
                ':item_cost' => number_format($itemCost, 2, '.', ''),
                ':item_quantity' => $itemQuantity,
                ':item_image' => $itemImage,
                ':inventory' => $inventory,
                ':description' => $description,
                ':unit' => $unit,
            ]);
            $success = 'Product added to category: ' . htmlspecialchars($productTables[$tableName], ENT_QUOTES, 'UTF-8');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['create_estimate']) || isset($_POST['create_invoice']))) {
    $documentType = isset($_POST['create_estimate']) ? 'estimate' : 'invoice';
    $customerName = trim((string) ($_POST['customer_name'] ?? ''));
    $customerPhone = trim((string) ($_POST['customer_phone'] ?? ''));
    $customerEmail = trim((string) ($_POST['customer_email'] ?? ''));
    $notes = trim((string) ($_POST['document_notes'] ?? ''));
    $lineProducts = $_POST['line_product'] ?? [];
    $lineQty = $_POST['line_quantity'] ?? [];
    $lineCost = $_POST['line_cost'] ?? [];
    $lineRows = [];

    foreach ($lineProducts as $index => $reference) {
        if (!is_string($reference) || $reference === '') {
            continue;
        }
        $parts = explode('|', $reference, 2);
        if (count($parts) !== 2) {
            continue;
        }
        [$table, $productId] = $parts;
        $isServicePackage = $table === 'service_packages';
        $isJobMaterial = $table === 'job_materials';
        if ((!isset($productTables[$table]) && !$isServicePackage && !$isJobMaterial) || !ctype_digit((string) $productId)) {
            continue;
        }
        $quantity = max(1, (int) ($lineQty[$index] ?? 1));

        if ($isServicePackage) {
            $row = $pdo->query('SELECT id, package_code AS item_number, name AS item_name, NULL AS item_manufacturer, NULL AS item_dimensions, final_price AS item_cost FROM service_packages WHERE is_active = 1 AND id = ' . (int) $productId . ' LIMIT 1')->fetch();
        } elseif ($isJobMaterial) {
            $row = $pdo->query("SELECT id, CONCAT('MAT-', id) AS item_number, name AS item_name, manufacturer AS item_manufacturer, unit AS item_dimensions, price AS item_cost FROM job_materials WHERE is_active = 1 AND id = " . (int) $productId . ' LIMIT 1')->fetch();
        } else {
            $row = $pdo->query('SELECT id, item_number, item_name, item_manufacturer, item_dimensions, item_cost FROM ' . $table . ' WHERE is_active = 1 AND id = ' . (int) $productId . ' LIMIT 1')->fetch();
        }
        if (!$row) {
            continue;
        }

        $costOverride = trim((string) ($lineCost[$index] ?? ''));
        $unitCost = $costOverride === '' ? (float) $row['item_cost'] : max(0, (float) $costOverride);
        $lineRows[] = [
            'table' => $table,
            'product_id' => (int) $productId,
            'item_number' => (string) $row['item_number'],
            'item_name' => (string) $row['item_name'],
            'item_manufacturer' => (string) $row['item_manufacturer'],
            'item_dimensions' => (string) $row['item_dimensions'],
            'item_cost' => $unitCost,
            'quantity' => $quantity,
        ];
    }

    if ($customerName === '' || $lineRows === []) {
        $error = 'A customer name and at least one product line are required.';
    } else {
        $subtotal = 0.0;
        foreach ($lineRows as $line) {
            $subtotal += $line['item_cost'] * $line['quantity'];
        }
        $taxRate = 13.00;
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        if ($documentType === 'estimate') {
            $stmt = $pdo->prepare('INSERT INTO estimates (customer_name, customer_email, customer_phone, status, notes, subtotal, tax_rate, tax_amount, total) VALUES (:customer_name, :customer_email, :customer_phone, :status, :notes, :subtotal, :tax_rate, :tax_amount, :total)');
            $stmt->execute([
                ':customer_name' => $customerName,
                ':customer_email' => $customerEmail,
                ':customer_phone' => $customerPhone,
                ':status' => 'draft',
                ':notes' => $notes,
                ':subtotal' => number_format($subtotal, 2, '.', ''),
                ':tax_rate' => number_format($taxRate, 2, '.', ''),
                ':tax_amount' => number_format($taxAmount, 2, '.', ''),
                ':total' => number_format($total, 2, '.', ''),
            ]);
            $docId = (int) $pdo->lastInsertId();
            $lineStmt = $pdo->prepare('INSERT INTO estimate_line_items (estimate_id, product_table, product_id, item_number, item_name, item_manufacturer, item_dimensions, item_cost, quantity, line_total) VALUES (:estimate_id, :product_table, :product_id, :item_number, :item_name, :item_manufacturer, :item_dimensions, :item_cost, :quantity, :line_total)');
            foreach ($lineRows as $line) {
                $lineTotal = $line['item_cost'] * $line['quantity'];
                $lineStmt->execute([
                    ':estimate_id' => $docId,
                    ':product_table' => $line['table'],
                    ':product_id' => $line['product_id'],
                    ':item_number' => $line['item_number'],
                    ':item_name' => $line['item_name'],
                    ':item_manufacturer' => $line['item_manufacturer'],
                    ':item_dimensions' => $line['item_dimensions'],
                    ':item_cost' => number_format($line['item_cost'], 2, '.', ''),
                    ':quantity' => $line['quantity'],
                    ':line_total' => number_format($lineTotal, 2, '.', ''),
                ]);
            }
            $success = 'Estimate created successfully.';
        } else {
            $estimateId = isset($_POST['estimate_id']) ? (int) $_POST['estimate_id'] : null;
            $stmt = $pdo->prepare('INSERT INTO invoices (estimate_id, customer_name, customer_email, customer_phone, status, notes, subtotal, tax_rate, tax_amount, total) VALUES (:estimate_id, :customer_name, :customer_email, :customer_phone, :status, :notes, :subtotal, :tax_rate, :tax_amount, :total)');
            $stmt->execute([
                ':estimate_id' => $estimateId > 0 ? $estimateId : null,
                ':customer_name' => $customerName,
                ':customer_email' => $customerEmail,
                ':customer_phone' => $customerPhone,
                ':status' => 'draft',
                ':notes' => $notes,
                ':subtotal' => number_format($subtotal, 2, '.', ''),
                ':tax_rate' => number_format($taxRate, 2, '.', ''),
                ':tax_amount' => number_format($taxAmount, 2, '.', ''),
                ':total' => number_format($total, 2, '.', ''),
            ]);
            $docId = (int) $pdo->lastInsertId();
            $lineStmt = $pdo->prepare('INSERT INTO invoice_line_items (invoice_id, product_table, product_id, item_number, item_name, item_manufacturer, item_dimensions, item_cost, quantity, line_total) VALUES (:invoice_id, :product_table, :product_id, :item_number, :item_name, :item_manufacturer, :item_dimensions, :item_cost, :quantity, :line_total)');
            foreach ($lineRows as $line) {
                $lineTotal = $line['item_cost'] * $line['quantity'];
                $lineStmt->execute([
                    ':invoice_id' => $docId,
                    ':product_table' => $line['table'],
                    ':product_id' => $line['product_id'],
                    ':item_number' => $line['item_number'],
                    ':item_name' => $line['item_name'],
                    ':item_manufacturer' => $line['item_manufacturer'],
                    ':item_dimensions' => $line['item_dimensions'],
                    ':item_cost' => number_format($line['item_cost'], 2, '.', ''),
                    ':quantity' => $line['quantity'],
                    ':line_total' => number_format($lineTotal, 2, '.', ''),
                ]);
            }
            $success = 'Invoice created successfully.';
        }
    }
}

$attachmentsByRequest = [];
$allRequestIds = array_map('intval', array_merge(array_column($requests, 'id'), array_column($archivedRequests, 'id')));
if ($allRequestIds !== []) {
    $in = implode(',', array_fill(0, count($allRequestIds), '?'));
    $stmt = $pdo->prepare('SELECT * FROM estimate_request_attachments WHERE estimate_request_id IN (' . $in . ') ORDER BY created_at DESC');
    $stmt->execute($allRequestIds);
    foreach ($stmt->fetchAll() as $attachment) {
        $attachmentsByRequest[(int) $attachment['estimate_request_id']][] = $attachment;
    }
}
?>
<!doctype html>
<html lang="en-CA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Review Admin</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f8; color: #18212d; margin: 0; }
    main { max-width: 1200px; margin: 2rem auto; padding: 1.25rem; }
    h1, h2, h3 { margin-top: 0; }
    .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem; }
    .topbar nav { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .topbar nav a { text-decoration: none; color: #10253d; background: #eef3f8; padding: 0.55rem 0.8rem; border-radius: 999px; font-size: 0.85rem; font-weight: 700; }
    .panel { background: #fff; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.06); }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .stat-card { background: linear-gradient(135deg, #10253d, #1a3558); color: white; border-radius: 12px; padding: 1rem 1.25rem; }
    .stat-card small { display: block; opacity: 0.8; margin-bottom: 0.35rem; }
    .stat-card strong { font-size: 2rem; line-height: 1; }
    label { display: block; font-weight: 600; margin-bottom: 0.75rem; }
    input, select, textarea, button { width: 100%; box-sizing: border-box; padding: 0.8rem 0.9rem; border: 1px solid #d7dfe6; border-radius: 8px; font: inherit; }
    textarea { min-height: 110px; }
    button { background: #0d1d2d; color: #fff; border: none; cursor: pointer; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 0.8rem; text-align: left; border-bottom: 1px solid #eaeef2; vertical-align: top; }
    .request-card { border: 1px solid #eaeef2; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; background: #fbfcfd; }
    .request-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
    .attachments { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.75rem; }
    .attachments a { display: inline-block; }
    .attachments img { width: 110px; height: 110px; object-fit: cover; border-radius: 8px; border: 1px solid #dfe6ec; }
    .badge { display: inline-block; padding: 0.35rem 0.6rem; border-radius: 999px; font-size: 12px; font-weight: 700; background: #eceff4; color: #213343; }
    .success { color: green; }
    .error { color: #b00020; }
    .muted { color: #536477; }
    a { color: #0d1d2d; }
  </style>
</head>
<body>
  <main>
    <div class="topbar">
      <div>
        <h1>Admin Dashboard</h1>
      </div>
      <nav>
        <a href="#overview">Overview</a>
        <a href="#requests">Requests</a>
        <a href="#reviews">Reviews</a>
        <a href="#catalog">Catalog</a>
        <a href="/admin/materials.php">Materials</a>
        <a href="#settings">Settings</a>
        <a href="/admin/reviews.php?logout=1">Sign out</a>
      </nav>
    </div>

    <?php if (!empty($success)): ?><p class="success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>

    <section id="overview" class="panel">
      <h2>Overview</h2>
      <div class="stats">
        <div class="stat-card">
          <small>Active requests</small>
          <strong><?php echo (int) $dashboardStats['active_requests']; ?></strong>
        </div>
        <div class="stat-card">
          <small>Archived</small>
          <strong><?php echo (int) $dashboardStats['archived_requests']; ?></strong>
        </div>
        <div class="stat-card">
          <small>Reviews</small>
          <strong><?php echo (int) $dashboardStats['reviews']; ?></strong>
        </div>
        <div class="stat-card">
          <small>Categories</small>
          <strong><?php echo (int) $dashboardStats['categories']; ?></strong>
        </div>
        <div class="stat-card">
          <small>Catalog items</small>
          <strong><?php echo (int) $dashboardStats['catalog_items']; ?></strong>
        </div>
        <div class="stat-card">
          <small>Service packages</small>
          <strong><?php echo (int) $dashboardStats['service_packages']; ?></strong>
        </div>
        <div class="stat-card">
          <small>Job materials</small>
          <strong><?php echo (int) $dashboardStats['job_materials']; ?></strong>
        </div>
      </div>
    </section>

    <section id="requests" class="panel">
      <h2>Estimate Requests</h2>
      <?php if ($requests === []): ?>
        <p class="muted">No estimate requests have been submitted yet.</p>
      <?php else: ?>
        <?php foreach ($requests as $request): ?>
          <?php $requestId = (int) $request['id']; $requestAttachments = $attachmentsByRequest[$requestId] ?? []; ?>
          <article class="request-card">
            <div class="request-meta">
              <div><strong>Name:</strong><br><?php echo htmlspecialchars((string) $request['name'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div><strong>Email:</strong><br><?php echo htmlspecialchars((string) $request['email'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div><strong>Phone:</strong><br><?php echo htmlspecialchars((string) $request['phone'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div><strong>Property:</strong><br><?php echo htmlspecialchars((string) $request['property_type'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div><strong>Preferred contact:</strong><br><?php echo htmlspecialchars((string) $request['preferred_contact'], ENT_QUOTES, 'UTF-8'); ?></div>
              <div><strong>Status:</strong><br><span class="badge"><?php echo htmlspecialchars((string) $request['status'], ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div><strong>Submitted:</strong><br><?php echo htmlspecialchars((string) $request['created_at'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div>
              <strong>Project details</strong>
              <p><?php echo nl2br(htmlspecialchars((string) $request['details'], ENT_QUOTES, 'UTF-8')); ?></p>
            </div>

            <div style="display:flex; gap:0.75rem; margin-top:1rem; flex-wrap:wrap;">
              <form method="post">
                <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                <button type="submit" name="mark_reviewed" value="1">Mark as reviewed</button>
              </form>
              <form method="post" onsubmit="return confirm('Archive this request and keep it for record-keeping?');">
                <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                <button type="submit" name="archive_request" value="1">Archive request</button>
              </form>
            </div>

            <?php if ($requestAttachments !== []): ?>
              <div>
                <strong>Attachments</strong>
                <div class="attachments">
                  <?php foreach ($requestAttachments as $attachment): ?>
                    <?php $url = '/admin/attachment.php?id=' . (int) $attachment['id']; ?>
                    <?php $isImage = str_starts_with((string) $attachment['mime_type'], 'image/'); ?>
                    <?php if ($isImage): ?>
                      <a href="<?php echo $url; ?>" target="_blank" rel="noopener"><img src="<?php echo $url; ?>" alt="<?php echo htmlspecialchars((string) $attachment['original_name'], ENT_QUOTES, 'UTF-8'); ?>"></a>
                    <?php else: ?>
                      <a href="<?php echo $url; ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars((string) $attachment['original_name'], ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <section id="reviews" class="panel">
      <h2>Add Review</h2>
      <form method="post">
        <div class="grid">
          <label>Name<input type="text" name="name" required></label>
          <label>City<input type="text" name="city" required></label>
          <label>Rating
            <select name="rating">
              <option value="5">5</option>
              <option value="4">4</option>
              <option value="3">3</option>
              <option value="2">2</option>
              <option value="1">1</option>
            </select>
          </label>
        </div>
        <label>Review<textarea name="review" rows="4" required></textarea></label>
        <button type="submit" name="add_review" value="1">Save review</button>
      </form>
    </section>

    <?php if ($archivedRequests !== []): ?>
    <section class="panel">
      <h2>Archived Requests</h2>
      <?php foreach ($archivedRequests as $request): ?>
        <?php $requestId = (int) $request['id']; $requestAttachments = $attachmentsByRequest[$requestId] ?? []; ?>
        <article class="request-card">
          <div class="request-meta">
            <div><strong>Name:</strong><br><?php echo htmlspecialchars((string) $request['name'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Email:</strong><br><?php echo htmlspecialchars((string) $request['email'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Phone:</strong><br><?php echo htmlspecialchars((string) $request['phone'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Archived:</strong><br><?php echo htmlspecialchars((string) ($request['archived_at'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
          <div style="display:flex; gap:0.75rem; margin-top:1rem; flex-wrap:wrap;">
            <form method="post">
              <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
              <button type="submit" name="restore_request" value="1">Restore request</button>
            </form>
          </div>
          <?php if ($requestAttachments !== []): ?>
            <div>
              <strong>Attachments</strong>
              <div class="attachments">
                <?php foreach ($requestAttachments as $attachment): ?>
                  <?php $url = '/admin/attachment.php?id=' . (int) $attachment['id']; ?>
                  <?php $isImage = str_starts_with((string) $attachment['mime_type'], 'image/'); ?>
                  <?php if ($isImage): ?>
                    <a href="<?php echo $url; ?>" target="_blank" rel="noopener"><img src="<?php echo $url; ?>" alt="<?php echo htmlspecialchars((string) $attachment['original_name'], ENT_QUOTES, 'UTF-8'); ?>"></a>
                  <?php else: ?>
                    <a href="<?php echo $url; ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars((string) $attachment['original_name'], ENT_QUOTES, 'UTF-8'); ?></a>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <section id="settings" class="panel">
      <h2>General Business Settings</h2>
      <form method="post">
        <div class="grid">
          <label>Company name<input type="text" name="company_name" value="<?php echo htmlspecialchars((string) ($generalInfo['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required></label>
          <label>Phone<input type="text" name="company_phone" value="<?php echo htmlspecialchars((string) ($generalInfo['company_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
          <label>Email<input type="email" name="company_email" value="<?php echo htmlspecialchars((string) ($generalInfo['company_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
          <label>Website<input type="url" name="company_www" value="<?php echo htmlspecialchars((string) ($generalInfo['company_www'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
          <label>Logo URL<input type="url" name="company_logo" value="<?php echo htmlspecialchars((string) ($generalInfo['company_logo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
          <label>Tax rate (%)<input type="number" step="0.01" name="tax_rate" value="<?php echo htmlspecialchars((string) ($generalInfo['tax_rate'] ?? '13.00'), ENT_QUOTES, 'UTF-8'); ?>"></label>
          <label>Labour rate 1<input type="number" step="0.01" name="labour_rate_1" value="<?php echo htmlspecialchars((string) ($generalInfo['labour_rate_1'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>"></label>
          <label>Labour rate 2<input type="number" step="0.01" name="labour_rate_2" value="<?php echo htmlspecialchars((string) ($generalInfo['labour_rate_2'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>"></label>
          <label>Labour rate 3<input type="number" step="0.01" name="labour_rate_3" value="<?php echo htmlspecialchars((string) ($generalInfo['labour_rate_3'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>"></label>
          <label>Contingency (%)<input type="number" step="0.01" name="contingency" value="<?php echo htmlspecialchars((string) ($generalInfo['contingency'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>"></label>
          <label>Markup (%)<input type="number" step="0.01" name="markup" value="<?php echo htmlspecialchars((string) ($generalInfo['markup'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>"></label>
          <label>Profit (%)<input type="number" step="0.01" name="profit" value="<?php echo htmlspecialchars((string) ($generalInfo['profit'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>"></label>
          <label>Address line 1<input type="text" name="company_address_line_1" value="<?php echo htmlspecialchars((string) ($generalInfo['company_address_line_1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
          <label>Address line 2<input type="text" name="company_address_line_2" value="<?php echo htmlspecialchars((string) ($generalInfo['company_address_line_2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
        </div>
        <label>Notes<textarea name="notes" rows="3"><?php echo htmlspecialchars((string) ($generalInfo['notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></label>
        <button type="submit" name="save_general_info" value="1">Save business settings</button>
      </form>
    </section>

    <section id="catalog" class="panel">
      <h2>Service Packages</h2>
      <form method="post">
        <div class="grid">
          <label>Package code<input type="text" name="service_package_code" required></label>
          <label>Package name<input type="text" name="service_name" required></label>
          <label>Category
            <select name="service_category">
              <option value="demolition">Demolition</option>
              <option value="disposal">Disposal</option>
              <option value="recycling">Recycling</option>
              <option value="cleanup">Cleanup</option>
              <option value="electrical">Electrical</option>
              <option value="plumbing">Plumbing</option>
              <option value="painting">Painting</option>
              <option value="general">General</option>
            </select>
          </label>
          <label>Labour hours<input type="number" name="service_labour_hours" step="0.25" min="0" value="0"></label>
          <label>Labour rate<input type="number" name="service_labour_rate" step="0.01" min="0" value="0"></label>
          <label>Materials cost<input type="number" name="service_materials_cost" step="0.01" min="0" value="0"></label>
          <label>Disposal cost<input type="number" name="service_disposal_cost" step="0.01" min="0" value="0"></label>
          <label>Recycling cost<input type="number" name="service_recycling_cost" step="0.01" min="0" value="0"></label>
          <label>Travel cost<input type="number" name="service_travel_cost" step="0.01" min="0" value="0"></label>
          <label>Markup %<input type="number" name="service_markup_pct" step="0.01" min="0" value="0"></label>
          <label>Profit %<input type="number" name="service_profit_pct" step="0.01" min="0" value="0"></label>
          <label>Final price<input type="number" name="service_final_price" step="0.01" min="0" value="0"></label>
        </div>
        <label>Description<textarea name="service_description" rows="3"></textarea></label>
        <button type="submit" name="add_service" value="1">Add service package</button>
      </form>
    </section>

    <section class="panel">
      <h2>Product Catalog</h2>
      <form method="post">
        <div class="grid">
          <label>Category
            <select name="product_category" required>
              <?php foreach ($productCategories as $category): ?>
                <option value="<?php echo htmlspecialchars((string) $category['table_name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Item number<input type="text" name="item_number" required></label>
          <label>Item name<input type="text" name="item_name" required></label>
          <label>Manufacturer<input type="text" name="item_manufacturer"></label>
          <label>Dimensions<input type="text" name="item_dimensions"></label>
          <label>Cost<input type="number" name="item_cost" step="0.01" min="0" required></label>
          <label>Quantity<input type="number" name="item_quantity" min="0" value="1"></label>
          <label>Inventory<input type="number" name="inventory" min="0" value="0"></label>
          <label>Unit<input type="text" name="unit" value="ea"></label>
          <label>Image URL<input type="url" name="item_image" placeholder="https://..."></label>
        </div>
        <label>Description<textarea name="description" rows="3"></textarea></label>
        <button type="submit" name="add_product" value="1">Add product</button>
      </form>
    </section>

    <section class="panel">
      <h2>Add Product Category</h2>
      <form method="post">
        <div class="grid">
          <label>Category name<input type="text" name="category_name" placeholder="e.g. Drywall" required></label>
          <label>Category description<textarea name="category_description" rows="3" placeholder="Optional notes"></textarea></label>
        </div>
        <button type="submit" name="add_product_category" value="1">Create category table</button>
      </form>
    </section>

    <section class="panel">
      <h2>Catalog Items</h2>
      <?php if ($productCategories === []): ?>
        <p class="muted">No product categories have been created yet.</p>
      <?php else: ?>
        <?php foreach ($productCategories as $category): ?>
          <?php $tableName = (string) $category['table_name']; $items = $categoryProducts[$tableName] ?? []; ?>
          <div class="request-card">
            <h3><?php echo htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php if ($items === []): ?>
              <p class="muted">No items in this category yet.</p>
            <?php else: ?>
              <table>
                <thead>
                  <tr>
                    <th>Item</th>
                    <th>Number</th>
                    <th>Cost</th>
                    <th>Qty</th>
                    <th>Inventory</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($items as $item): ?>
                    <tr>
                      <td>
                        <strong><?php echo htmlspecialchars((string) $item['item_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                        <span class="muted"><?php echo htmlspecialchars((string) ($item['item_manufacturer'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                      </td>
                      <td><?php echo htmlspecialchars((string) $item['item_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td>$<?php echo number_format((float) $item['item_cost'], 2); ?></td>
                      <td><?php echo (int) $item['item_quantity']; ?></td>
                      <td><?php echo (int) $item['inventory']; ?></td>
                      <td>
                        <form method="post" style="display:flex; flex-direction:column; gap:0.5rem;">
                          <input type="hidden" name="product_table" value="<?php echo htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8'); ?>">
                          <input type="hidden" name="product_id" value="<?php echo (int) $item['id']; ?>">
                          <input type="text" name="edit_item_number" value="<?php echo htmlspecialchars((string) $item['item_number'], ENT_QUOTES, 'UTF-8'); ?>" required>
                          <input type="text" name="edit_item_name" value="<?php echo htmlspecialchars((string) $item['item_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                          <input type="text" name="edit_item_manufacturer" value="<?php echo htmlspecialchars((string) ($item['item_manufacturer'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                          <input type="text" name="edit_item_dimensions" value="<?php echo htmlspecialchars((string) ($item['item_dimensions'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                          <input type="number" step="0.01" min="0" name="edit_item_cost" value="<?php echo htmlspecialchars((string) $item['item_cost'], ENT_QUOTES, 'UTF-8'); ?>">
                          <input type="number" min="0" name="edit_item_quantity" value="<?php echo (int) $item['item_quantity']; ?>">
                          <input type="number" min="0" name="edit_inventory" value="<?php echo (int) $item['inventory']; ?>">
                          <input type="text" name="edit_unit" value="<?php echo htmlspecialchars((string) ($item['unit'] ?? 'ea'), ENT_QUOTES, 'UTF-8'); ?>">
                          <input type="url" name="edit_item_image" value="<?php echo htmlspecialchars((string) ($item['item_image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                          <textarea name="edit_description" rows="2"><?php echo htmlspecialchars((string) ($item['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                          <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                            <button type="submit" name="save_product_edit" value="1">Save</button>
                            <button type="submit" name="delete_product" value="1" style="background:#a12626;">Delete</button>
                          </div>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <section class="panel">
      <h2>Estimate / Invoice Builder</h2>
      <form method="post">
        <div class="grid">
          <label>Customer name<input type="text" name="customer_name" required></label>
          <label>Phone<input type="text" name="customer_phone"></label>
          <label>Email<input type="email" name="customer_email"></label>
        </div>

        <?php for ($i = 0; $i < 3; $i++): ?>
          <div class="request-card" style="margin-top:1rem;">
            <div class="grid">
              <label>Product
                <select name="line_product[]">
                  <option value="">Select product</option>
                  <?php foreach ($allProducts as $product): ?>
                    <option value="<?php echo htmlspecialchars($product['table'], ENT_QUOTES, 'UTF-8'); ?>|<?php echo (int) $product['id']; ?>"><?php echo htmlspecialchars($product['label'] . ' - ' . $product['item_name'] . ' (' . $product['item_number'] . ')', ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>Qty<input type="number" name="line_quantity[]" min="1" value="1"></label>
              <label>Cost override<input type="number" name="line_cost[]" step="0.01" min="0" placeholder="Optional"></label>
            </div>
          </div>
        <?php endfor; ?>

        <label>Notes<textarea name="document_notes" rows="3"></textarea></label>
        <div style="display:flex; gap:0.75rem; flex-wrap:wrap; margin-top:1rem;">
          <button type="submit" name="create_estimate" value="1">Create estimate</button>
          <button type="submit" name="create_invoice" value="1">Create invoice</button>
        </div>
      </form>
    </section>

    <section class="panel">
      <h2>Existing Reviews</h2>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>City</th>
            <th>Rating</th>
            <th>Review</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reviews as $review): ?>
            <tr>
              <td><?php echo htmlspecialchars($review['name'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($review['city'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo (int) $review['rating']; ?> / 5</td>
              <td><?php echo htmlspecialchars($review['review'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
