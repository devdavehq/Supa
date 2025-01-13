<?php

namespace SUPA\mailer;


require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.example.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'your_email@example.com';
        $this->mail->Password = 'your_password';
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
    }

    public function to($recipient) {
        $this->mail->addAddress($recipient);
        return $this;
    }

    public function from($email, $name = null) {
        $this->mail->setFrom($email, $name);
        return $this;
    }

    public function subject($subject) {
        $this->mail->Subject = $subject;
        return $this;
    }

    public function body($htmlBody, $plainBody = null) {
        $this->mail->isHTML(true);
        $this->mail->Body = $htmlBody;
        if ($plainBody) {
            $this->mail->AltBody = $plainBody;
        }
        return $this;
    }

    public function attach($filePath) {
        $this->mail->addAttachment($filePath);
        return $this;
    }

    public function send() {
        try {
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            return $this->mail->ErrorInfo;
        }
    }
}




//  usage

// require 'vendor/autoload.php'; // Ensure PHPMailer is autoloaded

// $mailer = new Mailer();

// $result = $mailer->to('recipient@example.com')
//                  ->from('no-reply@example.com', 'Your Company')
//                  ->subject('Welcome to Our Platform')
//                  ->body('<h1>Hello!</h1><p>Thank you for signing up.</p>', 'Hello! Thank you for signing up.')
//                  ->attach('/path/to/file.pdf') // Optional
//                  ->send();

// if ($result === true) {
//     echo "Email sent successfully!";
// } else {
//     echo "Failed to send email: " . $result;
// }
