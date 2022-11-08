<?php
require_once "../models/User.php";
require_once "../helpers/session_helper.php";


class Users
{
  private $userModal;

  public function __construct()
  {
    $this->userModal = new User;
  }

  public function register()
  {
    //Process Form


    //Sanitize Post Data
    $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

    // Input Data
    $data = [
      'usersName' => trim($_POST['usersName']),
      'usersEmail' => trim($_POST['usersEmail']),
      'usersUid' => trim($_POST['usersUid']),
      'usersPwd' => trim($_POST['usersPwd']),
      'pwdRepeat' => trim($_POST['pwdRepeat']),
    ];

    // VAlidate input
    if (empty($data['usersName']) || empty($data['usersEmail']) || empty($data['usersUid']) || empty($data['usersPwd']) || empty($data['pwdRepeat'])) {
      flash("register", "Please fill out of all inputs!");
      redirect('../signup.php');
    }

    if (!preg_match("/^[a-zA-Z0-9]*$/", $data['usersUid'])) {
      flash("register", "Invalid Username");
      redirect("../signup.php");
    }
    if (!filter_var($data['usersEmail'], FILTER_VALIDATE_EMAIL)) {
      flash("register", "Invalid Email");
      redirect('../signup.php');
    }
    if (strlen($data['usersPwd']) < 6) {
      flash("register", "Invalid Password");
      redirect("../singup.php");
    } else if ($data['usersPwd'] !== $data['pwdRepeat']) {
      flash("register", "Password don't match");
      redirect('../signup.php');
    }

    // User with the same email or username already exists
    if ($this->userModal->findUserByEmailOrUsername($data['usersUid'], $data['usersEmail'])) {
      flash("register", "Username or Email already exists");
      return ('../signup.php');
    }

    // Pass all validation checks 
    // Now going to hash password
    $data['usersPwd'] = password_hash($data['usersPwd'], PASSWORD_DEFAULT);

    // Register User
    if ($this->userModal->register($data)) {
      redirect("../login.php");
    } else {
      die("Something went wrong");
    }
  }

  public function login()
  {
    $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

    $data = [
      'name/email' => trim($_POST['name/email']),
      'usersPwd' => trim($_POST['usersPwd']),
    ];

    if (empty($data['name/email']) || empty($data['usersPwd'])) {
      flash("login", "Please fill out of all inputs");
      header('location: ../login.php');
      exit();
    }

    // Check for user / email
    if ($this->userModal->findUserByEmailOrUsername($data['name/email'], $data['name/email'])) {
      // User found
      $loggedInUser = $this->userModal->login($data['name/email'], $data['usersPwd']);
      if ($loggedInUser) {
        // Create Session
        $this->createUserSession($loggedInUser);
      } else {
        flash("login", "Password Incorrect");
        redirect('../login.php');
      }
    } else {
      flash("login", "No user found");
      redirect('../login.php');
    }
  }

  public function createUserSession($user)
  {
    $_SESSION['usersId'] = $user->user_id;
    $_SESSION['usersName'] = $user->user_name;
    $_SESSION['usersEmail'] = $user->user_email;
    redirect('../index.php');
  }

  public function logout()
  {
    unset($_SESSION['usersId']);
    unset($_SESSION['usersName']);
    unset($_SESSION['usersEmail']);
    session_destroy();
    redirect("../index.php");
  }
}

$init = new Users();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  switch ($_POST['type']) {
    case 'register':
      $init->register();
      break;
    case 'login':
      $init->login();
      break;
    default:
      redirect('../index.php');
  }
} else {
  switch ($_GET['q']) {
    case 'logout':
      $init->logout();
      break;

    default:
      redirect('../index.php');
  }
}
