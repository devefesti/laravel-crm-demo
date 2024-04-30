<?php

namespace App\Http\Controllers;
use App\Models\Order;
use App\Models\MagentoManager;
use App\Models\OrderProds;
use App\Models\OperatorOrders;
use App\Models\OrderManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    //
    public static function showStandings()
    {
    //ottengo la lista degli operatori ordinata in base a quanti ordini hanno chiuso
    //cioè che hanno cambiato lo stato da in "lavorazione" a "da spedire"
    $ris = [];
        $classficiation = DB::table('orders_operators')
    ->join('orders', 'orders.order_id', '=', 'orders_operators.order_id')
    ->where('orders_operators.operator', 'not like', '%Anna Bianchi%')
    ->where('orders.status', '=', 'da_spedire')
    ->orWhere('orders.status', '=', 'complete')
    ->groupBy('orders_operators.operator')
    ->orderByDesc(DB::raw('COUNT(orders_operators.order_id)'))
    ->select('orders_operators.operator', DB::raw('COUNT(orders_operators.order_id) as order_count'))
    ->where('orders_operators.operator', 'not like', '%Anna Bianchi%')
    ->get();

    foreach($classficiation as $item)
    {
        //ottengo il totale degli ordini assegnati all'utente
       //$tot = Order::join('orders_operators', 'orders_operators.order_id', '=', 'orders.order_id')->where('orders_operators.operator', $item->operator)->groupBy('orders_operators.operator')->select(DB::raw('COUNT(orders_operators.order_id) as val'))->get();
       $tot = Order::join('orders_operators', 'orders_operators.order_id', '=', 'orders.order_id')->where('orders_operators.operator', $item->operator)->select('orders.order_id')->get();
        $stat = [
            'operator' => $item->operator,
            'closed_orders' => $item->order_count,
            'total' => count($tot)
        ];
        array_push($ris, $stat);

    }

    return view('stats.standings', ['classification' => $ris]);    
    }

    //from & to devono diventare dei tiemestamp
    public function searchOperator(Request $request)
    {
       $ris = null;
       $fromString = '1900-01-01 00:00:00';
       $toString = '2100-01-01 00:00:00';

       $from = $fromString;
       $to = $toString;

       if($request->from != null)
       { 
            $trimmed_tmstp = explode('T', $request->from);
            $from = $trimmed_tmstp[0] . ' '. $trimmed_tmstp[1] . ':00';
       }
   
       if($request->to != null)
       {
            $trimmed_tmstp = explode('T', $request->to);
            $to = $trimmed_tmstp[0] . ' '. $trimmed_tmstp[1] . ':00';
       }

       $ris = [];
       $classficiation = DB::table('orders_operators')
   ->join('orders', 'orders.order_id', '=', 'orders_operators.order_id')
   ->where('orders_operators.operator', 'not like', '%Anna Bianchi%')
   ->where('orders.status', '=', 'da_spedire')
   ->orWhere('orders.status', '=', 'complete')
   ->whereBetween('orders.updated_at', [$from, $to])
   ->groupBy('orders_operators.operator')
   ->orderByDesc(DB::raw('COUNT(orders_operators.order_id)'))
   ->select('orders_operators.operator', DB::raw('COUNT(orders_operators.order_id) as order_count'))
   ->where('orders_operators.operator', 'not like', '%Anna Bianchi%')
   ->get();


   foreach($classficiation as $item)
   {
       //ottengo il totale degli ordini assegnati all'utente
      //$tot = Order::join('orders_operators', 'orders_operators.order_id', '=', 'orders.order_id')->where('orders_operators.operator', $item->operator)->groupBy('orders_operators.operator')->select(DB::raw('COUNT(orders_operators.order_id) as val'))->get();
      $tot = Order::join('orders_operators', 'orders_operators.order_id', '=', 'orders.order_id')->where('orders_operators.operator', $item->operator)->whereBetween('orders_operators.updated_at', [$from, $to])->select('orders.order_id')->get();
       $stat = [
           'operator' => $item->operator,
           'closed_orders' => $item->order_count,
           'total' => count($tot)
       ];
       array_push($ris, $stat);
    }
       $search = [
        'from' => $request->from,
        'to' => $request->to
    ];
       return view('stats.standings', ['classification' => $ris, 'search' => $search]);   
    }
}
