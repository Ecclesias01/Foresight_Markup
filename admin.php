<?php
session_start();

// 1. CONFIGURATION & LOGIN CHECK
$admin_user = 'foresig2';
$admin_pass = '+YtCpSbeo{dd34xp'; // Change password before production

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] === $admin_user && $_POST['password'] === $admin_pass) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $login_error = "Invalid username or password.";
    }
}

// Render Login Page if not authenticated
if (!isset($_SESSION['admin_logged_in'])) :
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login - Foresight MFB</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 flex items-center justify-center min-h-screen p-4">
  <div class="bg-white p-8 rounded-xl shadow-2xl max-w-sm w-full">
    <div class="text-center mb-6">
      <h1 class="text-xl font-bold text-slate-800">Foresight MFB</h1>
      <p class="text-xs text-slate-500 uppercase tracking-wider mt-1">Portal Login</p>
    </div>
    <?php if (isset($login_error)): ?>
      <div class="bg-red-50 text-red-600 text-xs p-3 rounded-md mb-4 border border-red-200"><?php echo $login_error; ?></div>
    <?php endif; ?>
    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Username</label>
        <input type="text" name="username" required class="w-full border rounded-md p-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Password</label>
        <input type="password" name="password" required class="w-full border rounded-md p-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
      </div>
      <button type="submit" name="login" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-lg transition">Sign In</button>
    </form>
  </div>
</body>
</html>
<?php exit; endif; ?>

<?php
// 2. DATABASE CONNECTION & QUERIES
$db_host = 'localhost';
$db_user = 'YOUR_DB_USER';
$db_pass = 'YOUR_DB_PASSWORD';
$db_name = 'YOUR_DB_NAME';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Fetch Search and Filter Parameters
$search = trim($_GET['search'] ?? '');
$filter_type = trim($_GET['account_type'] ?? '');

$query = "SELECT * FROM account_applications WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $query .= " AND (surname LIKE ? OR other_names LIKE ? OR bvn LIKE ? OR nin LIKE ? OR email LIKE ?)";
    $search_param = "%{$search}%";
    array_push($params, $search_param, $search_param, $search_param, $search_param, $search_param);
    $types .= "sssss";
}

if ($filter_type !== '') {
    $query .= " AND account_type = ?";
    array_push($params, $filter_type);
    $types .= "s";
}

$query .= " ORDER BY submitted_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$applications = $result->fetch_all(MYSQLI_ASSOC);

// Quick Stats
$total_apps = count($applications);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Account Applications</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">

  <!-- Navigation Bar -->
  <header class="bg-slate-900 text-white px-6 py-4 flex justify-between items-center shadow-md">
    <div>
      <h1 class="text-lg font-bold tracking-tight">Foresight Microfinance Bank Ltd.</h1>
      <p class="text-xs text-slate-400">Account Application Processing Portal</p>
    </div>
    <a href="admin.php?action=logout" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded font-semibold transition">Logout</a>
  </header>

  <main class="max-w-7xl mx-auto py-8 px-4">
    
    <!-- Top Bar: Filters and Stats -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Applications Management</h2>
        <p class="text-xs text-slate-500">Total Applications Found: <span class="font-bold text-slate-700"><?php echo $total_apps; ?></span></p>
      </div>

      <!-- Search & Filter Form -->
      <form method="GET" class="flex flex-wrap items-center gap-2 w-full md:w-auto">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, BVN, NIN..." class="text-xs p-2.5 border rounded-lg bg-white w-full md:w-60 focus:ring-2 focus:ring-blue-500 outline-none">
        
        <select name="account_type" class="text-xs p-2.5 border rounded-lg bg-white outline-none">
          <option value="">All Account Types</option>
          <option value="Savings Account" <?php echo $filter_type === 'Savings Account' ? 'selected' : ''; ?>>Savings Account</option>
          <option value="Personal Banking" <?php echo $filter_type === 'Personal Banking' ? 'selected' : ''; ?>>Personal Banking</option>
          <option value="Cooperative Banking" <?php echo $filter_type === 'Cooperative Banking' ? 'selected' : ''; ?>>Cooperative Banking</option>
          <option value="Current Account" <?php echo $filter_type === 'Current Account' ? 'selected' : ''; ?>>Current Account</option>
        </select>

        <button type="submit" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-lg transition">Filter</button>
        <a href="admin.php" class="px-3 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs rounded-lg transition">Reset</a>
      </form>
    </div>

    <!-- Applications Table -->
    <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-bold uppercase tracking-wider">
              <th class="p-4">Applicant Name</th>
              <th class="p-4">Account Type</th>
              <th class="p-4">BVN / NIN</th>
              <th class="p-4">Contact Details</th>
              <th class="p-4">Submission Date</th>
              <th class="p-4 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
            <?php if (empty($applications)): ?>
              <tr>
                <td colspan="6" class="p-8 text-center text-slate-400">No applications found matching your criteria.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($applications as $app): ?>
                <tr class="hover:bg-slate-50 transition">
                  <td class="p-4 font-semibold text-slate-900">
                    <?php echo htmlspecialchars($app['surname'] . ' ' . $app['other_names']); ?>
                  </td>
                  <td class="p-4">
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                      <?php echo htmlspecialchars($app['account_type']); ?>
                    </span>
                  </td>
                  <td class="p-4">
                    <div>BVN: <span class="font-mono text-slate-600"><?php echo htmlspecialchars($app['bvn']); ?></span></div>
                    <div>NIN: <span class="font-mono text-slate-600"><?php echo htmlspecialchars($app['nin']); ?></span></div>
                  </td>
                  <td class="p-4">
                    <div><?php echo htmlspecialchars($app['mobile']); ?></div>
                    <div class="text-slate-400"><?php echo htmlspecialchars($app['email']); ?></div>
                  </td>
                  <td class="p-4 text-slate-500">
                    <?php echo date('M d, Y - H:i', strtotime($app['submitted_at'])); ?>
                  </td>
                  <td class="p-4 text-center">
                    <button onclick='openApplicantModal(<?php echo json_encode($app); ?>)' class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white font-semibold rounded text-xs transition">
                      Review
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- APPLICANT DETAILS MODAL -->
  <div id="detailsModal" class="fixed inset-0 bg-slate-900 bg-opacity-75 flex items-center justify-center p-4 hidden z-50">
    <div class="bg-white rounded-xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden border">
      
      <!-- Modal Header -->
      <div class="px-6 py-4 bg-slate-50 border-b flex justify-between items-center">
        <div>
          <h3 id="modalName" class="text-lg font-bold text-slate-800"></h3>
          <p id="modalAccountType" class="text-xs text-blue-600 font-semibold"></p>
        </div>
        <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 overflow-y-auto space-y-6 text-xs text-slate-700">
        
        <!-- Personal Details -->
        <div>
          <h4 class="font-bold text-slate-900 border-b pb-1 mb-3 uppercase tracking-wider text-[11px]">Personal Information</h4>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <div><span class="text-slate-400 block">Sex:</span> <strong id="modalSex"></strong></div>
            <div><span class="text-slate-400 block">Marital Status:</span> <strong id="modalMarital"></strong></div>
            <div><span class="text-slate-400 block">Date of Birth:</span> <strong id="modalDob"></strong></div>
            <div><span class="text-slate-400 block">Nationality:</span> <strong id="modalNationality"></strong></div>
            <div><span class="text-slate-400 block">State of Origin:</span> <strong id="modalState"></strong></div>
            <div><span class="text-slate-400 block">ID Type:</span> <strong id="modalIdType"></strong></div>
          </div>
          <div class="mt-3">
            <span class="text-slate-400 block">Residential Address:</span>
            <p id="modalAddress" class="font-medium text-slate-800"></p>
          </div>
        </div>

        <!-- Next of Kin -->
        <div>
          <h4 class="font-bold text-slate-900 border-b pb-1 mb-3 uppercase tracking-wider text-[11px]">Next of Kin</h4>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <div><span class="text-slate-400 block">Name:</span> <strong id="modalNokName"></strong></div>
            <div><span class="text-slate-400 block">Relationship:</span> <strong id="modalNokRel"></strong></div>
            <div><span class="text-slate-400 block">Telephone:</span> <strong id="modalNokPhone"></strong></div>
          </div>
        </div>

        <!-- Uploaded Attachments -->
        <div>
          <h4 class="font-bold text-slate-900 border-b pb-1 mb-3 uppercase tracking-wider text-[11px]">Uploaded Documents</h4>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            
            <!-- Passport -->
            <div class="border p-3 rounded-lg text-center bg-slate-50">
              <span class="block font-semibold text-slate-600 mb-2">Passport Photo</span>
              <div id="passportContainer" class="h-28 flex items-center justify-center overflow-hidden rounded border bg-white mb-2"></div>
              <a id="passportLink" target="_blank" class="text-blue-600 font-bold hover:underline">View / Download</a>
            </div>

            <!-- ID Card -->
            <div class="border p-3 rounded-lg text-center bg-slate-50">
              <span class="block font-semibold text-slate-600 mb-2">Means of ID</span>
              <div id="idCardContainer" class="h-28 flex items-center justify-center overflow-hidden rounded border bg-white mb-2"></div>
              <a id="idCardLink" target="_blank" class="text-blue-600 font-bold hover:underline">View / Download</a>
            </div>

            <!-- Signature -->
            <div class="border p-3 rounded-lg text-center bg-slate-50">
              <span class="block font-semibold text-slate-600 mb-2">Signature</span>
              <div id="signatureContainer" class="h-28 flex items-center justify-center overflow-hidden rounded border bg-white mb-2"></div>
              <a id="signatureLink" target="_blank" class="text-blue-600 font-bold hover:underline">View / Download</a>
            </div>

          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-3 bg-slate-50 border-t text-right">
        <button onclick="closeModal()" class="px-5 py-2 bg-slate-700 hover:bg-slate-800 text-white font-semibold rounded-lg text-xs transition">Close Window</button>
      </div>
    </div>
  </div>

  <script>
    function openApplicantModal(app) {
      document.getElementById('modalName').innerText = app.surname + ' ' + app.other_names;
      document.getElementById('modalAccountType').innerText = app.account_type;
      document.getElementById('modalSex').innerText = app.sex;
      document.getElementById('modalMarital').innerText = app.marital_status || 'N/A';
      document.getElementById('modalDob').innerText = app.dob;
      document.getElementById('modalNationality').innerText = app.nationality;
      document.getElementById('modalState').innerText = app.state_of_origin || 'N/A';
      document.getElementById('modalIdType').innerText = app.id_type;
      document.getElementById('modalAddress').innerText = app.residential_address;

      document.getElementById('modalNokName').innerText = app.nok_surname + ' ' + app.nok_other_names;
      document.getElementById('modalNokRel').innerText = app.nok_relationship;
      document.getElementById('modalNokPhone').innerText = app.nok_phone;

      // File Preview Handlers
      setupFilePreview(app.passport_file, 'passportContainer', 'passportLink');
      setupFilePreview(app.id_card_file, 'idCardContainer', 'idCardLink');
      setupFilePreview(app.signature_file, 'signatureContainer', 'signatureLink');

      document.getElementById('detailsModal').classList.remove('hidden');
    }

    function setupFilePreview(fileName, containerId, linkId) {
      const container = document.getElementById(containerId);
      const link = document.getElementById(linkId);

      if (fileName) {
        const filePath = 'uploads/' + fileName;
        link.href = filePath;
        link.style.display = 'inline';

        if (fileName.match(/\.(jpg|jpeg|png)$/i)) {
          container.innerHTML = `<img src="${filePath}" class="max-h-full max-w-full object-contain">`;
        } else {
          container.innerHTML = `<span class="text-slate-400 font-medium">PDF Document</span>`;
        }
      } else {
        container.innerHTML = `<span class="text-slate-300">No file</span>`;
        link.style.display = 'none';
      }
    }

    function closeModal() {
      document.getElementById('detailsModal').classList.add('hidden');
    }
  </script>
</body>
</html>