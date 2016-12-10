<?php

namespace Models;


class ForumSovPoki extends ForumAbstract
{
    const TOPIC_SELECTOR = 'ul.topics>li.row';
    const PAGES_SELECTOR = '.bottom .pagination li a';
    const EMAIL_SELECTOR = '.post .inner .postbody .content>a';

    /**
     * Gets emails from posts in theme by themes ids
     */
    public function parseEmails()
    {
        $emailsFromForums = new EmailsFromForums();
        $linksNumbers = json_decode(file_get_contents($this->linksFileName), true);

        // foreach theme id
        foreach ($linksNumbers as $key => $linksNumber) {

            echo <<<HEREDOC
        <br>
        ====================================<br>
        --- №$key. THEME: $linksNumber -----<br>
        ====================================<br>
HEREDOC;

            flush();
            ob_flush();

            // if ($key < 571) continue;

            // get all links in posts in theme
            $link = 'http://sovpoki.ru/viewtopic.php?f=25&t=' . $linksNumber;

            // if request server blocks our ip, we can use proxy ip
            $res = $this->client->request('GET', $link); // , ['proxy' => '84.51.80.131:8080']
            $saw = new \nokogiri($res->getBody());
            $nodes = $saw->get(self::EMAIL_SELECTOR)->toArray();

            // filter links, leave only emails
            $emailsRow = array_map(function($node) {

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
        }

        echo 'DONE!';
    }

    /**
     * Get numbers of every theme which have more than 1 answer, put it into json file.
     */
    public function parseLinks()
    {
        $links = array();

        // for every page
        for ($i = 0; $i <= 500; $i += 25) {

            $res = $this->client->request('GET', 'http://sovpoki.ru/viewforum.php?f=25&start=' . $i);

            $saw = new \nokogiri($res->getBody());
            $nodes = $saw->get(self::TOPIC_SELECTOR)->toArray();

            // filter by amount answers ( > 0)
            $nodesFiltred = array_filter($nodes, function($node) {
                return $node['dl'][0]['dd'][0]['#text'][0] > 0;
            });

            // get id of theme
            $linksNumbers = array_map(function($node) {
                $href = $node['dl'][0]['dt'][0]['div'][0]['a'][0]['href'];
                preg_match('/&t=([0-9]*)&/', $href, $match);

                if (is_numeric($match[1])) {

                    return $match[1];
                }
            }, $nodesFiltred);

            $links = array_merge($links, $linksNumbers);

            echo $i . '<br>';
            flush();
            ob_flush();
            sleep(1);
        }

        // seve ids to file
        file_put_contents($this->linksFileName, json_encode($links));
        var_dump($links);
    }
}
