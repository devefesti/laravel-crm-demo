<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MagentoModel;
use App\Http\Efesti\Magento\MagentoSingleton;
use App\Models\MagentoManager;

class MagentoController extends Controller
{
    public function index(){
      $manager = new MagentoManager();
      $manager->syncOrders();
    }

    public function getOrdersToFulfill(){
      //$magentoInstance = MagentoSingleton::getInstance();
       //dd($magentoInstance::getOrders()['items'][0]);
       $orders = $magentoInstance::getOrders();
       //return view("orders.index", ['orders' => $orders['items']]);
    }

   /*public function syncOrderProds(){
      $manager = new MagentoManager();
      $manager->syncOrdersProds();
    }*/

    public function show($id){
        return view('orders.show')->with('id', $id);
    }
}
