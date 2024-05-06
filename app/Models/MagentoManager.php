<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use App\Models\MagentoModel;
use GuzzleHttp\Client;
use App\Models\OrderModel;
use App\Models\Order;
use App\Models\OrderProds;
use App\Models\BundleOptions;
use App\Models\GlsOrders;
use App\Models\BundleProdsOrder;
use App\Models\MagentoAttributes;
use App\Models\MagentoAttributeLabel;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Models\Product;
use Webkul\Attribute\Models\AttributeValue;
use Webkul\Attribute\Models\Attribute;
use App\Models\ProductAttribute;
use App\Models\ConfigurableOption;
use App\Models\ProductConfiguration;
use App\Models\OrderProdAttribute;

class MagentoManager extends Model
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private static function fetchToken(){
        return MagentoModel::getToken();
    }

    public static function getLastOrder()
    {   
       
        //$lastOrder = OrderModel::latest()->first()->value('order_id');
        $lastOrder = OrderModel::latest('id')->first();

        if($lastOrder != null)
        {
            return $lastOrder['entity_id'];
        }
        return 0;
    }
   
    public static function syncOrders()
    {
        //dd("oke");
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        if ($mysqli->connect_error) {
            die('Connection failed: ' . $mysqli->connect_error);
        }

        // Get the last order_id in the CRM database
        $lastOrderId = self::getLastOrder();

        //$query = 'SELECT o.entity_id AS order_id, o.customer_email, o.status, o.total_due, o.created_at, o.increment_id AS order_increment_id, a.firstname AS shipping_firstname, a.lastname AS shipping_lastname, a.street AS shipping_street, a.city AS shipping_city, a.region AS shipping_region, a.postcode AS shipping_postcode, a.country_id AS shipping_country FROM sales_order o INNER JOIN sales_order_address a ON o.shipping_address_id = a.entity_id WHERE status="Canceled" AND o.entity_id > ? LIMIT 80000';
        $query = 'SELECT DISTINCT o.increment_id AS order_id, o.entity_id AS entity_id, o.customer_email, o.status, o.total_paid, o.total_due, o.created_at, o.increment_id AS order_increment_id, a.firstname AS shipping_firstname, a.lastname AS shipping_lastname, a.street AS shipping_street, a.city AS shipping_city, a.region AS shipping_region, a.postcode AS shipping_postcode, a.country_id AS shipping_country, o.bold_order_comment AS comment FROM sales_order o INNER JOIN sales_order_address a ON o.shipping_address_id = a.entity_id WHERE (o.status="Processing" or o.status="anelli") AND o.entity_id > ?';
        $stmt = $mysqli->prepare($query);
        
        $stmt->bind_param("i", $lastOrderId);
        
        // Execute the query
        $stmt->execute();
        
        // Get the result set
        $result = $stmt->get_result();

        $token = self::fetchToken();

        $data = [];
        while ($row = $result->fetch_assoc()) {

            $data[] = $row;
            Order::create([
                'order_id' => $row['order_id'],
                'entity_id' => $row['entity_id'],
                'status' => $row['status'],
                'email' => $row['customer_email'],
                'firstname' => $row['shipping_firstname'],
                'lastname' => $row['shipping_lastname'],
                'totale' => $row['total_paid'] != null ? $row['total_paid'] : $row['total_due'],
                'street' => $row['shipping_street'],
                'city' => $row['shipping_city'],
                'state' => $row['shipping_region'],
                'post_code' => $row['shipping_postcode'],
                'country' => $row['shipping_country'],
                'comment' => $row['comment'],
                'order_date' => $row['created_at']
            ]);

            //self::syncOrdersProds($row['order_id'], $token);

            //sincronizzo i prodotti legati all'ordine
            $query_prods = 'select order_id, sku, name, qty_ordered, product_type, item_id, product_options from sales_order_item where order_id = ?';
            $stmt2 = $mysqli->prepare($query_prods);
        
            $stmt2->bind_param("i", $row['entity_id']);
            
            // Execute the query
            $stmt2->execute();
            
            $lastParent = '';
            $lastItemId = '';
            //$isOption = false;

            $result2 = $stmt2->get_result();
            while ($row2 = $result2->fetch_assoc()) {
                $sku = $row2['sku'];

                if ($row2['product_type'] === 'bundle'){
                    $sku = explode('-', $row2['sku'])[0];
                    $lastParent = $sku;
                    $lastItemId = $row2['item_id'];
                } else {
                    if (strpos($row2['product_options'], 'bundle_option_qty')){
                        BundleProdsOrder::create([
                            'order_id' => $row['order_id'],
                            'item_id' => $lastItemId,
                            'sku_parent' => $lastParent,
                            'sku_option' => $sku,
                            'option_name' => $row2['name'],
                            'quantity' => $row2['qty_ordered'],
                        ]);

                        continue;
                    }
                }
                    OrderProds::create([
                        'order_id' => $row2['order_id'],
                        'sku' => $row2['sku'],
                        'product_name' => $row2['name'],
                        'item_id' => $row2['item_id'],
                        'quantity' => $row2['qty_ordered'],
                        'product_type' => $row2['product_type']
                    ]);
            } 

            

        }
       
        // Close the connection
        $mysqli->close();
    
        self::getOrderProdsAttributes($lastOrderId); 
        
    }

    public static function syncOrdersDate()
    {
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        if ($mysqli->connect_error) {
            die('Connection failed: ' . $mysqli->connect_error);
        }
        
        //$query = 'SELECT o.entity_id AS order_id, o.customer_email, o.status, o.total_due, o.created_at, o.increment_id AS order_increment_id, a.firstname AS shipping_firstname, a.lastname AS shipping_lastname, a.street AS shipping_street, a.city AS shipping_city, a.region AS shipping_region, a.postcode AS shipping_postcode, a.country_id AS shipping_country FROM sales_order o INNER JOIN sales_order_address a ON o.shipping_address_id = a.entity_id WHERE status="Canceled" AND o.entity_id > ? LIMIT 80000';
        //$query = 'SELECT DISTINCT o.increment_id AS order_id, o.entity_id AS entity_id, o.customer_email, o.status, o.total_paid, o.total_due, o.created_at, o.increment_id AS order_increment_id, a.firstname AS shipping_firstname, a.lastname AS shipping_lastname, a.street AS shipping_street, a.city AS shipping_city, a.region AS shipping_region, a.postcode AS shipping_postcode, a.country_id AS shipping_country, o.bold_order_comment AS comment FROM sales_order o INNER JOIN sales_order_address a ON o.shipping_address_id = a.entity_id WHERE (o.status="Processing" or o.status="anelli") and DATE(o.created_at) = "'.date("Y-m-d").'"';
       // $query = 'SELECT DISTINCT o.increment_id AS order_id, o.entity_id AS entity_id, o.customer_email, o.status, o.total_paid, o.total_due, o.created_at, o.increment_id AS order_increment_id, a.firstname AS shipping_firstname, a.lastname AS shipping_lastname, a.street AS shipping_street, a.city AS shipping_city, a.region AS shipping_region, a.postcode AS shipping_postcode, a.country_id AS shipping_country, o.bold_order_comment AS comment FROM sales_order o INNER JOIN sales_order_address a ON o.shipping_address_id = a.entity_id WHERE (o.status="Processing" or o.status="anelli") and DATE(o.created_at) BETWEEN "2024-03-08" AND "2024-03-11"' ;
        $query = 'SELECT DISTINCT o.increment_id AS order_id, o.entity_id AS entity_id, o.customer_email, o.status, o.total_paid, o.total_due, o.created_at, o.increment_id AS order_increment_id, a.firstname AS shipping_firstname, a.lastname AS shipping_lastname, a.street AS shipping_street, a.city AS shipping_city, a.region AS shipping_region, a.postcode AS shipping_postcode, a.country_id AS shipping_country, o.bold_order_comment AS comment FROM sales_order o INNER JOIN sales_order_address a ON o.shipping_address_id = a.entity_id WHERE (o.status="Processing" or o.status="anelli") and o.created_at > (NOW() - INTERVAL 24 HOUR)';
        $stmt = $mysqli->prepare($query);
        

        $stmt->execute();

        $result = $stmt->get_result();

        $token = self::fetchToken();
        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
        ];

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
            $existingOrder = Order::where('entity_id', $row['entity_id'])->first();
            if ($existingOrder === null){
                Order::create([
                    'order_id' => $row['order_id'],
                    'entity_id' => $row['entity_id'],
                    'status' => $row['status'],
                    'email' => $row['customer_email'],
                    'firstname' => $row['shipping_firstname'],
                    'lastname' => $row['shipping_lastname'],
                    'totale' => $row['total_paid'] != null ? $row['total_paid'] : $row['total_due'],
                    'street' => $row['shipping_street'],
                    'city' => $row['shipping_city'],
                    'state' => $row['shipping_region'],
                    'post_code' => $row['shipping_postcode'],
                    'country' => $row['shipping_country'],
                    'comment' => $row['comment'],
                    'order_date' => $row['created_at']
                ]);
    
                //self::syncOrdersProds($row['order_id'], $token);
    
                //sincronizzo i prodotti legati all'ordine
                $query_prods = 'select order_id, sku, name, qty_ordered, product_type, item_id, product_options from sales_order_item where order_id = ?';
                $stmt2 = $mysqli->prepare($query_prods);
            
                $stmt2->bind_param("i", $row['entity_id']);
                
                // Execute the query
                $stmt2->execute();
                
                $lastParent = '';
                $lastItemId = '';
                //$isOption = false;
    
                $result2 = $stmt2->get_result();
                while ($row2 = $result2->fetch_assoc()) {
                    $sku = $row2['sku'];
    
                    if ($row2['product_type'] === 'bundle'){
                        $sku = explode('-', $row2['sku'])[0];
                        $lastParent = $sku;
                        $lastItemId = $row2['item_id'];
                    } else {
                        //Caso simple product
                        if (strpos($row2['product_options'], '"bundle_selection_attributes"')){
                            BundleProdsOrder::create([
                                'order_id' => $row['order_id'],
                                'item_id' => $lastItemId,
                                'sku_parent' => $lastParent,
                                'sku_option' => $sku,
                                'option_name' => $row2['name'],
                                'quantity' => $row2['qty_ordered'],
                            ]);

                            self::decrementEcommerceQuantity($sku, (int)$row2['qty_ordered']);
                            continue;
                        }
                    }

                    OrderProds::create([
                        'order_id' => $row2['order_id'],
                        'sku' => $row2['sku'],
                        'product_name' => $row2['name'],
                        'item_id' => $row2['item_id'],
                        'quantity' => $row2['qty_ordered'],
                        'product_type' => $row2['product_type']
                    ]);

                    if ($row2['product_type'] === 'simple'){
                        self::decrementEcommerceQuantity($row2['sku'], (int)$row2['qty_ordered']);
                    }
                        
                }
    
                try {
    
                    $response = $client->get('http://magento.efesti.com/rest/V1/orders/'.$row['entity_id'], [
                        'headers' => $headers,
                    ]); 
            
                    $resultOrder = $response->getBody()->getContents();
            
                    $responseData = json_decode($resultOrder, true);
    
                    foreach ($responseData['items'] as $item){
                        if (str_contains(strtolower($item['name']), 'anello scudo') && $item['product_type'] == 'configurable'){

                            $currentOrder = Order::where('entity_id', $row['entity_id'])->first();
                            $currentOrder->update([
                                'status' => 'anelli',
                            ]);
                            
                            foreach ($item['product_option']['extension_attributes']['configurable_item_options'] as $confOption) {
                                $attribute = MagentoAttributes::join('mg_attribute_labels', 'mg_attributes.attribute_id', '=', 'mg_attribute_labels.attribute_id')->where('mg_attributes.attribute_id', $confOption['option_id'])->where('mg_attribute_labels.option_id', $confOption['option_value'])->first();
                                if (OrderProdAttribute::where('order_id', $row['entity_id'])->where('sku', $item['sku'])->where('item_id', $item['item_id'])->where('attribute_value', $attribute->value)->get()->count() == 0){
                                        OrderProdAttribute::create([
                                            'order_id' => $row['entity_id'],
                                            'item_id' => $item['item_id'],
                                            'sku' => $item['sku'],
                                            'attribute_value' => $attribute->value
                                        ]);
                                }
    
                            }
                            
                            try{
                                foreach($item['product_option']['extension_attributes']['custom_options'] as $customOption){
                                    ini_set('memory_limit', '2G');
                                    $host = config('magento-service.magento-db-host');
                                    $port = config('magento-service.magento-db-port');
                                    $database = config('magento-service.magento-db-name');
                                    $username = config('magento-service.magento-db-user');
                                    $password = config('magento-service.magento-db-pass');
                                    $mysqli = mysqli_connect($host, $username, $password, $database);
    
                                    if ($mysqli->connect_error) {
                                        die('Connection failed: ' . $mysqli->connect_error);
                                    }
    
                                    try{
                                        
                                        $query = "SELECT     sp.sku AS simple_sku,     cp.sku AS configurable_sku FROM
                                        catalog_product_relation AS pr JOIN     catalog_product_entity AS sp ON pr.child_id = sp.entity_id JOIN     catalog_product_entity AS cp ON pr.parent_id = cp.entity_id WHERE     sp.sku = '".$item['sku']."'";
    
                                        $stmt = $mysqli->prepare($query);
    
                                        // Execute the query
                                        $stmt->execute();
    
                                        $queryRes = $stmt->get_result();
    
                                        $row1 = $queryRes->fetch_assoc();
    
                                        $response1 = $client->get('http://magento.efesti.com/rest/default/V1/products/'.$row1['configurable_sku'], [
                                            'headers' => $headers,
                                        ]); 
                                
                                        
                                        $result1 = $response1->getBody()->getContents();
                                        
                                        $responseData1 = json_decode($result1, true);     
    
                                        //dd($responseData1);
                                        foreach ($responseData1["options"] as $option){
                                            try{
                                                //dd($option['values'][0]);
                                                foreach ($option['values'] as $value) {
                                                    //dd($value['title']);
                                                    //if (OrderProdAttribute::where('order_id', $row['entity_id'])->where('sku', $item['sku'])->where('item_id', $item['item_id'])->where('attribute_value', $value['title'])->get()->count() == 0){
                                                        //dd($value);
                                                        if ($value['option_type_id'] === (int)$customOption['option_value']){
                                                            OrderProdAttribute::create([
                                                                'order_id' => $row['entity_id'],
                                                                'item_id' => $item['item_id'],
                                                                'sku' => $item['sku'],
                                                                'attribute_value' => $value['title']
                                                            ]);
                                                        }
                                                    //}
                                                    
                                                }
                                            }catch (\Excpetion $e){
    
                                            }
                                        }
                                    }catch (\Exception $e){
                                        echo $e->getMessage();
                                    }
                                }
                            }catch(\Exception $e){

                            }
    
                        }
                    }
        
                }catch (\Exception $e){
        
                }
            }
            

        }
       
        // Close the connection
        $mysqli->close();

    }

    private static function decrementEcommerceQuantity($sku, $quantity){
        //Scalo quantità e-commerce nel caso di un prodotto semplice NON compreso nel bundle
        try{
            $productAttribute = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->select('attribute_values.id')
            ->where('products.sku', $sku)
            ->where('attributes.code', 'qty_ecommerce')
            ->first();
    
            $attributeValue = AttributeValue::where('id', $productAttribute->id)->first();
    
            
            //Aggiorna quantità ecommerce
            $attributeValue->update([
                'text_value' => strval((int)$attributeValue->text_value - (int)$quantity)
            ]);
    
            /* $product = Product::where('sku', $sku)->first();
            
            $product->update([
                'quantity' => $product->quantity - (int)$quantity,
            ]); */
        }catch (\Exception $e){
            echo "id null per sku:".$sku;
        }
        
    }

    private static function getOrderProdsAttributes($lastOrderId){
        $token = self::fetchToken();
        $client = new Client();

        $selectedattributes = [];

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
        ];

        $orders = DB::table('orders')->where('entity_id', '>', $lastOrderId)->get();

        foreach ($orders as $order){
            //if (OrderProdAttribute::where('order_id', $orderProd->order_id)->where('sku', $orderProd->sku)->get()->count() == 0){
                try {

                    $response = $client->get('http://magento.efesti.com/rest/V1/orders/'.$order->entity_id, [
                        'headers' => $headers,
                    ]); 
            
                    $result = $response->getBody()->getContents();
            
                    $responseData = json_decode($result, true);

                    foreach ($responseData['items'] as $item){
                        if (str_contains(strtolower($item['name']), 'anello scudo') && $item['product_type'] == 'configurable'){
                            foreach ($item['product_option']['extension_attributes']['configurable_item_options'] as $confOption) {
                                $attribute = MagentoAttributes::join('mg_attribute_labels', 'mg_attributes.attribute_id', '=', 'mg_attribute_labels.attribute_id')->where('mg_attributes.attribute_id', $confOption['option_id'])->where('mg_attribute_labels.option_id', $confOption['option_value'])->first();
                                if (OrderProdAttribute::where('order_id', $order->entity_id)->where('sku', $item['sku'])->where('item_id', $item['item_id'])->where('attribute_value', $attribute->value)->get()->count() == 0){
                                        OrderProdAttribute::create([
                                            'order_id' => $order->entity_id,
                                            'item_id' => $item['item_id'],
                                            'sku' => $item['sku'],
                                            'attribute_value' => $attribute->value
                                        ]);
                                }
    
                            }
                            
                            try{
                                foreach($item['product_option']['extension_attributes']['custom_options'] as $customOption){
                                    ini_set('memory_limit', '2G');
                                    $host = config('magento-service.magento-db-host');
                                    $port = config('magento-service.magento-db-port');
                                    $database = config('magento-service.magento-db-name');
                                    $username = config('magento-service.magento-db-user');
                                    $password = config('magento-service.magento-db-pass');
                                    $mysqli = mysqli_connect($host, $username, $password, $database);
    
                                    if ($mysqli->connect_error) {
                                        die('Connection failed: ' . $mysqli->connect_error);
                                    }
    
                                    try{
                                        
                                        $query = "SELECT     sp.sku AS simple_sku,     cp.sku AS configurable_sku FROM
                                        catalog_product_relation AS pr JOIN     catalog_product_entity AS sp ON pr.child_id = sp.entity_id JOIN     catalog_product_entity AS cp ON pr.parent_id = cp.entity_id WHERE     sp.sku = '".$item['sku']."'";
    
                                        $stmt = $mysqli->prepare($query);
    
                                        // Execute the query
                                        $stmt->execute();
    
                                        $queryRes = $stmt->get_result();
    
                                        $row = $queryRes->fetch_assoc();
    
                                        $response1 = $client->get('http://magento.efesti.com/rest/default/V1/products/'.$row['configurable_sku'], [
                                            'headers' => $headers,
                                        ]); 
                                
                                        
                                        $result1 = $response1->getBody()->getContents();
                                        
                                        $responseData1 = json_decode($result1, true);     
    
                                        //dd($responseData1);
                                        foreach ($responseData1["options"] as $option){
                                            try{
                                                //dd($option['values'][0]);
                                                foreach ($option['values'] as $value) {
                                                    //dd($value['title']);
                                                    if (OrderProdAttribute::where('order_id', $order->entity_id)->where('sku', $item['sku'])->where('item_id', $item['item_id'])->where('attribute_value', $value['title'])->get()->count() == 0){
                                                        //dd($value);
                                                        if ($value['option_type_id'] === (int)$customOption['option_value']){
                                                            OrderProdAttribute::create([
                                                                'order_id' => $order->entity_id,
                                                                'item_id' => $item['item_id'],
                                                                'sku' => $item['sku'],
                                                                'attribute_value' => $value['title']
                                                            ]);
                                                        }
                                                    }
                                                    
                                                }
                                            }catch (\Excpetion $e){
    
                                            }
                                        }
                                    }catch (\Exception $e){
                                        echo $e->getMessage();
                                    }
                                }
                            }catch(\Exception $e){

                            }

                        }
                    }
                    /* try{
                        foreach ($responseData['items'] as $item){
                            if (str_contains(strtolower($item['name']), 'anello scudo')){
                                foreach ($item['product_option']['extension_attributes']['configurable_item_options'] as $confOption) {
                                    $attribute = MagentoAttributes::join('mg_attribute_labels', 'mg_attributes.attribute_id', '=', 'mg_attribute_labels.attribute_id')->where('mg_attributes.attribute_id', $confOption['option_id'])->where('mg_attribute_labels.option_id', $confOption['option_value'])->first();
                                    if (OrderProdAttribute::where('order_id', $orderProd->order_id)->where('sku', $orderProd->sku)->where('item_id', $item['item_id'])->where('attribute_value', $attribute->value)->get()->count() == 0){
                                            OrderProdAttribute::create([
                                                'order_id' => $orderProd->order_id,
                                                'item_id' => $item['item_id'],
                                                'sku' => $orderProd->sku,
                                                'attribute_value' => $attribute->value
                                            ]);
                                    }
                                    
        
                                }

                                //dd($item['product_option']['extension_attributes']['custom_options']);
                                //array_push($selectedattributes, $item['product_option']['extension_attributes']['configurable_item_options']);
                                
                                foreach($item['product_option']['extension_attributes']['custom_options'] as $customOption){
                                    ini_set('memory_limit', '2G');
                                    $host = config('magento-service.magento-db-host');
                                    $port = config('magento-service.magento-db-port');
                                    $database = config('magento-service.magento-db-name');
                                    $username = config('magento-service.magento-db-user');
                                    $password = config('magento-service.magento-db-pass');
                                    $mysqli = mysqli_connect($host, $username, $password, $database);

                                    if ($mysqli->connect_error) {
                                        die('Connection failed: ' . $mysqli->connect_error);
                                    }

                                    try{
                                        
                                        $query = "SELECT     sp.sku AS simple_sku,     cp.sku AS configurable_sku FROM
                                        catalog_product_relation AS pr JOIN     catalog_product_entity AS sp ON pr.child_id = sp.entity_id JOIN     catalog_product_entity AS cp ON pr.parent_id = cp.entity_id WHERE     sp.sku = '".$orderProd->sku."'";

                                        $stmt = $mysqli->prepare($query);

                                        // Execute the query
                                        $stmt->execute();

                                        $queryRes = $stmt->get_result();

                                        $row = $queryRes->fetch_assoc();

                                        $response1 = $client->get('https://staging.amabilejewels.it/rest/default/V1/products/'.$row['configurable_sku'], [
                                            'headers' => $headers,
                                        ]); 
                                
                                        
                                        $result1 = $response1->getBody()->getContents();
                                        
                                        $responseData1 = json_decode($result1, true);     

                                        //dd($responseData1);
                                        foreach ($responseData1["options"] as $option){
                                            try{
                                                //dd($option['values'][0]);
                                                foreach ($option['values'] as $value) {
                                                    dd($value->title);
                                                    if (OrderProdAttribute::where('order_id', $orderProd->order_id)->where('sku', $orderProd->sku)->where('item_id', $item['item_id'])->where('attribute_value', $value->title)->get()->count() == 0){
                                                        dd($value);
                                                        if ($value['option_type_id'] === (int)$customOption['option_value']){
                                                            OrderProdAttribute::create([
                                                                'order_id' => $orderProd->order_id,
                                                                'item_id' => $item['item_id'],
                                                                'sku' => $orderProd->sku,
                                                                'attribute_value' => $value['title']
                                                            ]);
                                                        }
                                                    }
                                                    
                                                }
                                            }catch (\Excpetion $e){

                                            }
                                        }
                                    }catch (\Exception $e){
                                        echo $e->getMessage();
                                    }
                                }
                            }
                        }
                        
                    }catch(\Exception $e){
                        echo $e->getMessage();
                    } */
        
                }catch (\Exception $e){
        
                }
            //}
            
        } 
        
    }

    //Recupera gli attributi prodotto da magento
    public static function syncAttributes(){
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        if ($mysqli->connect_error) {
            die('Connection failed: ' . $mysqli->connect_error);
        }

        $query = 'select attribute_code, attribute_id from eav_attribute';
        $stmt = $mysqli->prepare($query);
        
        // Execute the query
        $stmt->execute();
        
        // Get the result set
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()){
            if (MagentoAttributes::where('attribute_id', $row['attribute_id'])->first() === null){
                MagentoAttributes::create([
                    'attribute_id' => $row['attribute_id'],
                    'attribute_code' => $row['attribute_code']
                ]);
            }

            $query = 'SELECT DISTINCT
                    eav_attribute_option.option_id,
                    eav_attribute_option_value.value AS option_label,
                    eav_attribute.attribute_code
                FROM
                    eav_attribute
                JOIN
                    eav_attribute_option
                    ON eav_attribute.attribute_id = eav_attribute_option.attribute_id
                JOIN
                    eav_attribute_option_value
                    ON eav_attribute_option.option_id = eav_attribute_option_value.option_id
                WHERE
                    eav_attribute.attribute_code = "'.$row['attribute_code'].'"';
            $stmt = $mysqli->prepare($query);

            $stmt->execute();
        
        // Get the result set
            $result2 = $stmt->get_result();

            while ($row2 = $result2->fetch_assoc()){
                if (MagentoAttributeLabel::where('attribute_id', $row['attribute_id'])->where('option_id', $row2['option_id'])->first() === null){
                    MagentoAttributeLabel::create([
                        'attribute_id' => $row['attribute_id'],
                        'option_id' => $row2['option_id'],
                        'value' => $row2['option_label']
                    ]);
                }    
            }
        }
        // Close the connection
        $mysqli->close();
    }
    
    private static function syncOrdersProds($id, $token)
    {
        //$token = self::fetchToken();
        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer '.$token,
          ];

        $response = $client->get(config('app.filtered_orders_url') . $id, [
            'headers' => $headers,
            'verify' => false
        ]); 

        $result = $response->getBody()->getContents();

        $responseData = json_decode($result, true);

        
        //dd($responseData['items'][0]['name']);
        foreach( $responseData['items'] as $item ){
            //dd($item['name']);
            OrderProds::create([
                'order_id' => $id,
                'sku' => $item['sku'],
                'quantity' => $item['qty_ordered'],
                'product_name' => $item['name'],
                'item_id' => $item['item_id'],
                'created_at' => $item['created_at'],
                'updated_at' => $item['updated_at'],
            ]);
        }
    }

    public static function syncOrderStatus($id, $items, $track_number){
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        if ($mysqli->connect_error) {
            die('Connection failed: ' . $mysqli->connect_error);
        }

        $token = self::fetchToken();
        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
        ];

        /* $payload = [
            'items' => $items,
            'notify' => true,
            'appendComment' => true,
            'comment' => [
                'extension_attributes' => [],
                'comment' => 'Commento',
                'is_visible_on_front' => 0
            ],
            'tracks' => [
                [
                    'extension_attributes' => [],
                    'track_number' => $track_number,
                    'title' => 'GLS',
                    'carrier_code' => 'gls'
                ]
            ],
            'packages' => [
                [
                    'extension_attributes' => []
                ]
            ]
        ]; */
        
        try{

            $payload = [
                "entity" => [
                    "entity_id" => $id,
                    "state" => "processing",
                    "status" => "da_spedire"
                ]
            ];

            $client->post('http://magento.efesti.com/rest/V1/orders', [
                'headers' => $headers,
                'json' => $payload,
            ]);
            /* $payload = [
                'items' => $items,
                'notify' => true,
                'appendComment' => true,
                'comment' => [
                    'extension_attributes' => [],
                    'comment' => 'Commento',
                    'is_visible_on_front' => 0
                ],
                'tracks' => [
                    [
                        'extension_attributes' => [],
                        'track_number' => $track_number,
                        'title' => 'GLS',
                        'carrier_code' => 'gls'
                    ]
                ],
                'packages' => [
                    [
                        'extension_attributes' => []
                    ]
                ]
            ];

            $client->post('http://magento.efesti.com/rest/default/V1/order/'. $id .'/ship', [
                'headers' => $headers,
                'json' => $payload,
            ]); */

            /* $query = 'UPDATE sales_order SET status="da_spedire", state="processing" WHERE entity_id = '.$id;
            $stmt = $mysqli->prepare($query);
            
            // Execute the query
            $stmt->execute();

            $query = 'UPDATE sales_order_grid SET status="da_spedire" WHERE entity_id = '.$id;
            $stmt = $mysqli->prepare($query);
            
            // Execute the query
            $stmt->execute(); */

            return true;
        }catch(\Exception $e){
            session()->flash('error', "Errore nel processo di spedizione");
            return false;
        }
    }

    //Aggiunge un prodotto al backend
    public static function addProduct($data){
        $token = self::fetchToken();
        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
        ];

        if ($data->prod_type === '3'){
            return self::addBundleProduct($headers, $data, $client);
        }else if($data->prod_type === '1'){
            return self::addSimpleProduct($headers, $data, $client);
        }else{
            return self::addConfigurableProduct($headers, $data, $client);
        }

    }

    public static function updateProduct($data){
        $token = self::fetchToken();
        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
        ];

        if ($data->prod_type === '3'){
            return self::updateBundleProduct($headers, $data, $client);
        }else{
            return self::updateSimpleProduct($headers, $data, $client);
        }
    }

    private static function updateSimpleProduct($headers, $data, $client){
        $qty_dif = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                ->where('attributes.code', 'qty_diffettosi')
                ->where('sku', '=', $data->sku)
                ->select('products.name as name', 'products.sku as sku', 'attribute_values.text_value as value', 'products.price as price')
                ->first();
        //qdd($data);

        $newQuantity = $data->qty_ecommerce - ((int)$data->qty_diffettosi - (int)$qty_dif->value);
        //dd($newQuantity);
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        if ($mysqli->connect_error) {
            die('Connection failed: ' . $mysqli->connect_error);
        }

        $query = "SELECT     sp.sku AS simple_sku,     cp.sku AS configurable_sku FROM
                catalog_product_relation AS pr JOIN     catalog_product_entity AS sp ON pr.child_id = sp.entity_id JOIN     catalog_product_entity AS cp ON pr.parent_id = cp.entity_id WHERE     sp.sku = '".$data->sku."'";
    
        $stmt = $mysqli->prepare($query);

        // Execute the query
        $stmt->execute();

        $queryRes = $stmt->get_result();

        $row = $queryRes->fetch_assoc();

        $payload = [
            'product' => [
                'sku' => $data->sku,
                'name' => $data->name,
                'attribute_set_id' => 4,
                'price' => (float)$data->price,
                //'status' => 1,
                //'visibility' => $row == null ? 4 : 1,
                'type_id' => 'simple',
                //'weight' => '0',
                'extension_attributes' => [
                    /* 'category_links' => [
                        [
                            'position' => 0,
                            'category_id' => '2'
                        ]
                    ], */
                    'stock_item' => [
                        'qty' => $newQuantity,
                        'is_in_stock' => $newQuantity > 0 ? true : false
                    ]
                ]
            ]
        ];

        try{
            $response = $client->request('PUT', 'http://magento.efesti.com/rest/V1/products/'.$data->sku, [
                'headers' => $headers,
                'json' => $payload,
            ]);

            return true;
        }catch(\Exception $e){
            dd($e);
            session()->flash('error', "Errore nella creazione del prodotto");
            return false;
        }

    }

    private static function updateBundleProduct($headers, $data, $client){
        $done = true;
        $count = 0;
        $bundleOptions = [];

        //Create bundle options
        while ($done){
            if ($data['option'.$count] != null){
                $bundleOption = [
                    'title' => $data['name'.$count],
                    'required' => true,
                    'type' => 'radio',
                    'position' => 0,
                    'sku' => $data->sku,
                    'product_links' => [
                        [
                            'sku' => $data['option'.$count],
                            'option_id' => 0,
                            'qty' => $data['qty'.$count],
                            'position' => 1,
                            'is_default' => true,
                            'price' => $data->dynamic_price === '0' ? 0 : $data['price'.$count],
                            'price_type' => 0,
                            'can_change_quantity' => $data['change'.$count] === null ? 0 : 1,
                        ],
                
                    ],
                ];

                array_push($bundleOptions, $bundleOption);
            }else{
                $done = false;
            }

            $count ++;
        }
        
        // Include an empty array if there are no bundle options
        if (empty($bundleOptions)) {
            $bundleOptions = [];
        }

        $payload = [
            'product' => [
                'sku' => $data->sku,
                'name' => $data->name,
                'attribute_set_id' => 4,
                //'status' => 1,
                //'visibility' => 4,
                'price' => (float)$data->price,
                'type_id' => 'bundle',
                //'weight' => '0',
                'extension_attributes' => [
                    'bundle_product_options' => $bundleOptions,
                    'stock_item' => [
                        'qty' => $data->qty_ecommerce,
                        'is_in_stock' => true,
                    ]/* ,
                    'website_ids' => [1],
                    'category_links' => [
                        [
                            'position' => 0,
                            'category_id' => '2',
                        ],
                    ], */
                ]/* ,
                'custom_attributes' => [
                    [
                        'attribute_code' => 'price_view',
                        'value' => '1',
                    ],
                ], */

            ]
        ];

        try{

            $response = $client->request('PUT', 'http://magento.efesti.com/rest/V1/products/'.$data->sku, [
                'headers' => $headers,
                'json' => $payload,
            ]);

            if (count($bundleOptions) == 0){
                $response = $client->get('http://magento.efesti.com/rest/V1/bundle-products/'.$data->sku.'/options/all', [
                    'headers' => $headers,
                    'json' => $payload,
                ]);

                $result = $response->getBody()->getContents();

                $options = json_decode($result, true);

                //dd($options);
                //Rimuovo quindi le opzioni del bundle product
                foreach($options as $option){
                    //dd($option['option_id']);
                    $response = $client->request('DELETE', 'http://magento.efesti.com/rest/V1/bundle-products/'.$data->sku.'/options/'.$option['option_id'], [
                        'headers' => $headers,
                    ]);
                }
                
            }
            //Recupero le opzioni del bundle product

            return true;
        }catch(\Exception $e){
            session()->flash('error', "Errore nella creazione del prodotto");
            return false;
        }

    }

    private static function addSimpleProduct($headers, $data, $client){
        $payload = [
            'product' => [
                'sku' => $data->sku,
                'name' => $data->name,
                'attribute_set_id' => 4,
                'price' => (float)$data->price,
                'status' => 0,
                'visibility' => 1,
                'type_id' => 'simple',
                'weight' => '0',
                'extension_attributes' => [
                    'category_links' => [
                        [
                            'position' => 0,
                            'category_id' => '2'
                        ]
                    ],
                    'stock_item' => [
                        'qty' => ((int)$data->qty_ecommerce - (int)$data->qty_diffettosi),
                        'is_in_stock' => ((int)$data->qty_ecommerce - (int)$data->qty_diffettosi) > 0 ? true : false
                    ]
                ],
                'custom_attributes' => [
                    [
                        'attribute_code' => 'url_key',
                        'value' => ''.$data->sku.''
                    ],
                    [
                        'attribute_code' => 'url_path',
                        'value' => ''.$data->sku.''
                    ],
                    [
                        'attribute_code' => 'required_options',
                        'value' => '0'
                    ],
                    [
                        'attribute_code' => 'has_options',
                        'value' => '0'
                    ],
                    [
                        'attribute_code' => 'meta_title',
                        'value' => $data->name
                    ],
                    [
                        'attribute_code' => 'meta_keyword',
                        'value' => ''
                    ],
                    /* [
                        'attribute_code' => 'meta_description',
                        'value' => 'crm meta description'
                    ], */
                    [
                        'attribute_code' => 'tax_class_id',
                        'value' => '4'
                    ],
                    [
                        'attribute_code' => 'category_ids',
                        'value' => []
                    ],
                    /* [
                        'attribute_code' => 'short_description',
                        'value' => $data->description
                    ],
                    [
                        'attribute_code' => 'description',
                        'value' => $data->description
                    ], */
                    [
                        'attribute_code' => 'nc_quantita',
                        'value' => '4,5'
                    ]
                ]
            ]
        ];

        try{

            $response = $client->post('http://magento.efesti.com/rest/V1/products', [
                'headers' => $headers,
                'json' => $payload,
            ]);

            return true;
        }catch(\Exception $e){
            session()->flash('error', "Errore nella creazione del prodotto");
            return false;
        }
    }

    //Crea un bundle product
    private static function addBundleProduct($headers, $data, $client){
        $done = true;
        $count = 0;
        $bundleOptions = [];

        //dd($data);
        //Create bundle options
        while ($done){
            if ($data['option'.$count] != null){
                $bundleOption = [
                    'title' => $data['name'.$count],
                    'required' => true,
                    'type' => 'radio',
                    'position' => 0,
                    'sku' => $data->sku,
                    'product_links' => [
                        [
                            'sku' => $data['option'.$count],
                            'option_id' => 0,
                            'qty' => $data['qty'.$count],
                            'position' => 1,
                            'is_default' => true,
                            'price' => $data->dynamic_price === null ? 0 : $data['price'.$count],
                            'price_type' => 0,
                            'can_change_quantity' => $data['change'.$count] === null ? 0 : 1,
                        ],
                
                    ],
                ];

                array_push($bundleOptions, $bundleOption);
            }else{
                $done = false;
            }

            $count ++;
        }

        $payload = [
            'product' => [
                'sku' => $data->sku,
                'name' => $data->name,
                'attribute_set_id' => 4,
                'status' => 0,
                'visibility' => 1,
                'price' => (float)$data->price,
                'type_id' => 'bundle',
                'weight' => '0',
                'extension_attributes' => [
                    'bundle_product_options' => $bundleOptions,
                    'stock_item' => [
                        'qty' => $data->qty_ecommerce,
                        'is_in_stock' => true,
                    ],
                    //'website_ids' => [1],
                    'category_links' => [
                        [
                            'position' => 0,
                            'category_id' => '2',
                        ],
                    ],
                ],
                'custom_attributes' => [
                    [
                        'attribute_code' => 'price_view',
                        'value' => '0',
                    ]
                ],
            ],
            'saveOptions' => false,
        ];

        try{

            $response = $client->post('http://magento.efesti.com/rest/V1/products', [
                'headers' => $headers,
                'json' => $payload,
            ]);

            if($data->dynamic_price === null){
                ini_set('memory_limit', '2G');
                $host = config('magento-service.magento-db-host');
                $port = config('magento-service.magento-db-port');
                $database = config('magento-service.magento-db-name');
                $username = config('magento-service.magento-db-user');
                $password = config('magento-service.magento-db-pass');
                $mysqli = mysqli_connect($host, $username, $password, $database);

                if ($mysqli->connect_error) {
                    die('Connection failed: ' . $mysqli->connect_error);
                }


                $query = "UPDATE catalog_product_entity_int
                        SET value = 1  
                        WHERE entity_id = (SELECT entity_id FROM catalog_product_entity WHERE sku = '".$data->sku."')
                        AND attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'price_type')";
                
                $stmt = $mysqli->prepare($query);
                
                $stmt->execute();
            }


            return true;
        }catch(\Exception $e){
            session()->flash('error', "Errore nella creazione del prodotto");
            return false;
        }

    }

    //Crea un prodotto configurabile
    private static function addConfigurableProduct($headers, $data, $client){   
        $payload = [
            'product' => [
                'sku' => $data->sku,
                'name' => $data->name,
                'attribute_set_id' => 4,
                'status' => 0,
                'visibility' => 1,
                'type_id' => 'configurable',
                'weight' => '0',
                'extension_attributes' => [
                    'category_links' => [
                        [
                            'position' => 0,
                            'category_id' => '2'
                        ]
                        ],
                        'stock_item' => [
                           /*  'qty' => $data['quantity'.$count], */
                            'is_in_stock' => true
                        ]
                ],
                'custom_attributes' => [
                    [
                        'attribute_code' => 'url_key',
                        'value' => ''.$data->sku.''
                    ],
                    [
                        'attribute_code' => 'url_path',
                        'value' => ''.$data->sku.''
                    ],
                    [
                        'attribute_code' => 'required_options',
                        'value' => '0'
                    ],
                    [
                        'attribute_code' => 'has_options',
                        'value' => '0'
                    ],
                    [
                        'attribute_code' => 'meta_title',
                        'value' => $data->name
                    ],
                    [
                        'attribute_code' => 'meta_keyword',
                        'value' => ''
                    ],
                    /* [
                        'attribute_code' => 'meta_description',
                        'value' => 'crm meta description'
                    ], */
                    [
                        'attribute_code' => 'tax_class_id',
                        'value' => '4'
                    ],
                    [
                        'attribute_code' => 'category_ids',
                        'value' => []
                    ],
                    [
                        'attribute_code' => 'short_description',
                        'value' => $data->description
                    ],
                    [
                        'attribute_code' => 'description',
                        'value' => $data->description
                    ]/* ,
                    [
                        'attribute_code' => 'nc_quantita',
                        'value' => '4,5'
                    ] */
                ]
            ]
        ];

        try{

            $response = $client->post('http://magento.efesti.com/rest/V1/products', [
                'headers' => $headers,
                'json' => $payload,
            ]);

            return true;
        }catch(\Exception $e){
            session()->flash('error', "Errore nella creazione del prodotto");
            return false;
        }
    }

    public static function deleteProduct($sku){
        $token = self::fetchToken();
        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
        ];

        try{
            $response = $client->request('DELETE','http://magento.efesti.com/rest/V1/products/'.$sku, [
                'headers' => $headers,
            ]);

            return true;
        }catch(\Exception $e){
            session()->flash('error', "Errore nella eliminazione del prodotto");
            return false;
        }
    }

    public static function updateOrderStatus(){
        $orders = GlsOrders::select('entity_id')->get();

        
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        if ($mysqli->connect_error) {
            die('Connection failed: ' . $mysqli->connect_error);
        }

        foreach($orders as $order){
            $query ="SELECT status, state FROM sales_order WHERE entity_id = ".$order->entity_id;

            $stmt = $mysqli->prepare($query);
            
            $stmt->execute();
            
            // Get the result set
            $result = $stmt->get_result();

            $row = $result->fetch_assoc();

            if ($row['status'] === 'complete' || $row['state'] === 'complete'){
                GlsOrders::where('entity_id', $order->entity_id)->delete();

                $mainOrder = Order::where('entity_id', $order->entity_id)->first();

                if ($mainOrder !== null){
                    $mainOrder->update([
                        'status' => 'complete',
                    ]);
                }
                
            }
        }

    }

    public static function getOrders(){
        $token = self::fetchToken();
        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer '.$token,
          ];

        $response = $client->get(config('app.orders_url'), [
            'headers' => $headers
        ]); 

        $result = $response->getBody()->getContents();

        $responseData = json_decode($result, true);

        
        return $responseData;
    }

    //Restituisce l'immagine associata al prodotto se esiste
    public static function GetProdImage($sku)
    {
       
        $token = self::fetchToken();
        $client = new Client();
        $requst_url = "http://magento.efesti.com/rest/default/V1/products/" . $sku;
        $image = "http://magento.efesti.com/media/catalog/product/404.jpg";

         try{
            $headers = [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ];
            
            $response = $client->get($requst_url, [
                'headers' => $headers
            ]);
            
            $result = $response->getBody()->getContents();
           
          
            $data = json_decode($result);
            if(isset($data->media_gallery_entries) && !empty($data->media_gallery_entries) ){
                $filePath = $data->media_gallery_entries[0]->file;
               
                $image = "http://magento.efesti.com/media/catalog/product/" . $filePath;
               
            }else{
                $image = "http://magento.efesti.com/media/catalog/product/404.jpg";
            }
           
            return $image;
        }catch (\Exception $e) {
            
            return $image;
        }
       
    }

    //Prodotti semplici
    public static function syncProducts(){
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        if ($mysqli->connect_error) {
            die('Connection failed: ' . $mysqli->connect_error);
        }

        $query ="SELECT DISTINCT
            catalog_product_entity.sku AS sku,
            catalog_product_entity_varchar.value AS name,
            cataloginventory_stock_item.qty AS quantity,
            catalog_product_entity.type_id AS type,
            catalog_product_entity_decimal.value AS price
            FROM catalog_product_entity 
            JOIN catalog_product_entity_varchar ON catalog_product_entity.entity_id = catalog_product_entity_varchar.entity_id 
            AND catalog_product_entity_varchar.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 4)
            JOIN cataloginventory_stock_item ON catalog_product_entity.entity_id = cataloginventory_stock_item.product_id 
            JOIN catalog_product_entity_decimal ON catalog_product_entity.entity_id = catalog_product_entity_decimal.entity_id AND catalog_product_entity_decimal.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'price' AND entity_type_id = 4) 
            WHERE catalog_product_entity.type_id = 'simple'";

        $stmt = $mysqli->prepare($query);
        
        $stmt->execute();
        
        // Get the result set
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()){
            $product = Product::where('sku', $row['sku'])->first();
            
            if ($product === null){

                $data = [
                    'name' => $row['name'],
                    'description' => null,
                    'sku' => $row['sku'],
                ];

                if ($row['type'] === 'simple'){
                    $trimmed_quantity = explode('.', $row['quantity']);
                    $data['prod_type'] = '1';
                    $data['quantity'] = $trimmed_quantity[0];
                    $data['price'] = $row['price'];
                    $data['qty_diffettosi'] = '0';
                    $data['qty_ecommerce'] = $trimmed_quantity[0];
                    $data['qty_store'] = '0';
                }

                //Creazione prodotto
                Product::create([
                    'name' => $data['name'],
                    'sku' => $data['sku'],
                    'description' => null,
                    'quantity' => $data['prod_type'] === '4' ? '0' : $data['quantity'],
                    'price' => $data['prod_type'] === '4' ? '0' : $data['price']
                ]);

                $product = Product::where('sku', $data['sku'])->first();

                foreach ($data as $key => $value){
                    $attribute = Attribute::where('code', $key)->where('entity_type', 'products')->first();

                    AttributeValue::create([
                        'attribute_id' => $attribute->id,
                        'text_value' => $key === 'prod_type' ? null : $value,
                        'boolean_value' => null,
                        'integer_value' => $key === 'prod_type' ? (int)$value : null,
                        'float_value' => null,
                        'datetime_value' => null,
                        'date_value' => null,
                        'json_value' => null,
                        'entity_id' => $product->id,
                        'entity_type' => 'products',
                    ]);
                }

                $query = 'SELECT DISTINCT e.sku,     c.attribute_code,     d.value AS option_value,     c.attribute_id,     c.frontend_input,     c.frontend_label FROM     catalog_product_entity
                    e JOIN catalog_product_entity_int b ON     e.entity_id = b.entity_id JOIN eav_attribute c ON     b.attribute_id = c.attribute_id JOIN eav_attribute_option_value d ON     b.value = d.option_id JOIN eav_attribute_option f ON     d.option_id = f.option_id WHERE e.sku = "'.$data['sku'].'"';

                $stmt = $mysqli->prepare($query);
                        
                $stmt->execute();

                $result2 = $stmt->get_result();

                
                while ($row2 = $result2->fetch_assoc()){
                    ProductAttribute::create([
                        'product_sku' => $row2['sku'],
                        'attribute_id' => $row2['attribute_id'],
                        'value' => $row2['option_value'],
                    ]);
                }
            }
        }

    }

    //Prodotti configurabili
    public static function syncConfigProducts(){
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        if ($mysqli->connect_error) {
            die('Connection failed: ' . $mysqli->connect_error);
        }

        $query = "SELECT distinct
                e.sku,
                c.value AS product_name
            FROM
                catalog_product_entity e
            JOIN catalog_product_super_link b ON
                e.entity_id = b.parent_id
            JOIN catalog_product_entity_varchar c ON
                e.entity_id = c.entity_id
            JOIN eav_attribute d ON
                c.attribute_id = d.attribute_id
            WHERE
                e.type_id = 'configurable'
                AND d.attribute_code = 'name'";
        
        $stmt = $mysqli->prepare($query);
        
        $stmt->execute();
        // Get the result set
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()){
            $data = [
                'name' => $row['product_name'],
                'description' => null,
                'sku' => $row['sku'],
                'quantity' => '0',
                'prod_type' => '4'
            ];

            // 1. Creazione del prodotto
            Product::create([
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => null,
                'quantity' => '0',
                'price' => $data['prod_type'] === '4' ? '0' : $data['price']
            ]);

            $product = Product::where('sku', $data['sku'])->first();
            // 2. Creazione degli attributi
            foreach ($data as $key => $value){
                $attribute = Attribute::where('code', $key)->where('entity_type', 'products')->first();

                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'text_value' => $key === 'prod_type' ? null : $value,
                    'boolean_value' => null,
                    'integer_value' => $key === 'prod_type' ? (int)$value : null,
                    'float_value' => null,
                    'datetime_value' => null,
                    'date_value' => null,
                    'json_value' => null,
                    'entity_id' => $product->id,
                    'entity_type' => 'products',
                ]);
            }

            // 3. Creazione opzioni possibili
            $token = self::fetchToken();
            $client = new Client();

            $headers = [
                'Authorization' => 'Bearer '.$token,
            ];

            $response = $client->get('http://magento.efesti.com/rest/default/V1/configurable-products/'.$data['sku'].'/options/all', [
                'headers' => $headers
            ]); 

            $result1 = $response->getBody()->getContents();

            $attributeOptions = json_decode($result1, true);
            
            foreach ($attributeOptions as $option){

                ConfigurableOption::create([
                    'sku_configuration' => $row['sku'],
                    'attribute_id' => (int)$option['attribute_id']
                ]);
            }

            // 4. Get configurazioni prodotti(simple prods)
            $response = $client->get('http://magento.efesti.com/rest/default/V1/products/'.$data['sku'], [
                'headers' => $headers
            ]); 

            $result1 = $response->getBody()->getContents();

            $configurations = json_decode($result1, true);

            try{
                foreach ($configurations['extension_attributes']['configurable_product_links'] as $linkedProd){
                    $query = "SELECT sku
                            FROM catalog_product_entity
                            WHERE entity_id = ".$linkedProd."";

                    $stmt = $mysqli->prepare($query);
                            
                    $stmt->execute();
                    // Get the result set
                    $result2 = $stmt->get_result();
                    
                    $row2 = $result2->fetch_assoc();

                    ProductConfiguration::create([
                        'parent_sku' => $row['sku'],
                        'child_sku' =>$row2['sku']
                    ]);
                }
            }catch (\Exception $e){
                echo $e->getMessage();
            }
            
        }
        
    }

    //Prodotti bundle
    public static function syncBundleProds(){
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        if ($mysqli->connect_error) {
            die('Connection failed: ' . $mysqli->connect_error);
        }

        $query ="SELECT DISTINCT
            catalog_product_entity.sku AS sku,
            catalog_product_entity_varchar.value AS name,
            cataloginventory_stock_item.qty AS quantity,
            catalog_product_entity.type_id AS type,
            catalog_product_entity_decimal.value AS price
            FROM catalog_product_entity 
            JOIN catalog_product_entity_varchar ON catalog_product_entity.entity_id = catalog_product_entity_varchar.entity_id 
            AND catalog_product_entity_varchar.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 4)
            JOIN cataloginventory_stock_item ON catalog_product_entity.entity_id = cataloginventory_stock_item.product_id 
            JOIN catalog_product_entity_decimal ON catalog_product_entity.entity_id = catalog_product_entity_decimal.entity_id AND catalog_product_entity_decimal.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'price' AND entity_type_id = 4) 
            WHERE catalog_product_entity.type_id = 'bundle'";

        $stmt = $mysqli->prepare($query);
        
        $stmt->execute();
        // Get the result set
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()){
            $token = self::fetchToken();
            $client = new Client();
            $requst_url = "http://magento.efesti.com/rest/default/V1/products/" . $row['sku'];

            $headers = [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ];
            $response = $client->get($requst_url, [
                'headers' => $headers
            ]);

            $result1 = $response->getBody()->getContents();
            $data1 = json_decode($result1);    

            $trimmed_quantity = explode('.', $row['quantity']);

            $data = [
                'name' => $row['name'],
                'description' => null,
                'sku' => $row['sku'],
                'prod_type' => '3',
                'quantity' => $trimmed_quantity[0],
                'qty_ecommerce' => $trimmed_quantity[0],
                'price' => $row['price']
            ];

            $query = "SELECT * FROM catalog_product_entity_int
                            WHERE entity_id = (SELECT entity_id FROM catalog_product_entity WHERE sku = '".$row['sku']."')
                            AND attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'price_type');";

            $stmt2 = $mysqli->prepare($query);
                    
            $stmt2->execute();

            $result2 = $stmt2->get_result();

            $row2 = $result2->fetch_assoc();

            if ($row2['value'] === 1){
                $data['dynamic_price'] = null;
            }else{
                $data['dynamic_price'] = "1";
            }

            try{
                //Creazione bundle product
                Product::create([
                    'name' => $data['name'],
                    'sku' => $data['sku'],
                    'description' => null,
                    'quantity' => $data['quantity'],
                    'price' => $data['price']
                ]);

                $prod = Product::where('sku', $data['sku'])->first();

                foreach ($data as $key => $value){
                    $attribute = Attribute::where('code', $key)->where('entity_type', 'products')->first();

                    AttributeValue::create([
                        'attribute_id' => $attribute->id,
                        'text_value' => $key === 'prod_type' ? null : $value,
                        'boolean_value' => null,
                        'integer_value' => $key === 'prod_type' ? (int)$value : null,
                        'float_value' => null,
                        'datetime_value' => null,
                        'date_value' => null,
                        'json_value' => null,
                        'entity_id' => $prod->id,
                        'entity_type' => 'products',
                    ]);
                }

                $extatt = $data1->extension_attributes;

                try{
                    $options = $extatt->bundle_product_options;
                    foreach ($options as $option){
                        $prodlinks = $option->product_links;
                        foreach ($prodlinks as $prodlink){
                            $product = Product::select('price', 'name')->where('sku', $prodlink->sku)->first();

                            BundleOptions::create([
                                'sku_bundle' => $row['sku'],
                                'sku_option' => $prodlink->sku,
                                'option_name' => $product->name,
                                'option_price' => (float)$product->price,
                                'qty' => $prodlink->qty,
                                'can_change_qty' => $prodlink->can_change_quantity,
                            ]);

                            
                        }
                    } 

                }catch(\Exception $e){
                    echo $e->getMessage();
                }
            }catch(\Exception $e){

            }
            
            
        }
        
        
    }

    public static function addProductConfiguration($data){
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        foreach ($data->selectedItems as $item){
            $count = ProductConfiguration::where('parent_sku', $data->parent_sku)->where('child_sku', $item)->get()->count();
            if ($count == 0){
                $token = self::fetchToken();
                $client = new Client();
                
                $payload = [
                    'childSku' => $item
                ];

                $headers = [
                    'Authorization' => 'Bearer '.$token,
                ];


                try{
                    $response = $client->post('http://magento.efesti.com/rest/V1/configurable-products/'.$data->parent_sku.'/child', [
                        'headers' => $headers,
                        'json' => $payload
                    ]);

                    ProductConfiguration::create([
                        'parent_sku' => $data->parent_sku,
                        'child_sku' => $item
                    ]);

                }catch (\Exception $e){
                    return false;
                }
            }
        }

        $query = "DELETE catalog_product_entity_int
                    FROM catalog_product_entity_int
                    JOIN catalog_product_entity ON catalog_product_entity.entity_id = catalog_product_entity_int.entity_id
                    WHERE catalog_product_entity.sku = '".$data->parent_sku."'
                    AND catalog_product_entity_int.store_id = 1
                    AND catalog_product_entity_int.attribute_id IN (97, 99, 136)";

        $stmt = $mysqli->prepare($query);
            
        $stmt->execute();

        return true;
    }

    //Aggiunge solo opzione al prodotto configurabile creato
    public static function addAttributeConfigurable($sku, $attributeId, $attributeCode){
        $token = self::fetchToken();
        $client = new Client();
        
        $payload = [
            "option" => [
                "attribute_id" => $attributeId,
                "label" => $attributeCode,
                "position" => 0,
                "is_use_default" => true,
                "values" => [
                    ["value_index" => 4]
                ]
            ]
        ];

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json'
        ];

        try{
            $response = $client->post('http://magento.efesti.com/rest/V1/configurable-products/'.$sku.'/options', [
                'headers' => $headers,
                'json' => $payload
            ]);
            return true;
        }catch(\Exception $e){
            return false;
        }
    }

    //Aggiunge opzioni attributo al prodotto configurabile
    public static function addAtrributeOption($data){
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        $token = self::fetchToken();
        $client = new Client();
        
        $payload = [
            "option" => [
                "attribute_id" => $data->attribute_id,
                "label" => $data->attribute_code,
                "position" => 0,
                "is_use_default" => true,
                "values" => [
                    ["value_index" => 4]
                ]
            ]
        ];

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json'
        ];

        try{
            $response = $client->post('http://magento.efesti.com/rest/V1/configurable-products/'.$data->sku.'/options', [
                'headers' => $headers,
                'json' => $payload
            ]);

            $done = true;
            $count = 0;

            while ($done){
                if ($data['sku'.$count] !== null){
                    $payload = [
                        'product' => [
                            'sku' => $data['sku'.$count],
                            'name' => $data['name'.$count],
                            'attribute_set_id' => 4,
                            'price' => (float)$data['price'.$count],
                            'status' => 0,
                            'visibility' => 1,
                            'type_id' => 'simple',
                            'weight' => '0',
                            'extension_attributes' => [
                                'category_links' => [
                                    [
                                        'position' => 0,
                                        'category_id' => '2'
                                    ]
                                ],
                                'stock_item' => [
                                    'qty' => $data['quantity'.$count],
                                    'is_in_stock' => (int)$data['quantity'.$count] > 0 ? true : false
                                ]
                            ],
                            'custom_attributes' => [
                                [
                                    'attribute_code' => 'url_key',
                                    'value' => ''.$data['sku'.$count].''
                                ],
                                [
                                    'attribute_code' => 'url_path',
                                    'value' => ''.$data['sku'.$count].''
                                ],
                                [
                                    'attribute_code' => 'required_options',
                                    'value' => '0'
                                ],
                                [
                                    'attribute_code' => 'has_options',
                                    'value' => '0'
                                ],
                                [
                                    'attribute_code' => 'meta_title',
                                    'value' => $data['name'.$count]
                                ],
                                [
                                    'attribute_code' => 'meta_keyword',
                                    'value' => ''
                                ],
                                [
                                    'attribute_code' => 'meta_description',
                                    'value' => 'crm meta description'
                                ],
                                [
                                    'attribute_code' => 'tax_class_id',
                                    'value' => '4'
                                ],
                                [
                                    'attribute_code' => 'category_ids',
                                    'value' => []
                                ],
                                [
                                    'attribute_code' => 'nc_quantita',
                                    'value' => '4,5'
                                ],
                                [
                                    'attribute_code' => $data->attribute_code,
                                    'value' => $data['value'.$count]
                                ]
                            ]
                        ]
                    ];

                    try{
    
                        $response = $client->post('http://magento.efesti.com/rest/V1/products', [
                            'headers' => $headers,
                            'json' => $payload,
                        ]);

                        $payload = [
                            'childSku' => $data['sku'.$count]
                        ];

                        try{
                            $response = $client->post('http://magento.efesti.com/rest/V1/configurable-products/'.$data->sku.'/child', [
                                'headers' => $headers,
                                'json' => $payload,
                            ]);

                        }catch (\Exception $e){
                            return false;
                        }
            
                    }catch(\Exception $e){
                        return false;
                    }


                    $count ++;
                }else{
                    $done = false;
                }
            }

            $query = "DELETE catalog_product_entity_int
                FROM catalog_product_entity_int
                JOIN catalog_product_entity ON catalog_product_entity.entity_id = catalog_product_entity_int.entity_id
                WHERE catalog_product_entity.sku = '".$data->sku."'
                AND catalog_product_entity_int.store_id = 1
                AND catalog_product_entity_int.attribute_id IN (97, 99, 136)";
            
            $stmt = $mysqli->prepare($query);
            
            $stmt->execute();

        }catch (\Exception $e){
            return false;
        }

        return true;
    }

    public static function removeConfigurationOption($parentSku, $childSku){
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        $token = self::fetchToken();
        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json'
        ];
        
        try{

            $response = $client->request('DELETE','http://magento.efesti.com/rest/V1/configurable-products/'.$parentSku.'/children/'.$childSku.'', [
                'headers' => $headers,
            ]);

            $query = "DELETE catalog_product_entity_int
                FROM catalog_product_entity_int
                JOIN catalog_product_entity ON catalog_product_entity.entity_id = catalog_product_entity_int.entity_id
                WHERE catalog_product_entity.sku = '".$parentSku."'
                AND catalog_product_entity_int.store_id = 1
                AND catalog_product_entity_int.attribute_id IN (97, 99, 136)";
            
            $stmt = $mysqli->prepare($query);
            
            $stmt->execute();

            return true;
        }catch (\Exception $e){
            return false;
        }

    }

    public static function getCommentsHistory($entity_id){
        $token = self::fetchToken();
        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json'
        ];
        
        try{

            $response = $client->get('http://magento.efesti.com/rest/V1/orders/'.$entity_id.'/comments', [
                'headers' => $headers,
            ]);

            $result = $response->getBody()->getContents();
            $data = json_decode($result);

            return $data->items;
        }catch (\Exception $e){
            return [];
        }
    }

    public static function syncAnelliStatus(){
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        if ($mysqli->connect_error) {
            die('Connection failed: ' . $mysqli->connect_error);
        }

        $start = date("Y-m-d").' 00:00:00';
        $end = date("Y-m-d").' 23:59:59';
        /* $start = '2024-02-27 00:00:00';
        $end = '2024-02-27 23:59:59'; */
    
        $query = "select increment_id, entity_id, customer_email, status, state from sales_order where status = 'anelli' and created_at between '".$start."' and '".$end."'";

        $stmt = $mysqli->prepare($query);
            
        $stmt->execute();
        
        // Get the result set
        $result = $stmt->get_result();

        while($row = $result->fetch_assoc()){
            $order = Order::where('entity_id', $row['entity_id'])->first();
            if ($order !== null){
                if ($order->status === 'processing'){
                    $order->update([
                        'status' => 'anelli'
                    ]);
                }
            }
        }
    }

    public static function syncCanceledAndClosedOrders(){
        $currentDate = date('Y-m-d');

        // Get the date 14 days ago
        $fourteenDaysAgoDate = date('Y-m-d', strtotime('-14 days'));

        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        if ($mysqli->connect_error) {
            die('Connection failed: ' . $mysqli->connect_error);
        }

        /* $start = date("Y-m-d").' 00:00:00';
        $end = date("Y-m-d").' 23:59:59'; */
    
        $query = "SELECT increment_id, entity_id, customer_email, status, state FROM sales_order where (status = 'canceled' OR status = 'closed') and DATE(created_at) between '".$fourteenDaysAgoDate."' and '".$currentDate."'";

        $stmt = $mysqli->prepare($query);
            
        $stmt->execute();
        
        // Get the result set
        $result = $stmt->get_result();

        while($row = $result->fetch_assoc()){
            $order = Order::where('entity_id', $row['entity_id'])->first();
            if ($order !== null){
                $order->update([
                    'status' => $row['status']
                ]);
            }
        }
    }

    public static function syncProdsQuantities(){
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        if ($mysqli->connect_error) {
            die('Connection failed: ' . $mysqli->connect_error);
        }

        $query ="select distinct catalog_product_entity.sku AS sku, cataloginventory_stock_item.qty AS quantity from catalog_product_entity join cataloginventory_stock_item ON catalog_product_entity.entity_id = cataloginventory_stock_item.product_id where catalog_product_entity.type_id = 'simple'";

        $stmt = $mysqli->prepare($query);
        
        $stmt->execute();
        
        // Get the result set
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()){
            $product = Product::where('sku', $row['sku'])->first();
            
            if ($product !== null){
                //Recupero e aggiorno la quantità ecommerce
                $productAttribute = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                ->select('attribute_values.id')
                ->where('products.sku', $row['sku'])
                ->where('attributes.code', 'qty_ecommerce')
                ->first();
        
                $attributeValue = AttributeValue::where('id', $productAttribute->id)->first();
        
                $trimmed_quantity = explode('.', $row['quantity']);
                $attributeValue->update([
                    'text_value' => strval($trimmed_quantity[0])
                ]);

                /* TEMPORANEO */
                /* Aggiorno quantità totale in base alla somma di ecommerce e difettosi */
                //Recupero la quantità difettosa
                /* $productAttribute = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                ->select('attribute_values.id')
                ->where('products.sku', $row['sku'])
                ->where('attributes.code', 'qty_diffettosi')
                ->first();

                $attributeValue = AttributeValue::where('id', $productAttribute->id)->first();

                $total = (int)$trimmed_quantity[0] + (int)$attributeValue->text_value;
                
                $product->update([
                    'quantity' => $total,
                ]); */
            }
        }
    }

    public static function getOrderDetails($entity_id){
        ini_set('memory_limit', '2G');
        $host = config('magento-service.magento-db-host');
        $port = config('magento-service.magento-db-port');
        $database = config('magento-service.magento-db-name');
        $username = config('magento-service.magento-db-user');
        $password = config('magento-service.magento-db-pass');
        $mysqli = mysqli_connect($host, $username, $password, $database);

        if ($mysqli->connect_error) {
            die('Connection failed: ' . $mysqli->connect_error);
        }

        // Get the last order_id in the CRM database
        $lastOrderId = self::getLastOrder();

        //$query = 'SELECT o.entity_id AS order_id, o.customer_email, o.status, o.total_due, o.created_at, o.increment_id AS order_increment_id, a.firstname AS shipping_firstname, a.lastname AS shipping_lastname, a.street AS shipping_street, a.city AS shipping_city, a.region AS shipping_region, a.postcode AS shipping_postcode, a.country_id AS shipping_country FROM sales_order o INNER JOIN sales_order_address a ON o.shipping_address_id = a.entity_id WHERE status="Canceled" AND o.entity_id > ? LIMIT 80000';
        $query = 'SELECT DISTINCT o.increment_id AS order_id, o.entity_id AS entity_id, o.customer_email, o.status, o.total_paid, o.total_due, o.created_at, o.increment_id AS order_increment_id, a.firstname AS shipping_firstname, a.lastname AS shipping_lastname, a.street AS shipping_street, a.city AS shipping_city, a.region AS shipping_region, a.postcode AS shipping_postcode, a.country_id AS shipping_country, o.bold_order_comment AS comment FROM sales_order o INNER JOIN sales_order_address a ON o.shipping_address_id = a.entity_id WHERE o.entity_id = ?';
        $stmt = $mysqli->prepare($query);
        
        $stmt->bind_param("i", $entity_id);
        
        // Execute the query
        $stmt->execute();
        
        // Get the result set
        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        return $row;
    }

    public static function removeDefectiveQty($sku, $qty){
        //dd($sku, $qty);
        $token = self::fetchToken();
        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json'
        ];

        $payload = [
            "product" => [
                "extension_attributes" => [
                    "stock_item" => [
                        "qty" => (int)$qty,
                        "is_in_stock" => true
                    ]
                ]
            ]
        ];
        
        try{

            $response = $client->request('PUT', 'http://magento.efesti.com/rest/V1/products/'.$sku, [
                'headers' => $headers,
                'json' => $payload,
            ]);

            return true;
        }catch (\Exception $e){
            //dd($e);
            return false;
        }
    }

    public static function addProductAtrribute($data, $sku, $attribute_code){
        $token = self::fetchToken();
        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json'
        ];

        $payload = [
            'product' => [
                'custom_attributes' => [
                    [
                        'attribute_code' => $attribute_code,
                        'value' => $data->item
                    ]
                ]
            ]
        ];
        
        try{

            $response = $client->request('PUT', 'http://magento.efesti.com/rest/V1/products/'.$sku, [
                'headers' => $headers,
                'json' => $payload,
            ]);

            return true;
        }catch (\Exception $e){
            //dd($e);
            return false;
        }
    }

    public static function updateQuantities($sku, $quantity, $name, $price){
        $token = self::fetchToken();
        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json'
        ];

        
        $payload = [
            'product' => [
                'extension_attributes' => [
                    'stock_item' => [
                        'qty' => (int)$quantity,
                        'is_in_stock' => (int)$quantity > 0 ? true : false
                    ]
                ]
            ]
        ];
        
        try{
            $response = $client->request('PUT', 'http://magento.efesti.com/rest/V1/products/'.$sku, [
                'headers' => $headers,
                'json' => $payload,
            ]);

            return true;
        }catch(\Exception $e){
            session()->flash('error', "Errore nell' aggiornamento delle quantità");
            return false;
        }
    }

    
}
