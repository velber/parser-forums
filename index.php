<?php

ini_set('display_errors', 'On');
require_once 'vendor/autoload.php';

//include 'sovpoki.php';
//include 'ru-ru-ru.php';

$forum = filter_input(INPUT_GET, 'forum', FILTER_SANITIZE_STRING);

if ($forum) {
    $forumParser = \Models\ForumFactory::factory($forum);
//    $forumParser->parseLinks();
    $forumParser->parseEmails();
} else {
    echo <<<HTML
    <ul>
        <li><a href="/?forum=biz-net">biz-net.kiev.ua</a></li>
        <li><a href="/?forum=sovpoki">sovpoki</a></li>
        <li><a href="/?forum=odezdaoptom">odezdaoptom</a></li>
    </ul>        
HTML;
}
