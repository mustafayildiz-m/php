<?php

/**
 * @author Mustafa YILDIZ, mustafayildiz.m@gmail.com
 */

namespace StudentPanel\Widgets;


use insan\Kullanici;
use LogV2\LogXS;
use Metadata\Metadata;
use Nesne\EgitimPaketleri;
use Nesne\Nesne;
use Veritabani\Db;
use Zaman;


class GetOnlineUsersName
{


    public function __construct()
    {
    }

    public static function calculatedLastHalfTime()
    {
        $now_unix_time = Zaman::second();
        $last_half = $now_unix_time - 1800;
        return $last_half;
    }


    public static function sql()
    {

        $vtable1 = LogXS::table();
        $vtable1_as = LogXS::TABLE_AS;

        $vtable2 = Kullanici::table();
        $vtable2_as = Kullanici::TABLE_AS;

        $sql = "SELECT
    " . $vtable2_as . ".*
            FROM
    " . $vtable1 . " " . $vtable1_as . "
            INNER JOIN " . $vtable2 . " " . $vtable2_as . "
            ON
                " . $vtable2_as . ".id = " . $vtable1_as . ".kul_id
            WHERE
                " . $vtable1_as . ".tarih > " . self::calculatedLastHalfTime() . "
                ORDER BY rand(), " . $vtable2_as . ".derece DESC 
                LIMIT 20";

        return $sql;


    }

    public static function getUsers()
    {
        $total = Db::fetch_all(self::sql());
        $query = Db::query(self::sql());
        $count = Db::row_count($query);
        if (!empty($count) && $count > 0) {
//            self::getUsersDegrees($total);
            return $total;
        } else {
            return false;
        }


    }

    //if user buy product -> return true , otherwise return false
    //mstf

    public static function didUserBuyProduct($user_id)
    {
        $vtable = Metadata::table();
        $vtable_as = Metadata::TABLE_AS;

        $vtable2 = Nesne::table();
        $vtable2_as = Nesne::TABLE_AS;

        $sql = "SELECT 
			$vtable2_as.id,
			$vtable_as.tarih,
			$vtable2_as.gecerlilik_suresi,
			$vtable2_as.bitis_tarihi,
			($vtable2_as.gecerlilik_suresi + $vtable_as.tarih) AS paket_bitis_tarihi,
			 $vtable2_as.isim
		FROM " . $vtable . " " . $vtable_as . "
            INNER JOIN " . $vtable2 . " $vtable2_as ON $vtable2_as.id=$vtable_as.veri2_id                        
                        AND $vtable2_as.durum='1'                        
                        AND $vtable2_as.tip IN('" . EgitimPaketleri::TIP . "','25')
                        AND (
                        (
                            $vtable2_as.bitis_tarihi = '0'
                            || $vtable2_as.bitis_tarihi IS NULL
                            || $vtable2_as.bitis_tarihi > UNIX_TIMESTAMP(NOW())
                        ) 
                        || $vtable2_as.gecerlilik_suresi > '0'
                        )
		WHERE 
                    $vtable_as.tip='" . Kullanici::MD_TIP . "' 
                    AND $vtable_as.tip2='" . Nesne::MD_TIP . "'
                    
                    AND $vtable_as.durum='1'
                    AND $vtable2_as.durum='1'
                    AND $vtable_as.veri_id='" . $user_id . "'                    
            GROUP BY $vtable2_as.id LIMIT 1";

        $query = Db::query($sql);
        $count = Db::row_count($query);

        if (isset($count) && $count > 0) {
            return true;
        } else {
            return false;
        }


    }

    public static function getUsersDegreesOfClassName($degree, $user_id)
    {
        $resultofproduct = self::didUserBuyProduct($user_id);
        if (isset($degree)) {
            switch ($degree) {
                case $degree >= Kullanici::ADMIN_DERECESI  :
                    return 'danger';
                    break;
                case $degree <= Kullanici::UYE_DERECESI && $resultofproduct == 1:
                    return 'success';
                    break;
                case $degree >= Kullanici::YETKILI_DERECESI && $degree <= Kullanici::ADMIN_DERECESI:
                    if ($resultofproduct == 1) {
                        return 'warning';
                    } else {
                        return 'outline';
                    }
                    break;
                default:
                    return 'outline';
                    break;
            }
        } else {
            return false;
        }

    }


    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }

}