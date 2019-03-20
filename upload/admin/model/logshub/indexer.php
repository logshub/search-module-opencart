<?php
/**
 * @package	LogshubSearch
 * @author	Golden Development Ltd.
 * @copyright	Copyright (c) 2019, Golden Development Ltd. (https://www.logshub.com/)
 * @license	https://opensource.org/licenses/GPL-3.0
 * @link	https://www.logshub.com
*/

include_once DIR_SYSTEM . '/library/logshub-search-client/all.php';

class ModelLogshubIndexer extends Model
{
    public function getClient($apiUrl, $apiHash, $apiSecret)
    {
        return new \Logshub\SearchClient\Client(
            new \Logshub\SearchClient\GuzzleClient(),
            $apiUrl,
            $apiHash,
            $apiSecret
        );
    }

    public function indexProducts(\Logshub\SearchClient\Client $client, $serviceId, array $products)
    {
        foreach (array_chunk($products, 5) as $chunk) {
            $request = new \Logshub\SearchClient\Request\IndexProducts($serviceId, $chunk);
            try {
                $response = $client->indexProducts($request);
                if ($response->isSuccessful()) {
                    $this->log('INFO', 'products sent');
                }

            } catch (\Exception $e) {
                $msg = $e->getMessage();
                if ($e->getPrevious()) {
                    $msg .= '; ' . $e->getPrevious()->getMessage();
                }
                $this->log('ERROR', $msg);
            }
        }
    }

    public function indexCategories(\Logshub\SearchClient\Client $client, $serviceId, array $categories)
    {
        foreach (array_chunk($categories, 5) as $chunk) {
            $request = new \Logshub\SearchClient\Request\IndexCategories($serviceId, $chunk);
            try {
                $response = $client->indexCategories($request);
                if ($response->isSuccessful()) {
                    $this->log('INFO', 'categories sent');
                } else {
                    $this->log('ERROR', 'Unable to send categories into the API');
                }

            } catch (\Exception $e) {
                $msg = '(categories): ' . $e->getMessage();
                if ($e->getPrevious()) {
                    $msg .= '; ' . $e->getPrevious()->getMessage();
                }
                $this->log('ERROR', $msg . ', context: ' . print_r($chunk, true));
            }
        }
    }

    public function getApiCategories()
    {
        $this->load->model("catalog/category");
        $allCategories = $this->model_catalog_category->getCategories();

        $apiCategories = [];
        foreach ($allCategories as $cat){
            $path = [];
            if ($cat['parent_id']){
                $path[] = $cat['parent_id'];
            }
            $path[] = $cat['category_id'];
            $apiCategories[] = new \Logshub\SearchClient\Model\Category($cat['category_id'], [
                'name' => html_entity_decode(str_replace('&nbsp;', ' ', $cat['name'])),
                'url' => '/index.php?route=product/category&path='.implode('_', $path),
                // TODO: other attributes: description, urlImage
            ]);
        }

        return $apiCategories;
    }

    /**
     * Converts products from DB into API products, ready to send
     */
    public function getApiProducts()
    {
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');
        $products = $this->model_catalog_product->getProducts([]);

        $currency = $this->config->get('config_currency');
        $apiProducts = [];
        foreach ($products as $prod){
            if ($prod['image']) {
                $img = $this->model_tool_image->resize($prod['image'], 200, 200);
            } else {
                $img = $this->model_tool_image->resize('placeholder.png', 200, 200);
            }
            // TODO: categories not working ??
            // $categories = $this->model_catalog_product->getCategories($prod['product_id']);
            // if (empty($categories[0]['category_id'])){
            //     $categoryName = $this->model_catalog_category->getCategory($categories[0]['category_id']);
            // }

            // TODO: better url, below links to admin
            // $this->url->link('product/product', 'product_id=' . $prod['product_id']),
            $price = $this->tax->calculate($prod['price'], $prod['tax_class_id'], $this->config->get('config_tax'));
            $apiProducts[] = new \Logshub\SearchClient\Model\Product($prod['product_id'], [
                'name' => $prod['name'],
                'url' => '/index.php?route=product/product&product_id='.$prod['product_id'],
                'urlImage' => $img,
                'price' => $price,
                'currency' => $currency,
                'sku' => $prod['sku'],
                'categories' => []
                // TODO: other attributes: description, headline, availibility, review_score, review_count, price_old
            ]);
        }

        return $apiProducts;
    }

    private function log($level, $message)
    {
        $log = new Log('logshubsearch.log');
        $log->write($level . ' ' . $message);
    }
}
