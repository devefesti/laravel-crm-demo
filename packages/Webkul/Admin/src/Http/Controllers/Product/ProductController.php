<?php

namespace Webkul\Admin\Http\Controllers\Product;

use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Http\Requests\AttributeForm;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Models\Product;
use Illuminate\Http\Request;
use App\Models\BundleOptions;
use App\Models\ProductConfiguration;
use App\Models\MagentoManager;
use App\Models\MagentoAttributes;
use App\Models\MagentoAttributeLabel;
use App\Models\ConfigurableOption;
use App\Models\ProductAttribute;
use Webkul\Attribute\Models\AttributeValue;
use Webkul\Attribute\Models\Attribute;
use Illuminate\Support\Facades\Session;

class ProductController extends Controller
{
    /**
     * ProductRepository object
     *
     * @var \Webkul\Product\Repositories\ProductRepository
     */
    protected $productRepository;

    /**
     * Create a new controller instance.
     *
     * @param \Webkul\Product\Repositories\ProductRepository  $productRepository
     *
     * @return void
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;

        request()->request->add(['entity_type' => 'products']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        /* if (Session::get('name') === null && Session::get('tipo') === null && Session::get('pages') === null){
            return redirect()->route('admin.products.list');
        }
 */
        if (request()->ajax()) {
            return app(\Webkul\Admin\DataGrids\Product\ProductDataGrid::class)->toJson();
        }

        $products = Product::orderBy('id', 'desc')->paginate(30);
        /* $attr = AttributeValue::select('text_value')->where('entity_id', 889)->where('attribute_id', 35)->get();
        dd($attr); */
        //Session::pull('page');
        Session::pull('name');
        Session::pull('tipo');
        Session::pull('pages');


        if(isset(request()->query()['page']) && request()->query()['page'] !== null){
            Session::put('page', request()->query()['page']);
        }

        return view('products.list', ['products' => $products]);
        //return view('admin::products.index');

        /* return redirect()->route('admin.products.search', ['page' => Session::get('page'),
            '_token' => Session::get('_token'),
            'name' => Session::get('name'),
            'tipo' => Session::get('tipo'),
            'pages' => Session::get('pages')]); */
    }

    public function clear(){

        $products = Product::orderBy('id', 'desc')->paginate(30);
        return view('products.list', ['products' => $products]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $bundleOptions = [];
        $configurations = [];
        $bundleInfo = [];
        $product = null;
        return view('admin::products.create', compact('bundleOptions', 'product', 'configurations', 'bundleInfo'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Webkul\Attribute\Http\Requests\AttributeForm $request
     * @return \Illuminate\Http\Response
     */
    public function store(AttributeForm $request)
    {
        if($request->prod_type === '3'){
            /* if((int)$request->qty_ecommerce == (int)$request->quantity){
                $magentoManager = MagentoManager::getInstance();
                $result = $magentoManager::addProduct($request);

                if ($result){
                    $this->success($request);
                    $this->alterBundleOptions($request);
                    $this->createCSV($request);
                }
            
                return redirect()->route('admin.products.index');
            }else{
                session()->flash('error', trans('admin::app.products.qty-error'));
                return redirect()->route('admin.products.create');
            } */

            $magentoManager = MagentoManager::getInstance();
            $result = $magentoManager::addProduct($request);

            if ($result){
                $this->success($request);
                $this->alterBundleOptions($request);
                $this->createCSV($request);
            }
        
            return redirect()->route('admin.products.index');
            
        }

        //Creazione prodotto CONFIGURABILE
        if($request->prod_type === '4'){
            $magentoManager = MagentoManager::getInstance();
            $result = $magentoManager::addProduct($request);
            if ($result){
                $this->success($request);
            }
            return redirect()->route('admin.products.index');
        }

        Event::dispatch('product.create.before');

        if (isset($request->qty_ecommerce) && isset($request->qty_store) && isset($request->qty_diffettosi)){
            /* if ((int)$request->quantity >= (int)$request->qty_ecommerce){
                $magentoManager = MagentoManager::getInstance();
                $result = $magentoManager::addProduct($request);
                if ($result){
                    $this->success($request);
                }
            }else{
                session()->flash('error', 'La quantità ecommerce non può essere maggiore della scorta');
                return redirect()->route('admin.products.create');
            } */
            //$request->quantity = strval((int)$request->quantity - ((int)$request->qty_ecommerce + (int)$request->qty_diffettosi));

            //dd((int)$request->qty_diffettosi > (int)$request->qty_ecommerce);
            if ((int)$request->qty_diffettosi > (int)$request->qty_ecommerce){
                session()->flash('error', 'Quantità difettosa superiore alla quantità ecommerce');
                return redirect()->route('admin.products.create');
            }

            $magentoManager = MagentoManager::getInstance();
            $result = $magentoManager::addProduct($request);
            if ($result){
                $this->success($request);
            }
        }else{
            //Creazione prodotto PACKAGING
            if ((float)$request->price == 0.0){
                    $this->success($request);
            }else{
                session()->flash('error', trans('admin::app.products.qty-error'));
                return redirect()->route('admin.products.create');
            }
        }

        return redirect()->route('admin.products.index');
    }

    //Genera il CSV per le cofnigurazioni quantita del bundle
    private function createCSV($data){
        //dd($data);
        /* if ($data->max_options !== null){
            $fileName = $data->sku.'.csv';
            
            //Definizione dei dati
            $bundleInfo = [];

            $count = 0;
            $done = true;

            while ($done) {
                if ($data['option'.$count] !== null){
                    $info = [
                        $data->sku,
                        $data['option'.$count],
                        (int)$data->max_options,
                        (int)$data['min0'],
                        (int)$data['max'.$count]
                    ];

                    $bundleInfo[] = $info;
                    $count ++;
                }else{
                    $done = false;
                } 
            }

            $file = fopen($fileName, 'w');

            foreach ($bundleInfo as $row) {
                fputcsv($file, $row);
            }

            fclose($file);

            //$command = "scp -i /Users/enrico/Desktop/amabile-magento-prod.pem ".$fileName." ubuntu@ec2-18-102-166-137.eu-south-1.compute.amazonaws.com:/home/ubuntu";

            // Execute the SCP command
            //$output = shell_exec($command);

            //dd($command);
        } */

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $product = $this->productRepository->findOrFail($id);

        $bundleOptions = BundleOptions::where('sku_bundle', $product->sku)->get();

        $configurations = ProductConfiguration::where('parent_sku', $product->sku)->get();

        $bundleInfo = [];

        try{
            $file = fopen($product->sku.'.csv', 'r');

            if ($file){
                while (($row = fgetcsv($file)) !== false) {
                    // Add the row to the array
                    $bundleInfo[] = $row;
                }
            
                // Close the file
                fclose($file);
            }

        }catch (\Exception $e){

        }

        return view('admin::products.edit', compact('product', 'bundleOptions', 'configurations', 'bundleInfo'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Webkul\Attribute\Http\Requests\AttributeForm $request
     * @param int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(AttributeForm $request, $id)
    {
        Event::dispatch('product.update.before', $id);
        if($request->prod_type === '3'){

            $magentoManager = MagentoManager::getInstance();
            $result = $magentoManager::updateProduct($request);

            if ($result){
                BundleOptions::where('sku_bundle', $request->sku)->delete();
                $this->alterBundleOptions($request);
                $this->createCSV($request);
                $this->success($request, $id, 'update');
            }
            
            return redirect()->route('admin.products.search', ['page' => Session::get('page'),
            '_token' => Session::get('_token'),
            'name' => Session::get('name'),
            'tipo' => Session::get('tipo'),
            'pages' => Session::get('pages')]);
            //return redirect()->route('admin.products.index', ['page' => Session::get('page')]);
        } 

        if($request->prod_type === '4'){
            /* ProductConfigurations::where('sku_parent', $request->sku)->delete();
            $this->alterConfigurations($request); */
            $this->success($request, $id, 'update');
            //dd(Session::get('pages'));
            return redirect()->route('admin.products.search', ['page' => Session::get('page'),
            '_token' => Session::get('_token'),
            'name' => Session::get('name'),
            'tipo' => Session::get('tipo'),
            'pages' => Session::get('pages')]);
            //return redirect()->route('admin.products.search');
            //return redirect()->route('admin.products.index', ['page' => Session::get('page')]);
        }
            
        if (isset($request->qty_ecommerce) && isset($request->qty_store) && isset($request->qty_diffettosi)){
            /* if ( (int)$request->quantity != ((int)$request->qty_ecommerce + (int)$request->qty_store + (int)$request->qty_diffettosi)){
                session()->flash('error', trans('admin::app.products.qty-error'));
                return redirect()->route('admin.products.update', ['id' => $id]);
            }else{
                $magentoManager = MagentoManager::getInstance();
                $result = $magentoManager::updateProduct($request);
                if ($result){
                    $this->success($request, $id, 'update');
                }
            } */
            
            $prod = $this->productRepository->findOrFail($id);
            
            $qty_dif = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                ->where('attributes.code', 'qty_diffettosi')
                ->where('sku', 'like', '%'. $prod->sku .'%')
                ->select('products.name as name', 'products.sku as sku', 'attribute_values.text_value as value', 'products.price as price')
                ->first();
            

            $increased = !(((int)$request->qty_diffettosi - (int)$qty_dif->value) === 0);
            

            if ($increased){
                if(((int)$request->qty_diffettosi - (int)$qty_dif->value) > $request->qty_ecommerce){
                    session()->flash('error', 'Quantità difettosa superiore alla quantità ecommerce');
                    return redirect()->route('admin.products.update', ['id' => $id]);
                }
            }
            

            $magentoManager = MagentoManager::getInstance();
            $result = $magentoManager::updateProduct($request);
            if ($result){
                $this->success($request, $id, 'update');
            }
            /* if ((int)$request->quantity >= ((int)$request->qty_ecommerce - (int)$qty_ecom->value)){
                $magentoManager = MagentoManager::getInstance();
                $result = $magentoManager::updateProduct($request);
                if ($result){
                    $this->success($request, $id, 'update');
                }
            }else{
                
                session()->flash('error', 'Scorta non sufficiente');
                return redirect()->route('admin.products.update', ['id' => $id]);
            } */

        }else{
            if ((float)$request->price == 0.0){
                $this->success($request, $id, 'update');
            }else{
                session()->flash('error', trans('admin::app.products.qty-error'));
                return redirect()->route('admin.products.update', ['id' => $id]);
            }
        }

        return redirect()->route('admin.products.search', ['page' => Session::get('page'),
            '_token' => Session::get('_token'),
            'name' => Session::get('name'),
            'tipo' => Session::get('tipo'),
            'pages' => Session::get('pages')]);
            //return redirect()->route('admin.products.search');
        //return redirect()->route('admin.products.index', ['page' => Session::get('page')]);
    }

    private function alterBundleOptions($request){
        $count = 0;
        $done = true;

        while ($done){
            if ($request['option'.$count] != null){

                try{
                BundleOptions::create([
                    'sku_bundle' => $request->sku,
                    'sku_option' => $request['option'.$count],
                    'option_name' => $request['name'.$count],
                    'option_price' => (float)$request['price'.$count],
                    'qty' => $request['qty'.$count],
                    'can_change_qty' => $request['change'.$count] === null ? 0 : 1,
                ]);
                }catch(\Exception $e){
                    //dd($e);
                }
            }else{
                $done = false;
            }

            $count ++;
        }

    }

    //Modifica le configurazioni dei product
    private function alterConfigurations($request){
        $count = 0;
        $done = true;

        while ($done){
            if ($request['option'.$count] != null){   
                try{
                    ProductConfigurations::create([
                        'sku_parent' => $request->sku,
                        'sku_child' => $request['option'.$count],
                        'configuration_name' => $request['name'.$count],
                    ]);
                }catch(\Exception $e){
                   // dd($e);
                }
            }else{
                $done = false;
            }

            $count ++;
        }
    }

    private function success($request, $id = null, $action = 'store'){
        switch ($action){
            case 'update':
                $product = $this->productRepository->update(request()->all(), $id);
                Event::dispatch('product.update.after', $product);
                session()->flash('success', trans('admin::app.products.update-success'));
                break;
            default :
                $product = $this->productRepository->create(request()->all());
                Event::dispatch('product.create.after', $product);
                session()->flash('success', trans('admin::app.products.create-success'));
                
        }
    }

    /**
     * Search product results
     *
     * @return \Illuminate\Http\Response
     */
    public function search()
    { 

        $name = Session::get('name') !== null ? Session::get('name') : '';
        $pages = Session::get('pages') !== null ? Session::get('pages') : 30;
        $tipo = Session::get('tipo') !== null ? Session::get('tipo') : '';

        //dd($name);
        if (request()->name !== null){
            Session::put('name', request()->name);
            $name = request()->name;
        }else{
            $name = '';
            Session::pull('name');
        }

        if (request()->pages !== null && request()->pages >= 1){
            Session::put('pages', request()->pages);
            $pages = request()->pages;
        }else{
            $pages = 30;
            Session::pull('pages');
        }

        if (request()->tipo !== null){
            Session::put('tipo', request()->tipo);
            $tipo = request()->tipo;
        }else{
            $tipo = '';
            Session::pull('tipo');
        }

        if (request()->page !== null){
            Session::put('page', request()->page);
        }else{
            Session::put('page', 1);
        }

        $search = [
            'name' => request()->name,
            'pages' => request()->pages,
            'tipo' => request()->tipo
        ];

        $productsQuery = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->where('attributes.code', 'prod_type')
            ->where('products.name', 'like', '%'.$name.'%')
            ->select('products.id as id', 'products.name as name', 'products.sku as sku', 'attribute_values.integer_value as value','products.price as price', 'products.quantity as quantity')
            ->orderBy('products.id', 'desc');

        $condition = $tipo != ''; // Your condition here

        //dd(Session::get('page'));
        $products = $productsQuery->when($condition, function ($query) use ($tipo){
            // Add your additional where clause conditionally
            return $query->where('attribute_values.integer_value', (int)$tipo);
        })->paginate($pages);

        //dd($products);

        //dd(Session::get('page'));
        return view('products.list', ['products' => $products,
                                    'search' => $search]);

        /* $results = $this->productRepository->findWhere([
            ['name', 'like', '%' . urldecode(request()->input('query')) . '%']
        ]);

        return response()->json($results); */
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //dd(request()->all());
        $product = $this->productRepository->findOrFail($id);

        $type = AttributeValue::select('integer_value')->where('entity_id', $product->id)->where('attribute_id', 33)->first();

        if ($type->integer_value === 2) {
            $this->productRepository->delete($id);
            session()->flash('success', 'Prodotto eliminato con successo');
            return redirect()->route('admin.products.index');
        }

        try{
            $magentoManager = MagentoManager::getInstance();
            $result = $magentoManager::deleteProduct($product->sku);

            if ($result){
                Event::dispatch('settings.products.delete.before', $id);

                $this->productRepository->delete($id);
                BundleOptions::where('sku_bundle', $product->sku)->delete();

                ConfigurableOption::where('sku_configuration', $product->sku)->delete();

                ProductConfiguration::where('parent_sku', $product->sku)->orWhere('child_sku', $product->sku)->delete();

                ProductAttribute::where('product_sku', $product->sku)->delete();
                Event::dispatch('settings.products.delete.after', $id);
                session()->flash('success', 'Prodotto eliminato con successo');

            } else {
                session()->flash('error', 'Errore nella eliminazione del prodotto');
            }
            
        }catch (\Exception $e){
            session()->flash('error', 'Errore nella eliminazione del prodotto');
        }

        return redirect()->route('admin.products.search', [
        '_token' => Session::get('_token'),
        'name' => Session::get('name'),
        'tipo' => Session::get('tipo'),
        'pages' => Session::get('pages'),
        'page' => Session::get('page')]);
        /* try {
            $magentoManager = MagentoManager::getInstance();
            $result = $magentoManager::deleteProduct($product->sku);

            if ($result){
                Event::dispatch('settings.products.delete.before', $id);

                $this->productRepository->delete($id);
                BundleOptions::where('sku_bundle', $product->sku)->delete();

                ConfigurableOption::where('sku_configuration', $product->sku)->delete();

                ProductConfiguration::where('parent_sku', $product->sku)->orWhere('child_sku', $product->sku)->delete();

                ProductAttribute::where('product_sku', $product->sku)->delete();
                Event::dispatch('settings.products.delete.after', $id);

                return response()->json([
                    'message' => trans('admin::app.response.destroy-success', ['name' => trans('admin::app.products.product')]),
                ], 200); 
            }
        } catch(\Exception $exception) {
            return response()->json([
                'message' => trans('admin::app.response.destroy-failed', ['name' => trans('admin::app.products.product')]),
            ], 400);
        } */
    }

    /**
     * Mass Delete the specified resources.
     *
     * @return \Illuminate\Http\Response
     */
    public function massDestroy()
    {
        foreach (request('rows') as $productId) {
            $product = $this->productRepository->findOrFail($productId);
            $magentoManager = MagentoManager::getInstance();
            $result = $magentoManager::deleteProduct($product->sku);

            if ($result){
                Event::dispatch('product.delete.before', $productId);

                $this->productRepository->delete($productId);

                BundleOptions::where('sku_bundle', $product->sku)->delete();

                ConfigurableOption::where('sku_configuration', $product->sku)->delete();

                ProductConfiguration::where('parent_sku', $product->sku)->orWhere('child_sku', $product->sku)->delete();

                ProductAttribute::where('product_sku', $product->sku)->delete();
                
                Event::dispatch('product.delete.after', $productId);
            }
        }

        return response()->json([
            'message' => trans('admin::app.response.destroy-success', ['name' => trans('admin::app.products.title')]),
        ]);
    }

    public function getDefectiveProducts(){
        $products = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->where('attributes.code', 'qty_diffettosi')
            ->where('text_value', '<>', '0')
            ->select('products.name as name', 'products.sku as sku', 'attribute_values.text_value as value','products.price as price')
            ->get();

        //dd($products);
        return view('products.index', ['products' => $products]);
    }

    public function searchDefectiveProducts(Request $request){

        $products = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->where('attributes.code', 'qty_diffettosi')
            ->where('text_value', '<>', '0')
            ->where('products.name', 'like', '%'. $request->name .'%')
            ->where('sku', 'like', '%'. $request->sku .'%')
            ->select('products.name as name', 'products.sku as sku', 'attribute_values.text_value as value', 'products.price as price')
            ->get();
        
        //dd($products[0]->price);

        return view('products.index', ['products' => $products, 'name' => $request->name, 'sku' => $request->sku]);
    }

    //Visualizza tutti i prodotti configurabili
    public function listConfigurableProducts(){

        $products = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->where('attributes.code', 'prod_type')
            ->where('integer_value', '=', 4)
            ->select('products.id as id','products.name as name', 'products.sku as sku', 'attribute_values.text_value as value', 'products.price as price')
            ->paginate(30);

            Session::pull('pages_conf');
            Session::pull('name_conf');
            //Session::pull('page_conf');
         if(isset(request()->query()['page']) && request()->query()['page'] !== null){
            Session::put('page_conf', request()->query()['page']);
        } 
        //dd(request()->all());
            //dd($products);
        return view('products.configurable.index', ['products' => $products]);
        /* return redirect()->route('admin.products.configurable.search', [
            'nome' => Session::get('name_conf'),
            'pages' => Session::get('pages_conf'),
            'page' => Session::get('page_conf')
        ]); */
    }

    public function clearConf(){

        $products = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->where('attributes.code', 'prod_type')
            ->where('integer_value', '=', 4)
            ->select('products.id as id','products.name as name', 'products.sku as sku', 'attribute_values.text_value as value', 'products.price as price')
            ->paginate(30);

        return view('products.configurable.index', ['products' => $products]);
    }

    public function searchConfigurableProducts(){
        $pages = 30;

        if(request()->page !== null){
            Session::put('page_conf', request()->page);
        }else{
            Session::put('page_conf', 1);
        }

        if (request()->pages !== null && request()->pages > 0){
            $pages = request()->pages;
            Session::put('pages_conf', request()->pages);
        }else{
            Session::pull('pages_conf');
        }

        if (request()->nome !== null){
            Session::put('name_conf', request()->nome);
        }else{
            Session::pull('name_conf');
        }

        $products = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->where('attributes.code', 'prod_type')
            ->where('integer_value', '=', 4)
            ->where('products.name', 'like', '%'.request()->nome.'%')
            ->select('products.id as id','products.name as name', 'products.sku as sku', 'attribute_values.text_value as value', 'products.price as price')
            ->paginate($pages);

        $search = [
            'nome' => request()->nome,
            'pages' => $pages
        ];

        return view('products.configurable.index', ['products' => $products, 'search' => $search]);
    }

    //Visualizza tutte le configurazioni per un prodotto
    public function listConfigurations($id){
        $product = $this->productRepository->findOrFail($id);
        $count = ProductConfiguration::where('parent_sku', $product->sku)->get()->count();
        $configurations = ProductConfiguration::where('parent_sku', $product->sku)->get();

        $products = [];

        foreach ($configurations as $configuration){
            $prod = Product::where('sku', $configuration->child_sku)->first();
            if ($prod !== null){
                array_push($products, $prod);
            }
            
        }

        $countAtrtributeConfigurations = ConfigurableOption::where('sku_configuration', $product->sku)->get()->count();

        return view('products.configurable.configurations', ['products' => $products, 'count' => $count, 'id'=> $id, 'countAtrtributeConfigurations' => $countAtrtributeConfigurations]);
    }

    //mostra i prodotti che possono essere aggiunti alla configurazione
    public function showProducts($id){
        $product = $this->productRepository->findOrFail($id);
        $options = ConfigurableOption::where('sku_configuration', $product->sku)->get();
        
        $configurations = ProductConfiguration::where('parent_sku', $product->sku)->get();

        $avoidAttrributes = [];
        foreach ($configurations as $configuration){
            
            if (Product::where('sku', $configuration->child_sku)->first() !== null){
                $attributes = ProductAttribute::select('attribute_id','value')->where('product_sku', $configuration->child_sku)->get();
                foreach ($attributes as $attribute){
                    $toAvoid = MagentoAttributeLabel::where('attribute_id', $attribute->attribute_id)->where('value', $attribute->value)->first();
                    if ($toAvoid !== null) {
                        array_push($avoidAttrributes, ['value', '<>', $toAvoid->value]);
                    }
                    
                }
            }
            
        }

        $products = [];
        foreach ($options as $option){
            //dd($option->attribute_id);
            $prods = ProductAttribute::select('product_sku')->where('attribute_id', $option->attribute_id)->where($avoidAttrributes)->get();
            
            foreach ($prods as $prod){
                $prod1 = Product::select('sku', 'name')->where('sku', $prod->product_sku)->first();
                if ($prod1 !== null){
                    array_push($products, $prod1);
                
                }
                //array_push($products, Product::select('sku', 'name')->where('sku', $prod->product_sku)->first());
                
            }
        }

        return view('products.configurable.list-products', ['products' => $products, 'parent_sku' => $product->sku, 'id' => $id]);
    }

    //Aggiunge le configurazioni di prodotto
    public function addProducts(Request $request, $id){

        $product = $this->productRepository->findOrFail($id);
        $magentoManager = MagentoManager::getInstance();
        $result = $magentoManager::addProductConfiguration($request);
        
        if ($result){ 
            foreach ($request->selectedItems as $item){
                if (ProductConfiguration::where('parent_sku', $product->sku)->where('child_sku', $item)->first() === null){
                    ProductConfiguration::create([
                        'parent_sku' => $product->sku,
                        'child_sku' => $item
                    ]);
                }
            }          
            session()->flash('success', "Prodotti aggiunti alla configurazione");
            return redirect()->route('admin.products.configurable.show-products', ['id' => $id]);
        }else{
            session()->flash('error', "Uno o piu prodotti non sono stati assegnati");
            return back()->with('id', $id);
        }
    }

    public function listAttributes($id){      
        $attributes = MagentoAttributes::where('attribute_code', 'color')->orWhere('attribute_code', 'like', 'nc%')->get();
        //$attributes = MagentoAttributes::all();
        return view('products.configurable.attributes', ['attributes' => $attributes]);
    }

    public function listOptions($id){
        $values = MagentoAttributeLabel::where('attribute_id', request()->attribute)->get();
        return view('products.configurable.attribute_values', ['values' => $values, 'attribute' => request()->attribute]);
    }

    public function showOption(Request $request, $id){
        
        $product = $this->productRepository->findOrFail($id);
        $attributeCode = MagentoAttributes::where('attribute_id', $request->attribute_id)->first();

        if ($request->selectedItems === null){
            $magentoManager = MagentoManager::getInstance();
            $result = $magentoManager::addAttributeConfigurable($product->sku, $request->attribute_id, $attributeCode->attribute_code);
            if ($result){
                ConfigurableOption::create([
                    'sku_configuration' => $product->sku,
                    'attribute_id' => $request->attribute_id
                ]);
                session()->flash('success', "Attributo configurato con successo");
            }else{
                session()->flash('error', "Errore nella configurazione dell'attributo");
            }

            return redirect()->route('admin.products.configurable.list', ['id' => $id]);
        }

        $values = [];
        foreach ($request->selectedItems as $item){
            $value = MagentoAttributeLabel::where('attribute_id', request()->attribute_id)->where('option_id', $item)->first();
            
            array_push($values, $value);

        }

        
        return view('products.configurable.create-product-config', ['baseSku'=> $product->sku,
                    'options' => $values,
                    'attribute_id' => $request->attribute_id,
                    'attribute_code' => $attributeCode->attribute_code,
                    'id' => $id]);
    }

    //Aggiungi le opzioni configurabili
    public function addOptions(Request $request, $id){

        $done = true;
        $count = 0;

        while ($done) {
            if ($request['sku'.$count] !== null){
                if (Product::where('sku', $request['sku'.$count])->first() !== null){
                    session()->flash('error', "Uno o più SKU generati esistono già");
                    return redirect()->route('admin.products.configurable');
                }
            }else{
                $done = false;
            }

            $count++;
        }

        $magentoManager = MagentoManager::getInstance();
        $result = $magentoManager::addAtrributeOption($request);

        if ($result) {
            ConfigurableOption::create([
                'sku_configuration' => $request->sku,
                'attribute_id' => $request->attribute_id
            ]);

            $done = true;
            $count = 0;

            while ($done) {
                if ($request['sku'.$count] !== null) {

                    $newSimpleProduct = [
                        'name' => $request['name'.$count],
                        'description' => null,
                        'sku' => $request['sku'.$count],
                        'prod_type' => '1',
                        'quantity' => $request['quantity'.$count],
                        'price' => $request['price'.$count],
                        'qty_diffettosi' => '0',
                        'qty_ecommerce' => $request['quantity'.$count],
                        'qty_store' => '0'
                    ];

                    Product::create([
                        'name' => $request['name'.$count],
                        'sku' => $request['sku'.$count],
                        'description' => null,
                        'quantity' => $request['quantity'.$count],
                        'price' => $request['price'.$count]
                    ]);
    
                    $prod = Product::where('sku', $request['sku'.$count])->first();
    
                    foreach ($newSimpleProduct as $key => $value){
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

                    $val = MagentoAttributeLabel::where('attribute_id', request()->attribute_id)->where('option_id', $request['value'.$count])->first();

                    //Aggiungo il prodotto alle configurazioni
                    ProductAttribute::create([
                        'product_sku' => $request['sku'.$count],
                        'attribute_id' => $request->attribute_id, 
                        'value' => $val->value
                    ]);

                    ProductConfiguration::create([
                        'parent_sku' => $request->sku,
                        'child_sku' => $request['sku'.$count]
                    ]);

                    $count ++;
                }else{
                    $done = false;
                }
            }

            session()->flash('success', "Prodotti creati con successo");
            //return redirect()->route('admin.products.configurable');
            return redirect()->route('admin.products.configurable.search', [
                'nome' => Session::get('name_conf'),
                'pages' => Session::get('pages_conf'),
                'page' => Session::get('page_conf')
            ]);
        }else{
            session()->flash('error', "Errore nella creazione delle opzioni prodotto");
            //return back()->with('id', $id);
            return redirect()->route('admin.products.configurable.search', [
                'nome' => Session::get('name_conf'),
                'pages' => Session::get('pages_conf'),
                'page' => Session::get('page_conf')
            ]);
        } 
    }

    public function removeConfigurableOption($id, $childSku){
        $product = $this->productRepository->findOrFail($id);
        
        $magentoManager = MagentoManager::getInstance();
        $result = $magentoManager::removeConfigurationOption($product->sku, $childSku);

        if ($result) {
            ProductConfiguration::where('child_sku', $childSku)->delete();
            session()->flash('success', "Configurazione rimossa con successo");
            return back()->with('id', $id);
        } else {
            session()->flash('error', "Errore nella rimozione della configurazione");
            return back()->with('id', $id);
        }
    }

    //opzioni attrributo per il singolo prodotto (semplice)
    public function listAttributesProduct($id){
        $attributes = MagentoAttributes::where('attribute_code', 'color')->orWhere('attribute_code', 'like', 'nc%')->get();
        return view('products.attributes', ['attributes' => $attributes, 'id' => $id]);
    }

    public function listOptionsProduct(Request $request, $id){
        //dd($request);
        $values = MagentoAttributeLabel::where('attribute_id', request()->attribute)->get();
        return view('products.attribute_values', ['values' => $values, 'attribute' => request()->attribute, 'id' => $id]);
    }

    public function addOptionProduct(Request $request, $id){
        if ($request->item === null){
            session()->flash('error', 'Nessuna opzione attributo specificata');
            //return redirect()->route('admin.products.index');
            return redirect()->route('admin.products.search', ['page' => Session::get('page'),
            '_token' => Session::get('_token'),
            'name' => Session::get('name'),
            'tipo' => Session::get('tipo'),
            'pages' => Session::get('pages')]);
        }

        $val = MagentoAttributeLabel::where('attribute_id', request()->attribute_id)->where('option_id', $request->item)->first();
        $attribute = MagentoAttributes::where('attribute_id', request()->attribute_id)->first();
        $product = $this->productRepository->findOrFail($id);

        $magentoManager = MagentoManager::getInstance();
        $result = $magentoManager::addProductAtrribute($request, $product->sku, $attribute->attribute_code); 

        if ($result){
            $prodAttributeCount = ProductAttribute::where('product_sku', $product->sku)
            ->where('attribute_id', $request->attribute_id)
            ->get()->count();

            if ($prodAttributeCount > 0){
                
                $prodAttribute = ProductAttribute::where('product_sku', $product->sku)
                    ->where('attribute_id', $request->attribute_id)
                    ->first();

                $prodAttribute->update([
                    'value' => $val->value
                ]);
            }else{
                ProductAttribute::create([
                    'product_sku' => $product->sku,
                    'attribute_id' => $request->attribute_id, 
                    'value' => $val->value
                ]);
            }
            

            session()->flash('success', 'Attributo assegnato correttamente');

        }else{
            session()->flash('error', 'Errore nell\'assegnazione dell\'attributo');
        }
        
        //return redirect()->route('admin.products.index');
        return redirect()->route('admin.products.search', ['page' => Session::get('page'),
            '_token' => Session::get('_token'),
            'name' => Session::get('name'),
            'tipo' => Session::get('tipo'),
            'pages' => Session::get('pages')]);
        
    }
}
