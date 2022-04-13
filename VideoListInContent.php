<?php

/**
 * @author Mustafa YILDIZ, mustafayildiz.m@gmail.com
 */

namespace StudentPanel\Widgets;


use KumeIliskileri;
use Metadata\Metadata;
use Nesne\Nesne;
use Nesne\Video;
use Veritabani\Db;

class VideoListInContent
{

    public function __construct()
    {
    }

    public static function getEbeveynId()
    {
        return KumeIliskileri::verisi($_GET['xcat'])['ebeveyn_id'];
    }

    public static function sqlData()
    {
        $vtable1 = Metadata::table();
        $vtable1_as = Metadata::TABLE_AS;

        $vtable2 = Nesne::table();
        $vtable2_as = Nesne::TABLE_AS;

        $sql = "SELECT 
                    " . $vtable2_as . ".*
                FROM " . $vtable1 . " " . $vtable1_as . "
                    INNER JOIN " . $vtable2 . " " . $vtable2_as . " ON 
                        " . $vtable2_as . ".id=" . $vtable1_as . ".veri_id
                        AND " . $vtable2_as . ".tip=" . Video::NESNE_TIPI . "
                        AND " . $vtable2_as . ".durum='1' 
                WHERE
                    " . $vtable1_as . ".tip ='" . Nesne::MD_TIP . "'
                    AND " . $vtable1_as . ".tip2 ='" . KumeIliskileri::MD_TIP . "'
                    AND " . $vtable1_as . ".veri2_id = '" . self::getEbeveynId() . "' LIMIT 10
                    ";

        return $sql;
    }


    public static function getVideos()
    {

        $query = Db::fetch_all(self::sqlData());
        $sql_process = Db::query(self::sqlData());
        $count = Db::row_count($sql_process);

        if ($count > 0 && isset($count)) {
            return $query;
        } else {
            return false;
        }


    }


    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }

}