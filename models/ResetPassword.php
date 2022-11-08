<?php
require_once "../libraries/database.php";
class ResetPassword
{
  private $db;

  public function __construct()
  {
    $this->db = new Database;
  }

  public function deleteEmail($email)
  {
    $this->db->query("DELETE FROM pwdreset where pwdresetemail=:email");
    $this->db->bind(':email', $email);

    if ($this->db->execute()) {
      return true;
    } else {
      return false;
    }
  }

  public function insertToken($email, $selector, $hashedToken, $expires)
  {
    $this->db->query('INSERT INTO pwdreset (pwdresetemail,pwdresetselector,pwdresettoken,pwdresetexpires) VALUES (:email,:selector,:token,:expires)');
    $this->db->bind(':email', $email);
    $this->db->bind(':selector', $selector);
    $this->db->bind(':token', $hashedToken);
    $this->db->bind(':expires', $expires);
    // Execute
    if ($this->db->execute()) {
      return true;
    } else {
      return false;
    }
  }

  public function resetPassword($selector, $currentDate)
  {
    $this->db->query("SELECT * FROM pwdreset where pwdresetselector=:selector AND pwdresetexpires >= :currentDate");
    $this->db->bind(':selector', $selector);
    $this->db->bind(':currentDate', $currentDate);

    //Execute
    $row = $this->db->single();

    // Check row
    if ($this->db->rowCount() > 0) {
      return $row;
    } else {
      return false;
    }
  }
}
