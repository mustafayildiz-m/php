<?php

namespace DeclareFakeUsers;

use insan\Kullanici;
use LogV2\LogXS;
use Veritabani\Db;

/**
 * @author Mustafa YILDIZ, mustafayildiz.m@gmail.com
 */
class GetFakeUsers
{
    const YETKI_ID = 22;
    const IS_BOUGHT_BEFORE = 1;
    const LIMIT = 1;
    const SCRIPT_ID = 425;
    const DEFAULT_USER_COUNT = 15;

    const ARRAY_HOURS = [
        '9::11' => 5,
        '11::13' => 10,
        '13::15' => 15,
        '15::20' => 20,
        '20::21' => 25,
        '21::22' => 35,
        '22::24' => 40,
        'default' => self::DEFAULT_USER_COUNT
    ];

    public function __construct()
    {
    }

    public static function getUser()
    {

        $sql = "SELECT 
                id
                FROM " . Kullanici::table() . " 
                WHERE ana_yetki_id='" . self::YETKI_ID . "' 
                AND is_bought_before='" . self::IS_BOUGHT_BEFORE . "'
                ORDER BY RAND()
                LIMIT " . self::LIMIT . " ";
        $query = Db::query($sql);
        $count = Db::row_count($query);
        $data = "";
        if ($count) {
            $data = Db::fetch_all($sql);

        } else {
            return false;
        }
        return $data;

    }

    public static function getTime()
    {
        return time();

    }

    public static function getHour()
    {
        $hour = date('H');
        return $hour;
    }

    public static function insertData()
    {
        $code = md5(rs_random(12));
        $arr = [
            'script_id' => self::SCRIPT_ID,
            'kul_id' => self::getUser()[0]['id'],
            'tarih' => self::getTime(),
            'session_id' => $code
        ];

        return Db::insert($arr, LogXS::table());

    }


    // to call this function in page. it will works data in xs_log with fake users data.
    public static function addUsers2XsLogtable()
    {

        $count = self::detectUserCount();
        for ($i = 0; $i < $count; $i++) {
            self::insertData();
        }

    }

    public static function detectUserCount(): int
    {
        $now = self::getHour();
        $interval = "";
        $hours = self::ARRAY_HOURS;
        if ($hours) {
            foreach ($hours as $key => $hour) {
                $hour_val = explode('::', $key);
                $hour_val1 = $hour_val[0];
                $hour_val2 = $hour_val[1];
                if ($now >= $hour_val1 && $now < $hour_val2) {
                    $interval = $hour;
                    break;
                }
            }

            if (!empty($interval)) {
                return $interval;
            }

        }
        return self::DEFAULT_USER_COUNT;

    }


    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }
}