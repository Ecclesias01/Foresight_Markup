<?php
// 1. DATABASE CONFIGURATION
$db_host = 'localhost';
$db_user = 'foresig2_foresig2';
$db_pass = '+YtCpSbeo{dd34xp';
$db_name = 'foresig2_bankdb';

// Connect to MySQL
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// 2. HELPER: INPUT SANITIZATION
function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data ?? '')));
}

// 3. CAPTURE & SANITIZE FORM INPUTS
$account_type           = sanitize($_POST['account_type']);
$surname                = sanitize($_POST['surname']);
$other_names            = sanitize($_POST['other_names']);
$bvn                    = sanitize($_POST['bvn']);
$nin                    = sanitize($_POST['nin']);
$sex                    = sanitize($_POST['sex']);
$marital_status         = sanitize($_POST['marital_status']);
$dob                    = sanitize($_POST['dob']);
$nationality            = sanitize($_POST['nationality']);
$state_of_origin        = sanitize($_POST['state_of_origin']);
$id_type                = sanitize($_POST['id_type']);
$residential_address    = sanitize($_POST['residential_address']);
$correspondence_address = sanitize($_POST['correspondence_address']);
$mobile                 = sanitize($_POST['mobile']);
$office_phone           = sanitize($_POST['office_phone']);
$email                  = sanitize($_POST['email']);
$occupation             = sanitize($_POST['occupation']);
$income                 = sanitize($_POST['income']);
$employer               = sanitize($_POST['employer']);
$employer_address       = sanitize($_POST['employer_address']);
$spouse_name            = sanitize($_POST['spouse_name']);
$spouse_occupation      = sanitize($_POST['spouse_occupation']);
$spouse_phone           = sanitize($_POST['spouse_phone']);
$nok_surname            = sanitize($_POST['nok_surname']);
$nok_other_names        = sanitize($_POST['nok_other_names']);
$nok_relationship       = sanitize($_POST['nok_relationship']);
$nok_phone              = sanitize($_POST['nok_phone']);
$nok_address            = sanitize($_POST['nok_address']);
$declaration_full_name  = sanitize($_POST['declaration_full_name']);

// 4. FILE UPLOAD HANDLING
$upload_dir = __DIR__ . '/uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$uploaded_files = [];
$allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];

function handle_upload($file_key, $prefix, $upload_dir, $allowed_extensions) {
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES[$file_key]['tmp_name'];
        $file_name = $_FILES[$file_key]['name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed_extensions)) {
            $new_name = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destination = $upload_dir . $new_name;
            if (move_uploaded_file($file_tmp, $destination)) {
                return [
                    'path' => $destination,
                    'name' => $new_name
                ];
            }
        }
    }
    return null;
}

$passport_file  = handle_upload('passport', 'passport', $upload_dir, $allowed_extensions);
$id_card_file   = handle_upload('id_card', 'id_card', $upload_dir, $allowed_extensions);
$signature_file = handle_upload('signature', 'signature', $upload_dir, $allowed_extensions);

$passport_path  = $passport_file['name'] ?? null;
$id_card_path   = $id_card_file['name'] ?? null;
$signature_path = $signature_file['name'] ?? null;

// 5. INSERT INTO MYSQL DATABASE
$stmt = $conn->prepare("INSERT INTO account_applications (
    account_type, surname, other_names, bvn, nin, sex, marital_status, dob, nationality, 
    state_of_origin, id_type, residential_address, correspondence_address, mobile, 
    office_phone, email, occupation, income, employer, employer_address, spouse_name, 
    spouse_occupation, spouse_phone, nok_surname, nok_other_names, nok_relationship, 
    nok_phone, nok_address, declaration_full_name, passport_file, id_card_file, signature_file
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "ssssssssssssssssssssssssssssssss",
    $account_type, $surname, $other_names, $bvn, $nin, $sex, $marital_status, $dob, $nationality,
    $state_of_origin, $id_type, $residential_address, $correspondence_address, $mobile,
    $office_phone, $email, $occupation, $income, $employer, $employer_address, $spouse_name,
    $spouse_occupation, $spouse_phone, $nok_surname, $nok_other_names, $nok_relationship,
    $nok_phone, $nok_address, $declaration_full_name, $passport_path, $id_card_path, $signature_path
);

$db_success = $stmt->execute();
$stmt->close();
$conn->close();

// 6. SEND EMAIL NOTIFICATION WITH ATTACHMENTS
$to      = 'info@foresightmfbltd.com.ng'; // Change to target recipient
$subject = "New Account Application - {$surname} {$other_names} ({$account_type})";
$boundary = md5(time());

// Headers
$headers = "From: Foresight MFB System <no-reply@foresightmfb.com>\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

// HTML Body Construction
$body = "--{$boundary}\r\n";
$body .= "Content-Type: text/html; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$body .= "
<html>
<head><style>body{font-family:Arial,sans-serif;} td{padding:6px;border-bottom:1px solid #ddd;}</style></head>
<body>
  <h2>New Online Account Application</h2>
  <p><strong>Account Type:</strong> {$account_type}</p>
  <h3>Personal Details</h3>
  <table>
    <tr><td><strong>Full Name:</strong></td><td>{$surname} {$other_names}</td></tr>
    <tr><td><strong>BVN / NIN:</strong></td><td>{$bvn} / {$nin}</td></tr>
    <tr><td><strong>Sex / DOB:</strong></td><td>{$sex} / {$dob}</td></tr>
    <tr><td><strong>Phone / Email:</strong></td><td>{$mobile} / {$email}</td></tr>
    <tr><td><strong>ID Type:</strong></td><td>{$id_type}</td></tr>
    <tr><td><strong>Address:</strong></td><td>{$residential_address}</td></tr>
  </table>
  <h3>Next of Kin</h3>
  <table>
    <tr><td><strong>Name:</strong></td><td>{$nok_surname} {$nok_other_names} ({$nok_relationship})</td></tr>
    <tr><td><strong>Phone:</strong></td><td>{$nok_phone}</td></tr>
  </table>
  <br><p>Uploaded documents (Passport, ID Card, Signature) are attached to this email.</p>
</body>
</html>
\r\n";

// Attach Files Function
$attachments = array_filter([$passport_file, $id_card_file, $signature_file]);
foreach ($attachments as $file) {
    if (file_exists($file['path'])) {
        $content = chunk_split(base64_encode(file_get_contents($file['path'])));
        $filename = $file['name'];

        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: application/octet-stream; name=\"{$filename}\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
        $body .= $content . "\r\n";
    }
}
$body .= "--{$boundary}--";

mail($to, $subject, $body, $headers);

// 7. USER SUCCESS FEEDBACK
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Application Submitted</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-4">
  <div class="bg-white p-8 rounded-xl shadow-md border max-w-md w-full text-center">
    <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">✓</div>
    <h1 class="text-xl font-bold text-slate-800 mb-2">Application Submitted!</h1>
    <p class="text-slate-600 text-sm mb-6">Thank you, <strong><?php echo $surname; ?></strong>. Your application for a <strong><?php echo $account_type; ?></strong> has been received successfully.</p>
    <a href="index.html" class="inline-block px-6 py-2.5 bg-blue-600 text-white font-semibold text-sm rounded-lg hover:bg-blue-700 transition">Return to Home</a>
  </div>
</body>
</html>