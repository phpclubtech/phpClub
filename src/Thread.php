<?php
namespace App;

use App\ActiveRecord;

class Thread extends ActiveRecord
{
    public $number;

    public $posts;

    public function addThread()
    {
        $pdo = $this->getPDO();

        $query = $pdo->prepare("INSERT INTO threads (number) VALUES (:number)");

        $query->bindValue(':number', $this->number);

        $query->execute();
    }

    public function getThread($number)
    {
        $pdo = $this->getPDO();

        $query = $pdo->prepare("SELECT * FROM threads WHERE number=:number");
        $query->bindValue(':number', $number);
        $query->execute();

        $result = $query->fetch(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return false;
        }

        $thread = new Thread($pdo);

        $thread->number = $result['number'];

        return $this;
    }

    public function getThreads($limit = 2147483647, $offset = 0)
    {
        $pdo = $this->getPDO();

        $query = $pdo->prepare("SELECT * FROM threads LIMIT :limit OFFSET :offset");
        $query->bindValue(':limit', (int) $limit, \PDO::PARAM_INT);
        $query->bindValue(':offset', (int) $offset, \PDO::PARAM_INT);
        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        $threads = new \SplObjectStorage();

        foreach ($results as $result) {
            $thread = new Thread($pdo);

            $thread->number = $result['number'];

            $threads->attach($thread);
        }

        return $threads;
    }
}