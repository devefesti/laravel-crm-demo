@extends('admin::layouts.master')

@section('content-wrapper')

<div class="content full-page dashboard">
    <h3>
        {{ Breadcrumbs::render('orders') }}

        {{ __('admin::app.order.title') }}
    </h3>

    <div class="row" class="search-section">
        <form action="{{ route('admin.orders.management.assigned.search') }}" method="get">
            @csrf
        <h1>Ricerca Ordini assegnati:</h1>
        <div class="col-md-3" style="display:flex;  flex-wrap: nowrap; ">
            <div class="form-group select" style="margin-right: 10px;">
                <select name="operator" id="operator" class="control">
                    <option name="user-0" id="user-0" value="" selected style="color:silver;">Seleziona operatore</option>
                    @foreach ($users as $user)
                    <option name="user-{{$user->id }}" id="user-{{$user->id}}" value="{{$user->name }}" @if(isset($search['operator']) && $search['operator'] == $user->name) selected @endif>{{ $user->name }}</option>
                     @endforeach
                    </select>
            </div>
            <div class="form-group select" style="margin-right: 10px;"><input class="control" type="number" name="order_id" id="order_id" @if(isset($search['order_id']) && $search['order_id'] != null) value="{{ $search['order_id'] }}" @endif placeholder="Numero ordine"></div>
            <div class="form-group"  style="margin-right: 10px;"><input class="control" type="date" name="from" id="from" format="dd-mm-yyyy" @if(isset($search['from']) && $search['from'] != null) value="{{ $search['from'] }}" @endif placeholder="Dal"></div>
            <div class="form-group"  style="margin-right: 10px;"><input class="control" type="date" name="to" id="to" format="dd-mm-yyyy" @if(isset($search['to']) && $search['from'] != null) value="{{ $search['to'] }}" @endif placeholder="Al"></div>
            <div class="form-group select"  style="margin-right: 10px;">
                <select class="control" name="stato">
                    <option value="" @if(isset($search['stato']) && $search['stato'] == null) selected @endif>Seleziona stato</option>
                    <option value="processing" @if(isset($search['stato']) && $search['stato'] == 'processing') selected @endif>In lavorazione</option>
                    <option value="anelli" @if(isset($search['stato']) && $search['stato'] == 'anelli') selected @endif>Anelli</option>
                    <option value="da_spedire" @if(isset($search['stato']) && $search['stato'] == 'da_spedire') selected @endif>Da spedire</option>
                    <option value="complete" @if(isset($search['stato']) && $search['stato'] == 'complete') selected @endif>Completato</option>
                </select>
            </div>
            <div style="margin-right: 10px;"><button type="submit" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Cerca</button></div>
            @if(isset($search) && $search != null)
            <div style="margin-right: 10px;"><a href="/admin/orders/management/" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Clear</a></div>
            @endif
        </div>
        </form>


        <div class="count-assigned" style="margin-bottom:25px">
            @if(isset($search['operator']) && $search['operator'] !== null)
                <h3>Ordini assegnati all'operatore {{$search['operator']}} : {{$count_assigned}}</h3>
            @endif 
        </div>
        {{-- <div div class="row" class="search-section">
            <div class="col-md-3" style="display:flex;  flex-wrap: nowrap; ">
                <div class="form-group"  style="margin-right: 10px;"><input class="control" type="date" name="from" id="from" format="dd-mm-yyyy" @if(isset($search['from']) && $search['from'] != null) value="{{ $search['from'] }}" @endif placeholder="Dal"></div>
                <div class="form-group"  style="margin-right: 10px;"><input class="control" type="date" name="to" id="to" format="dd-mm-yyyy" @if(isset($search['to']) && $search['from'] != null) value="{{ $search['to'] }}" @endif placeholder="Al"></div>
            </div>
        </div> --}}
    
    </div>


<div class="row">
    <h1>Ordini Assegnati:</h1>
    <a href="/admin/orders/management/assign" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Assegna Ordini</a>
    <a href="/admin/stats/standings" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Classifica</a>
</div>
<div class="row">
    <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
      <thead style="text-align: left">
          <th>Codice ordine</th>
          <th>Operatore</th>
          <th>Totale ordine</th>
          <th>Stato</th>
          <th>Nome</th>
          <th>Cognome</th>
          <th>Data</th>
          <th>Report</th>
          <th>Azioni</th>
          {{-- <th>Dettagli ordine</th> --}}
      </thead>
      <tbody>
          @foreach($orders as $item)
             <tr>
                <td>{{ $item['order_id'] }}</td>
                <td>{{ $item['operator'] }}</td>
                <td>{{ $item['totale'] }} €</td>
                <td>
                    @if( $item['status'] == 'da_spedire' )
                        Da spedire
                    @elseif ( $item['status'] == 'processing' )
                        In lavorazione
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
                <td>{{ $item['date'] }}</td>
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
                    @if ( $item->status == 'processing' || $item->status == 'anelli')
                        <a href="/admin/orders/management/assigned/show/{{ $item['order_id']}}"><i class="icon pencil-icon"></i></a>
                        <a href="/admin/orders/management/assigned/delete/{{ $item['order_id']}}"><i class="icon trash-icon"></i> </a>
                    @endif
                </td>
             </tr>
          @endforeach
      </tbody>
  </table>
  <div style="max-height: 50px; text-align: center;" class="tab-pagination">
      {{ $orders->withQueryString()->links() }}
  </div>
</div>

@stop