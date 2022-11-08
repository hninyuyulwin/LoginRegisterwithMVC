<?php
require_once '../libraries/database.php';

class User
{
  private $db;
  public function __construct()
  {
    $this->db = new Database();
  }

  // Find user by email or username
  public function findUserByEmailOrUsername($email, $username)
  {
    $this->db->query("SELECT * FROM users WHERE user_uid=:username OR user_email = :email");
    $this->db->bind(':username', $username);
    $this->db->bind(':email', $email);

    $row = $this->db->single();

    //Check row
    if ($this->db->rowCount() > 0) {
      return $row;
    } else {
      return false;
    }
  }

  //Register User
  public function register($data)
  {
    $this->db->query('INSERT INTO users (user_name,user_email,user_uid,user_pwd) VALUES (:name,:email,:Uid,:password)');

    // Bind Values
    $this->db->bind(':name', $data['usersName']);
    $this->db->bind(':email', $data['usersEmail']);
    $this->db->bind(':Uid', $data['usersUid']);
    $this->db->bind(':password', $data['usersPwd']);

    //Execute
    if ($this->db->execute()) {
      return true;
    } else {
      return false;
    }
  }

  // Login User
  public function login($nameOrEmail, $password)
  {
    $row = $this->findUserByEmailOrUsername($nameOrEmail, $nameOrEmail);

    if ($row == false) return false;

    $hashedPassword = $row->user_pwd;
    if (password_verify($password, $hashedPassword)) {
      return $row;
    } else {
      return false;
    }
  }

  //Reset password
  public function resetPassword($newPwdHash, $tokenEmail)
  {
    $this->db->query('UPDATE users SET user_pwd=:pwd where user_email=:email');
    $this->db->bind(':pwd', $newPwdHash);
    $this->db->bind(':email', $tokenEmail);

    //Execute
    if ($this->db->execute()) {
      return true;
    } else {
      return false;
    }
  }
}
