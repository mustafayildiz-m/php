<?php

/**
 * @author Mustafa YILDIZ, mustafayildiz.m@gmail.com
 */

namespace StudentPanel\Widgets;


use insan\Kullanici;
use Kume\Klasor;
use KumeIliskileri;
use LogV2\LogXS;
use Metadata\Metadata;
use Nesne\Nesne;
use Nesne\Video;
use Sayfa\Icon2SVG;
use Sayfa\Sayfa\Routes\StudentPanel;
use Sayfa\Template;
use Veritabani\Db;

class GetVideosInFolder
{

    public function __construct()
    {
    }

    public static function getCat()
    {
        if (!empty($_GET['xcat']) && isset($_GET['xcat'])) {
            return $_GET['xcat'];
        }

        return false;

    }

    public static function getId()
    {
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            $id = $_GET['id'];
        } else {
            return false;
        }
        return $id;
    }

    public static function sql()
    {

        $collected_videos = self::getCat();
        if (empty($collected_videos)) {
            return false;
        }
        $vtable = Nesne::table();
        $vtable_as = Nesne::TABLE_AS;
        $vtable2 = Metadata::table();
        $vtable2_as = Metadata::TABLE_AS;

        $sql = "SELECT 
                        " . $vtable_as . ".tip,
                        " . $vtable_as . ".isim,
                        " . $vtable_as . ".sure,
                        " . $vtable_as . ".foto,
                        " . $vtable_as . ".sabit,
                        " . $vtable_as . ".id,
                        " . $vtable_as . ".tarih,
                        " . $vtable_as . ".icon
            
                FROM 
                    " . $vtable2 . " " . $vtable2_as . "
                INNER JOIN " . $vtable . " " . $vtable_as . "
                    ON " . $vtable_as . ".id =" . $vtable2_as . ".veri_id
                    AND $vtable_as.id != '" . self::getId() . "'
                    AND $vtable_as.tip='" . Video::NESNE_TIPI . "'
                    AND $vtable_as.durum='1'
                    AND $vtable_as.yayinlanma_durumu='1'
                    
                WHERE
                    " . $vtable2_as . ".tip ='" . Nesne::MD_TIP . "'
                    AND " . $vtable2_as . ".tip2='" . KumeIliskileri::MD_TIP . "'
                    AND " . $vtable2_as . ".durum='1'
                    AND " . $vtable2_as . ".veri2_id = '" . self::getCat() . "'
                    LIMIT 10";
        return $sql;

    }

    public static function getVideos()
    {
        $sql = self::sql();
        if (empty($sql)) {
            return false;
        }

        $query = Db::query($sql);
        $count = Db::row_count($query);
        if ($count > 0) {
            return Db::fetch_all(self::sql());
        }

        return false;

    }

    public static function htmlVideoList()
    {
        $output = '';
        $videos = self::getVideos();
        if ($videos) {
            $output .= '<div class="kt-notification kt-notification--fit">
       
        <div class="kt-widget4">';
            $dom = '<div class="kt-widget4__item">
					 <div class="kt-notification__item-icon">
                    
                    {{ICON}}
                </div>
                    <a style="margin-left: 7px" data-trigger-process-bar="true" data-video-id="{{ID}}" data-video-time="{{SURE}}" href="{{LINK}}" class="kt-widget4__title kt-widget4__title--light">
                        {{ISIM}}  <span id="video-info-for-watch-{{ID}}"><i></i></span>
                        <div class="progress">
                            <div  id="video-time-{{ID}}" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0"
                                  aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </a>
                    </div>';
            foreach ($videos as $key => $row_table2) {
                $row_table2['icon'] = isset(Icon2SVG::$SVG[$row_table2['icon']]) ? Icon2SVG::$SVG[$row_table2['icon']] : $row_table2['icon'];
                $link = StudentPanel::set_url($row_table2);
                $row_table2['link'] = $link;
                $row_table2['tarih'] = \Zaman::set_day_time($row_table2['tarih']);
                $output .= Template::embed($row_table2, $dom, ['use' => 'd']);
            }
            $output .= '</div></div>';
        }
        return $output;
    }


    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }

}