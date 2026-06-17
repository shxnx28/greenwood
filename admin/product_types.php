<?php
require_once __DIR__ . '/auth_check.php'; 
require_once 'db.php';

// Fetch all product types
$product_types = [];
$sql = "SELECT * FROM product_type ORDER BY product_type_id DESC";
$result = $conn->query($sql);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $product_types[] = $row;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product_type'])) {
        $product_type_name = $conn->real_escape_string($_POST['product_type_name']);
        
        $sql = "INSERT INTO product_type (product_type_name) VALUES ('$product_type_name')";
        if ($conn->query($sql)) {
            header("Location: product_types.php?success=Product type added successfully");
            exit;
        } else {
            $error = "Error: " . $conn->error;
        }
    }
    
    if (isset($_POST['update_product_type'])) {
        $product_type_id = intval($_POST['product_type_id']);
        $product_type_name = $conn->real_escape_string($_POST['product_type_name']);
        
        $sql = "UPDATE product_type SET product_type_name='$product_type_name' WHERE product_type_id=$product_type_id";
        if ($conn->query($sql)) {
            header("Location: product_types.php?success=Product type updated successfully");
            exit;
        } else {
            $error = "Error: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_product_type'])) {
        $product_type_id = intval($_POST['product_type_id']);
        
        // Check if type is used in products
        $check_sql = "SELECT COUNT(*) as count FROM product WHERE product_type_id = $product_type_id";
        $check_result = $conn->query($check_sql);
        $check_row = $check_result->fetch_assoc();
        
        if ($check_row['count'] > 0) {
            $error = "Cannot delete product type. It is being used by " . $check_row['count'] . " product(s)";
        } else {
            $sql = "DELETE FROM product_type WHERE product_type_id=$product_type_id";
            if ($conn->query($sql)) {
                header("Location: product_types.php?success=Product type deleted successfully");
                exit;
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

// Get product type for editing
$edit_type = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $sql = "SELECT * FROM product_type WHERE product_type_id = $edit_id";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $edit_type = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Types Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📑 Product Types Management</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="colors.php">Colors</a>
                <a href="products.php">Products</a>
                <a href="product_colors.php">Product Colors</a>
                <a href="product_types.php" class="active">Product Types</a>
            </nav>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content">
            <!-- Add/Edit Form -->
            <div class="form-card">
                <h2><?php echo $edit_type ? 'Edit Product Type' : 'Add New Product Type'; ?></h2>
                <form method="POST" action="product_types.php">
                    <?php if ($edit_type): ?>
                        <input type="hidden" name="product_type_id" value="<?php echo $edit_type['product_type_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Product Type Name *</label>
                        <input type="text" name="product_type_name" required 
                               value="<?php echo $edit_type ? htmlspecialchars($edit_type['product_type_name']) : ''; ?>"
                               placeholder="e.g., Apparel">
                    </div>

                    <div class="form-actions">
                        <?php if ($edit_type): ?>
                            <a href="product_types.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="update_product_type" class="btn btn-success">Update Type</button>
                        <?php else: ?>
                            <button type="submit" name="add_product_type" class="btn btn-primary">Add Product Type</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Product Types Table -->
            <div class="table-card">
                <h2>All Product Types</h2>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search product types..." onkeyup="searchTable()">
                </div>
                
                <table id="dataTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Type Name</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($product_types)): ?>
                            <tr>
                                <td colspan="4" class="empty-state">No product types found. Add your first product type!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($product_types as $type): ?>
                                <tr>
                                    <td><?php echo $type['product_type_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($type['product_type_name']); ?></strong></td>
                                    <td><?php echo $type['created_at']; ?></td>
                                    <td class="actions">
                                        <a href="product_types.php?edit=<?php echo $type['product_type_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product type?');">
                                            <input type="hidden" name="product_type_id" value="<?php echo $type['product_type_id']; ?>">
                                            <button type="submit" name="delete_product_type" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('dataTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const txtValue = tr[i].textContent || tr[i].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>