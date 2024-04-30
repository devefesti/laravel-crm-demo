@extends('admin::layouts.master')

@section('page_title')
    {{ __('admin::app.order.title') }}
@stop

@section('content-wrapper')
    <div class="content full-page dashboard">
        <h1>
            {{ Breadcrumbs::render('orders') }}
        
            {{ __('admin::app.order.title') }}
        </h1>
        <div class="row">
            <div class="row" class="search-section">
                <form action="{{ route('admin.orders.operator.search')}}" method="get">
                    @csrf
                @if(isset($user))
                    <h1>Ricerca Ordini assegnati:</h1>
                @else
                    <h1>Ricerca Ordini:</h1>
                @endif

                <div class="col-md-3" style="display:flex;  flex-wrap: nowrap; ">
                    <div class="form-group select" style="margin-right: 10px;"><input class="control" type="number" name="order_id" id="order_id" @if(isset($search['order_id']) && $search['order_id'] != null) value="{{ $search['order_id'] }}" @endif placeholder="Numero ordine"></div>
                    <div class="form-group"  style="margin-right: 10px;"><input class="control" type="date" name="from" id="from" format="dd-mm-yyyy" @if(isset($search['from']) && $search['from'] != null) value="{{ $search['from'] }}" @endif placeholder="Dal"></div>
                    <div class="form-group"  style="margin-right: 10px;"><input class="control" type="date" name="to" id="to" format="dd-mm-yyyy" @if(isset($search['to']) && $search['from'] != null) value="{{ $search['to'] }}" @endif placeholder="Al"></div>
                    <div class="form-group select"  style="margin-right: 10px;">
                        <select class="control" name="stato">
                            <option value="" @if(isset($search['stato']) && $search['stato'] == null) selected @endif>Seleziona stato</option>
                            <option value="processing" @if(isset($search['stato']) && $search['stato'] == 'processing') selected @endif>In lavorazione</option>
                            <option value="anelli" @if(isset($search['stato']) && $search['stato'] == 'anelli') selected @endif>Anelli</option>
                            @if ($user == null)
                                <option value="da_spedire" @if(isset($search['stato']) && $search['stato'] == 'da_spedire') selected @endif>Da spedire</option>
                                <option value="complete" @if(isset($search['stato']) && $search['stato'] == 'complete') selected @endif>Completato</option>
                            @endif
                        </select>
                    </div>
                    <div style="margin-right: 10px;"><button type="submit" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Cerca</button></div>
                    @if(isset($search) && $search != null)
                    <div style="margin-right: 10px;"><a href="/admin/orders" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Clear</a></div>
                    @endif
                </div>
                @if ($user == null)
                    <p>Ordini completati: {{ $count }}</p>
                    <p>Ordini in lavorazione: {{ $count2 }}</p>
                @endif
                </form>
            
            </div>
        </div>

        <div class="row">
        {{-- <h1>{{ __('admin::app.order.title') }}</h1> --}}

        <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
            <thead style="text-align: left">
                @php
                    /* if (Session::has('order')){
                        if (Session::get('order') == 'asc'){
                            Session::put('order', 'desc');
                        }else {
                            Session::put('order', 'asc');
                        }
                    }else{
                        Session::put('order', 'asc');
                    } */
                @endphp
                <th>
                    @if (Session::has('param') && Session::get('param') == 'order_id')
                        <a href="{{route('admin.orders.sort', ['param' => 'order_id', 'order' => Session::get('order') ])}}">Codice ordine</a>
                        @if (Session::has('param') && Session::get('param') === 'order_id')
                            @if (Session::get('order') === 'asc')
                                &uarr; 
                            @else
                                &darr;
                            @endif
                        @endif
                    @else
                        <a href="{{route('admin.orders.sort', ['param' => 'order_id', 'order' => 'asc' ])}}">Codice ordine</a>
                    @endif  
                </th>
                <th>Totale ordine</th>
                <th>Stato</th>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Email cliente</th>
                <th>
                    @if (Session::has('param') && Session::get('param') == 'order_date')
                        <a href="{{route('admin.orders.sort', ['param' => 'order_date', 'order' => Session::get('order') ])}}">Data</a>
                        @if (Session::has('param') && Session::get('param') === 'order_date')
                            @if (Session::get('order') === 'asc')
                                &uarr; 
                            @else
                                &darr;
                            @endif
                        @endif
                    @else
                        <a href="{{route('admin.orders.sort', ['param' => 'order_date', 'order' => 'asc' ])}}">Data</a>
                    @endif 
                </th>
                <th>
                    @if (Session::has('param') && Session::get('param') == 'report')
                        <a href="{{route('admin.orders.sort', ['param' => 'report', 'order' => Session::get('order') ])}}">Report</a>
                        @if (Session::has('param') && Session::get('param') === 'report')
                            @if (Session::get('order') === 'asc')
                                &uarr; 
                            @else
                                &darr;
                            @endif
                        @endif
                    @else
                        <a href="{{route('admin.orders.sort', ['param' => 'report', 'order' => 'asc' ])}}">Report</a>
                    @endif 
                </th>
                <th>Dettagli ordine</th>
            </thead>
            <tbody>
                @foreach($orders as $item)
                    <tr>
                        <td>{{ $item['order_id'] }}</td>
                        <td>{{ $item['totale'] }} €</td>
                        <td>{{-- $item['status'] --}}
                            @if ($item['status'] == 'processing')
                                In lavorazione
                            @elseif ($item['status'] == 'da_spedire')
                                Da spedire
                            @elseif ($item['status'] == 'complete')
                                Completato
                            @elseif ($item['status'] == 'anelli')
                                Anelli
                            @elseif ($item['status'] == 'canceled')
                                Cancellato
                            @elseif ($item['status'] == 'closed')
                                Chiuso
                            @endif
                        </td>
                        <td>{{ $item['firstname'] }}</td>
                        <td>{{ $item['lastname'] }}</td>
                        <td>{{ $item['email'] }}</td>
                        <td>
                            <?php
                                $dateTime = new DateTime($item['order_date']);
                                $dateTime->add(new DateInterval('PT2H'));
                                $formattedDate = $dateTime->format('d-m-Y H:i:s');
                                echo $formattedDate;  
                            ?>
                        </td>
                        <td @if( $item->report != null && $item->report != 'none') style="background-color: yellow;" @endif>
                            @switch($item->report)
                                @case('out_of_stock')
                                    Prodotti esaurtiti
                                    @break
                                @case('delayed_shipping')
                                    Consegna posticipata
                                    @break
                                @case('pack_out_of_stock')
                                    Packaging esauriti
                                    @break
                                @default
                            @endswitch
                        </td>
                        <td>
                            <a href="/admin/orders/{{ $item['order_id'] }}/details" style="color:black;">
                                <i class="icon sprite products-icon"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="max-height: 50px; text-align: center;" class="tab-pagination">
            {{ $orders->withQueryString()->links() }}
        </div>
    </div>
    </div>
@stop
