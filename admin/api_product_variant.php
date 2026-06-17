<?php
require_once __DIR__ . '/auth_check.php'; 
require_once 'db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true);

// Check if database connection exists
if (!isset($conn)) {
    sendResponse(false, 'Database connection failed');
}

// Helper: safely parse int — returns null if missing/empty, 0 is valid only if explicitly 0
function safeInt($value, $default = null) {
    if (!isset($value) || $value === '' || $value === null) return $default;
    return intval($value);
}

// Helper: safely parse float — returns null if missing/empty
function safeFloat($value, $default = null) {
    if (!isset($value) || $value === '' || $value === null) return $default;
    return floatval($value);
}

switch ($method) {

    case 'GET':
        try {
            $variants = [];
            $sql = "
            SELECT 
                pv.variant_id,
                pv.product_id,
                pv.sku,
                pv.stock,
                pv.image_path,
                pv.created_at,
                pv.updated_at,
                p.product_name,
                c.color_id,
                c.color_name,
                c.hex_code,
                s.size_id,
                s.size_name,
                s.sell_unit,
                s.pieces_per_box,
                MAX(CASE WHEN pr.price_type = 'retail' THEN pr.price END) as retail_price,
                MAX(CASE WHEN pr.price_type = 'wholesale' THEN pr.price END) as wholesale_price,
                MAX(CASE WHEN pr.price_type = 'wholesale' THEN pr.min_quantity END) as wholesale_min_qty
            FROM product_variant pv
            INNER JOIN product p ON pv.product_id = p.product_id
            INNER JOIN color c ON pv.color_id = c.color_id
            INNER JOIN size s ON pv.size_id = s.size_id
            LEFT JOIN price pr ON pv.variant_id = pr.variant_id
            GROUP BY pv.variant_id, pv.product_id, pv.sku, pv.stock, pv.image_path, pv.created_at, pv.updated_at,
                     p.product_name, c.color_id, c.color_name, c.hex_code,
                     s.size_id, s.size_name, s.sell_unit, s.pieces_per_box
            ORDER BY p.product_id, c.color_name, s.size_name
            ";

            $result = $conn->query($sql);

            if ($result === false) {
                sendResponse(false, 'Database query error: ' . $conn->error);
            }

            while ($row = $result->fetch_assoc()) {
                $variants[] = $row;
            }

            sendResponse(true, 'Product variants retrieved successfully', $variants);

        } catch (Exception $e) {
            sendResponse(false, 'Error fetching variants: ' . $e->getMessage());
        }
        break;

    case 'POST':
        try {
            // Validate required fields are present and not empty
            if (empty($input['product_id']) || empty($input['color_id']) || empty($input['size_id']) || !isset($input['sku']) || $input['sku'] === '') {
                sendResponse(false, 'Missing required fields: product_id, color_id, size_id, sku');
            }

            // FIX: Use safeInt so empty string never becomes 0 unexpectedly
            $product_id        = safeInt($input['product_id']);
            $color_id          = safeInt($input['color_id']);
            $size_id           = intval($input['size_id']); // int(11) in DB
            $sku               = trim($input['sku']);
            // FIX: stock — intval('') was silently 0; now explicit: missing/empty defaults to 0 intentionally
            $stock             = isset($input['stock']) && $input['stock'] !== '' ? intval($input['stock']) : 0;
            $image_path        = !empty($input['image_path']) ? $input['image_path'] : null;

            // FIX: safeFloat so empty string doesn't become 0.0 and bypass the > 0 check incorrectly
            $retail_price      = safeFloat($input['retail_price'] ?? null);
            $wholesale_price   = safeFloat($input['wholesale_price'] ?? null);
            // FIX: min_qty — intval('') = 0, not 1; explicit check needed
            $wholesale_min_qty = isset($input['wholesale_min_qty']) && $input['wholesale_min_qty'] !== '' ? intval($input['wholesale_min_qty']) : 1;

            // Validate product_id is a real positive int after parsing
            if (!$product_id || $product_id <= 0) {
                sendResponse(false, 'Invalid product_id');
            }
            if (!$color_id || $color_id <= 0) {
                sendResponse(false, 'Invalid color_id');
            }
            if ($size_id === '') {
                sendResponse(false, 'Invalid size_id');
            }

            // Validate that product exists
            $stmt = $conn->prepare("SELECT 1 FROM product WHERE product_id = ?");
            if (!$stmt) sendResponse(false, 'Database prepare error: ' . $conn->error);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $stmt->close();
                sendResponse(false, "Invalid product selected");
            }
            $stmt->close();

            // Validate that color exists
            $stmt = $conn->prepare("SELECT 1 FROM color WHERE color_id = ?");
            if (!$stmt) sendResponse(false, 'Database prepare error: ' . $conn->error);
            $stmt->bind_param("i", $color_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $stmt->close();
                sendResponse(false, "Invalid color selected");
            }
            $stmt->close();

            // Validate that size exists
            $stmt = $conn->prepare("SELECT 1 FROM size WHERE size_id = ?");
            if (!$stmt) sendResponse(false, 'Database prepare error: ' . $conn->error);
            $size_id = intval($size_id);
            $stmt->bind_param("i", $size_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $stmt->close();
                sendResponse(false, "Invalid size selected");
            }
            $stmt->close();

            // Auto-resolve duplicate SKU by appending incrementing suffix
            $baseSku = $sku;
            $counter = 1;
            $checkStmt = $conn->prepare("SELECT 1 FROM product_variant WHERE sku = ?");
            if (!$checkStmt) sendResponse(false, 'Database prepare error: ' . $conn->error);
            $checkStmt->bind_param("s", $sku);
            $checkStmt->execute();
            while ($checkStmt->get_result()->num_rows > 0) {
                $counter++;
                $sku = $baseSku . '-' . $counter;
                $checkStmt->bind_param("s", $sku);
                $checkStmt->execute();
            }
            $checkStmt->close();

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Insert new variant
                // FIX: bind_param type string "iissис" = product_id(i), color_id(i), size_id(s), sku(s), stock(i), image_path(s)
                $stmt = $conn->prepare("
                    INSERT INTO product_variant (product_id, color_id, size_id, sku, stock, image_path)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                if (!$stmt) throw new Exception('Database prepare error: ' . $conn->error);
                $stmt->bind_param("iiisis", $product_id, $color_id, $size_id, $sku, $stock, $image_path);
                if (!$stmt->execute()) throw new Exception('Error inserting variant: ' . $stmt->error);

                $variant_id = $conn->insert_id;
                $stmt->close();

                // Insert retail price if provided and valid
                if ($retail_price !== null && $retail_price > 0) {
                    $stmt = $conn->prepare("INSERT INTO price (variant_id, price_type, min_quantity, price) VALUES (?, 'retail', 1, ?)");
                    if (!$stmt) throw new Exception('Database prepare error for retail price: ' . $conn->error);
                    $stmt->bind_param("id", $variant_id, $retail_price);
                    if (!$stmt->execute()) throw new Exception('Error inserting retail price: ' . $stmt->error);
                    $stmt->close();
                }

                // Insert wholesale price if provided and valid
                if ($wholesale_price !== null && $wholesale_price > 0) {
                    $stmt = $conn->prepare("INSERT INTO price (variant_id, price_type, min_quantity, price) VALUES (?, 'wholesale', ?, ?)");
                    if (!$stmt) throw new Exception('Database prepare error for wholesale price: ' . $conn->error);
                    // FIX: correct type order — variant_id(i), wholesale_min_qty(i), wholesale_price(d)
                    $stmt->bind_param("iid", $variant_id, $wholesale_min_qty, $wholesale_price);
                    if (!$stmt->execute()) throw new Exception('Error inserting wholesale price: ' . $stmt->error);
                    $stmt->close();
                }

                $conn->commit();
                sendResponse(true, 'Product variant and prices created successfully', ['variant_id' => $variant_id]);

            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            $msg = $e->getMessage();
            // MySQL error 1062 = Duplicate entry — give a specific, actionable message
            if (strpos($msg, '1062') !== false || strpos($msg, 'Duplicate entry') !== false) {
                if (strpos($msg, 'uq_variant_sku') !== false || strpos($msg, 'sku') !== false) {
                    sendResponse(false, 'That SKU already exists — please enter a unique SKU manually.');
                } else {
                    sendResponse(false, 'COMBO_DUPLICATE: A variant with this Product + Color + Size already exists. Please choose a different Color or Size.');
                }
            }
            sendResponse(false, 'Error creating variant: ' . $msg);
        }
        break;

    case 'PUT':
        try {
            if (!isset($input['variant_id']) || $input['variant_id'] === '') {
                sendResponse(false, 'Missing variant_id');
            }

            $variant_id = intval($input['variant_id']);
            if ($variant_id <= 0) {
                sendResponse(false, 'Invalid variant_id');
            }

            // FIX: use safeInt so intval('') = 0 doesn't masquerade as a valid ID
            $product_id        = safeInt($input['product_id'] ?? null);
            $color_id          = safeInt($input['color_id'] ?? null);
            $size_id           = isset($input['size_id']) && $input['size_id'] !== '' ? intval($input['size_id']) : null;
            $sku               = isset($input['sku']) && $input['sku'] !== '' ? trim($input['sku']) : null;
            // FIX: stock must explicitly check for empty string
            $stock             = isset($input['stock']) && $input['stock'] !== '' ? intval($input['stock']) : 0;
            $image_path        = !empty($input['image_path']) ? $input['image_path'] : null;

            // FIX: safeFloat prevents empty string → 0.0
            $retail_price      = safeFloat($input['retail_price'] ?? null);
            $wholesale_price   = safeFloat($input['wholesale_price'] ?? null);
            // FIX: intval('') = 0, not 1
            $wholesale_min_qty = isset($input['wholesale_min_qty']) && $input['wholesale_min_qty'] !== '' ? intval($input['wholesale_min_qty']) : 1;

            $conn->begin_transaction();

            try {
                // Fetch current variant to fill in any fields not being updated
                $stmt = $conn->prepare("SELECT * FROM product_variant WHERE variant_id = ?");
                $stmt->bind_param("i", $variant_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    $stmt->close();
                    $conn->rollback();
                    sendResponse(false, 'No variant found with that ID');
                }

                $current_variant = $result->fetch_assoc();
                $stmt->close();

                // Fall back to existing DB values for any field not supplied
                // FIX: only fall back when null — not when 0 (0 could be a legit product_id... but we guard above)
                if ($product_id === null) $product_id = $current_variant['product_id'];
                if ($color_id === null)   $color_id   = $current_variant['color_id'];
                if ($size_id === null)    $size_id    = $current_variant['size_id'];
                if ($sku === null)        $sku        = $current_variant['sku'];
                if ($image_path === null) $image_path = $current_variant['image_path'];

                // If combination changed, check it won't create a duplicate
                if ($product_id != $current_variant['product_id'] ||
                    $color_id   != $current_variant['color_id']   ||
                    $size_id    != $current_variant['size_id']) {

                    $stmt = $conn->prepare("
                        SELECT variant_id FROM product_variant
                        WHERE product_id = ? AND color_id = ? AND size_id = ? AND variant_id != ?
                    ");
                    $stmt->bind_param("iiii", $product_id, $color_id, $size_id, $variant_id);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $stmt->close();
                        $conn->rollback();
                        sendResponse(false, 'A variant with this product, color, and size combination already exists');
                    }
                    $stmt->close();
                }

                // Update the variant row
                // FIX: correct bind_param type string:
                // product_id(i), color_id(i), size_id(s), sku(s), stock(i), image_path(s), variant_id(i) = "iissisi"
                $stmt = $conn->prepare("
                    UPDATE product_variant
                    SET product_id = ?, color_id = ?, size_id = ?, sku = ?, stock = ?, image_path = ?,
                        updated_at = current_timestamp()
                    WHERE variant_id = ?
                ");
                if (!$stmt) throw new Exception('Database prepare error: ' . $conn->error);
                $stmt->bind_param("iiisisi", $product_id, $color_id, $size_id, $sku, $stock, $image_path, $variant_id);
                if (!$stmt->execute()) throw new Exception('Error updating variant: ' . $stmt->error);
                $stmt->close();

                // Upsert retail price
                if ($retail_price !== null) {
                    $check_stmt = $conn->prepare("SELECT price_id FROM price WHERE variant_id = ? AND price_type = 'retail'");
                    $check_stmt->bind_param("i", $variant_id);
                    $check_stmt->execute();
                    $price_exists = $check_stmt->get_result()->num_rows > 0;
                    $check_stmt->close();

                    if ($price_exists) {
                        $stmt = $conn->prepare("UPDATE price SET price = ? WHERE variant_id = ? AND price_type = 'retail'");
                        $stmt->bind_param("di", $retail_price, $variant_id);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO price (variant_id, price_type, min_quantity, price) VALUES (?, 'retail', 1, ?)");
                        $stmt->bind_param("id", $variant_id, $retail_price);
                    }
                    if (!$stmt->execute()) throw new Exception('Error updating retail price: ' . $stmt->error);
                    $stmt->close();
                }

                // Upsert wholesale price
                if ($wholesale_price !== null) {
                    $check_stmt = $conn->prepare("SELECT price_id FROM price WHERE variant_id = ? AND price_type = 'wholesale'");
                    $check_stmt->bind_param("i", $variant_id);
                    $check_stmt->execute();
                    $price_exists = $check_stmt->get_result()->num_rows > 0;
                    $check_stmt->close();

                    if ($price_exists) {
                        $stmt = $conn->prepare("UPDATE price SET price = ?, min_quantity = ? WHERE variant_id = ? AND price_type = 'wholesale'");
                        $stmt->bind_param("dii", $wholesale_price, $wholesale_min_qty, $variant_id);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO price (variant_id, price_type, min_quantity, price) VALUES (?, 'wholesale', ?, ?)");
                        // FIX: correct type order — variant_id(i), wholesale_min_qty(i), wholesale_price(d)
                        $stmt->bind_param("iid", $variant_id, $wholesale_min_qty, $wholesale_price);
                    }
                    if (!$stmt->execute()) throw new Exception('Error updating wholesale price: ' . $stmt->error);
                    $stmt->close();
                }

                $conn->commit();
                sendResponse(true, 'Product variant and prices updated successfully');

            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            sendResponse(false, 'Error updating variant: ' . $e->getMessage());
        }
        break;

    case 'DELETE':
        try {
            if (!isset($input['variant_id']) || $input['variant_id'] === '') {
                sendResponse(false, 'Missing variant_id');
            }

            $variant_id = intval($input['variant_id']);
            if ($variant_id <= 0) {
                sendResponse(false, 'Invalid variant_id');
            }

            $stmt = $conn->prepare("DELETE FROM product_variant WHERE variant_id = ?");
            if (!$stmt) sendResponse(false, 'Database prepare error: ' . $conn->error);
            $stmt->bind_param("i", $variant_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $stmt->close();
                    sendResponse(true, 'Product variant deleted successfully');
                } else {
                    $stmt->close();
                    sendResponse(false, 'No variant found with that ID');
                }
            } else {
                $error = $stmt->error;
                $stmt->close();
                sendResponse(false, 'Error deleting variant: ' . $error);
            }

        } catch (Exception $e) {
            sendResponse(false, 'Error deleting variant: ' . $e->getMessage());
        }
        break;

    default:
        sendResponse(false, 'Invalid request method');
}
?>