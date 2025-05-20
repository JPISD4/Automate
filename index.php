
<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$csvFile = "errors.csv";
$csvHeader = ['error_code', 'error_description', 'solution', 'screenshot_path', 'username', 'timestamp'];

// Ensure the CSV file exists with headers (including timestamp)
if (!file_exists($csvFile)) {
    if (($fp = fopen($csvFile, 'w')) !== false) {
        fputcsv($fp, $csvHeader);
        fclose($fp);
    } else {
        die("Unable to create CSV file.");
    }
} else {
    // If file exists but header is missing timestamp, add it
    $fp = fopen($csvFile, 'r+');
    $header = fgetcsv($fp);
    if ($header && count($header) < count($csvHeader)) {
        // Add missing timestamp to header and all rows
        $rows = [];
        while (($row = fgetcsv($fp)) !== false) {
            $rows[] = $row;
        }
        fclose($fp);
        $fp = fopen($csvFile, 'w');
        fputcsv($fp, $csvHeader);
        foreach ($rows as $row) {
            // Pad missing columns with empty string
            $row = array_pad($row, count($csvHeader), '');
            fputcsv($fp, $row);
        }
        fclose($fp);
    } else {
        fclose($fp);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    // Use strip_tags and trim instead of deprecated FILTER_SANITIZE_STRING
    $errorCode = isset($_POST['error_code']) ? trim(strip_tags($_POST['error_code'])) : '';
    $errorDesc = isset($_POST['error_description']) ? trim(strip_tags($_POST['error_description'])) : '';
    $solution  = isset($_POST['solution']) ? trim(strip_tags($_POST['solution'])) : '';
    $username  = $_SESSION['username'];
    $timestamp = date('Y-m-d H:i:s');

    if (empty($errorCode) || empty($errorDesc) || empty($solution)) {
        $uploadError = "Please fill all the fields.";
    } else {
        $targetFile = '';
        if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === 0) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0777, true)) {
                    $uploadError = "Failed to create uploads directory.";
                }
            }
            $filename     = basename($_FILES["screenshot"]["name"]);
            $uniquePrefix = uniqid();
            $targetFile   = $targetDir . $uniquePrefix . "_" . $filename;

            $imageFileType = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowedTypes  = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($imageFileType, $allowedTypes)) {
                $uploadError = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            } elseif (!move_uploaded_file($_FILES["screenshot"]["tmp_name"], $targetFile)) {
                $uploadError = "An error occurred while uploading your file.";
            }
        } else {
            $uploadError = "Please upload a valid screenshot image.";
        }

        // If no upload error, append to CSV
        if (!isset($uploadError)) {
            if (($fp = fopen($csvFile, 'a')) !== false) {
                $row = [
                    $errorCode,
                    $errorDesc,
                    $solution,
                    $targetFile,
                    $username,
                    $timestamp
                ];
                fputcsv($fp, $row);
                fclose($fp);
                $uploadSuccess = "Success! Error record added.";
            } else {
                $uploadError = "Error writing to the CSV file.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Error - Error Manager System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
  <!-- Bootstrap Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .cip-footer {
      text-align: center;
      width: 100%;
      margin-top: 2rem;
      color: #888;
      font-size: 0.95rem;
      border-top: 1px solid #eee;
      padding: 0.5rem 0;
      background: #fff;
    }
    .icon-top {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-top: 2.5rem;
      margin-bottom: 0.5rem;
    }
    .icon-top .bi {
      font-size: 3.5rem;
      color: #BB40F0;
      text-shadow: 0 2px 12px rgba(180,180,255,0.08);
    }
    .modern-header {
      margin: 0 0 1.5rem 0;
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
  </style>
</head>
<body>
  <div class="container">
    <div class="icon-top">
      <i class="bi bi-search-heart-fill"></i>
    </div>
    <h1 class="modern-header">Add Error Record</h1>
    <div class="mb-4 d-flex justify-content-between align-items-center" style="gap:1rem;">
      <div>
        <span class="me-2" style="font-size:1.08rem;">👤 <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
      </div>
      <div>
        <a href="logout.php" class="btn btn-outline-secondary btn-sm me-1">Logout</a>
        <a href="search.php" class="btn btn-outline-primary btn-sm ms-1">Search Error</a>
        <?php if ($_SESSION['username'] === 'isd'): ?>
          <a href="member.php" class="btn btn-outline-primary btn-sm ms-1">User Management</a>
          <a href="edit.php" class="btn btn-outline-primary btn-sm ms-1">Edit Errors</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="modern-card card shadow-sm">
      <div class="card-header">
        <span>Upload an Error Record</span>
      </div>
      <div class="card-body">
        <?php if(isset($uploadError)): ?>
          <div class="alert alert-danger"><?php echo $uploadError; ?></div>
        <?php endif; ?>
        <?php if(isset($uploadSuccess)): ?>
          <div class="alert alert-success"><?php echo $uploadSuccess; ?></div>
        <?php endif; ?>
        <form id="errorForm" action="" method="post" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="error_code" class="form-label">Error Code</label>
            <input type="text" class="form-control" id="error_code" name="error_code" required>
          </div>
          <div class="mb-3">
            <label for="error_description" class="form-label">Error Description</label>
            <textarea class="form-control" id="error_description" name="error_description" rows="4" required></textarea>
          </div>
          <div class="mb-3">
            <label for="solution" class="form-label">Solution / Resolution Steps</label>
            <textarea class="form-control" id="solution" name="solution" rows="4" required></textarea>
          </div>
          <div class="mb-3">
            <label for="screenshot" class="form-label">Upload Screenshot</label>
            <input type="file" class="form-control" id="screenshot" name="screenshot" accept="image/*" required>
          </div>
          <button type="submit" name="upload" class="btn btn-primary">Upload Error</button>
        </form>
      </div>
    </div>
  </div>
  <div class="cip-footer">
    &copy; 2025 CIP/LTI - Toshiba Information Equipment Phil. Inc.
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
