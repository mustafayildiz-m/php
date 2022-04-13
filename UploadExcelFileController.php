<?php

namespace App\Controllers;

use App\Models\Products;
use App\Models\ProductVariant;
use CodeIgniter\Controller;
use CodeIgniter\Database\Config;

class UploadExcelFileController extends Controller
{
    protected $fileName;
    protected $type;
    protected $tmpName;
    protected $category_id;
    protected $excel;
    protected $db;
    protected $objPHPExcel;

    //this class works firstly to create an obj and you should give $arr array in class such as parameter and then call functions
    public function __construct($arr)
    {
        $this->fileName = $arr['excelFileName'];
        $this->type = $arr['excelFileType'];
        $this->tmpName = $arr['excelFileTmpName'];
        $this->category_id = $arr['category_id'];
        $this->excel = \PHPExcel_IOFactory::load($this->tmpName);
        $this->db = Config::connect();
        $this->objPHPExcel = new \PHPExcel();
        define('DEFAULT_COLUMN_COUNT', 30);

    }

    public function save_specs($product_id = null): void
    {

        $product_specs = new \App\Models\ProductSpecs();
        $builder = $this->db->table('spec_to_category');
        $builder->distinct();
        $builder->select('spec_value.spec_id as spec_value_id,spec_value.id as spec_value_self_id,specs2.name as specs_name2,specs.name as specs_name,spec_value.name as spec_values_name,spec_value.slug as variant_values_slug');
        $builder->where(['category_id' => $this->category_id]);
        $builder->join('specs', 'spec_to_category.spec_id=specs.id');
        $builder->join('specs as specs2', 'specs2.parent_id=specs.id');
        $builder->join('spec_value', 'spec_value.spec_id=specs2.id');
        $specs = $builder->get()->getResultArray();

        $spec_title = [];
        foreach ($specs as $spec) {
            if (!in_array($spec['specs_name2'], $spec_title)) {
                array_push($spec_title, [
                        'spec_title' => $spec['specs_name2'],
                        'spec_id' => $spec['spec_value_self_id']
                    ]
                );

            }
        }

        //tüm kolonlar toplamı
        $count_column = count($spec_title) + DEFAULT_COLUMN_COUNT;
        foreach ($this->excel->getWorksheetIterator() as $row) {
            //val değeri kaç adet satır olduğunu döndürür
            $val = $row->getHighestRow();
            for ($i = 0; $i <= $count_column; $i++) {
                $spec_title1 = $row->getCellByColumnAndRow($i, 1)->getValue();
                foreach ($spec_title as $spec_titl) {
                    if ($spec_titl['spec_title'] == $spec_title1) {
                        for ($r = 0; $r <= $val; $r++) {

                            if ($row->getCellByColumnAndRow($i, $r)->getValue() == $spec_titl['spec_title']) {
                                $spec = $spec_titl['spec_title'];
                                $data = [
                                    "product_id" => $product_id,
                                    'spec' => $spec,
                                    'spec_value' => $spec_titl['spec_id']
                                ];
                                //inserting data to spec_value
                                $product_specs->insert($data);


                            }

                        }
                    }
                }
            }
        }


    }

    public function save_multi_products()
    {
        $model = new \App\Models\Products();
        $product_variants = new \App\Models\ProductVariant();
        $product_specs = new \App\Models\ProductSpecs();
        $last_category = $this->category_id;
        $result = "";
        foreach ($this->excel->getWorksheetIterator() as $row) {
            //val değeri kaç adet satır olduğunu döndürür
            $val = $row->getHighestRow();
            for ($i = 0; $i <= $val; $i++) {
                if ($i >= 2) {
                    $data = array(
                        "vendor_id" => pKullanici(),
                        "title" => $row->getCellByColumnAndRow(0, $i)->getValue(),
                        "slug" => seo($row->getCellByColumnAndRow(0, $i)->getValue()),
                        "code" => $row->getCellByColumnAndRow(4, $i)->getValue(),
                        "type" => $row->getCellByColumnAndRow(1, $i)->getValue(),
                        "gtin" => "test",
                        "mpn" => "test",
                        "brand_id" => $row->getCellByColumnAndRow(2, $i)->getValue(),
                        "model" => $row->getCellByColumnAndRow(3, $i)->getValue(),
                        "sale_start_date" => "test",
                        "sale_finish_date" => "test",
                        "sale_status" => $row->getCellByColumnAndRow(5, $i)->getValue(),
                        "preparation_day" => $row->getCellByColumnAndRow(6, $i)->getValue(),
                        "spec_id" => $row->getCellByColumnAndRow(7, $i)->getValue(),
                        "variation_id" => $row->getCellByColumnAndRow(8, $i)->getValue(),
                        "category_id" => $row->getCellByColumnAndRow(9, $i)->getValue(),
                        "end_category_id" => $last_category,
                        "product_description" => "test",
                    );
                    $model->insert($data);
                    $product_id = $model->getInsertID();

                    $image = $row->getCellByColumnAndRow(12, $i)->getValue() . ',' .
                        $row->getCellByColumnAndRow(13, $i)->getValue() . ',' .
                        $row->getCellByColumnAndRow(14, $i)->getValue() . ',' .
                        $row->getCellByColumnAndRow(15, $i)->getValue() . ',' .
                        $row->getCellByColumnAndRow(16, $i)->getValue() . ',' .
                        $row->getCellByColumnAndRow(17, $i)->getValue();
                    $variation_id = $row->getCellByColumnAndRow(19, $i)->getValue();
                    $variant_data = array(
                        "product_id" => $product_id,
                        "variant_title" => $row->getCellByColumnAndRow(18, $i)->getValue(),
                        "variant_id" => $row->getCellByColumnAndRow(19, $i)->getValue(),
                        "variant_value_id" => $row->getCellByColumnAndRow(20, $i)->getValue(),
                        "color" => $row->getCellByColumnAndRow(21, $i)->getValue(),
                        "image" => $image,
                        "barcode" => $row->getCellByColumnAndRow(22, $i)->getValue(),
                        "market_sale_price" => para_kaydet($row->getCellByColumnAndRow(23, $i)->getValue()),
                        "kintshop_sale_price" => para_kaydet($row->getCellByColumnAndRow(24, $i)->getValue()),
                        "stock" => $row->getCellByColumnAndRow(25, $i)->getValue(),
                        "tax" => $row->getCellByColumnAndRow(26, $i)->getValue(),
                        "stock_code" => $row->getCellByColumnAndRow(28, $i)->getValue(),
                        "active" => $row->getCellByColumnAndRow(27, $i)->getValue(),
                        'user_id' => pKullanici(),
                    );

                    //gerekli kontroller sonra yapılacak
//                    $product_variants->where("product_id", $product_id);
//                    $product_variants->where("variant_title", $row->getCellByColumnAndRow(18, $i)->getValue());
//                    $product_variants->where("variant_id", $row->getCellByColumnAndRow(19, $i)->getValue());
//                    $product_variants->where("variant_value_id", $row->getCellByColumnAndRow(20, $i)->getValue());
//                    $product_variants->where("color", $row->getCellByColumnAndRow(21, $i)->getValue());
//                    $variant_control = $product_variants->findAll();
//                    if (empty($variant_control)) {
//                    }
                    $product_variants->insert($variant_data);
                    $this->save_specs($product_id);


                }
            }


        }

        return true;

    }


    public function getWorkSheetInfo()
    {
        //get varyant info by category id


        $builder = $this->db->table('variant_to_category');
        $builder->distinct();
        $builder->select('variants . title as variats_title,variant_values . title as variant_values_title,variant_values . slug as variant_values_slug');
        $builder->where(['category_id' => $this->category_id]);
        $builder->join('variants', 'variant_to_category . variant_id = variants . id');
        $builder->join('variant_values', 'variants . id = variant_values . variant_id');
        $result = $builder->get()->getResultArray();

        return $result;

    }

    public function getVariantDataFromSheet()
    {
        $variant_val = [];
        //firstly get variant id
        $builder = $this->db->table('variant_to_category');
        $builder->select('variant_id');
        $builder->where(['category_id' => $this->category_id]);
        $variant_id = $builder->get()->getResultArray()[0]['variant_id'];
        $user_id = pKullanici();

        foreach ($this->excel->getWorksheetIterator() as $row) {
            $val = $row->getHighestRow();
            for ($i = 0; $i <= $val; $i++) {
                if ($i >= 2) {

                    array_push($variant_val, $row->getCellByColumnAndRow(29, $i)->getValue());

                    $arr = [
                        'user_id' => $user_id,
                        'variant_id' => $variant_id,
                        'variant_val' => $variant_val
                    ];

                }
            }

        }
        return $arr;

    }


    //excel dosyasındaki dataları çeker ve çektiği dataları veritabanına kaydeder
    public function getData(): void
    {
        $type_arr = array(
            'application / xls',
            'application / xlsx',
            'application / vnd . ms - excel . sheet . macroenabled.12',
            'application / vnd . ms - excel',
            'application / vnd . openxmlformats - officedocument . spreadsheetml . sheet',
        );

        if (in_array($this->type, $type_arr)) {

            $product_model = new Products();
            $product_variant_model = new ProductVariant();
            $product_data = [];
            $product_variant_data = [];
            foreach ($this->excel->getWorksheetIterator() as $row) {
                //val değeri kaç adet satır olduğunu döndürür
                $val = $row->getHighestRow();
                for ($i = 0; $i <= $val; $i++) {
                    if ($i >= 2) {
                        //product table
                        $data_product_table = [
                            'title' => $row->getCellByColumnAndRow(0, $i)->getValue(),
                            'seo_description' => seo($row->getCellByColumnAndRow(0, $i)->getValue()),
                            'slug' => seo($row->getCellByColumnAndRow(0, $i)->getValue()),
                            'type' => $row->getCellByColumnAndRow(1, $i)->getValue(),
                            'brand_id' => $row->getCellByColumnAndRow(2, $i)->getValue(),
                            'model' => $row->getCellByColumnAndRow(3, $i)->getValue(),
                            'code' => $row->getCellByColumnAndRow(4, $i)->getValue(),
                            'sale_status' => $row->getCellByColumnAndRow(5, $i)->getValue(),
                            'preparation_day' => $row->getCellByColumnAndRow(6, $i)->getValue(),
                            'spec_id' => $row->getCellByColumnAndRow(7, $i)->getValue(),
                            'variation_id' => $row->getCellByColumnAndRow(8, $i)->getValue(),
                            'category_id' => $row->getCellByColumnAndRow(9, $i)->getValue(),
                            'vendor_id' => pKullanici(),
                        ];

                        $insert = $product_model->insert($data_product_table);
                        if ($insert) {
                            $image =
                                $row->getCellByColumnAndRow(12, $i)->getValue() . ',' .
                                $row->getCellByColumnAndRow(13, $i)->getValue() . ',' .
                                $row->getCellByColumnAndRow(14, $i)->getValue() . ',' .
                                $row->getCellByColumnAndRow(15, $i)->getValue() . ',' .
                                $row->getCellByColumnAndRow(16, $i)->getValue() . ',' .
                                $row->getCellByColumnAndRow(17, $i)->getValue();
                            $data_product_variant_table = [
                                'product_main_code' => $row->getCellByColumnAndRow(10, $i)->getValue(),
                                'image' => $image,
                                'product_id' => $product_model->getInsertID(),
                                'user_id' => pKullanici(),
                                'variant_title' => $row->getCellByColumnAndRow(18, $i)->getValue(),
                                'variant_id' => $row->getCellByColumnAndRow(19, $i)->getValue(),
                                'variant_value_id' => $row->getCellByColumnAndRow(20, $i)->getValue(),
                                'color' => $row->getCellByColumnAndRow(21, $i)->getValue(),
                                'barcode' => $row->getCellByColumnAndRow(22, $i)->getValue(),
                                'market_sale_price' => $row->getCellByColumnAndRow(23, $i)->getValue(),
                                'kintshop_sale_price' => $row->getCellByColumnAndRow(24, $i)->getValue(),
                                'stock' => $row->getCellByColumnAndRow(25, $i)->getValue(),
                                'tax' => $row->getCellByColumnAndRow(26, $i)->getValue(),
                                'active' => $row->getCellByColumnAndRow(27, $i)->getValue(),
                                'stock_code' => $row->getCellByColumnAndRow(28, $i)->getValue(),
                            ];
                            $product_variant_model->insert($data_product_variant_table);

                        }


                    }
                }
            }

        } else {
            print('Dosya Türü Uyumsuz');
        }
    }


    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }


}