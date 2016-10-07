<?php

namespace Models;

class DB
{
    /**
     * Екземпляр даного класу
     * @var object
     */
    private static $_instance;

    /**
     * Об’єкт PDO
     * @var \PDO
     */
    private $dbh;

    /**
     * Приватний метод. Створює об’єкт PDO
     * return $this
     */
    private function __construct()
    {
        list($host, $db, $user, $pass) = explode(";", getenv('MYSQLCONNSTR_MyClientDB'));

        try {
            $this->dbh = new \PDO("mysql:host=$host;dbname=$db", $user, $pass);
//            echo "Connected<p>";
        } catch (Exception $e) {
            echo "Unable to connect: " . $e->getMessage() ."<p>";
        }

        return $this;
    }

    /**
     * Заборонаєм клонування
     */
    private function __clone() {}

    /**
     * Дозволяє сторити лише 1 екземпляр даного класу, повертає створений екземпляр даного класу
     * @return PDO
     */
    public static function getInstance()
    {
        // проверяем актуальность экземпляра
        if (null === self::$_instance) {
            // создаем новый экземпляр
            self::$_instance = new self();
        }
        // возвращаем созданный или существующий экземпляр
        return self::$_instance;
    }

    /**
     * Гет об’єкт PDO
     * @return \PDO
     */
    public function getConnect()
    {
        return $this->dbh;
    }
}
