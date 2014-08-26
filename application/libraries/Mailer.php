<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
 
class Mailer {
 
    var $mail;
 
    public function __construct()
    {
        require_once('PHPMailer-5.2.8/class.phpmailer.php');
 
        // the true param means it will throw exceptions on errors, which we need to catch
        $this->mail = new PHPMailer(true);
 
        $this->mail->IsSMTP(); // telling the class to use SMTP
        $this->mail->IsHTML(true); //支持html格式内容 
        $this->mail->CharSet = "utf-8";                  // 一定要設定 CharSet 才能正確處理中文
        $this->mail->SMTPDebug  = 1;                     // enables SMTP debug information
        $this->mail->SMTPAuth   = true;  
        // $this->mail->SMTPSecure = "tls";                   // enable SMTP authentication
        $this->mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
        // $this->mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
        // $this->mail->Port       = 465;                   // set the SMTP port for the GMAIL server
        $this->mail->Host       = "smtp.163.com";      // sets GMAIL as the SMTP server
        $this->mail->Port       = 25; 
        $this->mail->Username   = "15680935639@163.com";// GMAIL username
        $this->mail->Password   = "xyf010294214";       // GMAIL password
        $this->mail->AddReplyTo('15680935639@163.com', '破题高手');
        $this->mail->SetFrom('15680935639@163.com', '破题高手');
        // $this->mail->Username   = "xiaoyifei1989@gmail.com";// GMAIL username
        // $this->mail->Password   = "xyf010294214";       // GMAIL password
        // $this->mail->AddReplyTo('xiaoyifei1989@gmail.com', '破题高手');
        // $this->mail->SetFrom('xiaoyifei1989@gmail.com', '破题高手');
    }
 
    public function sendmail($to, $to_name, $subject, $body){
        try{
            $this->mail->AddAddress($to, $to_name);
 
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;
 
            $this->mail->Send();
                echo "Message Sent OK</p>\n";
 
        } catch (phpmailerException $e) {
            echo $e->errorMessage(); //Pretty error messages from PHPMailer
        } catch (Exception $e) {
            echo $e->getMessage(); //Boring error messages from anything else!
        }
    }
}
 
/* End of file mailer.php */
 