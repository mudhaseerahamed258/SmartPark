<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

function sendOTPEmail($email, $otp)
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // CHANGE THIS
        $mail->Username = 'skmudhaseerahamed2580@gmail.com';

        // CHANGE THIS (Gmail App Password)
        $mail->Password = 'bgda svav hnct aeul';

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('skmudhaseerahamed2580@gmail.com', 'SmartPark Connect');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'SmartPark Password Reset OTP';

        $mail->Body = "
        <h3>SmartPark Connect</h3>
        <p>Your OTP for password reset is:</p>
        <h2>$otp</h2>
        <p>This OTP will expire in 5 minutes.</p>
        ";

        $mail->send();

        return true;

    } catch (Exception $e) {
        return false;
    }
}