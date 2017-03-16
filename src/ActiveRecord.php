<?php
namespace App;

//ActiveRecord is worthless DB pattern
class ActiveRecord
{
    protected $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPDO()
    {
        return $this->pdo;
    }
}