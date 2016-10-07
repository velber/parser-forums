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
}
