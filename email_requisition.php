<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'phpmailer/Exception.php';
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';

function sendEmailRequisition($toEmail, $itemName, $quantity, $dateNeeded, $satelliteName)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'Smiletech4edLab@taytayrizal.gov.ph';
        $mail->Password = 'odml cajk cfil hqah';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('Smiletech4edLab@taytayrizal.gov.ph', 'Municipal Education Office System');
        $mail->addAddress($toEmail);

        // Dynamic Subject na may Satellite Name
        $mail->isHTML(true);
        $mail->Subject = 'MEdO System Notice: New Supplies Requisition - ' . $satelliteName;
        $mail->Body = '
            <div style="font-family: \'Inter\', \'Segoe UI\', Helvetica, Arial, sans-serif; background-color: #f4f7fb; padding: 40px 20px; color: #1e293b;">
                <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);">
                    
                    <div style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: #ffffff; padding: 35px 30px; text-align: center;">
                        <h1 style="margin: 0; font-size: 22px; font-weight: 700;">Municipal Education Office System</h1>
                        <p style="margin: 8px 0 0; font-size: 12px; color: #bfdbfe; text-transform: uppercase; letter-spacing: 2px;">Satellite Office Requisition Alert</p>
                    </div>
                    
                    <div style="padding: 40px 35px;">
                        <h2 style="color: #0f172a; margin-top: 0; font-size: 17px; font-weight: 600; border-bottom: 2px solid #eff6ff; padding-bottom: 12px;">New Supplies Request Submitted</h2>
                        <p style="font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 25px;">
                            A new supplies requisition was submitted by <strong>' . $satelliteName . '</strong> and is pending review.
                        </p>
                        
                        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; padding: 22px; border-radius: 8px; margin-bottom: 30px;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                                <tr>
                                    <td style="padding: 8px 0; color: #64748b; width: 38%;"><strong>Satellite Office:</strong></td>
                                    <td style="padding: 8px 0; color: #1e3a8a; font-weight: 600;">' . $satelliteName . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #64748b;"><strong>Item Description:</strong></td>
                                    <td style="padding: 8px 0; color: #0f172a; font-weight: 600;">' . $itemName . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #64748b;"><strong>Quantity Needed:</strong></td>
                                    <td style="padding: 8px 0; color: #0f172a;">x' . $quantity . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #64748b;"><strong>Date Needed:</strong></td>
                                    <td style="padding: 8px 0; color: #0f172a;">' . $dateNeeded . '</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div style="text-align: center; margin-top: 10px;">
                            <a href="http://localhost/Stti/sat_index.php" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; display: inline-block; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);">Review Requisition Panel</a>
                        </div>
                    </div>
                    
                    <div style="background-color: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
                        <p style="margin: 0;">Automated system message from Municipal Education Office System.</p>
                    </div>
                    
                </div>
            </div>
        ';

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>