<?php

namespace App\Http\Efesti\Magento;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\MagentoModel;
use GuzzleHttp\Client;
use App\Models\OrderModel;
use App\Models\Order;

class MagentoSingleton{
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
            return $lastOrder['order_id'];
        }
        return 0;
    }
   
    public static function syncOrders()
    {
        //dd(config('magento-service.magento-db-host'));
        ini_set('memory_limit', '512M');
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
        echo "ultimo order ID" . $lastOrderId . "<br>";
        
        // Define the SQL query
        $query = 'SELECT o.entity_id AS order_id, o.customer_email, o.status, o.total_due, o.created_at, o.increment_id AS order_increment_id, a.firstname AS shipping_firstname, a.lastname AS shipping_lastname, a.street AS shipping_street, a.city AS shipping_city, a.region AS shipping_region, a.postcode AS shipping_postcode, a.country_id AS shipping_country FROM sales_order o INNER JOIN sales_order_address a ON o.shipping_address_id = a.entity_id WHERE status="Canceled" AND o.entity_id > ? LIMIT 1000';
        
        // Prepare the statement
        $stmt = $mysqli->prepare($query);
        
        // Bind the parameter (assuming $lastOrderId is an integer)
        $stmt->bind_param("i", $lastOrderId);
        
        // Execute the query
        $stmt->execute();
        
        // Get the result set
        $result = $stmt->get_result();
       

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
            echo "data creazione: " . $row['created_at'] . "<br>";
            /*$order = new Order;
            $order->order_id = $row['order_id'];
            $order->status = $row['status'];
            $order->customer_email = $row['customer_email'];
            $order->firstname = $row['shipping_firstname'];
            $order->lastname = $row['shipping_lastname'];
            $order->totale = $row['total_due'];
            $order->street = $row['shipping_street'];
            $order->city = $row['shipping_city'];
            $order->state = $row['shipping_region'];
            $order->post_code = $row['shipping_postcode'];
            $order->country = $row['shipping_country'];
            $order->order_date = $row['created_at'];
            $order->save(); */

            Order::create([
                'order_id' => $row['order_id'],
                'status' => $row['status'],
                'email' => $row['customer_email'],
                'firstname' => $row['shipping_firstname'],
                'lastname' => $row['shipping_lastname'],
                'totale' => $row['total_due'],
                'street' => $row['shipping_street'],
                'city' => $row['shipping_city'],
                'state' => $row['shipping_region'],
                'post_code' => $row['shipping_postcode'],
                'country' => $row['shipping_country'],
                'order_date' => $row['created_at']
            ]);
    
        }
        
       
        // Close the connection
        $mysqli->close(); 
    
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

}

?>