<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once "../models/ResetPassword.php";
require_once "../helpers/session_helper.php";
require_once "../models/User.php";

//Require PHP Mailer
require_once "../PHPMailer/src/PHPMailer.php";
require_once "../PHPMailer/src/Exception.php";
require_once "../PHPMailer/src/SMTP.php";

class ResetPasswords
{
  private $resetModel;
  private $userModel;
  private $mail;

  public function __construct()
  {
    $this->resetModel = new ResetPassword;
    $this->userModel = new User;
    // Setup PHPMailer
    $this->mail = new PHPMailer();

    $this->mail->CharSet = "utf-8";
    $this->mail->isSMTP();
    $this->mail->Host = 'smtp.gmail.com';
    $this->mail->SMTPAuth = true;
    $this->mail->Port = 465;
    $this->mail->Username = 'chengyi5211314@gmail.com';
    $this->mail->Password = 'lteihgkdpjilxjkd';


    $this->mail->SMTPSecure = "ssl";
    $this->mail->From = "chengyi5211314@gmail.com";
    $this->mail->FromName = "Cheng Yi";
    $this->mail->IsHTML(true);
  }

  public function sendEmail()
  {
    // Sanitize POST Data
    $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

    $usersEmail = trim($_POST['usersEmail']);

    if (empty($usersEmail)) {
      flash("reset", "Please input email");
      redirect("../reset-password.php");
    }

    if (!filter_var($usersEmail, FILTER_VALIDATE_EMAIL)) {
      flash("reset", "Invalid email");
      redirect("../reset-password.php");
    }

    //Will be used to query the user from the database
    $selector = bin2hex(random_bytes(8));
    // Will be used for confirmation once the db entry has been matched
    $token = random_bytes(32);
    $url = 'http://localhost:8000/create-new-password.php?selector=' . $selector . '&validator=' . bin2hex($token);
    // Expiration Date will last for half an hour
    $expires = date("U") + 1800;
    if (!$this->resetModel->deleteEmail($usersEmail)) {
      die('There was an error');
    }
    $hashedToken = password_hash($token, PASSWORD_DEFAULT);
    if (!$this->resetModel->insertToken($usersEmail, $selector, $hashedToken, $expires)) {
      die("There was an error");
    }
    //Can send email now
    $subject = "Reset your password";
    $message = "<h4>We recived a password reset request.</h4>";
    $message .= "<p>Here is your password reset link :</p>";
    $message .= "<a href='" . $url . "'>" . $url . "</a>";

    $this->mail->Subject = $subject;
    $this->mail->Body = $message;
    $this->mail->addAddress($usersEmail);

    $this->mail->send();

    flash("reset", "Check your email", 'form-message form-message-green');
    redirect('../reset-password.php');
  }

  public function resetPassword()
  {
    // Sanitize post data
    $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    $data = [
      'selector' => trim($_POST['selector']),
      'validator' => trim($_POST['validator']),
      'pwd' => trim($_POST['pwd']),
      'pwd-repeat' => trim($_POST['pwd-repeat']),
    ];
    $url = '../create-new-password.php?selector=' . $data['selector'] . '&validator=' . $data['validator'];
    if (empty($_POST['pwd'] || $_POST['pwd-repeat'])) {
      flash("newReset", "Please fill out all fields");
      redirect($url);
    } else if ($data['pwd'] != $data['pwd-repeat']) {
      flash("newReset", "Password do not match");
      redirect($url);
    } else if (strlen($data['pwd']) < 6) {
      flash("newReset", "Password lenght at least 6 characters!");
      redirect($url);
    }

    $currentDate = date("U");
    if (!$row = $this->resetModel->resetPassword($data['selector'], $currentDate)) {
      flash("newReset", "Sorry.The link is no longer valid");
      redirect($url);
    }

    $tokenBin = hex2bin($data['validator']);
    $tokenCheck = password_verify($tokenBin, $row->pwdresettoken);
    if (!$tokenCheck) {
      flash("newReset", "You need to re-Submit your reset request");
      redirect($url);
    }

    $tokenEmail = $row->pwdresetemail;
    if (!$this->userModel->findUserByEmailOrUsername($tokenEmail, $tokenEmail)) {
      flash("newReset", "Wrong E-mail");
      redirect($url);
    }

    $newPwdHash = password_hash($data['pwd'], PASSWORD_DEFAULT);
    if (!$this->userModel->resetPassword($newPwdHash, $tokenEmail)) {
      flash("newReset", "There was an error");
      redirect($url);
    }

    if (!$this->resetModel->deleteEmail($tokenEmail)) {
      flash("newReset", "Sorry.The link is no longer valid");
      redirect($url);
    }

    flash("newReset", "Password Updated", "form-message form-message-green");
    redirect($url);
  }
}
$init = new ResetPasswords();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  switch ($_POST['type']) {
    case 'send':
      $init->sendEmail();
      break;
    case 'reset':
      $init->resetPassword();
      break;
    default:
      header("location : ../index.php");
  }
} else {
  header('location: ../index.php');
}