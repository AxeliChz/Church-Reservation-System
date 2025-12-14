<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Make sure you install PHPMailer via Composer


function sendEmail($to, $subject, $body, $isHTML = false) {
    $mail = new PHPMailer(true);
    
    try {
       
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Change to your SMTP host
        $mail->SMTPAuth   = true;
        $mail->Username   = 'joy.marvie129@gmail.com'; 
        $mail->Password   = 'qapg fnjr mekm krin'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
      
        $mail->setFrom('joy.marvie129@gmail.com', 'Church Reservation System');
        $mail->addAddress($to);
        
       
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        if (!$isHTML) {
            $mail->AltBody = $body;
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}


function sendNotificationEmail($userEmail, $userName, $eventName, $status) {
    $subject = "Update on Your Change Request - $eventName";
    
    $statusMessage = $status === 'approved' 
        ? "has been approved" 
        : "has been rejected";
    
    $body = "Dear $userName,

Your change request for the event '$eventName' $statusMessage.

If you have any questions, please contact the church administration.

Best regards,
Church Administration Team";
    
    return sendEmail($userEmail, $subject, $body);
}


function sendEventConfirmation($userEmail, $userName, $eventName, $eventDate, $startTime) {
    $subject = "Event Confirmation - $eventName";
    
    $body = "Dear $userName,

Your event reservation has been confirmed!

Event Details:
━━━━━━━━━━━━━━━━━━
Event: $eventName
Date: $eventDate
Time: $startTime

Thank you for using our Church Reservation System.

Best regards,
Church Administration Team";
    
    return sendEmail($userEmail, $subject, $body);
}
?>
