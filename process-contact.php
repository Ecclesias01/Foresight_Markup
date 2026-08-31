<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuration
require '/home/foresig2/public_html/db_config.php';

// PHPMailer
require '/home/foresig2/public_html/phpmailer/Exception.php';
require '/home/foresig2/public_html/phpmailer/PHPMailer.php';
require '/home/foresig2/public_html/phpmailer/SMTP.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed.'
    ]);

    exit;
}

// Collect form data
$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$service   = trim($_POST['service'] ?? '');
$message   = trim($_POST['message'] ?? '');

// Validate
if (
    empty($firstName) ||
    empty($lastName) ||
    empty($phone) ||
    empty($service) ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'message' => 'Please fill in all required fields correctly.'
    ]);

    exit;
}

$mail = new PHPMailer(true);

try {

    // ==========================================
    // SMTP SETTINGS
    // Same configuration as your WORKING PHP
    // ==========================================

    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;

    // Use the working configuration
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;


    // ==========================================
    // SENDER
    // ==========================================

    $mail->setFrom(
        'noreply@foresightmfbltd.com.ng',
        'Foresight MFB Website'
    );


    // ==========================================
    // REPLY TO CUSTOMER
    // ==========================================

    $mail->addReplyTo(
        $email,
        $firstName . ' ' . $lastName
    );


    // ==========================================
    // CUSTOMER SERVICE RECIPIENT
    // ==========================================

    $mail->addAddress(
        'customerservice@foresightmfbltd.com.ng',
        'Customer Service Team'
    );


    // ==========================================
    // EMAIL CONTENT
    // ==========================================

    $mail->isHTML(true);

    $mail->Subject = 'New Website Message / Application: ' . $service;

    // Escape values before inserting into HTML
    $safeFirstName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
    $safeLastName  = htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8');
    $safeEmail     = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safePhone     = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $safeService   = htmlspecialchars($service, ENT_QUOTES, 'UTF-8');
    $safeMessage   = nl2br(
        htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
    );

    $mail->Body = "
        <div style='font-family:Arial,sans-serif;color:#333;padding:20px;border:1px solid #e2e8f0;border-radius:8px;max-width:600px;margin:0 auto;'>

            <h2 style='color:#0f172a;border-bottom:2px solid #3b82f6;padding-bottom:10px;'>
                New Contact Form Submission
            </h2>

            <p>
                <strong>Name:</strong>
                {$safeFirstName} {$safeLastName}
            </p>

            <p>
                <strong>Email:</strong>
                <a href='mailto:{$safeEmail}'>{$safeEmail}</a>
            </p>

            <p>
                <strong>Phone:</strong>
                {$safePhone}
            </p>

            <p>
                <strong>Service Interested In:</strong>
                <span style='background:#eff6ff;color:#1d4ed8;padding:3px 8px;border-radius:4px;'>
                    {$safeService}
                </span>
            </p>

            <hr style='border:0;border-top:1px solid #e2e8f0;margin:15px 0;'>

            <p>
                <strong>Message / Requirements:</strong>
            </p>

            <p style='background:#f8fafc;padding:12px;border-radius:6px;line-height:1.5;'>
                {$safeMessage}
            </p>

        </div>
    ";

    // Plain-text version
    $mail->AltBody =
        "New Contact Form Submission\n\n" .
        "Name: {$firstName} {$lastName}\n" .
        "Email: {$email}\n" .
        "Phone: {$phone}\n" .
        "Service: {$service}\n\n" .
        "Message:\n{$message}";


    // ==========================================
    // SEND
    // ==========================================

    $mail->send();

    echo json_encode([
        'status' => 'success',
        'message' => 'Message sent successfully!'
    ]);

    exit;

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => 'Mailer Error: ' . $mail->ErrorInfo
    ]);

    exit;
}
?>
