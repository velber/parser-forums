<?php

namespace Models;

$client = new \GuzzleHttp\Client();
$emails = array();

// for every page
for ($i = 1; $i <= 6; $i++) {

    $res = $client->request('GET', 'http://www.ru-ru-ru.ru/organizer.html?p=' . $i);

    $saw = new \nokogiri($res->getBody());
    $nodes = $saw->get('.modtab td:nth-child(5)')->toArray();

    // validate values by email
    $emailsRow = array_map(function($node) {

        return filter_var($node['#text'][0], FILTER_VALIDATE_EMAIL);
    }, $nodes);

    // delete null values
    $emailsRow = array_filter($emailsRow);

    // merge emails
    $emails = array_merge($emails, $emailsRow);
}

// insert all founded emails into DB
$emailsFromForums = new EmailsFromForums();
$emailsFromForums->insertByArray($emails);
