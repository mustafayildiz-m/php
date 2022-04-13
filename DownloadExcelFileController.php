<?php

namespace App\Controllers;

use App\Models\Categories;
use CodeIgniter\Controller;

class DownloadExcelFileController extends Controller
{
    protected $objPHPExcel;
    protected $objWriter;
    protected $filename;
    protected $category_id;
    protected $db;

    public function __construct($category_id = null)
    {
        $this->db = \Config\Database::connect();

        $this->objPHPExcel = new \PHPExcel();
        $this->category_id = $category_id;
        helper(['kintshop']);
        date_default_timezone_set('Europe/Istanbul');

    }

    //output download button
    public function output_button()
    {
        return '<a  href="' . base_url('download?id=' . $this->category_id) . '" class="btn btn-add float-left mt-3 mb-3">İndir</a>';
    }

    //upload form button & form
    public function uploadFormOutput()
    {
        $url = base_url('ProductController/excel_file_upload');
        $output = <<<EOF
 <form method="POST" action="$url"
                              enctype="multipart/form-data">
                              <input type="file" name="excelFile">
                              <input type="hidden" name="category_id" value="$this->category_id">
                            <input class="btn btn-add float-left mt-3 mb-3" value="Yükle" type="submit">
                        </form>
EOF;
        return $output;


    }

    //document properties
    public function setDocumentProperties()
    {
        // Set document properties
        $this->objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        return $this;
    }

    //get specs from db
    public function querySpecs($id)
    {
        //query specs
        $builder = $this->db->table('spec_to_category');
        $builder->distinct('spec_value.spec_id');
        $builder->select('spec_value.spec_id as spec_value_id,specs.parent_id,specs2.name as specs_name2,specs.name as specs_name,spec_value.name as spec_values_name,spec_value.slug as variant_values_slug');
        $builder->where(['category_id' => $id]);
        $builder->where('spec_value.deleted_at is null');
        $builder->join('specs', 'spec_to_category.spec_id=specs.id');
        $builder->join('specs as specs2', 'specs2.parent_id=specs.id');
        $builder->join('spec_value', 'spec_value.spec_id=specs2.id');
        $specs = $builder->get()->getResultArray();
        return $specs;
    }

    //get variants from db
    public function queryVariants($id)
    {
        //query varyant
        $builder = $this->db->table('variant_to_category');
        $builder->distinct();
        $builder->select('variants.title as variats_title,variant_values.title as variant_values_title,variant_values.slug as variant_values_slug');
        $builder->where(['category_id' => $id]);
        $builder->join('variants', 'variant_to_category.variant_id=variants.id');
        $builder->join('variant_values', 'variants.id=variant_values.variant_id');
        $variants = $builder->get()->getResultArray();
        return $variants;

    }

    //adding data to excel from db
    public function addData()
    {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
        }
        $this->getVariants($this->queryVariants($id));
        $this->getSpecs($this->querySpecs($id));
        return $this;
    }

    //setting specs in worksheet
    public function getSpecs(array $specs): voidfe
    {
        $spec_title = [];
        $row = 1;
        $letter = 'E';
        $letter2 = 'A';
        $row2 = 2;

        //specleri excel e bastırmak için gerekli döngü
        foreach ($specs as $key => $spec) {
            $spec_val = '';
            if (!in_array($spec['specs_name2'], $spec_title)) {
                //bu kısım speclerdeki değerleri select option şeklinde excele bastırmaya yarar.başlangıç
                foreach ($specs as $spec2) {
                    if ($spec['spec_value_id'] == $spec2['spec_value_id']) {
                        $spec_val .= $spec2['spec_values_name'] . ',';
                    }

//                    $dstCell = \PHPExcel_Cell::stringFromColumnIndex(1) . (string)('A' . $letter . $row2);
//                    $this->objPHPExcel->getActiveSheet()->duplicateStyle($this->objPHPExcel->getActiveSheet()->getStyle('AC1'), 'AC3:AC13')
//                        ->setCellValue('A' . $letter . $row2, "Değer Seç");

                    //bu kısımda aynı satırları alt satırlara dublicate edilmesi gerekecek .
                    //https://stackoverflow.com/questions/15147110/phpexcel-get-column-name-relative-to-given-column
                    //mstf

                    $this->objPHPExcel->getActiveSheet()->setCellValue($letter2 . $letter . $row2, "Özellik Seç");
                    // Add some data
                    $objValidation = $this->objPHPExcel->getActiveSheet()->getCell($letter2 . $letter . $row2)->getDataValidation();
                    $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                    $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                    $objValidation->setAllowBlank(false);
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setShowErrorMessage(true);
                    $objValidation->setShowDropDown(true);
                    $objValidation->setErrorTitle('Hatalı giriş');
                    $objValidation->setError('Değer listede yok.');
                    $objValidation->setPromptTitle('Listeden seç');
                    $objValidation->setPrompt('Lütfen Listedeki değerleri manuel girmeyin.');
                    $objValidation->setFormula1('"' . $spec_val . '"');

                }

                //bu kısım speclerdeki değerleri select option şeklinde excele bastırmaya yarar.bitiş
                array_push($spec_title, $spec['specs_name2']);
                //bu kısım spec başlıklarını excele bastırır.başlangıç
                $this->objPHPExcel->getActiveSheet()->setCellValue($letter2 . $letter . $row, $spec['specs_name2']);
                $this->cellColor($letter2 . $letter . $row, 'FFD700');
                $this->objPHPExcel->setActiveSheetIndex(0);
                //bu kısım spec başlıklarını excele bastırır.bitiş
                if ($letter == 'Z') {
                    $letter2++;
                    $letter = 'A';
                    continue;
                }
                $letter++;

            }
        }

    }

    //setting variant in worksheet
    public function getVariants(array $variants): void
    {
        $variant_val = '';
        $variant_title = [];
        foreach ($variants as $key => $variant) {
            if (!in_array($variant['variats_title'], $variant_title)) {
                array_push($variant_title, $variant['variats_title']);
                $this->objPHPExcel->getActiveSheet()->setCellValue('AD1', $variant['variats_title']);
                $this->cellColor('AD1', '8FBC8F');
                $this->objPHPExcel->setActiveSheetIndex(0);
            }
            $variant_val .= $variant['variant_values_title'] . ',';

            $this->objPHPExcel->setActiveSheetIndex(0);
            $this->objPHPExcel->getActiveSheet()
                ->setCellValue('AD2', "Varyant Seç");
// Add some data
            $objValidation = $this->objPHPExcel->getActiveSheet()->getCell('AD2')->getDataValidation();
            $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
            $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
            $objValidation->setAllowBlank(false);
            $objValidation->setShowInputMessage(true);
            $objValidation->setShowErrorMessage(true);
            $objValidation->setShowDropDown(true);
            $objValidation->setErrorTitle('Hatalı giriş');
            $objValidation->setError('Değer listede yok.');
            $objValidation->setPromptTitle('Listeden seç');
            $objValidation->setPrompt('Lütfen Listedeki değerleri manuel girmeyin.');
            $objValidation->setFormula1('"' . $variant_val . '"');


        }

    }

    public function setActiveSheet()
    {
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $this->objPHPExcel->setActiveSheetIndex(0);
        return $this;

    }

    //get category for name of worksheet
    public static function getCategory()
    {
        $obj = new Categories();
        $query = $obj->select('title')->find($_GET['id']);
        $title = $query['title'];
        return $title;

    }

    //rename worksheet
    public function renameWorkseet()
    {

        // Rename worksheet
        $this->objPHPExcel->getActiveSheet()->setTitle(self::getCategory());
        return $this;

    }

    //header
    public function redirectHeader()
    {

        $file_name = seo(self::getCategory()) . '_' . date("Y_m_d-H_i_s");
// Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $file_name . '.xlsx"');
        header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

    }

    //worksheet default data
    public function addDefaultData($category_id)
    {
        $this->objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'Ürün Başlık')
            ->setCellValue('B1', 'Ürün Tipi')
            ->setCellValue('C1', 'Marka')
            ->setCellValue('D1', 'Model')
            ->setCellValue('E1', 'Ürün Kodu')
            ->setCellValue('F1', 'Satış Durumu')
            ->setCellValue('G1', 'Sevkiyat Süre')
            ->setCellValue('H1', 'Özellik')
            ->setCellValue('I1', 'Varyant')
            ->setCellValue('J1', 'Kategori')
            ->setCellValue('K1', 'Ürün Açıklamalası')
            //VARYANT OZELLIKLERI
            ->setCellValue('L1', 'Ürün Kodu')
            ->setCellValue('M1', 'Görsel 1')
            ->setCellValue('N1', 'Görsel 2')
            ->setCellValue('O1', 'Görsel 3')
            ->setCellValue('P1', 'Görsel 4')
            ->setCellValue('Q1', 'Görsel 5')
            ->setCellValue('R1', 'Görsel 6')
            ->setCellValue('S1', 'Varyant Başlık')
            ->setCellValue('T1', 'Varyant id')
            ->setCellValue('U1', 'Varyant value id')
            ->setCellValue('V1', 'Renk')
            ->setCellValue('W1', 'Barcode')
            ->setCellValue('X1', 'Market Satış Fiyatı')
            ->setCellValue('Y1', 'Kintshop Satış Fiyatı')
            ->setCellValue('Z1', 'Stok')
            ->setCellValue('AA1', 'Vergi')
            ->setCellValue('AB1', 'Durum')
            ->setCellValue('AC1', 'Stok Kodu');
//        $this->objPHPExcel->getActiveSheet()->setCellValue('C2', "brand id buraya");
        $this->objPHPExcel->getActiveSheet()->setCellValue('J2', $category_id)->setTitle('test');

//
//        $objValidation->setPromptTitle('Listeden seç');
//        $objValidation->setPrompt('Lütfen Listedeki değerleri manuel girmeyin.');


        //setting width of titles
        $this->setWidthOfColumn();

        //setting height of titles
        //$this->setHeight();

        //setting color of titles
        $this->setColorOfTitles();
        return $this;
    }

    //height
    public function setHeight()
    {
        for ($i = 1; $i < 100; $i++) {
            $this->objPHPExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(30);
        }
        return $this;
    }

    //width
    public function setWidthOfColumn()
    {
        for ($i = 'A'; $i < 'ZZ'; $i++) {
            $this->objPHPExcel->getActiveSheet()->getColumnDimension($i)->setWidth(20);
        }
        return $this;
    }

    //color
    public function setColorOfTitles(): void
    {
        //SET COLOR OF TITLES
        $this->cellColor('A1', 'F28A8C');
        $this->cellColor('B1', 'F28A8C');
        $this->cellColor('C1', 'F28A8C');
        $this->cellColor('D1', 'F28A8C');
        $this->cellColor('E1', 'F28A8C');
        $this->cellColor('F1', 'F28A8C');
        $this->cellColor('G1', 'F28A8C');
        $this->cellColor('H1', 'F28A8C');
        $this->cellColor('I1', 'F28A8C');
        $this->cellColor('J1', 'F28A8C');
        $this->cellColor('K1', 'F28A8C');
        //varyant özellikleri
        $this->cellColor('L1', 'FF8C00');
        $this->cellColor('M1', 'FF8C00');
        $this->cellColor('N1', 'FF8C00');
        $this->cellColor('O1', 'FF8C00');
        $this->cellColor('P1', 'FF8C00');
        $this->cellColor('Q1', 'FF8C00');
        $this->cellColor('R1', 'FF8C00');
        $this->cellColor('S1', 'FF8C00');
        $this->cellColor('T1', 'FF8C00');
        $this->cellColor('U1', 'FF8C00');
        $this->cellColor('V1', 'FF8C00');
        $this->cellColor('W1', 'FF8C00');
        $this->cellColor('X1', 'FF8C00');
        $this->cellColor('Y1', 'FF8C00');
        $this->cellColor('Z1', 'FF8C00');
        $this->cellColor('AA1', 'FF8C00');
        $this->cellColor('AB1', 'FF8C00');
        $this->cellColor('AC1', 'FF8C00');

    }

    public function cellColor($cells, $color)
    {
        $this->objPHPExcel->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
            'type' => \PHPExcel_Style_Fill::FILL_SOLID,
            'startcolor' => array(
                'rgb' => $color
            )
        ));
    }

//generate button(last process)
    public function exec_button()
    {

        $this->setDocumentProperties();
        $this->addDefaultData($_GET['id']);
        $this->addData();
        $this->setActiveSheet();

//        -------


// Miscellaneous glyphs, UTF-8
//        $objPHPExcel->setActiveSheetIndex(0)
//            ->setCellValue('A4', 'Miscellaneous glyphs')
//            ->setCellValue('A5', 'éàèùâêîôûëïüÿäöüç');

        $this->renameWorkseet();
        $this->redirectHeader();
        $objWriter = \PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel2007');

        $objWriter->save('php://output');
        exit;

    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }


}