<?php
/**
 * Created by PhpStorm.
 * User: webler
 * Date: 12/9/16
 * Time: 3:18 PM
 */

namespace Models;


class ForumFactory
{
    private static $instance;

    public static function factory ($url)
    {
        switch ($url) {
            case 'biz-net':
                self::$instance = new ForumBizNet($url);
                break;
        }

        return self::$instance;
    }
}
