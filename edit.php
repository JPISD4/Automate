<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'isd') {
    header('Location: login.php');
    exit;
}

$csvFile = "errors.csv";
$csvHeader = ['error_code', 'error_description', 'solution', 'screenshot_path', 'username', 'timestamp'];
$errors = [];
$editIndex = null;
$editData = null;
$message = "";

// Load all errors
if (file_exists($csvFile)) {
    if (($fp = fopen($csvFile, 'r')) !== false) {
        $header = fgetcsv($fp);
        while (($row = fgetcsv($fp)) !== false) {
            $errors[] = $row;
        }
        fclose($fp);
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delIndex = intval($_GET['delete']);
    if (isset($errors[$delIndex])) {
        array_splice($errors, $delIndex, 1);
        // Save back to CSV
        if (($fp = fopen($csvFile, 'w')) !== false) {
            fputcsv($fp, $csvHeader);
            foreach ($errors as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            $message = "Record deleted.";
        }
    }
}

// Handle edit form display
if (isset($_GET['edit'])) {
    $editIndex = intval($_GET['edit']);
    if (isset($errors[$editIndex])) {
        $editData = $errors[$editIndex];
    }
}

// Handle edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edit'])) {
    $idx = intval($_POST['edit_index']);
    if (isset($errors[$idx])) {
        $errors[$idx] = [
            trim(strip_tags($_POST['error_code'])),
            trim(strip_tags($_POST['error_description'])),
            trim(strip_tags($_POST['solution'])),
            trim(strip_tags($_POST['screenshot_path'])),
            trim(strip_tags($_POST['username'])),
            trim(strip_tags($_POST['timestamp']))
        ];
        // Save back to CSV
        if (($fp = fopen($csvFile, 'w')) !== false) {
            fputcsv($fp, $csvHeader);
            foreach ($errors as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            $message = "Record updated.";
        }
    }
}

// Handle add new record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_new'])) {
    $newRow = [
        trim(strip_tags($_POST['error_code'])),
        trim(strip_tags($_POST['error_description'])),
        trim(strip_tags($_POST['solution'])),
        trim(strip_tags($_POST['screenshot_path'])),
        trim(strip_tags($_POST['username'])),
        trim(strip_tags($_POST['timestamp']))
    ];
    $errors[] = $newRow;
    // Save back to CSV
    if (($fp = fopen($csvFile, 'w')) !== false) {
        fputcsv($fp, $csvHeader);
        foreach ($errors as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        $message = "New record added.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Errors - Error Manager System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .modern-header {
            margin: 1.5rem 0 1.5rem 0;
            font-weight: bold;
            font-size: 2.2rem;
            text-align: center;
            background: linear-gradient(90deg, #BB40F0 0%, #BB40F0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
            letter-spacing: 2px;
            text-shadow: 0 2px 12px rgba(180,180,255,0.08);
            user-select: none;
        }
        .search-box {
            max-width: 350px;
            margin-bottom: 1rem;
        }
        .cip-footer {
            text-align:center;
            margin-top:2rem;
            color:#888;
            font-size:0.95rem;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1 class="modern-header">Edit Error Records</h1>
    <div class="mb-3">
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to Add Error</a>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($editData !== null): ?>
        <!-- Edit Form -->
        <div class="card mb-4">
            <div class="card-header">Edit Error Record</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="edit_index" value="<?php echo $editIndex; ?>">
                    <div class="mb-2">
                        <label class="form-label">Error Code</label>
                        <input type="text" name="error_code" class="form-control" value="<?php echo htmlspecialchars($editData[0]); ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Error Description</label>
                        <textarea name="error_description" class="form-control" required><?php echo htmlspecialchars($editData[1]); ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Solution</label>
                        <textarea name="solution" class="form-control" required><?php echo htmlspecialchars($editData[2]); ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Screenshot Path</label>
                        <input type="text" name="screenshot_path" class="form-control" value="<?php echo htmlspecialchars($editData[3]); ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($editData[4]); ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Timestamp</label>
                        <input type="text" name="timestamp" class="form-control" value="<?php echo htmlspecialchars($editData[5]); ?>">
                    </div>
                    <button type="submit" name="save_edit" class="btn btn-primary">Save Changes</button>
                    <a href="edit.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Add New Record Form -->
        <div class="card mb-4">
            <div class="card-header">Add New Error Record</div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-2">
                        <label class="form-label">Error Code</label>
                        <input type="text" name="error_code" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Error Description</label>
                        <textarea name="error_description" class="form-control" required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Solution</label>
                        <textarea name="solution" class="form-control" required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Screenshot Path</label>
                        <input type="text" name="screenshot_path" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="isd">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Timestamp</label>
                        <input type="text" name="timestamp" class="form-control" value="<?php echo date('Y-m-d H:i:s'); ?>">
                    </div>
                    <button type="submit" name="add_new" class="btn btn-success">Add Record</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search Box with Button -->
    <div class="search-box input-group mb-3">
        <input type="text" id="errorSearch" class="form-control" placeholder="Search error records...">
        <button class="btn btn-outline-primary" type="button" id="searchBtn">Search</button>
    </div>
</div>

<!-- Error Records Table with margin -->
<div class="container-fluid px-0">
    <div class="mx-3 mb-4">
        <div class="card">
            <div class="card-header">All Error Records</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm w-100 mb-0" id="errorsTable">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <?php foreach ($csvHeader as $col): ?>
                                <th><?php echo htmlspecialchars($col); ?></th>
                            <?php endforeach; ?>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($errors as $i => $row): ?>
                            <tr>
                                <td><?php echo $i+1; ?></td>
                                <?php foreach ($row as $cell): ?>
                                    <td><?php echo htmlspecialchars($cell); ?></td>
                                <?php endforeach; ?>
                                <td>
                                    <a href="edit.php?edit=<?php echo $i; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="edit.php?delete=<?php echo $i; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="cip-footer">
    &copy; 2025 CIP/LTI - Toshiba Information Equipment Phil. Inc.
</div>
<script>
    // Search on input or button click
    function filterTable() {
        const filter = document.getElementById('errorSearch').value.toLowerCase();
        const rows = document.querySelectorAll('#errorsTable tbody tr');
        rows.forEach(row => {
            let match = false;
            row.querySelectorAll('td').forEach(cell => {
                if (cell.textContent.toLowerCase().indexOf(filter) > -1) {
                    match = true;
                }
            });
            row.style.display = match ? '' : 'none';
        });
    }
    document.getElementById('errorSearch').addEventListener('input', filterTable);
    document.getElementById('searchBtn').addEventListener('click', filterTable);
</script>
</body>
</html>