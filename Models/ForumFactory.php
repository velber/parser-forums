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
            case 'sovpoki':
                self::$instance = new ForumSovPoki($url);
                break;
            case 'odezdaoptom':
                self::$instance = new ForumOdezdaOptom($url);
                self::$instance->id = 3;
                break;
        }

        return self::$instance;
    }
}
