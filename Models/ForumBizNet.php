<?php

namespace Models;


class ForumBizNet extends ForumAbstract
{
    const TOPIC_SELECTOR = '.ipb_table .__topic';
    const PAGES_SELECTOR = '.topic .ipsList_inline .page';
    const EMAIL_SELECTOR = '.post_body .entry-content';

    /**
     * Get numbers of every theme which have more than 1 answer, put it into json file.
     */
    public function parseLinks()
    {
        $links = array();

        // for every page
        for ($i = 1; $i <= 12; $i++) {

            $res = $this->client->request('GET', 'http://biznet.kiev.ua/index.php?showforum=117&prune_day=100&sort_by=Z-A&sort_key=last_post&topicfilter=all&page=' . $i);

            $saw = new \nokogiri($res->getBody());
            $nodes = $saw->get(self::TOPIC_SELECTOR)->toArray();

            // filter by amount answers ( > 0)
            $nodesFiltred = array_filter($nodes, function ($node) {
                $amount = preg_replace('/[^\d]+/', '', $node['td'][3]['ul'][0]['li'][0]['#text'][0]);
                return $amount > 0;
            });

            // get id of theme
            $linksNumbers = array_map(function ($node) {
                $href = $node['td'][1]['h4'][0]['a'][0]['id'];
                preg_match('/tid-link-([\d]+)/', $href, $match);

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
            $link = 'http://biznet.kiev.ua/index.php?showtopic=' . $linksNumber;

            // if request server blocks our ip, we can use proxy ip
            $res = $this->client->request('GET', $link); // , ['proxy' => '84.51.80.131:8080']
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
            sleep(5);

            // check if theme has more than 1 page
            $pages = $saw->get(self::PAGES_SELECTOR)->toArray();

            if (count($pages) > 0) {

                // filter links, leave only emails
                $pagesFiltred = array_map(function($node) {

                    return isset($node['a'][0]['href']) ? $node['a'][0]['href'] : null;
                }, $pages);

                // delete null values
                $pagesFiltred = array_filter($pagesFiltred);

                // for every page
                foreach ($pagesFiltred as $key1 => $url) {
                    printf("<br>-------- №%s. theme:%s, page: %s -------<br>", $key, $linksNumber, $key1);
                    $res = $this->client->request('GET', $url);

                    $saw = new \nokogiri($res->getBody());
                    $nodes = $saw->get(self::EMAIL_SELECTOR)->toTextArray();

                    // filter links, leave only emails
                    $emailsRow = array_map(function($node) {
                        preg_match(self::EMAIL_REGEXP, $node, $matches);
                        return filter_var(@$matches[0], FILTER_VALIDATE_EMAIL);;
                    }, $nodes);

                    // delete null values
                    $emailsRow = array_unique(array_filter($emailsRow));

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
}
