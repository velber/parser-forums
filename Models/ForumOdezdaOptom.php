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
    const TOPIC_SELECTOR = '#allEntries .eTitle';
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

            echo <<<HEREDOC
        <br>
        ====================================<br>
        --- №$key. THEME: {$topic->url} -----<br>
        ====================================<br>
HEREDOC;

            flush();
            ob_flush();

            // if ($key < 571) continue;

            // if request server blocks our ip, we can use proxy ip
            $res = $this->client->request('GET', self::URL . $topic->url); // , ['proxy' => '84.51.80.131:8080']
            $saw = new \nokogiri($res->getBody());
            $nodes = $saw->get(self::EMAIL_SELECTOR)->toArray();

            // filter links, leave only emails
            $emailsRow = array_map(function($node) {
exit;
                return filter_var($node['#text'][0], FILTER_VALIDATE_EMAIL);
            }, $nodes);

            // delete null values
            $emailsRow = array_filter($emailsRow);

            // insert into db emails from theme
            $emailsFromForums->insertByArray($emailsRow, 'sovpoki');
            sleep(5);

            // check if theme has more than 1 page
            $pages = $saw->get(self::PAGES_SELECTOR)->toArray();

            if (count($pages) > 0) {

                // filter links, leave only emails
                $pagesFiltred = array_map(function($node) {

                    return filter_var($node['#text'][0], FILTER_VALIDATE_INT);
                }, $pages);

                // delete null values
                $pagesFiltred = array_filter($pagesFiltred);
                $maxPage = max($pagesFiltred) * 20;

                // for every page
                for ($i = 20; $i < $maxPage; $i += 20) {
                    printf("<br>-------- №%s. theme:%s, page: %s -------<br>", $key, $linksNumber, $i / 20);
                    $res = $this->client->request('GET', $link . '&start=' . $i);

                    $saw = new \nokogiri($res->getBody());
                    $nodes = $saw->get(self::EMAIL_SELECTOR)->toArray();

                    // filter links, leave only emails
                    $emailsRow = array_map(function($node) {

                        return filter_var($node['#text'][0], FILTER_VALIDATE_EMAIL);
                    }, $nodes);

                    // delete null values
                    $emailsRow = array_filter($emailsRow);

                    // insert into db emails from theme
                    $emailsFromForums->insertByArray($emailsRow, $this->forum);

                    flush();
                    ob_flush();
                    sleep(5);
                }
            }
            break;
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
            '?page' => 1183,
            'dir/?page' => 79
        );

        // generate pages
        foreach ($topicsDirs as $dir => $lastPage) {
            array_walk(range(1, $lastPage), function ($page, $key, $arg) {
                $arg[0][] = $arg[1] . $arg[2] . $page;

            }, [&$pages, self::URL, $dir]);
        }

        // for every page
        foreach ($pages as $i => $page) {

            $res = $this->client->request('GET', $page);

            $saw = new \nokogiri($res->getBody());
            $nodes = $saw->get(self::TOPIC_SELECTOR)->toArray();

            // filter by amount answers ( > 0)
            $nodesFiltred = array_filter($nodes, function($node) {
                return $node['table'][0]['tbody'][0]['tr'][0]['td'][0]['span'][0]['span'][1]['a'][1]['#text'][0] > 0;
            });

            // get url of theme
            array_walk($nodesFiltred, function(&$node) {
                $node = $node['a'][0]['href'];
            });

            $links = array_merge($links, $nodesFiltred);

            echo $i . '<br>';
            flush();
            ob_flush();
            sleep(1);
        }

        // seve url to DB
        $emailsFromForums = new EmailsFromForums();
        $emailsFromForums->insertIntoTopics($links, $this->id);
        var_dump($links);
    }
}
