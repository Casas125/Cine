<?php
// En un entorno de producción, estos datos deben estar en variables de entorno.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Tu contraseña de MySQL
define('DB_NAME', 'cine_db');

class Database {
    private $conn;

    public function connect() {
        $this->conn = null;
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $this->conn->set_charset('utf8');
        } catch (Exception $e) {
            echo 'Error de conexión: ' . $e->getMessage();
        }
        return $this->conn;
    }
}
?>