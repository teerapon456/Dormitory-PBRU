<?php
require_once 'config.php';

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            $this->connection = new PDO(
                "mysql:host=" . Config::$dbHost . ";dbname=" . Config::$dbName . ";charset=utf8mb4",
                Config::$dbUser,
                Config::$dbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
        }
    }

    // Singleton pattern
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // รับ connection
    public function getConnection()
    {
        return $this->connection;
    }

    // ป้องกันการ clone object
    private function __clone() {}

    // ป้องกันการ unserialize
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    // ฟังก์ชันสำหรับ query ข้อมูล
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
        }
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    // ฟังก์ชันสำหรับดึงข้อมูลแถวเดียว
    public function fetch($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    // ฟังก์ชันสำหรับดึงข้อมูลหลายแถว
    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    // ฟังก์ชันสำหรับเพิ่มข้อมูล
    public function insert($table, $data)
    {
        try {
            $fields = array_keys($data);
            $values = array_fill(0, count($fields), '?');
            $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";

            $stmt = $this->connection->prepare($sql);
            $stmt->execute(array_values($data));
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("เกิดข้อผิดพลาดในการเพิ่มข้อมูล: " . $e->getMessage());
        }
    }

    // ฟังก์ชันสำหรับอัพเดทข้อมูล
    public function update($table, $data, $where, $whereParams = [])
    {
        try {
            $set = array_map(function ($field) {
                return "{$field} = ?";
            }, array_keys($data));

            $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
            $params = array_merge(array_values($data), $whereParams);

            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new Exception("เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $e->getMessage());
        }
    }

    // ฟังก์ชันสำหรับลบข้อมูล
    public function delete($table, $where, $params = [])
    {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new Exception("เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage());
        }
    }

    // ฟังก์ชันสำหรับเริ่ม transaction
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    // ฟังก์ชันสำหรับ commit transaction
    public function commit()
    {
        return $this->connection->commit();
    }

    // ฟังก์ชันสำหรับ rollback transaction
    public function rollBack()
    {
        return $this->connection->rollBack();
    }
}