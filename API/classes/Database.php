<?php

require '../App/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable('../App');
$dotenv->load();

class Database
{
    private static $host;
    private static $dbName;
    private static $username;
    private static $password;

    public function __construct()
    {
        self::$host = $_ENV['DB_HOST'];
        self::$dbName = $_ENV['DB_DATABASE'];
        self::$username = $_ENV['DB_USER'];
        self::$password = $_ENV['DB_PASS'];
    }

    private static function connect()
    {
        try {
            $dataSourceName = 'mysql:host='.self::$host.';dbname='.self::$dbName;
            $pdo = new \PDO($dataSourceName, self::$username, self::$password);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            return $pdo;
        } catch (\PDOException $e) {
            echo 'Connection failed'.$e->getMessage();
        }
    }

    public static function GET($query, $params)
    {
        $stmt = self::connect()->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        return $data;
    }

    public static function getTransactions($params = array())
    {
        $query = 'SELECT v.id, t.from_account, t.from_amount, t.from_currency, t.date, v.firstName, v.lastName, t.to_account, t.to_currency, t.to_amount FROM transactions t
                    INNER JOIN vw_users v ON t.from_account = v.account_id
                WHERE t.from_account = ? OR t.to_account = ? ORDER BY t.date DESC';
        $data = self::GET($query, $params);

        return $data;
    }

    public static function getBalance($params = array())
    {
        try {
            $query = 'SELECT v.balance, a.currency FROM vw_users v
                        INNER JOIN account a ON  a.id = v.account_id 
                    WHERE v.id = ?';
            $data = self::GET($query, $params);
            if (!empty($data)) {
                return $data;
            } else {
                throw new Exception('Balance not found.');
            }
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public static function getReceivers($params = array())
    {
        $query = 'SELECT firstName, lastName, username, mobilephone FROM vw_users';
        $data = self::GET($query, $params);

        return $data;
    }

    public static function getAccount($params = array())
    {
        $query = 'SELECT account_id, currency, mobilephone FROM vw_users v 
                    INNER JOIN account a ON a.id = v.account_id 
                WHERE username = ?';
        $data = self::GET($query, $params);

        return $data[0];
    }

    public static function setTransaction($params = array())
    {
        $query = 'INSERT INTO transactions (from_amount, from_account, from_currency, to_amount, to_account, to_currency, currency_rate)
                    VALUES (?,?,?,?,?,?,?)';

        $stmt = self::connect()->prepare($query);
        $stmt->execute($params);
    }
}
