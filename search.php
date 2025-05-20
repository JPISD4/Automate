<?php
$csvFile = "errors.csv";

// AJAX Search Endpoint (public)
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $searchCode = trim($_GET['search_code'] ?? '');
    header('Content-Type: application/json');
    if (empty($searchCode)) {
        echo json_encode(['error' => 'Empty search code.']);
        exit;
    }
    $matches = [];
    if (($fp = fopen($csvFile, "r")) !== false) {
        $headerRow = fgetcsv($fp);
        while (($row = fgetcsv($fp)) !== false) {
            $record = array_combine($headerRow, $row);
            if (strtolower($record['error_code']) === strtolower($searchCode)) {
                $matches[] = $record;
            }
        }
        fclose($fp);
    }
    if (count($matches) === 0) {
        echo json_encode(['error' => "No record found for error code: $searchCode"]);
    } elseif (count($matches) === 1) {
        echo json_encode($matches[0]);
    } else {
        echo json_encode(['multiple' => true, 'records' => $matches]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Error Manager System - Search</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
  <!-- Bootstrap Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body, html {
      height: 100%;
      margin: 0;
      padding: 0;
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
    .modern-card {
      min-width: 350px;
      max-width: 500px;
      margin: 0 auto;
      align-self: center;
    }
    .search-btn-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 0.5rem;
    }
    .search-btn-row .btn {
      min-width: 120px;
    }
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
    .multi-match-list {
      margin-top: 1rem;
    }
    .multi-match-item {
      border: 1px solid #eee;
      border-radius: 8px;
      padding: 0.75rem 1rem;
      margin-bottom: 0.75rem;
      background: #f9f9fb;
      box-shadow: 0 2px 8px rgba(180,180,255,0.04);
    }
    .multi-match-item .btn {
      float: right;
    }
    @media (max-width: 600px) {
      .search-btn-row {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
      }
      .search-btn-row .btn {
        min-width: unset;
      }
    }
  </style>
</head>
<body>
  <div class="container" style="display:block;">
    <div class="icon-top">
      <i class="bi bi-search-heart-fill"></i>
    </div>
    <h1 class="modern-header">Error Manager System</h1>
    <div class="modern-card card shadow-sm">
      <div class="card-header">
        <span>Search for an Error Record</span>
      </div>
      <div class="card-body">
        <form id="searchForm">
          <div class="mb-3">
            <label for="search_code" class="form-label">Enter Error Code</label>
            <input type="text" class="form-control" id="search_code" name="search_code" required>
          </div>
          <div class="search-btn-row">
            <button type="submit" class="btn btn-success">Search</button>
            <a href="index.php" class="btn btn-primary">Add Error</a>
          </div>
        </form>
        <div id="searchResult" class="mt-4" style="display:none;"></div>
      </div>
    </div>
  </div>
  <div class="cip-footer">
    &copy; 2025 CIP/LTI - Toshiba Information Equipment Phil. Inc.
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Helper to render a single record (with timestamp)
    function renderRecord(data) {
      return `
        <div class="search-result-popout-wrapper">
          <div class="modern-card search-result-popout-card mt-3">
            <div class="card-body">
              <h3 class="card-title" style="font-size:1.3rem;font-weight:700;">Error Code: ${data.error_code}</h3>
              <p class="card-text" style="margin-bottom:0.7rem;">
                <strong>Description:</strong><br>${(data.error_description || '').replace(/\n/g, '<br>')}
              </p>
              <p class="card-text" style="margin-bottom:0.7rem;">
                <strong>Solution:</strong><br>${(data.solution || '').replace(/\n/g, '<br>')}
              </p>
              <p class="card-text" style="margin-bottom:0.7rem;">
                <strong>Added By:</strong> ${data.username ? data.username : 'N/A'}
              </p>
              <p class="card-text" style="margin-bottom:0.7rem;">
                <strong>Created At:</strong> ${data.timestamp ? data.timestamp : 'N/A'}
              </p>
              <img src="${data.screenshot_path}" class="img-fluid" alt="Error Screenshot">
            </div>
          </div>
        </div>
      `;
    }

    // Helper to render multiple matches
    function renderMultiMatch(records) {
      let html = `<div class="multi-match-list"><strong>Multiple records found for this error code. Please select one to view:</strong>`;
      records.forEach((rec, idx) => {
        html += `
          <div class="multi-match-item" data-idx="${idx}">
            <div>
              <strong>Description:</strong> ${rec.error_description ? rec.error_description.substring(0, 60) + (rec.error_description.length > 60 ? '...' : '') : 'N/A'}
              <br>
              <strong>Added By:</strong> ${rec.username ? rec.username : 'N/A'}
              <br>
              <strong>Created At:</strong> ${rec.timestamp ? rec.timestamp : 'N/A'}
              <button class="btn btn-outline-primary btn-sm view-match-btn" data-idx="${idx}">View</button>
            </div>
          </div>
        `;
      });
      html += `</div>`;
      return html;
    }

    document.getElementById("searchForm").addEventListener("submit", function(event) {
      event.preventDefault();
      const searchCode = document.getElementById("search_code").value.trim();
      if (!searchCode) {
        alert("Please enter an error code");
        return;
      }
      fetch(`?ajax=1&search_code=${encodeURIComponent(searchCode)}`)
        .then(response => response.json())
        .then(data => {
          const resultDiv = document.getElementById("searchResult");
          if (data.error) {
            resultDiv.innerHTML = `<div class="alert alert-warning">${data.error}</div>`;
            resultDiv.style.display = "block";
          } else if (data.multiple && Array.isArray(data.records)) {
            // Multiple matches: show selection list
            resultDiv.innerHTML = renderMultiMatch(data.records);
            resultDiv.style.display = "block";
            // Add event listeners to the "View" buttons
            document.querySelectorAll('.view-match-btn').forEach(btn => {
              btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-idx'));
                resultDiv.innerHTML = renderRecord(data.records[idx]);
              });
            });
          } else {
            // Single match
            resultDiv.innerHTML = renderRecord(data);
            resultDiv.style.display = "block";
          }
        })
        .catch(err => {
          console.error("Fetch error:", err);
          alert("An error occurred while processing your request.");
        });
    });
  </script>
</body>
</html>
