<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from public view in production
session_start();

require_once __DIR__ . '/db_config.php';

// 1. CONFIGURATION & LOGIN CHECK
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] === ADMIN_USER && $_POST['password'] === ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $login_error = "Invalid username or password.";
    }
}

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
// 2. DATABASE CONNECTION
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Handle Status Updates / Account Activation via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_application_id'])) {
    $app_id = intval($_POST['update_application_id']);
    $new_status = trim($_POST['status'] ?? 'Pending');
    $assigned_acct = trim($_POST['assigned_account_number'] ?? '');
    
    $up_stmt = $conn->prepare("UPDATE account_applications SET status = ?, assigned_account_number = ? WHERE id = ?");
    $up_stmt->bind_param("ssi", $new_status, $assigned_acct, $app_id);
    $up_stmt->execute();
    $up_stmt->close();

    // If status is Approved/Activated and an account number is provided, email the user automatically
    if (($new_status === 'Approved' || $new_status === 'Activated') && !empty($assigned_acct)) {
        // Fetch applicant email & name
        $f_stmt = $conn->prepare("SELECT surname, other_names, email, account_type FROM account_applications WHERE id = ?");
        $f_stmt->bind_param("i", $app_id);
        $f_stmt->execute();
        $res = $f_stmt->get_result()->fetch_assoc();
        $f_stmt->close();

        if ($res && !empty($res['email'])) {
            require_once '/home/foresig2/public_html/phpmailer/Exception.php';
            require_once '/home/foresig2/public_html/phpmailer/PHPMailer.php';
            require_once '/home/foresig2/public_html/phpmailer/SMTP.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'mail.foresightmfbltd.com.ng';
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                $mail->setFrom('noreply@foresightmfbltd.com.ng', 'Foresight Microfinance Bank');
                $mail->addAddress($res['email'], $res['surname'] . ' ' . $res['other_names']);
                $mail->isHTML(true);
                $mail->Subject = "Congratulations! Your " . $res['account_type'] . " has been Activated";
                
                $mailBody = "Dear " . $res['surname'] . " " . $res['other_names'] . ",<br><br>";
                $mailBody .= "We are pleased to inform you that your application for a <strong>" . $res['account_type'] . "</strong> with Foresight Microfinance Bank Ltd has been successfully processed and approved.<br><br>";
                $mailBody .= "Your New Account Number is: <h2 style='color: #1d4ed8; font-family: monospace;'>" . $assigned_acct . "</h2><br>";
                $mailBody .= "You can now fund your account and begin enjoying our banking services. Welcome to the Foresight family!<br><br>";
                $mailBody .= "Warm regards,<br><strong>Foresight MFB Team</strong><br>";
                $mailBody .= "<div style='text-align: center; color: #555; font-size: 12px; margin-top: 20px;'><strong>PLEASE DO NOT RESPOND TO THIS E-MAIL</strong></div>";

                $mail->Body = $mailBody;
                $mail->send();
            } catch (Exception $e) {
                // Mail logging failure can be ignored or handled silently
            }
        }
    }

    header("Location: admin.php?updated=1");
    exit;
}

// Fetch Search, Filter Parameters
$search = trim($_GET['search'] ?? '');
$filter_type = trim($_GET['account_type'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

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

if ($filter_status !== '') {
    $query .= " AND status = ?";
    array_push($params, $filter_status);
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
$total_apps = count($applications);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Account Applications</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
  @media print {
    body * { visibility: hidden !important; }
    #printableArea, #printableArea * { visibility: visible !important; }
    html, body { height: auto !important; overflow: visible !important; background: white !important; }
    #detailsModal { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; background: white !important; display: block !important; z-index: 9999 !important; }
    #printableArea { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; margin: 0 !important; padding: 10px !important; }
  }
  </style>
</head>
<body class="bg-slate-100 min-h-screen">

  <!-- Navigation Bar -->
  <header class="bg-slate-900 text-white px-6 py-4 flex justify-between items-center shadow-md print:hidden">
    <div>
      <h1 class="text-lg font-bold tracking-tight">Foresight Microfinance Bank Ltd.</h1>
      <p class="text-xs text-slate-400">Account Application Processing Portal</p>
    </div>
    <a href="admin.php?action=logout" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded font-semibold transition">Logout</a>
  </header>

  <main class="max-w-7xl mx-auto py-8 px-4 print:p-0">
    
    <?php if (isset($_GET['updated'])): ?>
      <div class="bg-green-50 border border-green-200 text-green-700 text-xs p-3 rounded-lg mb-6 flex justify-between items-center">
        <span>Application status updated successfully!</span>
        <a href="admin.php" class="font-bold underline">Dismiss</a>
      </div>
    <?php endif; ?>

    <!-- Top Bar: Filters and Stats -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4 print:hidden">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Applications Management</h2>
        <p class="text-xs text-slate-500">Total Found: <span class="font-bold text-slate-700"><?php echo $total_apps; ?></span></p>
      </div>

      <!-- Search & Filter Form -->
      <form method="GET" class="flex flex-wrap items-center gap-2 w-full md:w-auto">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, BVN, NIN..." class="text-xs p-2.5 border rounded-lg bg-white w-full md:w-48 focus:ring-2 focus:ring-blue-500 outline-none">
        
        <select name="account_type" class="text-xs p-2.5 border rounded-lg bg-white outline-none">
          <option value="">All Account Types</option>
          <option value="Savings Account" <?php echo $filter_type === 'Savings Account' ? 'selected' : ''; ?>>Savings Account</option>
          <option value="Personal Banking" <?php echo $filter_type === 'Personal Banking' ? 'selected' : ''; ?>>Personal Banking</option>
          <option value="Cooperative Banking" <?php echo $filter_type === 'Cooperative Banking' ? 'selected' : ''; ?>>Cooperative Banking</option>
          <option value="Current Account" <?php echo $filter_type === 'Current Account' ? 'selected' : ''; ?>>Current Account</option>
        </select>

        <select name="status" class="text-xs p-2.5 border rounded-lg bg-white outline-none">
          <option value="">All Statuses</option>
          <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
          <option value="Under Review" <?php echo $filter_status === 'Under Review' ? 'selected' : ''; ?>>Under Review</option>
          <option value="Approved" <?php echo $filter_status === 'Approved' ? 'selected' : ''; ?>>Approved</option>
          <option value="Activated" <?php echo $filter_status === 'Activated' ? 'selected' : ''; ?>>Activated</option>
          <option value="Rejected" <?php echo $filter_status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
        </select>

        <button type="submit" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-lg transition">Filter</button>
        <a href="admin.php" class="px-3 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs rounded-lg transition">Reset</a>
      </form>
    </div>

    <!-- Applications Table -->
    <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden print:hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-bold uppercase tracking-wider">
              <th class="p-4">Applicant Name</th>
              <th class="p-4">Account Type</th>
              <th class="p-4">Status</th>
              <th class="p-4">BVN / Contact</th>
              <th class="p-4">Submitted</th>
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
                    <?php if(!empty($app['assigned_account_number'])): ?>
                      <div class="text-[10px] font-mono text-blue-600">Acct: <?php echo htmlspecialchars($app['assigned_account_number']); ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="p-4">
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border">
                      <?php echo htmlspecialchars($app['account_type']); ?>
                    </span>
                  </td>
                  <td class="p-4">
                    <?php 
                      $statusColor = 'bg-amber-50 text-amber-700 border-amber-200';
                      if($app['status'] === 'Approved' || $app['status'] === 'Activated') $statusColor = 'bg-green-50 text-green-700 border-green-200';
                      if($app['status'] === 'Rejected') $statusColor = 'bg-red-50 text-red-700 border-red-200';
                    ?>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border <?php echo $statusColor; ?>">
                      <?php echo htmlspecialchars($app['status'] ?? 'Pending'); ?>
                    </span>
                  </td>
                  <td class="p-4">
                    <div>BVN: <span class="font-mono text-slate-600"><?php echo htmlspecialchars($app['bvn']); ?></span></div>
                    <div class="text-slate-400"><?php echo htmlspecialchars($app['mobile']); ?></div>
                  </td>
                  <td class="p-4 text-slate-500">
                    <?php echo date('M d, Y', strtotime($app['submitted_at'])); ?>
                  </td>
                  <td class="p-4 text-center">
                    <button onclick='openApplicantModal(<?php echo json_encode($app); ?>)' class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white font-semibold rounded text-xs transition">
                      Review & Update
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

  <!-- APPLICANT DETAILS & STATUS MODAL -->
  <div id="detailsModal" class="fixed inset-0 bg-slate-900 bg-opacity-75 flex items-center justify-center p-4 hidden z-50 print:relative print:inset-auto print:bg-white print:p-0 print:block">
    <div class="bg-white rounded-xl max-w-4xl w-full max-h-[95vh] flex flex-col shadow-2xl overflow-hidden border print:max-h-none print:shadow-none print:border-none">
      
      <!-- Modal Header Bar -->
      <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center print:hidden">
        <div>
          <h3 class="text-base font-bold">Foresight Microfinance Bank Ltd - Application Review & Status Portal</h3>
        </div>
        <button onclick="closeModal()" class="text-slate-300 hover:text-white text-xl font-bold">&times;</button>
      </div>

      <!-- Status Update Toolbar (Non-printable) -->
      <form method="POST" class="bg-slate-100 px-6 py-3 border-b flex flex-wrap items-center justify-between gap-3 print:hidden">
        <input type="hidden" name="update_application_id" id="modalAppIdInput">
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold text-slate-700">Application Status:</span>
          <select name="status" id="modalStatusSelect" class="text-xs p-1.5 border rounded bg-white font-semibold outline-none">
            <option value="Pending">Pending</option>
            <option value="Under Review">Under Review</option>
            <option value="Approved">Approved</option>
            <option value="Activated">Activated</option>
            <option value="Rejected">Rejected</option>
          </select>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold text-slate-700">Account Number:</span>
          <input type="text" name="assigned_account_number" id="modalAcctInput" placeholder="Enter Account No." class="text-xs p-1.5 border rounded bg-white font-mono w-36 outline-none">
        </div>
        <button type="submit" class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white font-bold text-xs rounded transition shadow">
          Save Status & Email Customer
        </button>
      </form>

      <!-- Modal Body Content Container -->
      <div id="printableArea" class="p-8 overflow-y-auto space-y-6 text-xs text-slate-800 bg-white flex-1">
        
        <!-- Header Branding -->
        <div class="border-b-2 border-slate-900 pb-4 flex justify-between items-start gap-4">
          <div class="space-y-3 flex-1">
            <div>
              <h1 class="text-base font-black uppercase tracking-tight text-slate-900">Foresight Microfinance Bank Ltd.</h1>
              <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">ACCOUNT OPENING FORM</p>
            </div>
            <div>
              <span class="text-[10px] text-slate-500 font-bold block mb-1">ACCOUNT TYPE:</span>
              <span class="inline-block px-3 py-1 bg-slate-100 border border-slate-300 font-bold text-xs uppercase rounded text-blue-800" id="formAccountType"></span>
              <span class="text-[10px] text-slate-400 ml-3">Ref ID: <span id="formAppId"></span></span>
            </div>
          </div>

          <!-- Passport Photograph -->
          <div class="border-2 border-slate-300 p-1.5 rounded bg-slate-50 text-center w-32 shadow-sm shrink-0">
            <span class="block font-bold text-[9px] text-slate-500 mb-1 uppercase">Passport Photo</span>
            <div id="passportContainer" class="h-32 w-full flex items-center justify-center overflow-hidden rounded bg-white border mb-1"></div>
            <a id="passportLink" target="_blank" class="text-blue-600 font-semibold text-[9px] hover:underline block print:hidden">Open Full</a>
          </div>
        </div>

        <!-- Section A: Personal Details -->
        <div class="space-y-3">
          <h4 class="font-bold text-white bg-slate-800 px-3 py-1.5 uppercase tracking-wider text-[11px]">(A.) PERSONAL DETAILS</h4>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-slate-50 p-3.5 border rounded">
            <div><span class="text-slate-500 block text-[10px] font-semibold">SURNAME:</span> <strong id="formSurname" class="text-slate-900 text-sm"></strong></div>
            <div class="md:col-span-2"><span class="text-slate-500 block text-[10px] font-semibold">OTHER NAMES:</span> <strong id="formOtherNames" class="text-slate-900 text-sm"></strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">SEX:</span> <strong id="formSex"></strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">BVN:</span> <strong id="formBvn" class="font-mono"></strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">NIN:</span> <strong id="formNin" class="font-mono"></strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">MARITAL STATUS:</span> <strong id="formMarital"></strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">DATE OF BIRTH:</span> <strong id="formDob"></strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">NATIONALITY:</span> <strong id="formNationality"></strong></div>
            <div class="md:col-span-3"><span class="text-slate-500 block text-[10px] font-semibold">STATE OF ORIGIN:</span> <strong id="formState"></strong></div>
          </div>
          
          <div class="bg-slate-50 p-3.5 border rounded space-y-3">
            <div>
              <span class="text-slate-500 block text-[10px] font-semibold mb-1.5">MEANS OF IDENTIFICATION:</span> 
              <div class="flex flex-wrap items-center gap-6 font-semibold text-slate-800 text-xs">
                <span id="idTypeIntl" class="px-2.5 py-1 border rounded bg-white">Int'l Passport [ ]</span>
                <span id="idTypeDriver" class="px-2.5 py-1 border rounded bg-white">Driver's Licence [ ]</span>
                <span id="idTypeOthers" class="px-2.5 py-1 border rounded bg-white">Others [ ]</span>
              </div>
            </div>

            <div class="pt-2 border-t flex items-center justify-between gap-4">
              <div>
                <span class="text-slate-500 block text-[10px] font-semibold">ATTACHED ID DOCUMENT FILE:</span>
                <a id="idCardLink" target="_blank" class="inline-block mt-1 px-3 py-1 bg-blue-50 text-blue-700 border border-blue-200 font-bold hover:bg-blue-100 rounded text-xs transition">
                  Download / View Full ID Document
                </a>
              </div>
              <div id="idCardContainer" class="h-20 w-32 flex items-center justify-center overflow-hidden rounded border bg-white shadow-sm shrink-0"></div>
            </div>

            <div><span class="text-slate-500 block text-[10px] font-semibold mt-2">RESIDENTIAL ADDRESS:</span> <span id="formAddress" class="font-medium text-slate-900 text-sm"></span></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">CORRESPONDENCE ADDRESS:</span> <span id="formCorrAddress" class="font-medium text-slate-900 text-sm"></span></div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-slate-50 p-3.5 border rounded">
            <div><span class="text-slate-500 block text-[10px] font-semibold">MOBILE PHONE:</span> <strong id="formMobile"></strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">OFFICE PHONE:</span> <strong id="formOfficePhone">N/A</strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">EMAIL ADDRESS:</span> <strong id="formEmail"></strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">OCCUPATION / PROFESSION:</span> <strong id="formOccupation"></strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">GROSS ANNUAL INCOME:</span> <strong id="formIncome"></strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">NAME OF EMPLOYER:</span> <strong id="formEmployer"></strong></div>
            <div class="md:col-span-3"><span class="text-slate-500 block text-[10px] font-semibold">ADDRESS OF EMPLOYER:</span> <strong id="formEmployerAddress"></strong></div>
          </div>
        </div>

        <!-- Section B: If Married -->
        <div class="space-y-3">
          <h4 class="font-bold text-white bg-slate-800 px-3 py-1.5 uppercase tracking-wider text-[11px]">(B.) IF MARRIED</h4>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-slate-50 p-3.5 border rounded">
            <div><span class="text-slate-500 block text-[10px] font-semibold">NAME OF SPOUSE:</span> <strong id="formSpouseName">N/A</strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">OCCUPATION / PROFESSION:</span> <strong id="formSpouseOcc">N/A</strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">TELEPHONE:</span> <strong id="formSpousePhone">N/A</strong></div>
          </div>
        </div>

        <!-- Section C: Next of Kin -->
        <div class="space-y-3">
          <h4 class="font-bold text-white bg-slate-800 px-3 py-1.5 uppercase tracking-wider text-[11px]">(C.) NEXT OF KIN</h4>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-slate-50 p-3.5 border rounded">
            <div><span class="text-slate-500 block text-[10px] font-semibold">SURNAME:</span> <strong id="formNokSurname"></strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">OTHER NAMES:</span> <strong id="formNokOtherNames"></strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">RELATIONSHIP:</span> <strong id="formNokRel"></strong></div>
            <div><span class="text-slate-500 block text-[10px] font-semibold">TELEPHONE:</span> <strong id="formNokPhone"></strong></div>
            <div class="md:col-span-2"><span class="text-slate-500 block text-[10px] font-semibold">CONTACT ADDRESS:</span> <strong id="formNokAddress"></strong></div>
          </div>
        </div>

        <!-- Declaration & Signatures -->
        <div class="bg-slate-50 p-4 border rounded space-y-4">
          <h4 class="font-bold text-slate-900 uppercase tracking-wider text-[11px]">DECLARATION</h4>
          <p class="text-xs text-slate-600 italic">"I/We wish to open an account and confirm that I/We have read and understand the rule and regulations of operating the scheme."</p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-3 border-t">
            <div>
              <span class="text-slate-500 block text-[10px] font-semibold">Declared By:</span> 
              <strong id="formDeclaredBy" class="text-slate-900 text-sm"></strong>
            </div>
            <div>
              <span class="text-slate-500 block text-[10px] font-semibold mb-1">Customer Signature:</span>
              <div id="formSignaturePreview" class="h-20 flex items-center justify-center border bg-white rounded p-1 mb-1"></div>
              <a id="signatureLink" target="_blank" class="text-blue-600 font-bold hover:underline text-[10px] print:hidden">Open Full Signature File</a>
            </div>
          </div>
        </div>

        <!-- FOR BANK USE ONLY -->
        <div class="border-2 border-slate-300 p-4 rounded bg-slate-50 space-y-4">
          <h4 class="font-bold text-slate-900 uppercase tracking-wider text-[11px] border-b pb-1.5">FOR BANK USE ONLY</h4>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-xs">
            <div><span class="text-slate-500 block font-semibold">Account Opening Officer:</span> <div class="border-b border-slate-400 h-6 mt-1"></div></div>
            <div><span class="text-slate-500 block font-semibold">CSO:</span> <div class="border-b border-slate-400 h-6 mt-1"></div></div>
            <div><span class="text-slate-500 block font-semibold">BOM's Approval:</span> <div class="border-b border-slate-400 h-6 mt-1"></div></div>
            <div><span class="text-slate-500 block font-semibold">Opening Amount:</span> <div class="border-b border-slate-400 h-6 mt-1"></div></div>
            <div><span class="text-slate-500 block font-semibold">TRANS. I.D.:</span> <div class="border-b border-slate-400 h-6 mt-1"></div></div>
            <div><span class="text-slate-500 block font-semibold">Acct. No.:</span> <div class="border-b border-slate-400 h-6 mt-1 font-bold text-blue-700" id="formPrintedAcctNo"></div></div>
            <div class="md:col-span-3"><span class="text-slate-500 block font-semibold">Sign / Date:</span> <div class="border-b border-slate-400 h-8 mt-1"></div></div>
          </div>
        </div>

      </div>

      <!-- Modal Footer Controls -->
      <div class="px-6 py-3 bg-slate-100 border-t flex justify-between items-center print:hidden">
        <button onclick="window.print()" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs transition flex items-center gap-2 shadow">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
          Print Continuous Form / Save PDF
        </button>
        <button onclick="closeModal()" class="px-5 py-2 bg-slate-700 hover:bg-slate-800 text-white font-semibold rounded-lg text-xs transition">Close Window</button>
      </div>
    </div>
  </div>

  <script>
    function openApplicantModal(app) {
      document.getElementById('modalAppIdInput').value = app.id;
      document.getElementById('modalStatusSelect').value = app.status || 'Pending';
      document.getElementById('modalAcctInput').value = app.assigned_account_number || '';
      document.getElementById('formPrintedAcctNo').innerText = app.assigned_account_number || '';

      document.getElementById('formAppId').innerText = '#' + app.id;
      document.getElementById('formAccountType').innerText = app.account_type;
      document.getElementById('formSurname').innerText = app.surname;
      document.getElementById('formOtherNames').innerText = app.other_names;
      document.getElementById('formSex').innerText = app.sex;
      document.getElementById('formBvn').innerText = app.bvn;
      document.getElementById('formNin').innerText = app.nin;
      document.getElementById('formMarital').innerText = app.marital_status || 'N/A';
      document.getElementById('formDob').innerText = app.dob;
      document.getElementById('formNationality').innerText = app.nationality;
      document.getElementById('formState').innerText = app.state_of_origin || 'N/A';
      
      const idType = (app.id_type || '').toLowerCase();
      document.getElementById('idTypeIntl').innerText = "Int'l Passport " + (idType.includes('passport') ? '[X]' : '[ ]');
      document.getElementById('idTypeDriver').innerText = "Driver's Licence " + (idType.includes('driver') ? '[X]' : '[ ]');
      document.getElementById('idTypeOthers').innerText = "Others " + (!idType.includes('passport') && !idType.includes('driver') ? '[X]' : '[ ]');

      document.getElementById('formAddress').innerText = app.residential_address;
      document.getElementById('formCorrAddress').innerText = app.correspondence_address || app.residential_address;
      document.getElementById('formMobile').innerText = app.mobile;
      document.getElementById('formEmail').innerText = app.email;

      document.getElementById('formOccupation').innerText = app.occupation || 'N/A';
      document.getElementById('formIncome').innerText = app.gross_income || 'N/A';
      document.getElementById('formEmployer').innerText = app.employer_name || 'N/A';
      document.getElementById('formEmployerAddress').innerText = app.employer_address || 'N/A';
      document.getElementById('formOfficePhone').innerText = app.office_phone || 'N/A';

      document.getElementById('formSpouseName').innerText = app.spouse_name || 'N/A';
      document.getElementById('formSpouseOcc').innerText = app.spouse_occ || 'N/A';
      document.getElementById('formSpousePhone').innerText = app.spouse_phone || 'N/A';

      document.getElementById('formNokSurname').innerText = app.nok_surname;
      document.getElementById('formNokOtherNames').innerText = app.nok_other_names;
      document.getElementById('formNokRel').innerText = app.nok_relationship;
      document.getElementById('formNokPhone').innerText = app.nok_phone;
      document.getElementById('formNokAddress').innerText = app.nok_address || 'N/A';

      document.getElementById('formDeclaredBy').innerText = app.surname + ' ' + app.other_names;

      setupFilePreview(app.passport_file, 'passportContainer', 'passportLink');
      setupFilePreview(app.id_card_file, 'idCardContainer', 'idCardLink');
      setupSignaturePreview(app.signature_file, 'formSignaturePreview', 'signatureLink');

      document.getElementById('detailsModal').classList.remove('hidden');
    }

    function setupFilePreview(fileName, containerId, linkId) {
      const container = document.getElementById(containerId);
      const link = document.getElementById(linkId);

      if (fileName && fileName.trim() !== "") {
        const filePath = 'uploads/' + fileName;
        if (link) {
          link.href = filePath;
          link.style.display = (linkId === 'idCardLink') ? 'inline-block' : 'inline';
        }
        if (fileName.match(/\.(jpg|jpeg|png)$/i)) {
          container.innerHTML = `<img src="${filePath}" class="max-h-full max-w-full object-contain">`;
        } else {
          container.innerHTML = `<span class="text-slate-500 font-semibold text-[10px]">Document File (PDF)</span>`;
        }
      } else {
        container.innerHTML = `<span class="text-slate-400 text-[10px]">Not Provided</span>`;
        if (link) link.style.display = 'none';
      }
    }

    function setupSignaturePreview(fileName, containerId, linkId) {
      const container = document.getElementById(containerId);
      const link = document.getElementById(linkId);

      if (fileName && fileName.trim() !== "") {
        const filePath = 'uploads/' + fileName;
        if (link) {
          link.href = filePath;
          link.style.display = 'inline';
        }
        if (fileName.match(/\.(jpg|jpeg|png)$/i)) {
          container.innerHTML = `<img src="${filePath}" class="max-h-full max-w-full object-contain">`;
        } else {
          container.innerHTML = `<span class="text-slate-500 text-[10px]">Signature File</span>`;
        }
      } else {
        container.innerHTML = `<span class="text-slate-400 text-[10px]">No Signature</span>`;
        if (link) link.style.display = 'none';
      }
    }

    function closeModal() {
      document.getElementById('detailsModal').classList.add('hidden');
    }
  </script>
</body>
</html>