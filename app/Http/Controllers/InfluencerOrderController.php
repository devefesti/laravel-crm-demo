<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InfluencerOrder;
use App\Models\Influencer;
use App\Models\OrderPack;
use App\Models\OrderProds;
use Webkul\Product\Models\Product;
use App\Models\MagentoManager;
use Webkul\Attribute\Models\AttributeValue;
use Illuminate\Support\Facades\Session;

class InfluencerOrderController extends Controller{

    public function index(){
        $orders = InfluencerOrder::paginate(10);

        Session::pull('nome_inf_ord');
        Session::pull('cognome_inf_ord');
        Session::pull('email_inf_ord');
        Session::pull('pages_inf_ord');
        Session::pull('data_inf_ord');
        Session::pull('stato_inf_ord');

        if(isset(request()->query()['page']) && request()->query()['page'] !== null){
            Session::put('page_inf_ord', request()->query()['page']);
        }

        return view('influencers.orders.index', ['orders' => $orders]);
    }

    public function create(){
        $influencers = Influencer::select('id', 'nome', 'cognome')->get();
        $products = \Webkul\Product\Models\Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->select('products.sku', 'products.name', 'products.price', 'products.quantity')
            ->where('attributes.code', 'prod_type')
            ->where('integer_value', '<>', 3)
            ->where('integer_value', '<>', 4)
            ->where('price', '>', 0)
            ->get();

        $packFissi = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
        ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
        ->select('products.name')
        ->where('attributes.code', 'prod_type')
        ->where('attribute_values.integer_value', 2)
        ->where('products.sku','not like', 'scatolina-%')
        ->get();

        $packagings = Product::where('sku', 'like', 'scatolina-%')->orderBy('name', 'desc')->get();
        return view('influencers.orders.create', ['influencers' => $influencers, 'products' => $products, 'packFissi' => $packFissi, 'packagings' => $packagings]);
    }

    public function store(Request $request){
        $done = true;
        $i = 0;

        //Verifica disponibilità di packaging variabili

        while ($done){
            if ($request['package-name'.$i] != null){
                $packaging = Product::where('sku', $request['package-name'.$i])->first();

                $quantityAvailable = ($packaging->quantity >= (int)$request['package-quantity'.$i]);

                if (!$quantityAvailable){
                    session()->flash('error', 'Quantità di '. $packaging->name .' insufficiente');
                    return back();
                }

            }else{
                $done = false;
            }

            $i ++;
        }

        //Check quantità packaging fissi
        $fissi = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
        ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
        ->select('products.name', 'products.sku', 'products.quantity')
        ->where('attributes.code', 'prod_type')
        ->where('attribute_values.integer_value', 2)
        ->where('products.sku','not like', 'scatolina-%')
        ->get();

        foreach($fissi as $key => $item){
            if ($item->quantity < (int)$request['package-fixed-quantity'.($key + 1)]){
                session()->flash('error', 'Quantità di '. $item->name .' insufficiente');
                return back();
            }
        } 

        //check disponibilità prodotti ecommerce e scorte
        $filled = true;
        $count_items = 0;

        
        while ($filled){
            if($request['option'.$count_items] != null){
                
                $productAttribute = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                        ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                        ->select('attribute_values.id')
                        ->where('products.sku', $request['option'.$count_items])
                        ->where('attributes.code', 'qty_ecommerce')
                        ->first();
        
                $attributeValue = AttributeValue::where('id', $productAttribute->id)->first();

                if((int)$attributeValue->text_value < (int)$request['qty'.$count_items]){
                    $product = Product::select('quantity')->where('sku', $request['option'.$count_items])->first();

                    if ((int)$product->quantity < (int)$request['qty'.$count_items]){
                        session()->flash('error', 'Quantità di '.$request['name'.$count_items].' non sufficiente per la spedizione');
                        return back();
                    }
                }
            }else{
                $filled = false;
            }

            $count_items ++;
        }

        //**** CREAZIONE DELL'ORDINE **** 
              
        //Calcola il costo totale dei prodotti
        $done = true;
        $i = 0;
        $totalDue = 0;

        while ($done){
            if ($request['option'.$i] != null){
                $totalDue += (int)$request['qty'.$i] * (float)$request['price'.$i];
            }else{
                $done = false;
            }

            $i ++;
        }

        $influencer = Influencer::where('id', $request->influencer)->first();

        $order = InfluencerOrder::create([
            'nome' => $influencer->nome,
            'cognome' => $influencer->cognome,
            'email' => $influencer->email,
            'stato' => 'processing',
            'data' => date("Y-m-d"),
            'totale' => $totalDue,
            'nota' => $request->nota
        ]);

        //Aggiorno le quantità dei packaging fissi
        foreach($fissi as $key => $item){
            $packFisso = Product::where('sku', $item->sku)->first();
            $packFisso->update([
                'quantity' => $packFisso->quantity - (int)$request['package-fixed-quantity'.($key + 1)],
            ]);
        }
        
        $i = 1;
        $done = true;

        //Inserisco nel db i package varaiabili utilizzati
        while($done){
            if ($request['package-name'.$i] != null){
                OrderPack::create([
                    'order_id' => $order->id,
                    'pack_id' => $request['package-name'.$i],
                    'qty' => (int)$request['package-quantity'.$i],
                    'order_type' => 'influencer'
                ]);

                $packaging = Product::where('sku', $request['package-name'.$i])->first();

                $packaging->update([
                    'quantity' => $packaging->quantity - (int)$request['package-quantity'.$i],
                ]); 
            }else{
                $done = false;
            }

            $i ++;
        }

        //Aggiorno le quantità dei prodotti
        $filled = true;
        $count_items = 0;

        while ($filled){
            if($request['option'.$count_items] != null){
                
                $productAttribute = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                        ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                        ->select('attribute_values.id')
                        ->where('products.sku', $request['option'.$count_items])
                        ->where('attributes.code', 'qty_ecommerce')
                        ->first();
        
                $attributeValue = AttributeValue::where('id', $productAttribute->id)->first();

                //Scalo Qty ecommerce
                if((int)$attributeValue->text_value >= (int)$request['qty'.$count_items]){
                    $magentoManager = MagentoManager::getInstance();
                    $result = $magentoManager::updateQuantities($request['option'.$count_items], (int)$attributeValue->text_value - (int)$request['qty'.$count_items], $request['name'.$count_items], $request['price'.$count_items]);
                    
                    
                    if ($result){
                        $attributeValue->update([
                            'text_value' => strval((int)$attributeValue->text_value - (int)$request['qty'.$count_items]),
                        ]);
                    }
                }else{
                    $productAttr = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                        ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                        ->select('attribute_values.id')
                        ->where('products.sku', $request['option'.$count_items])
                        ->where('attributes.code', 'quantity')
                        ->first();

                        $attributeVal = AttributeValue::where('id', $productAttr->id)->first();
                    $attributeVal->update([
                        'text_value' => strval((int)$attributeVal->text_value - (int)$request['qty'.$count_items]),
                    ]);

                    //Scalo quantity scorte
                    $product = Product::where('sku', $request['option'.$count_items])->first();
                    $updatedQuantity = (int)$product->quantity - (int)$request['qty'.$count_items];
                    $product->update([
                        'quantity' => $updatedQuantity,
                    ]);

                    $productAttribute = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                        ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                        ->select('attribute_values.id')
                        ->where('products.sku', $request['option'.$count_items])
                        ->where('attributes.code', 'quantity')
                        ->first();
                }

                

                OrderProds::create([
                    'order_id' => $order->id,
                    'sku' => $request['option'.$count_items],
                    'product_name' => $request['name'.$count_items],
                    'item_id' => 0,
                    'quantity' => $request['qty'.$count_items],
                    'product_type' => 'simple'
                ]);

            }else{
                $filled = false;
            }

            $count_items ++;
        }

        return redirect()->route('admin.influencers.orders');
    }

    public function edit($id){
        $order = InfluencerOrder::findOrFail($id);
        $influencer = Influencer::where('email', $order->email)->first();
        $products = OrderProds::where('order_id', $id)->get();

        $images = self::getProdsImages($products);
        return view('influencers.orders.edit', ['influencer' => $influencer, 'order' => $order, 'products' => $products, 'images' => $images]);
    }

    public function update(Request $request, $id){
        $order = InfluencerOrder::findOrFail($id);

        $order->update([
            'stato' => $request->stato,
        ]);

        //return redirect()->route('admin.influencers.orders');
        return redirect()->route('admin.influencers.orders.search',
                    ['nome' => Session::get('nome_inf_ord'),
                    'cognome' => Session::get('cognome_inf_ord'),
                    'email' => Session::get('email_inf_ord'),
                    'data' => Session::get('data_inf_ord'),
                    'stato' => Session::get('stato_inf_ord'),
                    'pages' => Session::get('pages_inf_ord'),
                    'page' => Session::get('page_inf_ord')]);
    }

    private static function getProdsImages($products)
    {
       
        $magentoManager = MagentoManager::getInstance();
        $ris = array();
        foreach ($products as $product) {
            $imageUrl = $magentoManager::GetProdImage($product->sku);
            if ($imageUrl !== null) {
                $ris[$product->sku] = $imageUrl;
            }

        }
       return $ris;

    }

    public function search(){
        $pages = 10;

        if (request()->pages != null && request()->pages > 0){
            $pages = request()->pages;
            Session::put('pages_inf_ord', $pages);
        }else{
            Session::pull('pages_inf_ord');
        }

        if (request()->nome != null){
            Session::put('nome_inf_ord', request()->nome);
        }else{
            Session::pull('nome_inf_ord');
        }

        if (request()->cognome != null){
            Session::put('cognome_inf_ord', request()->cognome);
        }else{
            Session::pull('cognome_inf_ord');
        }

        if (request()->email != null){
            Session::put('email_inf_ord', request()->email);
        }else{
            Session::pull('email_inf_ord');
        }

        if (request()->stato != null){
            Session::put('stato_inf_ord', request()->stato);
        }else{
            Session::pull('stato_inf_ord');
        }

        if (request()->page != null){
            Session::put('page_inf_ord', request()->page);
        }else{
            Session::put('page_inf_ord', 1);
        }

        if (request()->data != null){
            Session::put('data_inf_ord', request()->data);
            $orders = InfluencerOrder::where('nome', 'like', '%'.request()->nome.'%')
                ->where('cognome', 'like', '%'.request()->cognome.'%')
                ->where('email', 'like', '%'.request()->email.'%')
                ->where('stato', 'like', '%'.request()->stato.'%')
                ->where('data', request()->data)
                ->paginate($pages);
        }else{
            $orders = InfluencerOrder::where('nome', 'like', '%'.request()->nome.'%')
            ->where('cognome', 'like', '%'.request()->cognome.'%')
            ->where('email', 'like', '%'.request()->email.'%')
            ->where('stato', 'like', '%'.request()->stato.'%')
            ->paginate($pages);
        }
        

        $search = [
            'nome' => request()->nome,
            'cognome' => request()->cognome,
            'email' => request()->email,
            'stato' => request()->stato,
            'data' => request()->data,
            'pages' => $pages
        ];

        return view('influencers.orders.index', ['orders' => $orders, 'search' => $search]);
    }

    public function destroy($id){

        InfluencerOrder::where('id', $id)->delete();
        //return redirect()->route('admin.influencers.orders');
        return redirect()->route('admin.influencers.orders.search',
                    ['nome' => Session::get('nome_inf_ord'),
                    'cognome' => Session::get('cognome_inf_ord'),
                    'email' => Session::get('email_inf_ord'),
                    'data' => Session::get('data_inf_ord'),
                    'stato' => Session::get('stato_inf_ord'),
                    'pages' => Session::get('pages_inf_ord'),
                    'page' => Session::get('page_inf_ord')]);
    }
}