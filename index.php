<?php
if (!class_exists('SimpleNote')) {
   class SimpleNote
   {
      private static $instance = null;

      protected $db = null;

      protected $dbConfig = [
         'host' => 'localhost',
         'name' => 'simple_note',
         'user' => 'root',
         'pass' => '',
      ];

      public static function instance()
      {
         if (is_null(self::$instance)) {
            self::$instance = new self();
         }

         return self::$instance;
      }

      public function db()
      {
         try {
            $dbConn = new \PDO(sprintf('mysql:host=%s;dbname=%s', $this->dbConfig['host'], $this->dbConfig['name']), $this->dbConfig['user'], $this->dbConfig['pass']);
            $dbConn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $dbConn->exec('SET NAMES utf8');

            return $dbConn;
         } catch (\PDOException $dbErr) {
            die($dbErr->getMessage());
         }
      }

      public function __construct()
      {
         $this->db = $this->db();
      }
   }

   SimpleNote::instance();
}
