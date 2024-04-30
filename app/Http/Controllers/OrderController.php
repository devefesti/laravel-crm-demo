<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\MagentoManager;
use App\Models\OrderProds;
use App\Models\OperatorOrders;
use App\Models\OrderManager;
use App\Models\GlsOrders;
use Illuminate\Support\Facades\DB;
use App\Models\OrderPack;
use App\Models\Report;
use App\Models\BundleProdsOrder;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Auth;
use Webkul\Attribute\Models\AttributeValue;
use Illuminate\Support\Str;
use App\Models\OrderProdAttribute;
use App\Models\ReportOrder;
use Illuminate\Support\Facades\Session;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //ottengo il ruolo dello user loggato e se si tratta di un operatore , restituisco la lista degli ordini filtrata per operatore
        /* $manager = new MagentoManager();
             $manager->syncBundleProds(); */
        $orders = null;
        $id = Auth::id();
        $user = DB::table('users')->where('id', $id)->where('role_id', 3)->first();

        if($user != null)
        {
            $orders =  Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')->leftJoin('report_orders', 'orders.order_id', '=', 'report_orders.order_id')->where('orders_operators.operator', $user->name)->where('orders.status', 'processing')->orWhere('orders.status', 'anelli')
            ->select('orders.*', 'report_orders.report as report')->when(Session::has('param'), function ($query) {
                // Add orderBy clause only when Session::has('param') is true
                return $query->orderBy(Session::get('param'), Session::get('order')); // Replace 'your_column' with the actual column you want to order by
            })->paginate(50);

        }
        else{
            $orders = Order::leftJoin('report_orders', 'orders.order_id', '=', 'report_orders.order_id')->when(Session::has('param'), function ($query) {
                // Add orderBy clause only when Session::has('param') is true
                return $query->orderBy(Session::get('param'), Session::get('order')); // Replace 'your_column' with the actual column you want to order by
            })->select('orders.*', 'report')->paginate(50);
        }
        

        Session::pull('order_id');
        Session::pull('from');
        Session::pull('to');
        Session::pull('stato');

        if(isset(request()->query()['page']) && request()->query()['page'] !== null){
            Session::put('order_page', request()->query()['page']);
        }

        $count = Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')->where('orders.status', 'like', '%da_spedire%')->orWhere('orders.status', '=', 'complete')->get()->count();
        $count2 = Order::where('orders.status', '=', 'processing')->orWhere('orders.status', '=', 'anelli')->get()->count();
        return view("orders.index", ['orders' => $orders, 'user' => $user , 'count' => $count, 'count2' => $count2]);
    }

    //funzione che visualizza tutti i prodotti nella schermata dello user di tipo orders admin, con le assegnazioni fatte
    public function showAdminOrders()
    {
        $id = Auth::id();
        $user = DB::table('users')->where('id', $id)->where('role_id', 3)->first();

        if($user != null)
        {
            return redirect("admin/orders");
        }

        $ordersOperators =  Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')->leftJoin('report_orders', 'orders.order_id', '=', 'report_orders.order_id')
        ->select('orders.order_id as order_id', 'orders.status', 'orders.email','orders.totale', 'orders.firstname', 'orders.lastname' , 'orders_operators.operator', 'orders_operators.id','orders_operators.date','report_orders.report')
        ->orderBy('orders.order_id', 'desc')
        ->paginate(50);  

        //ottengo anche la lista degli operatori che servirà per il filtro
        $users = DB::table('users')->where('role_id', 3)->get();

        Session::pull('order_id_man');
        Session::pull('from_man');
        Session::pull('to_man');
        Session::pull('stato_man');
        Session::pull('order_id_man');
        Session::pull('operator_man');

        if(isset(request()->query()['page']) && request()->query()['page'] !== null){
            Session::put('page_man', request()->query()['page']);
        }
        $count_assigned = 0;
        return view("orders.admin.index", ['orders' => $ordersOperators, 'users' => $users, 'count_assigned' => $count_assigned]);

    }

    //questa funzione fa atterrare l'admin nella schermata che permette di assegnare ordini ad un utente 
    public function showOrdersToAssign()
    {
        $id = Auth::id();
        $user = DB::table('users')->where('id', $id)->where('role_id', 3)->first();

        if($user != null)
        {
            return redirect("admin/orders");
        }

        if(isset(request()->query()['page']) && request()->query()['page'] !== null){
            Session::put('page_ass', request()->query()['page']);
        }

     //ottengo la lista degli ordini che sono da assegnare 
     //TO DO: gli ordini da assegnare devono avere lo stato "in lavorazione" e non deve essere già assegnato ad un altro operatore
     //inoltre non ci devono essere gli ordini che hanno già uno user associato

     $filterOrders = Order::leftJoin('orders_operators','orders.order_id','=','orders_operators.order_id')->whereNull('orders_operators.order_id')->where('orders.status', 'processing')->orWhere('orders.status', 'anelli')
     ->select('orders.*', 'orders_operators.id')
     ->where('status', 'like', '%processing%')
     ->orWhere('status', 'like', '%anelli%')
     ->when(Session::has('param'), function ($query) {
        // Add orderBy clause only when Session::has('param') is true
        return $query->orderBy(Session::get('param'), Session::get('order')); // Replace 'your_column' with the actual column you want to order by
    })
     ->paginate(50);

     //Ottengo la lista degli operatori (role_id=3):
     $users = DB::table('users')->where('role_id', 3)->get();
     return view("orders.admin.assign",['users' => $users, 'orders' => $filterOrders]);
    }

    public function assignOrders(Request $request)
    {
        $id = Auth::id();
        $user = DB::table('users')->where('id', $id)->where('role_id', 3)->first();

        if($user != null)
        {
            return redirect("admin/orders");
        }

        $orderManager = new OrderManager();
        //riceve i dati dell'operatore, e la lista degli ordini assegnati, i dati devono essere salvati  nella tabella orders_operators

        //salvo l'id dell'operatore
        $operator = $request->operator;
        //array degli ordini selezionati per essere assegnati all'operatore
        $selectedItems = $request->selectedItems;
    
        $assign_date = date('d-m-Y');
        //ottengo lo stato dell'ordine
        
        //dd($request->selectedItems);
        if ($request->selectedItems !== null){
            //aggiorno la tabella orders-operators con i codici associati allo user che dovrà evadere l'ordine
            for($i=0; $i<count($selectedItems); $i++)
            {
                OperatorOrders::create(
                    [
                        'order_id' => $selectedItems[$i],
                        'order_state' => 'processing',
                        'operator' => $request->operator,
                        'date' => $assign_date
                    ]
                );
            } 
        }else{
            session()->flash('error', 'Nessun ordine selezionato');
            return back();
        }
        
         
        /* $ordersOperators =  Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')
        ->select('orders.order_id as order_id', 'orders.status', 'orders.email','orders.totale', 'orders.firstname', 'orders.lastname' , 'orders_operators.operator','orders_operators.date','orders_operators.report')
        ->paginate(50);   */
        
       // $users = DB::table('users')->where('role_id', 3)->get();

       // return view("orders.admin.index", ['orders' => $ordersOperators, 'users' => $users]);

        return redirect()->route('admin.orders.management');
    }
    
    //TO DO: la funzione che permette all'admin degli ordini di modificare l'operatore assegnato all'ordine
    //il pulsante di modifica sarà presenten nella tabella della panoramica che mostra gli ordini e i relativi operatori
    public static function showAssignedOrder($id)
    {
        $id_user = Auth::id();
        $user = DB::table('users')->where('id', $id_user)->where('role_id', 3)->first();

        if($user != null)
        {
            return redirect("admin/orders");
        }

        $operators = DB::table('users')->where('role_id', 3)->get();
        $assignedOrder = OperatorOrders::where('order_id', $id)->first();
        return view('orders.admin.edit', ['order' => $assignedOrder, 'operators' => $operators]);
    
    }

    public static function editAssignedOrder(Request $request)
    {
      
        $ordersOperators = OperatorOrders::where('order_id',$request->order_id)->first();
        //$ordersOperators->update(['operator', $request->operator]);
        $ordersOperators->operator = $request->operator;
        $ordersOperators->save();

        $ordersOperators =  Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')
        ->select('orders.order_id as order_id', 'orders.status', 'orders.email','orders.totale', 'orders.firstname', 'orders.lastname' , 'orders_operators.operator','orders_operators.date','orders_operators.report')
        ->paginate(50);  
	//return view("orders.admin.index", ['orders' => $ordersOperators]);
	//return redirect()->route('admin.orders.management');
        return redirect()->route('admin.orders.management.assigned.search', [
            'operator' => Session::get('operator_man'),
            'order_id' => Session::get('order_id_man'),
            'from' => Session::get('from_man'),
            'to' => Session::get('to_man'),
            'stato' => Session::get('stato_man'),
            'page' => Session::get('page_man')]);
    }

    public static function cancelAssignment($id)
    {
      
        $order = OperatorOrders::where('order_id', $id)->first();
        $order->delete();
       
        //return redirect()->route('admin.orders.management');
        return redirect()->route('admin.orders.management.assigned.search', [
            'operator' => Session::get('operator_man'),
            'order_id' => Session::get('order_id_man'),
            'from' => Session::get('from_man'),
            'to' => Session::get('to_man'),
            'stato' => Session::get('stato_man'),
            'page' => Session::get('page_man')]);
    }

    public static function searchOrderToAssign(Request $request)
    {

    $filterOrders = null;
    $fromString = '1900-01-01 00:00:00';
    $toString = '2100-01-01 00:00:00';
    $pages = 10;
    if(isset($request->pages) && $request->pages != null && $request->pages > 0)
    {
     $pages = $request->pages;
    } else{
        $pages = 10;
    } 
/* 
    if (request()->page !== null){
        Session::put('page_ass', request()->page);
    } */

    /* $from=date(strtotime($fromString));
    $to=date(strtotime($toString)); */
    $from= $fromString;
    $to= $toString;
    
    
    if(request()->input('from') != null)
    { 
        /* $dateTime = strtotime(request()->input('from'));
        $from = date('Y-m-d h:m',$dateTime); */
        $from = request()->input('from').' 00:00:00';
    }

    if(request()->input('to') != null)
    {
        /* $dateTime = strtotime(request()->input('to'));
        $to = date('Y-m-d h:m',$dateTime); */
        $to = request()->input('to').' 00:00:00';
    }
    //ottengo la lista degli ordini che popolano la select degli operatori
    $users = DB::table('users')->where('role_id', 3)->get();
    //filtro la tabella degli ordini in base ai campi di ricerca
    
    if(request()->input('order_id') != null)
    {
    /*  $filterOrders = Order::whereBetween('order_date', [$from, $to])
     ->where('order_id', request()->input('order_id'))
     ->where('status', 'like', '%processing%')
     ->orWhere('status', 'like', '%anelli%')
     ->orderByDesc('orders.order_id')
     ->paginate($pages); */

     //dd("ok");
     $filterOrders = Order::leftJoin('orders_operators','orders.order_id','=','orders_operators.order_id')->whereNull('orders_operators.order_id')->whereRaw("DATE_ADD(orders.order_date, INTERVAL 1 HOUR) BETWEEN ? AND ?", [$from, $to])
     ->where('orders.order_id', request()->input('order_id'))
    ->where(function ($query) {
        $query->where('orders.status', 'like', '%processing%')
            ->orWhere('orders.status', 'like', '%anelli%');
    })
    ->select('orders.*')
    ->paginate($pages);

    //dd("ok");
    }
    else{
        $filterOrders = Order::leftJoin('orders_operators','orders.order_id','=','orders_operators.order_id')
        ->whereNull('orders_operators.order_id')
        ->where('status', 'like', '%processing%')
        ->orWhere('status', 'like', '%anelli%')
        /* ->whereBetween('orders.order_date', [$from, $to]) */
        ->whereRaw("DATE_ADD(orders.order_date, INTERVAL 1 HOUR) BETWEEN ? AND ?", [$from, $to])
        ->select('orders.*')
        ->orderByDesc('orders.order_id')
        ->paginate($pages);

    }

    /* if ($request->order_id !== null){
        Session::put('order_id_ass', $request->order_id);
    }else{
        Session::pull('order_id_ass');
    } */

    $search = [
        'order_id' => $request->order_id,
        'from' => $request->from,
        'to' => $request->to,
        'pages' => $request->pages
    ];

    return view("orders.admin.assign",['users' => $users, 'orders' => $filterOrders, 'search' => $search]);

    }

    public static function searchAssignedOrder(Request $request)
    {

        $ordersOperators = null;
        $fromString = '01-01-1900';
        $toString = '01-01-2100';
        $stato = Session::get('stato_man') !== null ? Session::get('stato_man') : '';

        //dd(Session::get('from_man'));
        $from= Session::get('from_man') !== null ? Session::get('from_man') : date(strtotime($fromString));
      
        $to= Session::get('to_man') !== null ? Session::get('to_man') : date(strtotime($toString));

        $users = DB::table('users')->where('role_id', 3)->get();

        if($request->stato != null){
            $stato = $request->stato;
            Session::put('stato_man', $request->stato);
        }else{
            $stato = '';
            Session::pull('stato_man');
        }
        
        if($request->from != null)
        { 
             $dateTime = strtotime($request->from);
             $from = date('d-m-Y',$dateTime);
             Session::put('from_man', $request->from);
           
        }else{
            Session::pull('from_man');
        }
    
        if($request->to != null)
        {
            $dateTime2 = strtotime($request->to);
            $to = date('d-m-Y',$dateTime2);
            Session::put('to_man', $request->to);
        }else{
            Session::pull('to_man');
        }

        if (request()->page !== null){
            Session::put('page_man', request()->page);
        }else{
            Session::put('page_man', 1);
        }
     
        if(($request->order_id == null && $request->operator == null) /* || (Session::get('order_id_man') === null && Session::get('operator_man') === null) */)
        {
            $ordersOperators =  Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')
            ->whereBetween('orders_operators.date', [$from, $to])
            ->where('orders.status', 'like', '%'.$stato.'%')
            ->select('orders.order_id as order_id', 'orders.status', 'orders.email','orders.totale', 'orders.firstname', 'orders.lastname' , 'orders_operators.operator', 'orders_operators.id','orders_operators.date','orders_operators.report')
            ->paginate(50);
        }
        else{
            
            if(($request->order_id != null &&  $request->operator != null) /* || (Session::get('order_id_man') !== null && Session::get('operator_man') !== null) */)
            {
                $ordersOperators =  Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')
                ->where('orders.order_id', $request->order_id)->where('orders_operators.operator', $request->operator)
                ->whereBetween('orders_operators.date', [$from, $to])
                ->where('orders.status', 'like', '%'.$stato.'%')
                ->select('orders.order_id as order_id', 'orders.status', 'orders.email','orders.totale', 'orders.firstname', 'orders.lastname' , 'orders_operators.operator', 'orders_operators.id','orders_operators.date','orders_operators.report')
                ->paginate(50);
                //dd($ordersOperators);
                
            }
            
            if(($request->order_id != null &&  $request->operator == null) /* || (Session::get('order_id_man') !== null && Session::get('operator_man') === null) */){
                $ordersOperators =  Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')
                ->where('orders.order_id', $request->order_id)
                ->whereBetween('orders_operators.date', [$from, $to])
                ->where('orders.status', 'like', '%'.$stato.'%')
                ->select('orders.order_id as order_id', 'orders.status', 'orders.email','orders.totale', 'orders.firstname', 'orders.lastname' , 'orders_operators.operator', 'orders_operators.id','orders_operators.date','orders_operators.report')
                ->paginate(50);

                //dd($ordersOperators);
            }
            
            if(($request->order_id == null &&  $request->operator != null) /* || (Session::get('order_id_man') === null && Session::get('operator_man') !== null) */){
               $ordersOperators = Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')
               ->where('orders_operators.operator', $request->operator)
               ->whereBetween('orders_operators.date', [$from, $to])
               ->where('orders.status', 'like', '%'.$stato.'%')
               ->select('orders.order_id as order_id', 'orders.status', 'orders.email','orders.totale', 'orders.firstname', 'orders.lastname' , 'orders_operators.operator', 'orders_operators.id','orders_operators.date','orders_operators.report')
                ->paginate(50);

            }

        }

        if ($request->operator){
            Session::put('operator_man', $request->operator);
        }else{
            Session::pull('operator_man');
        }

        if ($request->order_id){
            Session::put('order_id_man', $request->order_id);
        }else{
            Session::pull('order_id_man');
        }

        $search = [
            'operator' => Session::get('operator_man'),
            'order_id' => Session::get('order_id_man'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'stato' => $request->stato
        ];

        $count_assigned = Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')
            ->where('orders_operators.operator', $request->operator)->get()->count();

        return view("orders.admin.index", ['orders' => $ordersOperators, 'users' => $users, 'search' => $search, 'count_assigned' => $count_assigned]);
    }


    //funzione che effettua la ricerca degli ordini da parte di un operatore
    //Works
    public static function searchOrdersOperator(Request $request)
    {
        $fromString = '01-01-1900';
        $toString = '01-01-2100';
        $stato = Session::get('stato') !== null ? Session::get('stato') : '';

        $from= Session::get('from') !== null ? Session::get('from') : date(strtotime($fromString));
        $to= Session::get('to') !== null ? Session::get('to') : date(strtotime($toString));

        //dd($request->from);
        if($request->input('from') != null)
        { 
            $dateTime = strtotime($request->input('from'));
            $from = date('Y-m-d',$dateTime);
            Session::put('from', $from);
            //dd($from);
        }else{
            Session::pull('from');
        }
    
        if($request->input('to') != null)
        {
            $dateTime2 = strtotime($request->input('to'));
            $to = date('Y-m-d',$dateTime2);
            Session::put('to', $to);
        }else{
            Session::pull('to');
        }

        if($request->input('stato') != null){
            $stato = $request->input('stato');
            Session::put('stato', $stato);
        }else{
            $stato = '';
            Session::pull('stato');
        }

        if (request()->page !== null){
            Session::put('order_page', request()->page);
        }else{
            Session::put('order_page', 1);
        }

        $orders = null;
        $id = Auth::id();
        $user = DB::table('users')->where('id', $id)->where('role_id', 3)->first();

        if($user != null)
        {
        if($request->input('order_id') == null /* || Session::get('order_id') === null */)
        {
            $orders =  Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')->where('orders_operators.operator', $user->name)/* ->whereBetween('orders.order_date', [$from, $to]) */->whereRaw("DATE_ADD(orders.order_date, INTERVAL 1 HOUR) BETWEEN ? AND ?", [$from, $to])->where('orders.status', 'like', '%'.$stato.'%')->whereNotIn('orders.status', ['complete', 'canceled', 'closed', 'da_spedire'])
            ->when(Session::has('param'), function ($query) {
                // Add orderBy clause only when Session::has('param') is true
                return $query->orderBy(Session::get('param'), Session::get('order')); // Replace 'your_column' with the actual column you want to order by
            })
            ->select('orders.*')->paginate(50);

            //$count = Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')->where('orders_operators.operator', $user->name)->whereBetween('orders_operators.date', [$from, $to])->where('orders.status', 'like', '%'.$stato.'%')/*->count() */->get();

        }else{
            Session::put('order_id', $request->input('order_id'));
            $orders =  Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')->where('orders_operators.operator', $user->name)->where('orders.order_id', $request->order_id)/* ->whereBetween('orders.order_date', [$from, $to]) */->whereRaw("DATE_ADD(orders.order_date, INTERVAL 1 HOUR) BETWEEN ? AND ?", [$from, $to])->where('orders.status', 'like', '%'.$stato.'%')->whereNotIn('orders.status', ['complete', 'canceled', 'closed', 'da_spedire'])
            ->when(Session::has('param'), function ($query) {
                // Add orderBy clause only when Session::has('param') is true
                return $query->orderBy(Session::get('param'), Session::get('order')); // Replace 'your_column' with the actual column you want to order by
            })
            ->select('orders.*')->paginate(50);

            //$count = Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')->where('orders_operators.operator', $user->name)->whereBetween('orders_operators.date', [$from, $to])->where('orders.status', 'like', '%'.$stato.'%')/*->count() */->get();
        }

        }
        else{
        //$orders = Order::orderByDesc('id')->paginate(100);
        if($request->input('order_id') == null /* || Session::get('order_id') === null */)
        {
            $orders =  Order::leftJoin('report_orders', 'orders.order_id', '=', 'report_orders.order_id')/* ->whereBetween('orders.order_date', [$from, $to]) */->whereRaw("DATE_ADD(orders.order_date, INTERVAL 1 HOUR) BETWEEN ? AND ?", [$from, $to])->where('orders.status', 'like', '%'.$stato.'%')
            ->when(Session::has('param'), function ($query) {
                // Add orderBy clause only when Session::has('param') is true
                return $query->orderBy(Session::get('param'), Session::get('order')); // Replace 'your_column' with the actual column you want to order by
            })
            ->select('orders.*', 'report_orders.report as report')->paginate(50);

            //$count = Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')->where('orders_operators.operator', $user->name)->whereBetween('orders_operators.date', [$from, $to])->where('orders.status', 'like', '%'.$stato.'%')/*->count() */->get();
        }else{
            Session::put('order_id', $request->input('order_id'));
            $orders =  Order::leftJoin('report_orders', 'orders.order_id', '=', 'report_orders.order_id')/* ->whereBetween('orders.order_date', [$from, $to]) */->whereRaw("DATE_ADD(orders.order_date, INTERVAL 1 HOUR) BETWEEN ? AND ?", [$from, $to])->where('orders.order_id', $request->order_id)->where('orders.status', 'like', '%'.$stato.'%')
            ->when(Session::has('param'), function ($query) {
                // Add orderBy clause only when Session::has('param') is true
                return $query->orderBy(Session::get('param'), Session::get('order')); // Replace 'your_column' with the actual column you want to order by
            })
            ->select('orders.*', 'report_orders.report as report')->paginate(50);
            
            //$count = Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')->where('orders_operators.operator', $user->name)->whereBetween('orders_operators.date', [$from, $to])->where('orders.status', 'like', '%'.$stato.'%')/*->count() */->get();
        }

        }

        if ($request->input('order_id') !== null){
            Session::put('order_id', $request->input('order_id'));
        }else{
            Session::pull('order_id');
        }

        $search = [
            'order_id' => Session::get('order_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'stato' => $request->input('stato')
        ];

        $count = Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')/* ->whereBetween('orders.order_date', [$from, $to]) */->whereRaw("DATE_ADD(orders.order_date, INTERVAL 1 HOUR) BETWEEN ? AND ?", [$from, $to])->where('orders.status', 'like', '%da_spedire%')->orWhere('orders.status', 'like', '%complete%')->get()->count();
        $count2 = Order::whereRaw("DATE_ADD(orders.order_date, INTERVAL 1 HOUR) BETWEEN ? AND ?", [$from, $to])
        ->where(function ($query) {
            $query->where('status', 'processing')
                  ->orWhere('status', 'anelli');
        })
        ->get()->count();
        return view("orders.index", ['orders' => $orders, 'search' => $search , 'count' => $count, 'user' => $user, 'count2' => $count2]);
       
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {   
        $orderDetail = Order::where('order_id', $id)->first(); 
        $products = OrderProds::where('order_id', $orderDetail->entity_id)->where('product_type', '<>', 'configurable')->get();
        $packagings = Product::where('sku', 'like', 'scatolina-%')->orderBy('name', 'desc')->get();
        //$packagingFissi = Product::where('sku', 'not like', 'scatolina-%')->where('sku', 'not like', 'cart-%')->get();
        $packagingFissi = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
        ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
        ->select('products.name')
        ->where('attributes.code', 'prod_type')
        ->where('attribute_values.integer_value', 2)
        ->where('products.sku','not like', 'scatolina-%')
        ->get();

        $orderProdsAttributes = OrderProdAttribute::where('order_id', $orderDetail->entity_id)->get();

        //dd($orderAttributes);
        //dd($packagingFissi);
        
        $prodsImages = self::getProdsImages($products);
        $reports = Report::all();
        $usr = Auth::id();
        $user = DB::table('users')->where('id', $usr)->select('role_id')->first();
        
        //Recuperare tutti i bundle e i prodotti all'interno
        $bundles = OrderProds::where('order_id', $orderDetail->entity_id)->where('product_type', 'bundle')->get();
        $bundleDetails = [];

        foreach($bundles as $bundle){
            $bundleOptions = BundleProdsOrder::where('item_id', $bundle->item_id)->where('order_id', $id)->get()->toArray();

            foreach($bundleOptions as $option){
                array_push($bundleDetails, $option);
            }
        }

        //Recupera cronologia commenti ordine
        $manager = new MagentoManager();
        $comments = $manager->getCommentsHistory($orderDetail->entity_id);

        //Recupera dettagli ordine aggiornati
        $updatedOrderDetail = $manager->getOrderDetails($orderDetail->entity_id);

//dd($updatedOrderDetail);
        $optionsImages = self::getOptionsImages($bundleDetails);
        return view('orders.show', ['orderDetail' => $orderDetail,
                                    'updatedOrderDetail' => $updatedOrderDetail,
                                    'products' => $products,
                                    'packagings' => $packagings,
                                    'packFissi' => $packagingFissi,
                                    'reports' => $reports,
                                    'role' => $user->role_id,
                                    'prodsImages' => $prodsImages,
                                    'bundleDetails' => $bundleDetails,
                                    'optionsImages' => $optionsImages,
                                    'attributes' => $orderProdsAttributes,
                                    'comments' => $comments]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

     //Aggiorna lo status dell'ordine da processing a "da spedire" aggiornando anche lo stato su magento
    //inviando una mail al cliente (tramite API) 
    public function update(Request $request, $id)
    {

        $i = 1;
        $done = true;

        $order_items = [];
        $filled = true;
        $count_items = 0;
        $defectiveProducts = false;

        //Nel caso in cui ci siano stati problemi con l'ordine, non effettuo alcuna operazione
        //e torno alla lista degli ordini
        $order = Order::where('order_id', $id)->first();
        $entity_id = $order->entity_id;
        if($request->problemi != null){
            
            /* $operator = OperatorOrders::where('order_id', $id)->first();

            $operator->update([
                'report' => $request->problemi
            ]);*/
            $reportOrder = ReportOrder::where('order_id', $id)->first();

            if ($reportOrder === null){
                ReportOrder::create([
                    'order_id' => $id,
                    'report' => $request->problemi
                ]);
            }else{
                $reportOrder->update([
                    'report' => $request->problemi,
                ]);
            }

            if ($request->problemi != 'none'){
                return redirect()->route('admin.orders.operator.search', ['order_id' => Session::get('order_id'),
                'from' => Session::get('from'),
                'to' => Session::get('to'),
                'stato' => Session::get('stato'),
                'page' => Session::get('order_page')]);
            }
            
        }
        

        if ($request->stato === 'da_spedire' && ($order->status === 'processing' || $order->status === 'anelli')){
            //Verifica prodotti difettosi
            $filled = true;
            $count_items = 0;
            
            //Verifica disponibilità di packaging variabili
            $notAvailableProduct = '';
            $notAvailablePackaging = '';

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

            //dd($fissi);

            foreach($fissi as $key => $item){
                if ($item->quantity < (int)$request['package-fixed-quantity'.($key + 1)]){
                    session()->flash('error', 'Quantità di '. $item->name .' insufficiente');
                    return back();
                }
            }

            //Check Difettossi
            $filled = true;
            $count_items = 0;

            while ($filled){
                if($request['product'.$count_items] != null){
                    $productAttribute = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                            ->select('attribute_values.id')
                            ->where('products.sku', $request['sku'.$count_items])
                            ->where('attributes.code', 'qty_ecommerce')
                            ->first();
            
                    $attributeValue = AttributeValue::where('id', $productAttribute->id)->first();

                    if ((int)$attributeValue->text_value < (int)$request['difettoso'.$count_items]){
                        session()->flash('error', 'Quantità di '.$request['product-name'.$count_items].' specificata supera la quantità totale');
                        return back();
                    }
                    //$this->checkDifettosi((int)$request['difettoso'.$count_items], (int)$attributeValue->text_value, $request['product-name'.$count_items]);
                }else{
                    $filled = false;
                }
                $count_items ++;
            }

            //Scalo quantità difettose
            $filled = true;
            $count_items = 0;

            while ($filled){
                if($request['product'.$count_items] != null){
                    $productAttribute = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                            ->select('attribute_values.id')
                            ->where('products.sku', $request['sku'.$count_items])
                            ->where('attributes.code', 'qty_ecommerce')
                            ->first();
            
                    $attributeValue = AttributeValue::where('id', $productAttribute->id)->first();

                    //Aggiorna il numero di prodotti difettosi se necessario
                    //E se il prodotto non è un bundle
                    /* if ($request['difettoso'.$count_items] !== 'null'){
                        $this->updateDefectiveProducts($request['sku'.$count_items], (int)$request['difettoso'.$count_items], (int)$attributeValue->text_value, $request['product-name'.$count_items]);
                    } */

                    /* if ($request['sku'.$count_items] === 'crm4'){
                        dd((int)$attributeValue->text_value, (int)$request['difettoso'.$count_items]);
                    } */

                    if ((int)$request['difettoso'.$count_items] > 0){
                        if ((int)$attributeValue->text_value >= (int)$request['difettoso'.$count_items]){
                            
                            $prod = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                            ->select('attribute_values.id')
                            ->where('products.sku', $request['sku'.$count_items])
                            ->where('attributes.code', 'qty_diffettosi')
                            ->first();
                        
                            $attributeVal = AttributeValue::where('id', $prod->id)->first();
            
                            $attributeVal->update([
                                'text_value' => strval((int)$attributeVal->text_value + (int)$request['difettoso'.$count_items])
                            ]);
            
                            $prod = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                            ->select('attribute_values.id')
                            ->where('products.sku', $request['sku'.$count_items])
                            ->where('attributes.code', 'qty_ecommerce')
                            ->first();
            
                            $attributeVal = AttributeValue::where('id', $prod->id)->first();
            
                            //Scalo della quantità da magento
                            $magentoManager = MagentoManager::getInstance();
                            $result = $magentoManager::removeDefectiveQty($request['sku'.$count_items], (int)$attributeVal->text_value - (int)$request['difettoso'.$count_items]);

                            $attributeVal->update([
                                'text_value' => strval((int)$attributeVal->text_value - (int)$request['difettoso'.$count_items])
                            ]);

            
                        }else{
                            session()->flash('error', 'Quantità di '.$request['product-name'.$count_items].' specificata supera la quantità totale');
                            return back();
                        }
                    }

                }else{
                    $filled = false;
                }

                $count_items ++;
            }

            //Check disponibilità prodotti
            /* $filled = true;
            $count_items = 0;

            while ($filled){
                if($request['product'.$count_items] != null){
                    $productAttribute = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                            ->select('attribute_values.id')
                            ->where('products.sku', $request['sku'.$count_items])
                            ->where('attributes.code', 'qty_ecommerce')
                            ->first();
            
                    $attributeValue = AttributeValue::where('id', $productAttribute->id)->first();

                    if((int)$attributeValue->text_value < (int)$request['quantity'.$count_items]){
                        session()->flash('error', 'Quantità di '.$request['product-name'.$count_items].' non sufficiente per la spedizione');
                        return back();
                    }
                }else{
                    $filled = false;
                }

                $count_items ++;
            } */

            /* *************************** */
            /* INIZIO EVASIONE ORDINE */
            $magentoManager = MagentoManager::getInstance();
            $result = $magentoManager::syncOrderStatus($entity_id, $order_items, 'empty');
            
            //Se l'operazione ha avuto successo effettuo tutte le modifiche sulle quantità
            //e sullo stato dell'ordine
            if($result){

                $order = Order::where('order_id', $id)->first();
                $order->update([
                    'status' => $request->stato,
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
                            'order_id' => $id,
                            'pack_id' => $request['package-name'.$i],
                            'qty' => (int)$request['package-quantity'.$i],
                        ]);

                        $packaging->update([
                            'quantity' => $packaging->quantity - (int)$request['package-quantity'.$i],
                        ]); 
                    }else{
                        $done = false;
                    }

                    $i ++;
                }
                
                //Aggiorno le quantità dei prodotti
                /* $filled = true;
                $count_items = 0;
                
                while($filled){
                    if($request['product'.$count_items] != null){
                        //Nel caso di un bundle rimuovo le quantità dei singoli prodotti contenuti
                        if ($request['difettoso'.$count_items] === 'null'){
                            $bundle = OrderProds::where('order_id', $entity_id)->where('product_type', 'bundle')->where('item_id', $request['product'.$count_items])->first();

                            $bundleOptions = BundleProdsOrder::where('item_id', $bundle->item_id)->where('order_id', $id)->get();

                            foreach ($bundleOptions as $option){
                                $prodAttribute = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                                        ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                                        ->select('attribute_values.id')
                                        ->where('products.sku', $option->sku_option)
                                        ->where('attributes.code', 'qty_ecommerce')
                                        ->first();
                        
                                $attributeVal = AttributeValue::where('id', $prodAttribute->id)->first();

                                $attributeVal->update([
                                    'text_value' => strval((int)$attributeVal->text_value - (int)$option->quantity)
                                ]);

                                $product1 = Product::where('sku', $option->sku_option)->first();
                    
                                
                                $product1->update([
                                    'quantity' => $product1->quantity - (int)$option->quantity
                                ]);

                            }
                        }
                        
            
                        $productAttribute = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                            ->select('attribute_values.id')
                            ->where('products.sku', $request['sku'.$count_items])
                            ->where('attributes.code', 'qty_ecommerce')
                            ->first();

                        $attributeValue = AttributeValue::where('id', $productAttribute->id)->first();

                        
                        //Aggiorna quantità ecommerce
                        $attributeValue->update([
                            'text_value' => strval((int)$attributeValue->text_value - (int)$request['quantity'.$count_items])
                        ]);

                        $product = Product::where('sku', $request['sku'.$count_items])->first();
                        
                        $product->update([
                            'quantity' => $product->quantity - (int)$request['quantity'.$count_items],
                        ]);

                    }else{
                        $filled = false;
                    }

                    $count_items ++;
                } */

                //Aggiungo l'rdine nella tabella degli ordini da passare in statoi completato
                GlsOrders::create([
                    'order_id' => $id,
                    'entity_id' => $entity_id
                ]);

                return redirect()->route('admin.orders.operator.search', ['order_id' => Session::get('order_id'),
                'from' => Session::get('from'),
                'to' => Session::get('to'),
                'stato' => Session::get('stato'),
                'page' => Session::get('order_page')]);
            }
            
            return redirect()->route('admin.orders.operator.search', ['order_id' => Session::get('order_id'),
            'from' => Session::get('from'),
            'to' => Session::get('to'),
            'stato' => Session::get('stato'),
            'page' => Session::get('order_page')]);

        }else{
            return redirect()->route('admin.orders.operator.search', ['order_id' => Session::get('order_id'),
            'from' => Session::get('from'),
            'to' => Session::get('to'),
            'stato' => Session::get('stato'),
            'page' => Session::get('order_page')]);
        }

        return redirect()->route('admin.orders.operator.search', ['order_id' => Session::get('order_id'),
        'from' => Session::get('from'),
        'to' => Session::get('to'),
        'stato' => Session::get('stato'),
        'page' => Session::get('order_page')]);

    }

    //Aggiorna il numero totale di prodotti difettosi 
    //Controllo su $product != null per i test
    private function updateDefectiveProducts($sku, $difettosi, $qty_ecommerce, $product_name) {
        if ($difettosi > 0){
            if ($qty_ecommerce >= $difettosi){
                $product = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                ->select('attribute_values.id')
                ->where('products.sku', $sku)
                ->where('attributes.code', 'qty_diffettosi')
                ->first();
            
                $attributeValue = AttributeValue::where('id', $product->id)->first();

                $attributeValue->update([
                    'text_value' => strval((int)$attributeValue->text_value + $difettosi)
                ]);

                $product = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                ->select('attribute_values.id')
                ->where('products.sku', $sku)
                ->where('attributes.code', 'qty_ecommerce')
                ->first();

                $attributeValue = AttributeValue::where('id', $product->id)->first();

                $attributeValue->update([
                    'text_value' => strval((int)$attributeValue->text_value - $difettosi)
                ]);

            }else{
                session()->flash('error', 'Quantità di '.$product_name.' specificata supera la quantità totale');
                return back();
            }
        }
    }

    private function checkDifettosi($difettosi, $qty_ecommerce, $product_name){
        if ($qty_ecommerce < $difettosi){
            dd($product_name, $difettosi, $qty_ecommerce);
            session()->flash('error', 'Quantità di '.$product_name.' specificata supera la quantità totale');
            return back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
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

    private static function getOptionsImages($bundleDetails){
        $magentoManager = MagentoManager::getInstance();
        $ris = array();
        foreach ($bundleDetails as $detail) {
            $imageUrl = $magentoManager::GetProdImage($detail['sku_option']);
            if ($imageUrl !== null) {
                $ris[$detail['sku_option']] = $imageUrl;
            }

        }

       return $ris;
    }

    public static function getCompletedOrdersCount(){
        $count = Order::where('status', 'completed')->get()->count();

        return response()->json($count);
    }

    public static function orderData($param, $order){
        //$orders = Order::orderBy($param, $order)->get();

        if (Session::has('order')){
            if ($order == 'asc'){
                Session::put('order', 'desc');
            }else{
                Session::put('order', 'asc');
            }
        }else{
            Session::put('order', $order);
        }

        Session::put('param', $param);

        /* $orders = null;
        $id = Auth::id();
        $user = DB::table('users')->where('id', $id)->where('role_id', 3)->first(); */

/*         if($user != null)
        {
            $orders =  Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')->leftJoin('report_orders', 'orders.order_id', '=', 'report_orders.order_id')->where('orders_operators.operator', $user->name)->where('orders.status', 'processing')->orWhere('orders.status', 'anelli')
            ->when(Session::has('param'), function ($query) {
                // Add orderBy clause only when Session::has('param') is true
                return $query->orderBy(Session::get('param'), Session::get('order')); // Replace 'your_column' with the actual column you want to order by
            })
            ->select('orders.*', 'report_orders.report as report')->orderBy($param, $order)->paginate(50);

        }
        else{
            $orders = Order::leftJoin('report_orders', 'orders.order_id', '=', 'report_orders.order_id')->when(Session::has('param'), function ($query) {
                // Add orderBy clause only when Session::has('param') is true
                return $query->orderBy(Session::get('param'), Session::get('order')); // Replace 'your_column' with the actual column you want to order by
            })->select('orders.*', 'report')->paginate(50);
        }
        

        $count = Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')->where('orders.status', 'like', '%da_spedire%')->orWhere('orders.status', '=', 'complete')->get()->count();
        $count2 = Order::where('orders.status', '=', 'processing')->orWhere('orders.status', '=', 'anelli')->get()->count();

        return view("orders.index", ['orders' => $orders, 'user' => $user , 'count' => $count, 'count2' => $count2, 'param' => $param, 'order' => $order]);
        dd($param, $order); */
        return redirect()->route('admin.orders.operator.search', ['order_id' => Session::get('order_id'),
        'from' => Session::get('from'),
        'to' => Session::get('to'),
        'stato' => Session::get('stato'),
        'page' => Session::get('order_page')]);
    }

    public static function orderDataAssigned($param, $order){
        if (Session::has('order')){
            if ($order == 'asc'){
                Session::put('order', 'desc');
            }else{
                Session::put('order', 'asc');
            }
        }else{
            Session::put('order', $order);
        }

        Session::put('param', $param);

        //********** */
        $orders = null;
        $id = Auth::id();
        $user = DB::table('users')->where('id', $id)->where('role_id', 3)->first();

        if($user != null)
        {
            return redirect("admin/orders");
        }

     //ottengo la lista degli ordini che sono da assegnare 
     //TO DO: gli ordini da assegnare devono avere lo stato "in lavorazione" e non deve essere già assegnato ad un altro operatore
     //inoltre non ci devono essere gli ordini che hanno già uno user associato

        $filterOrders = Order::leftJoin('orders_operators','orders.order_id','=','orders_operators.order_id')->whereNull('orders_operators.order_id')->where('orders.status', 'processing')->orWhere('orders.status', 'anelli')
        ->select('orders.*', 'orders_operators.id')
        ->when(Session::has('param'), function ($query) {
            // Add orderBy clause only when Session::has('param') is true
            return $query->orderBy(Session::get('param'), Session::get('order')); // Replace 'your_column' with the actual column you want to order by
        })
        ->paginate(50);
        

        /* $count = Order::join('orders_operators', 'orders.order_id', '=', 'orders_operators.order_id')->where('orders.status', 'like', '%da_spedire%')->orWhere('orders.status', '=', 'complete')->get()->count();
        $count2 = Order::where('orders.status', '=', 'processing')->orWhere('orders.status', '=', 'anelli')->get()->count(); */

        $users = DB::table('users')->where('role_id', 3)->get();
        return view("orders.admin.assign",['users' => $users, 'orders' => $filterOrders]);
        //return view("orders.index", ['orders' => $orders, 'user' => $user , 'count' => $count, 'count2' => $count2, 'param' => $param, 'order' => $order]);
    }
}
