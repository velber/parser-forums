<?php

namespace Models;

class EmailsFromForums
{
    private $dbh;

    public function __construct()
    {
        $this->dbh = \Models\DB::getInstance()->getConnect();

    }

    public function insertByArray($emails, $forum)
    {

        $stmt = $this->dbh->prepare("INSERT INTO emails_from_forums (email, forum) VALUES (:email, :forum)");

        $i = 0;
        foreach ($emails as $email) {

            $result = (int)$stmt->execute(array(
                ':email' => $email,
                ':forum' => $forum,
            ));

            printf("%s. %s - %s<br>", $i, $email, $result);
            flush();
            ob_flush();

            $i++;
        }

        return $this;
    }

    public function insertIntoTopics($topics, $forum)
    {

        $stmt = $this->dbh->prepare("INSERT INTO topics (url, forum_id) VALUES (:topic, :forum)");

        $i = 0;
        foreach ($topics as $topic) {

            $result = (int)$stmt->execute(array(
                ':topic' => $topic,
                ':forum' => $forum,
            ));

            printf("%s. %s - %s<br>", $i, $topic, $result);
            flush();
            ob_flush();

            $i++;
        }

        return $this;
    }

    public function findTopicsByForumId($forumId)
    {
        $stmt = $this->dbh->prepare("SELECT * FROM topics WHERE forum_id = :forum");

        $stmt->execute(array(':forum' => $forumId));
        $topics = $stmt->fetchAll(\PDO::FETCH_OBJ);

        return $topics;
    }
}
