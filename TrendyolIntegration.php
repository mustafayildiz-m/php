<?php

namespace App\Libraries;
/**
 * @author Mustafa YILDIZ, mustafayildiz.m@gmail.com
 */
class TrendyolIntegration
{
    public $data;
    protected $trendyol;

    public function __construct(array $arr_trndyol)
    {
        $this->trendyol = new \IS\PazarYeri\Trendyol\TrendyolClient();
        $this->trendyol->setSupplierId($arr_trndyol['seller_id']);
        $this->trendyol->setUsername($arr_trndyol['api_key']);
        $this->trendyol->setPassword($arr_trndyol['api_secret']);
        $this->data = $this->trendyol->product->filterProducts([
            'approved' => true,
            'size' => 100
        ]);

        //sınıf tetiklemesi
        $this->getData();

    }

    public function getData()
    {

        //api verileri hatalı ise
        if (isset($this->data->errors[0]->key)) {
            $this->data = [
                'status' => 'Api ayarlarını Kontrol Ediniz'
            ];
        } else {
            //api verileri doğru ise
            $this->data = [
                'trendyol_data' => $this->data
            ];
        }

        return $this;

    }

    /**
     *
     * Trendyol üzerindeki bütün kategorileri getirir.
     * createProduct V2 servisine yapılacak isteklerde gönderilecek categoryId
     * bilgisi bu servis kullanılarak alınacaktır.
     *
     * createProduct yapmak için en alt seviyedeki kategori ID bilgisi kullanılmalıdır.
     * Seçtiğiniz kategorinin alt kategorileri var ise bu kategori bilgisi ile ürün aktarımı yapamazsınız.
     *
     * @return array
     *
     */
    public function getCategoryTree()
    {
        return $this->trendyol->category->getCategoryTree();

    }

    /**
     *
     * Trendyol üzerindeki kategorinin özelliklerini döndürür.
     * createProduct servisine yapılacak isteklerde gönderilecek attributes bilgileri
     * ve bu bilgilere ait detaylar bu servis kullanılarak alınacaktır.
     *
     * createProduct yapmak için en alt seviyedeki kategori ID bilgisi kullanılmalıdır.
     * Seçtiğiniz kategorinin alt kategorileri var ise (leaf:true) bu kategori bilgisi ile ürün aktarımı yapamazsınız.
     *
     * @param int $categoryId
     * @return array
     *
     */
    public function getCategoryAttributes($id = null)
    {
        return $this->trendyol->category->getCategoryAttributes($id);


    }

    /**
     *
     * createProduct servisine yapılacak isteklerde gönderilecek brandId bilgisi bu servis kullanılarak alınacaktır.
     * Bir sayfada en fazla 500 adet brand bilgisi alınabilmektedir.
     *
     */
    public function getBrands()
    {
        return $this->trendyol->brand->getBrands(100, 0);

    }


    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }

}