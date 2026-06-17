<?php
require_once __DIR__ . '/auth_check.php'; 
require_once 'db.php';

// Fetch all colors
$colors = [];
$sql = "SELECT * FROM color ORDER BY color_id DESC";
$result = $conn->query($sql);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $colors[] = $row;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_color'])) {
        $color_name = $conn->real_escape_string($_POST['color_name']);
        $hex_code = $conn->real_escape_string($_POST['hex_code']);
        
        $sql = "INSERT INTO color (color_name, hex_code) VALUES ('$color_name', '$hex_code')";
        if ($conn->query($sql)) {
            header("Location: colors.php?success=Color added successfully");
            exit;
        } else {
            $error = "Error: " . $conn->error;
        }
    }
    
    if (isset($_POST['update_color'])) {
        $color_id = intval($_POST['color_id']);
        $color_name = $conn->real_escape_string($_POST['color_name']);
        $hex_code = $conn->real_escape_string($_POST['hex_code']);
        
        $sql = "UPDATE color SET color_name='$color_name', hex_code='$hex_code' WHERE color_id=$color_id";
        if ($conn->query($sql)) {
            header("Location: colors.php?success=Color updated successfully");
            exit;
        } else {
            $error = "Error: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_color'])) {
        $color_id = intval($_POST['color_id']);
        
        // Check if color is used in product_color
        $check_sql = "SELECT COUNT(*) as count FROM product_color WHERE color_id = $color_id";
        $check_result = $conn->query($check_sql);
        $check_row = $check_result->fetch_assoc();
        
        if ($check_row['count'] > 0) {
            $error = "Cannot delete color. It is being used by " . $check_row['count'] . " product(s)";
        } else {
            $sql = "DELETE FROM color WHERE color_id=$color_id";
            if ($conn->query($sql)) {
                header("Location: colors.php?success=Color deleted successfully");
                exit;
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

// Get color for editing
$edit_color = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $sql = "SELECT * FROM color WHERE color_id = $edit_id";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $edit_color = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colors Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎨 Colors Management</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="colors.php" class="active">Colors</a>
                <a href="products.php">Products</a>
                <a href="product_colors.php">Product Colors</a>
                <a href="product_types.php">Product Types</a>
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
                <h2><?php echo $edit_color ? 'Edit Color' : 'Add New Color'; ?></h2>
                <form method="POST" action="colors.php">
                    <?php if ($edit_color): ?>
                        <input type="hidden" name="color_id" value="<?php echo $edit_color['color_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Color Name *</label>
                        <input type="text" name="color_name" required 
                               value="<?php echo $edit_color ? htmlspecialchars($edit_color['color_name']) : ''; ?>"
                               placeholder="e.g., Royal Blue">
                    </div>

                    <div class="form-group">
                        <label>Hex Code</label>
                        <input type="color" name="hex_code" 
                               value="<?php echo $edit_color && $edit_color['hex_code'] ? htmlspecialchars($edit_color['hex_code']) : '#000000'; ?>"
                               style="height: 50px; width: 100%;">
                    </div>

                    <div class="form-actions">
                        <?php if ($edit_color): ?>
                            <a href="colors.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="update_color" class="btn btn-success">Update Color</button>
                        <?php else: ?>
                            <button type="submit" name="add_color" class="btn btn-primary">Add Color</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Colors Table -->
            <div class="table-card">
                <h2>All Colors</h2>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search colors..." onkeyup="searchTable()">
                </div>
                
                <table id="dataTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Color Name</th>
                            <th>Hex Code</th>
                            <th>Preview</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($colors)): ?>
                            <tr>
                                <td colspan="6" class="empty-state">No colors found. Add your first color!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($colors as $color): ?>
                                <tr>
                                    <td><?php echo $color['color_id']; ?></td>
                                    <td><?php echo htmlspecialchars($color['color_name']); ?></td>
                                    <td><?php echo htmlspecialchars($color['hex_code'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="color-preview" style="background-color: <?php echo htmlspecialchars($color['hex_code'] ?? '#ccc'); ?>"></div>
                                    </td>
                                    <td><?php echo $color['created_at']; ?></td>
                                    <td class="actions">
                                        <a href="colors.php?edit=<?php echo $color['color_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this color?');">
                                            <input type="hidden" name="color_id" value="<?php echo $color['color_id']; ?>">
                                            <button type="submit" name="delete_color" class="btn btn-danger btn-sm">Delete</button>
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