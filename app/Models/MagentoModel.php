<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client;

class MagentoModel extends Model
{
    use HasFactory;

    protected $username = 'api-amabile';
    protected $password = 'Jiu(2@idl2930=1#@dje5';

    public static function getToken(){

        try{

            $client = new Client();
        
            $bodyParams = [
                'username' => 'api-amabile',
                'password' => 'Jiu(2@idl2930=1#@dje5',
            ];
    
            $response = $client->post(config('app.token_url'), [
                // You can also use 'json' instead of 'form_params' if you want to send JSON data
                'json' => $bodyParams
            ]); 
    
            $result = $response->getBody()->getContents();
    
            $responseData = json_decode($result, true);
            
            return $responseData;

        }catch(\Exception $e)
        {
          return null;
        }

    
    }

    //funzione per sincronizzare gli ordini -> questa funzione verrà associata ad un cron che popola il db del crm
    //nella fase di test gli con stato cancellato
    //un volta finita la migrazione degli ordini verranno sincronizzati gli ordini con stato in-lavorazione e da spedire
    public static function syncOrders()
    {
        $token = self::getToken();
        $client = new Client();

        

    }

    public static function getOrders(){
        $token = self::getToken();
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
