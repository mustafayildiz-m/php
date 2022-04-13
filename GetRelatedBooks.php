<?php

/**
 * @author Mustafa YILDIZ, mustafayildiz.m@gmail.com
 */

namespace StudentPanel\Widgets;


use Kume\Klasor;
use KumeIliskileri;
use Metadata\Metadata;
use Nesne\Kitap;
use Nesne\Nesne;
use Veritabani\Db;

class GetRelatedBooks
{

    public function __construct()
    {
    }

    public static function folders()
    {
        if ($_GET['xcat'] && isset($_GET['xcat'])) {
            $arr = Klasor::yukari_dogru_topla($_GET['xcat']);

            return $arr;
        } else {
            return false;
        }


    }

    public static function sql()
    {

        $folders = self::folders();

        if (empty($folders)) {
            return false;
        }
        $vtable = Nesne::table();
        $vtable_as = Nesne::TABLE_AS;
        $vtable2 = Metadata::table();
        $vtable2_as = Metadata::TABLE_AS;
        $collectedData = implode_sql($folders);
        $sql = "SELECT 
                " . $vtable_as . ".id,
                " . $vtable_as . ".foto,
                " . $vtable_as . ".isim
            FROM " . $vtable2 . " " . $vtable2_as . "
            INNER JOIN " . $vtable . " " . $vtable_as . "
                ON " . $vtable_as . ".id=" . $vtable2_as . ".veri_id
                AND $vtable_as.tip='" . Kitap::NESNE_TIPI . "'
                AND $vtable_as.durum='1'
                AND $vtable_as.yayinlanma_durumu='1'
            WHERE 
                " . $vtable2_as . ".tip = '" . Nesne::MD_TIP . "'
                AND " . $vtable2_as . ".tip2='" . KumeIliskileri::MD_TIP . "'
                AND " . $vtable2_as . ".durum ='1'
                AND " . $vtable2_as . ".veri2_id IN (" . $collectedData . ")
            LIMIT 1
         ";
        return $sql;
    }

    public static function getBooks()
    {
        $sql = self::sql();
        if (empty($sql)) {
            return false;
        }
        $query = Db::query($sql);
        $count = Db::row_count($query);
        if ($count) {
            $book_list = Db::db_fetch($query);
            return $book_list;
        } else {
            return false;
        }
    }


    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }

}