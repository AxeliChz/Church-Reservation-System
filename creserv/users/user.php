<?php
require_once "../config/db.php";

class User {
    public $id;
    public $email;
    public $username;
    public $password;
    protected $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function addUser() {
        $sql = "INSERT INTO users (email, username, password) VALUES (:email, :username, :password)";
        $stmt = $this->db->connect()->prepare($sql);
        return $stmt->execute([
            ':email' => $this->email,
            ':username' => $this->username,
            ':password' => $this->password
        ]);
    }

    public function login($email, $password) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) return $user;
        return false;
    }
}
?>
