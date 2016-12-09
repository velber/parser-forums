<?php
/**
 * Created by PhpStorm.
 * User: webler
 * Date: 12/9/16
 * Time: 7:27 PM
 */

namespace Models;


class ForumAbstract
{
    const LINKS_FILE_NAME = 'links-';
    const EMAIL_REGEXP = '/[A-Za-z\d._%+-]+@[a-z\d.-]+\.[a-z]{2,4}\b/';

    protected $client;

    protected $forum;

    protected $linksFileName;

    public function __construct($forum)
    {
        $this->client = new \GuzzleHttp\Client();
        $this->forum = $forum;
        $this->linksFileName = self::LINKS_FILE_NAME . $forum;
    }

    /**
     * Get numbers of every theme which have more than 1 answer, put it into json file.
     */
    public function parseLinks() {}

    /**
     * Gets emails from posts in theme by themes ids
     */
    public function parseEmails() {}
}
