<?php
/**
 * Created by PhpStorm.
 * User: webler
 * Date: 12/10/16
 * Time: 3:47 PM
 */

namespace Models;


class ForumOdezdaOptom extends ForumAbstract
{
    const TOPIC_SELECTOR1 = '#allEntries .eTitle';
    const TOPIC_SELECTOR2 = '#allEntries .eTitle>a';
    const PAGES_SELECTOR = '.pagesBlock1 .swchItem';
    const EMAIL_SELECTOR = '#allEntries .cMessage';
    const URL = 'http://odezdaoptom.at.ua';


    /**
     * Gets emails from posts in theme by themes ids
     */
    public function parseEmails()
    {
        $emailsFromForums = new EmailsFromForums();
        $topics = $emailsFromForums->findTopicsByForumId($this->id);

        // foreach theme id
        foreach ($topics as $key => $topic) {
            if ($key < 374) continue; // 13484 (17418)

            echo <<<HEREDOC
        <br>
        ====================================<br>
        --- №$key. THEME URL: {$topic->url}<br>
        ====================================<br>
HEREDOC;

            flush();
            ob_flush();

            // if ($key < 571) continue;

            // if request server blocks our ip, we can use proxy ip
            $res = $this->client->request('GET', self::URL . $topic->url); // , ['proxy' => '84.51.80.131:8080']
            $saw = new \nokogiri($res->getBody());
            $nodes = $saw->get(self::EMAIL_SELECTOR)->toTextArray();

            // filter links, leave only emails
            $emailsRow = array_map(function($node) {
                preg_match(self::EMAIL_REGEXP, $node, $matches);
                return filter_var(@$matches[0], FILTER_VALIDATE_EMAIL);
            }, $nodes);

            // delete null values
            $emailsRow = array_unique(array_filter($emailsRow));

            // insert into db emails from theme
            $emailsFromForums->insertByArray($emailsRow, $this->forum);
            sleep(6);

            // check if theme has more than 1 page
            $pages = $saw->get(self::PAGES_SELECTOR)->toArray();

            if (count($pages) > 0) {

                // filter links, leave only emails
                $pagesFiltred = array_map(function($node) {

                    return filter_var($node['span'][0]['#text'][0], FILTER_VALIDATE_INT);
                }, $pages);

                // delete null values
                $pagesFiltred = array_filter($pagesFiltred);
                $maxPage = max($pagesFiltred);

                // for every page
                for ($i = 2; $i <= $maxPage; $i++) {
                    printf("<br>-------- №%s. page: %s -------<br>", $key, $i);

                    // change page
                    $explodeUrl = explode('/', $topic->url);
                    $explodeLastPart = explode('-', end($explodeUrl));
                    $explodeLastPart[1] = $i;
                    array_pop($explodeUrl);
                    $explodeUrl[] = implode('-', $explodeLastPart);
                    $url = implode('/', $explodeUrl);

                    $res = $this->client->request('GET', self::URL . $url);

                    $saw = new \nokogiri($res->getBody());
                    $nodes = $saw->get(self::EMAIL_SELECTOR)->toTextArray();

                    // filter links, leave only emails
                    $emailsRow = array_map(function($node) {
                        preg_match(self::EMAIL_REGEXP, $node, $matches);
                        return filter_var(@$matches[0], FILTER_VALIDATE_EMAIL);
                    }, $nodes);

                    // delete null values
                    $emailsRow = array_unique(array_filter($emailsRow));

                    // insert into db emails from theme
                    $emailsFromForums->insertByArray($emailsRow, $this->forum);

                    flush();
                    ob_flush();
                    sleep(6);
                }
            }
        }

        echo 'DONE!';
    }

    /**
     * Get numbers of every theme which have more than 1 answer, put it into json file.
     */
    public function parseLinks()
    {
        $links = array();
        $pages = array();

        $topicsDirs = array(
            '/?page' => 1183,
            '/dir/?page' => 79
        );

        $emailsFromForums = new EmailsFromForums();

        // generate pages
        foreach ($topicsDirs as $dir => $lastPage) {
            $arrayPages = range(1, $lastPage);
            array_walk($arrayPages, function ($page, $key, $arg) {
                $arg[0][] = $arg[1] . $arg[2] . $page;

            }, [&$pages, self::URL, $dir]);
        }

        // for every page
        foreach ($pages as $i => $page) {
            if ($i < 0) continue;

            $res = $this->client->request('GET', $page);
            $saw = new \nokogiri($res->getBody());
            $nodes = $saw->get(
                strpos($page, '/dir/?pag') ? self::TOPIC_SELECTOR2 : self::TOPIC_SELECTOR1
            )->toArray();

            if (strpos($page, '/dir/?pag')) {

                // get url of theme
                $nodesFiltred = array_map(function ($node) {
                    return $node['href'];
                }, $nodes);
            } else {

                // filter by amount answers ( > 0)
                $nodesFiltred = array_filter($nodes, function($node) {
                    return $node['table'][0]['tbody'][0]['tr'][0]['td'][0]['span'][0]['span'][1]['a'][1]['#text'][0] > 0;
                });

                // get url of theme
                array_walk($nodesFiltred, function(&$node) {
                    $node = $node['a'][0]['href'];
                });
            }

            $links = array_merge($links, $nodesFiltred);

            echo $i . '<br>';
            flush();
            ob_flush();
            sleep(6);

            // seve url to DB
            $emailsFromForums->insertIntoTopics($nodesFiltred, $this->id);
        }

        echo '<br>DONE!<br>';
        var_dump($links);
    }
}
