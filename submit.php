<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration file (stores database and SMTP credentials securely)
require '/home/foresig2/public_html/db_config.php';

// Include PHPMailer classes using your exact folder path
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '/home/foresig2/public_html/phpmailer/Exception.php';
require '/home/foresig2/public_html/phpmailer/PHPMailer.php';
require '/home/foresig2/public_html/phpmailer/SMTP.php';

// Connect to Database using constants from db_config.php
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize and Collect All Text Inputs
    $account_type           = trim($_POST['account_type'] ?? '');
    $surname                = trim($_POST['surname'] ?? '');
    $other_names            = trim($_POST['other_names'] ?? '');
    $sex                    = trim($_POST['sex'] ?? '');
    $marital_status         = trim($_POST['marital_status'] ?? '');
    $dob                    = trim($_POST['dob'] ?? '');
    $nationality            = trim($_POST['nationality'] ?? '');
    $state_of_origin        = trim($_POST['state_of_origin'] ?? '');
    $residential_address    = trim($_POST['residential_address'] ?? '');
    $correspondence_address = trim($_POST['correspondence_address'] ?? '');
    $bvn                    = trim($_POST['bvn'] ?? '');
    $nin                    = trim($_POST['nin'] ?? '');
    $id_type                = trim($_POST['id_type'] ?? '');
    $mobile                 = trim($_POST['mobile'] ?? '');
    $email                  = trim($_POST['email'] ?? '');
    
    // Employment & Income Details
    $occupation             = trim($_POST['occupation'] ?? '');
    $gross_income           = trim($_POST['gross_income'] ?? '');
    $employer_name          = trim($_POST['employer_name'] ?? '');
    $employer_address       = trim($_POST['employer_address'] ?? '');
    $office_phone           = trim($_POST['office_phone'] ?? '');

    // Spouse Details (If Married)
    $spouse_name            = trim($_POST['spouse_name'] ?? '');
    $spouse_occ             = trim($_POST['spouse_occ'] ?? '');
    $spouse_phone           = trim($_POST['spouse_phone'] ?? '');

    // Next of Kin Details
    $nok_surname            = trim($_POST['nok_surname'] ?? '');
    $nok_other_names        = trim($_POST['nok_other_names'] ?? '');
    $nok_relationship       = trim($_POST['nok_relationship'] ?? '');
    $nok_phone              = trim($_POST['nok_phone'] ?? '');
    $nok_address            = trim($_POST['nok_address'] ?? '');

    // 2. Handle File Uploads with Auto-Orientation Support for Portraits
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    function uploadFile($fileKey, $upload_dir) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES[$fileKey]['tmp_name'];
            $fileName = $_FILES[$fileKey]['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $newFileName = $fileKey . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $fileExtension;
            $dest_path = $upload_dir . $newFileName;

            // Auto-correct phone/camera rotation metadata for JPEG passport uploads
            if (in_array($fileExtension, ['jpg', 'jpeg']) && function_exists('exif_read_data')) {
                $exif = @exif_read_data($fileTmpPath);
                if ($exif && isset($exif['Orientation'])) {
                    $image = imagecreatefromjpeg($fileTmpPath);
                    $rotated = false;
                    switch ($exif['Orientation']) {
                        case 3:
                            $image = imagerotate($image, 180, 0);
                            $rotated = true;
                            break;
                        case 6:
                            $image = imagerotate($image, -90, 0);
                            $rotated = true;
                            break;
                        case 8:
                            $image = imagerotate($image, 90, 0);
                            $rotated = true;
                            break;
                    }
                    if ($rotated) {
                        imagejpeg($image, $dest_path);
                        imagedestroy($image);
                        return $newFileName;
                    }
                }
            }

            // Standard upload fallback
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                return $newFileName;
            }
        }
        return null;
    }

    $passport_file  = uploadFile('passport', $upload_dir);
    $id_card_file   = uploadFile('id_card', $upload_dir);
    $signature_file = uploadFile('signature', $upload_dir);

    // 3. Prepare Database Statement
    $sql = "INSERT INTO account_applications (
        account_type, surname, other_names, sex, marital_status, dob, nationality, 
        state_of_origin, residential_address, bvn, nin, id_type, mobile, email, 
        occupation, gross_income, employer_name, employer_address,
        spouse_name, spouse_occ, spouse_phone,
        nok_surname, nok_other_names, nok_relationship, nok_phone, nok_address, 
        passport_file, id_card_file, signature_file, office_phone, correspondence_address
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param(
        "sssssssssssssssssssssssssssssss",
        $account_type, $surname, $other_names, $sex, $marital_status, $dob, $nationality, 
        $state_of_origin, $residential_address, $bvn, $nin, $id_type, $mobile, $email, 
        $occupation, $gross_income, $employer_name, $employer_address,
        $spouse_name, $spouse_occ, $spouse_phone,
        $nok_surname, $nok_other_names, $nok_relationship, $nok_phone, $nok_address, 
        $passport_file, $id_card_file, $signature_file, $office_phone, $correspondence_address
    );

    if ($stmt->execute()) {
        $applicant_name = $surname . " " . $other_names;

        // Reusable SMTP Mail Function using PHPMailer and secure config constants
        function sendSmtpNotification($senderEmail, $senderName, $recipientEmail, $recipientName, $subject, $bodyText, $isHtml = false) {
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'mail.foresightmfbltd.com.ng'; 
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER; 
                $mail->Password   = SMTP_PASS; // Pulled securely from db_config.php
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            
                $mail->Port       = 465;

                // Sender & Reply-To settings
                $mail->setFrom($senderEmail, $senderName);
                $mail->addReplyTo($senderEmail, $senderName);
                
                // Recipient
                $mail->addAddress($recipientEmail, $recipientName);

                // Content
                $mail->isHTML($isHtml);
                $mail->Subject = $subject;
                $mail->Body    = $bodyText;

                $mail->send();
                return true;
            } catch (Exception $e) {
                return false;
            }
        }

        // A. Send Audit Review Email to Customer Service (From noreply)
        $cs_to = "customerservice@foresightmfbltd.com.ng"; 
        $cs_subject = "ACTION REQUIRED: New Account Application - " . $applicant_name;
        
        $cs_message = "Hello Customer Service Team,\n\n";
        $cs_message .= "A new account application has just been submitted on the website portal.\n\n";
        $cs_message .= "----------------------------------------\n";
        $cs_message .= "Applicant Name: " . $applicant_name . "\n";
        $cs_message .= "Account Type: " . $account_type . "\n";
        $cs_message .= "Mobile Number: " . $mobile . "\n";
        $cs_message .= "Email Address: " . $email . "\n";
        $cs_message .= "BVN: " . $bvn . "\n";
        $cs_message .= "----------------------------------------\n\n";
        $cs_message .= "Please log in to the Admin Portal to review the application and print the official audit file:\n";
        $cs_message .= "https://www.foresightmfbltd.com.ng/admin.php\n\n";
        $cs_message .= "Foresight MFB Automated Notification System\n\n";
        $cs_message .= "PLEASE DO NOT RESPOND TO THIS E-MAIL";
        
        sendSmtpNotification(
            'noreply@foresightmfbltd.com.ng', 
            'Foresight MFB System', 
            $cs_to, 
            'Customer Service Team', 
            $cs_subject, 
            $cs_message, 
            false
        );

        // B. Send Confirmation Email to the Applicant (From noreply, with centered footer)
        if (!empty($email)) {
            $client_subject = "Account Application Received - Foresight Microfinance Bank Ltd";
            
            $client_message = "Dear " . $applicant_name . ",<br><br>";
            $client_message .= "Thank you for applying to open a " . $account_type . " with Foresight Microfinance Bank Ltd.<br><br>";
            $client_message .= "We have successfully received your application form. Our customer service team is currently processing your request, and we will contact you shortly once your account has been fully activated.<br><br>";
            $client_message .= "Summary of your application:<br>";
            $client_message .= "- Account Type: " . $account_type . "<br>";
            $client_message .= "- Mobile: " . $mobile . "<br><br>";
            $client_message .= "If you have any questions, please reach out to our support team.<br><br>";
            $client_message .= "Warm regards,<br>";
            $client_message .= "Foresight Microfinance Bank Ltd Team<br>";
            $client_message .= "https://www.foresightmfbltd.com.ng<br><br>";
            $client_message .= "<div style='text-align: center; color: #555; font-size: 12px; margin-top: 20px;'><strong>PLEASE DO NOT RESPOND TO THIS E-MAIL</strong></div>";

            sendSmtpNotification(
                'noreply@foresightmfbltd.com.ng', 
                'Foresight Microfinance Bank', 
                $email, 
                $applicant_name, 
                $client_subject, 
                $client_message, 
                true
            );
        }

        echo "<script>alert('Account application submitted successfully!'); window.location.href='open-account.html';</script>";
        exit;
    } else {
        echo "Error saving application: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: open-account.html");
    exit;
}
?>