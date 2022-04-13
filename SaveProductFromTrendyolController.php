<?php



namespace App\Controllers;

use App\Libraries\TrendyolIntegration;
use App\Models\ApiSettings;
use App\Models\Categories;
use App\Models\Products_tmp;
use App\Models\ProductSpecs_tmp;
use App\Models\ProductVariant_tmp;
use CodeIgniter\Controller;
use CodeIgniter\Database\Config;
use Config\Database;
/**
 * @author Mustafa YILDIZ, mustafayildiz.m@gmail.com
 */
class SaveProductFromTrendyolController extends Controller
{
    protected $trendyolData;
    protected $api_model;
    protected $user_id;
    protected $plat;
    protected $query;
    protected $arr_trendyol = [];
    protected $product_tmp_model;
    protected $product_variants_tmp;
    protected $product_specs_tmp_model;
    protected $db;

    public function __construct()
    {
        $this->product_tmp_model = new Products_tmp();
        $this->product_variants_tmp = new ProductVariant_tmp();
        $this->product_specs_tmp_model = new ProductSpecs_tmp();
        $this->db = Database::connect();
        helper('kintshop');
        $this->plat = 'trendyol';
        $this->api_model = new ApiSettings();
        $this->user_id = pKullanici();
        $this->query = $this->getUserApiData();
        $this->arr_trendyol = [
            'seller_id' => $this->query[0]['seller_id'],
            'api_key' => $this->query[0]['api_key'],
            'api_secret' => $this->query[0]['api_secret']
        ];
        $this->trendyolData = new TrendyolIntegration($this->arr_trendyol);
        $this->calculateCountData();

    }

    public function getUserApiData()
    {
        $query = $this->api_model
            ->select('platform,api_key,api_secret,seller_id,store_name')
            ->where('platform', $this->plat)
            ->where('user_id', $this->user_id)
            ->get()
            ->getResultArray();
        return $query;

    }

    public function declare_button()
    {
        $count = $this->product_tmp_model->countAll();
        if ($count == 0) {
            print '<a  class="btn btn-info kydt-btn" data-url="' . base_url('save-trendyol-data') . '">Geçici Kaydet  </a>  
<div class="alert alert-success mt-4">Toplam <strong>' . PHP_EOL . $this->calculateCountData() . PHP_EOL . '</strong> ürün</div>
';
        } elseif ($count > 0 && $count != $this->calculateCountData()) {
            print '<a class="btn btn-warning kydt-btn" data-url="' . base_url('save-trendyol-data-again') . '"> Tekrar Geçici Kaydet </a>
            <div class="alert alert-danger mt-4">Eksik Kayıtlar Var. Eksik Kayıt Sayısı : <strong>' . (intval($this->calculateCountData()) - $count) . '</strong> </div>';
        } elseif ($count == $this->product_tmp_model->countAll()) {
            print '<a style="margin-left:7px" class="btn btn-warning opt-btn" href="' . base_url('category-optimization') . '">Kategori Optimizasyonu </a>
            <a style="margin-left:7px" class="btn btn-danger kydt-btn" data-url="' . base_url('delete-saved-data') . '">Geçici Kayıtları Sil</a>
            <div class="alert alert-success mt-4">Toplam <strong>' . $count . PHP_EOL . '</strong> Ürün Başarıyla Kaydedildi </div>';
        } else {
            print '<a style="margin-left:7px" class="btn btn-danger kydt-btn" href="' . base_url('delete-saved-data') . '">Geçici Kayıtları Sil</a>';
        }
    }

    public static function loading_output_dom($action = null)
    {
        return <<<EOF
<div id="test" style="z-index: 999;" class="wrapper d-none">
    <div class="circle"></div>
    <div class="circle"></div>
    <div class="circle"></div>
    <div class="shadow"></div>
    <div class="shadow"></div>
    <div class="shadow"></div>
    <p>$action</p>
</div>
EOF;


    }

    public function save_trendyol_data_again()
    {
        $delete = $this->delete_saved_data();
        if ($delete) {
            return $this->exec_button();
        }
    }


    public function delete_saved_data()
    {
        $arr = [
            'data_from' => 'trendyol'
        ];
        $products = $this->db->table('products_tmp');
        $product_variant = $this->db->table('product_variants_tmp');
        $product_spec = $this->db->table('product_specs_tmp');
        $del_products = $products->where($arr)->delete();

        if ($del_products) {
            $del_products_variants = $product_variant->where($arr)->delete();
            if ($del_products_variants) {
                $del_product_specs = $product_spec->where($arr)->delete();
                if ($del_product_specs) {
                    $data = array('Başarılı', 'Geçici Veriler Temizlendi !', 'success', '');
                    session()->set("swall", $data);
                    session()->markAsFlashdata('swall');
                    $encode = base64_encode('trendyol');
                    return redirect()->to(base_url('platforms?plat=') . $encode);
                }
            }
        }

    }


    public function category_optimization()
    {

        $builder = $this->db->table('products_tmp');
        $builder->distinct();
        $builder->select('products_tmp.title,
        products_tmp.product_description,
        products_tmp.id,
        products_tmp.category_status,
        products_tmp.model,
        product_variants_tmp.barcode,
        product_variants_tmp.market_sale_price,
        product_variants_tmp.kintshop_sale_price,
        product_variants_tmp.image');
        $builder->where('products_tmp.vendor_id=' . pKullanici());

        $builder->where('products_tmp.category_status', '1');
        $builder->orWhere('products_tmp.category_status', '0');
        $builder->join('product_specs_tmp', 'products_tmp.id=product_specs_tmp.product_id', 'right');
        $builder->join('product_variants_tmp', 'products_tmp.id=product_variants_tmp.product_id', 'left');
        $query = $builder->get()->getResultArray();

        $builder1 = $this->db->table('product_specs_tmp');
        $builder1->select('products_tmp.id as product_id,
        product_specs_tmp.spec_value');

        $builder->where('products_tmp.category_status', '1');
        $builder->orWhere('products_tmp.category_status', '0');
        $builder1->join('products_tmp', 'products_tmp.id=product_specs_tmp.product_id', 'right');
        $specs = $builder1->get()->getResultArray();

        $data = [
            'products' => $query,
            'specs' => $specs
        ];
        return view('settings/trendyol_data', $data);
    }

    public function checked_category()
    {
        $ajax_data = [];
        if ($_POST) {
            $data = [
                'category_status' => $this->request->getPost("category_status")
            ];
            $up = $this->product_tmp_model->update($this->request->getPost("id"), $data);
            if ($up) {
                $ajax_data['ok'] = 'Güncelleme Başarılı';
                print(json_encode($ajax_data));
            } else {
                $ajax_data['ok'] = 'Güncelleme Başarısız';
                print(json_encode($ajax_data));
            }

        } else {
            $ajax_data['ok'] = 'Post yok';
            print(json_encode($ajax_data));
        }
    }

    public function select_all_button()
    {
        return '<a href="" class="btn btn-primary" id="select_all" ">Tümünü Seç</a>';
    }

    public function back_button()
    {
        return '<a href="' . base_url('category-optimization') . '" class="btn btn-danger" ">Geri</a>';
    }

    public function un_select_all_button()
    {
        return '<a href="" style="margin-left: 5px" class="btn btn-danger" id="un_select_all" ">Seçimleri Kaldır</a>';
    }

    public function after_select_button()
    {
        return '<a href="' . base_url('declare-category') . '" style="margin-left: 5px; float:right;" class="btn btn-primary" ">Seçilenlere Kategori Eşle</a>';
    }

    public function checked_all()
    {
        $data = [];
        $result = $this->product_tmp_model->findAll();
        foreach ($result as $item) {
            $data = [
                'category_status' => $this->request->getPost("category_status")
            ];
            $up = $this->product_tmp_model->update($item['id'], $data);
            if ($up) {
                $data['ok'] = 'Güncelleme Başarılı';


            }
        }
        print(json_encode($data));

    }

    public function declare_category()
    {
        $category_model = new Categories();
        $category_model->select('id,title');
        $categories = $category_model->findAll();
        $builder = $this->db->table('products_tmp');
        $builder->distinct();
        $builder->select('products_tmp.title,
        products_tmp.product_description,
        products_tmp.id,
        products_tmp.category_status,
        products_tmp.model,
        product_variants_tmp.barcode,
        product_variants_tmp.market_sale_price,
        product_variants_tmp.kintshop_sale_price,
        product_variants_tmp.image');
        $builder->where('products_tmp.category_status', '1');
        $builder->where('products_tmp.vendor_id=' . pKullanici());
        $builder->join('product_specs_tmp', 'products_tmp.id=product_specs_tmp.product_id', 'right');
        $builder->join('product_variants_tmp', 'products_tmp.id=product_variants_tmp.product_id', 'left');
        $query = $builder->get()->getResultArray();

        $builder1 = $this->db->table('product_specs_tmp');
        $builder1->select('products_tmp.id as product_id,
        product_specs_tmp.spec_value');
        $builder->where('products_tmp.category_status=1');
        $builder1->join('products_tmp', 'products_tmp.id=product_specs_tmp.product_id', 'right');
        $specs = $builder1->get()->getResultArray();

        $data = [
            'products' => $query,
            'specs' => $specs,
            'categories' => $categories
        ];

        return view('settings/declare_category_trendyol_data', $data);
    }

    public function un_checked_all()
    {
        $data = [];
        $result = $this->product_tmp_model->findAll();
        foreach ($result as $item) {
            $data = [
                'category_status' => $this->request->getPost("category_status")
            ];
            $up = $this->product_tmp_model->update($item['id'], $data);
            if ($up) {
                $data['ok'] = 'Güncelleme Başarılı';


            }
        }
        print(json_encode($data));

    }

    public function category_match()
    {
        if ($_POST) {
            $data = [
                'category_id' => $this->request->getPost("category_id"),
                'category_status' => '2'
            ];
            $query = $this->product_tmp_model->where('category_status', '1')->where('vendor_id', pKullanici())->findAll();
            foreach ($query as $item) {
                $this->product_tmp_model->update($item['id'], $data);
            }
            $data = array('Başarılı', 'Eşleşme Tamamlandı !', 'success', '');
            session()->set("swall", $data);
            session()->markAsFlashdata('swall');
            return redirect()->to(base_url('category-optimized?cat=').base64_encode($this->request->getPost("category_id")));

        }

    }
    public function category_optimized()
    {

        $category_model = new Categories();
        $category_model->select('id,title');
        $categories = $category_model->findAll();
        $builder = $this->db->table('products_tmp');
        $builder->distinct();
        $builder->select('products_tmp.title,
        products_tmp.product_description,
        products_tmp.category_id,
        products_tmp.id,
        categories.title as category_name,
        products_tmp.category_status,
        products_tmp.model,
        product_variants_tmp.barcode,
        product_variants_tmp.market_sale_price,
        product_variants_tmp.kintshop_sale_price,
        product_variants_tmp.image');
        $builder->where('products_tmp.category_status', '2');
        $builder->where('products_tmp.vendor_id=' . pKullanici());
        $builder->join('product_specs_tmp', 'products_tmp.id=product_specs_tmp.product_id' );
        $builder->join('product_variants_tmp', 'products_tmp.id=product_variants_tmp.product_id');
        $builder->join('categories', 'categories.id=products_tmp.category_id');
        $query = $builder->get()->getResultArray();

        $builder1 = $this->db->table('product_specs_tmp');
        $builder1->select('products_tmp.id as product_id,
        product_specs_tmp.spec_value');
        $builder->where('products_tmp.category_status=1');
        $builder1->join('products_tmp', 'products_tmp.id=product_specs_tmp.product_id', 'right');
        $specs = $builder1->get()->getResultArray();

        $data = [
            'products' => $query,
            'specs' => $specs,
            'categories' => $categories
        ];

        return view('settings/category_optimized', $data);

    }

    //calculating how many product are there
    public function calculateCountData(): int
    {
        return $this->trendyolData->data['trendyol_data']->size;
    }



    public function exec_button()
    {
        foreach ($this->trendyolData->data['trendyol_data']->content as $trendyol_item) {

            $data = array(
                "vendor_id" => pKullanici(),
                "title" => $trendyol_item->title,
                'data_from' => 'trendyol',
                "slug" => seo($trendyol_item->title),
                "brand_id" => $trendyol_item->brandId,
                "model" => $trendyol_item->brand,
                "product_description" => $trendyol_item->description,
            );
            $this->product_tmp_model->insert($data);
            $product_id = $this->product_tmp_model->getInsertID();
            //setting images
            $images = "";
            foreach ($trendyol_item->images as $key => $image) {
                $images .= $image->url . ",";
            }

            //variant
            $product_variant_data = array(
                "product_id" => $product_id,
                'user_id' => pKullanici(),
                "variant_title" => "",
                "variant_id" => "",
                'data_from' => 'trendyol',
                "variant_value_id" => "",
                "image" => $images,
                "barcode" => $trendyol_item->barcode,
                "market_sale_price" => $trendyol_item->listPrice,
                "kintshop_sale_price" => $trendyol_item->salePrice,
                "stock" => "",
                "stock_code" => $trendyol_item->stockCode,
            );
            $insert_product_variant_tmp = $this->product_variants_tmp->insert($product_variant_data);

            if ($insert_product_variant_tmp) {
                foreach ($trendyol_item->attributes as $attribute) {
                    $product_specs_tmp_data = array(
                        "attributeId" => $attribute->attributeId,
                        "product_id" => $product_id,
                        'data_from' => 'trendyol',
                        "spec" => $attribute->attributeName,
                        "spec_value" => $attribute->attributeValue,
                        'attributeValueId' => isset($attribute->attributeValueId) ? $attribute->attributeValueId : ''

                    );
                    $this->product_specs_tmp_model->insert($product_specs_tmp_data);
                }

            }
            // if ($ins) return redirect(base_url('platforms ? plat = ' . base64_encode('trendyol')));
        }
        $data = array('Başarılı', 'Ürün eklenmiştir !', 'success', '');
        session()->set("swall", $data);
        session()->markAsFlashdata('swall');
        $encode = base64_encode('trendyol');
        return redirect()->to(base_url('platforms ? plat = ') . $encode);
    }


    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }


}