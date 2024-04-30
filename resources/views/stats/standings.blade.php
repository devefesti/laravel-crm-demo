@extends('admin::layouts.master')

@section('page_title')
    {{ __('admin::app.order.title') }}
@stop

@section('content-wrapper')
    <div class="content full-page dashboard">
        <div class="row" class="search-section">
            <form action="{{ route('stats.standings.search') }}" method="post">
                @csrf
            <h1>Filtro per data</h1>
            <div class="col-md-3" style="display:flex;  flex-wrap: nowrap; ">
                <div class="form-group"  style="margin-right: 10px;"><input class="control" type="datetime-local" name="from" id="from" format="dd-mm-yyyy" @if(isset($search['from']) && $search['from'] != null) value="{{ $search['from'] }}" @endif placeholder="Dal"></div>
                <div class="form-group"  style="margin-right: 10px;"><input class="control" type="datetime-local" name="to" id="to" format="dd-mm-yyyy" @if(isset($search['to']) && $search['from'] != null) value="{{ $search['to'] }}" @endif placeholder="Al"></div>
                <div style="margin-right: 2px;"><button type="submit" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Cerca</button></div>
                @if(isset($search) && $search != null)
                <div style="margin-right: 10px;"><a href="/admin/stats/standings" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Clear</a></div>
                @endif
            </div>
            </form>
        
        </div>
        <div class="row">
            <h1>Classifica</h1>
            <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
              <thead style="text-align: left">
                  <th class="medal-col"></th>
                  <th>Posizione</th>
                  <th>Operatore</th>
                  <th>Ordini completati</th>
                  <th>Ordini assegnati</th>
                  <th>% chiusura ordini</th>
                  {{-- <th>Dettagli ordine</th> --}}
              </thead>
              <tbody>
                 <?php $i = 0;?>
                  @foreach($classification as $item)
                    <?php 
                    $i +=1 ;
                    $medal = "";
                    if($i == 1)
                    {
                        $medal = "/images/gold-medal.png";
                    }
                    if($i == 2)
                    {
                        $medal = "/images/silver-medal.png";
                    }
                    if($i == 3)
                    {
                        $medal = "/images/bronze-medal.png";
                    }
                    ?>
                     <tr>
                        <td class="medal-col">@if($i == 1 || $i == 2 || $i == 3) <img  width=40 src="{{ $medal }}" alt="">@endif</td>
                        <td>{{ $i; }}</td>
                        <td>{{ $item['operator'] }}</td>
                        <td>{{ $item['closed_orders'] }}</td>
                        <td>{{ $item['total'] }}</td>
                        <td>{{ number_format(($item['closed_orders'] / $item['total']) * 100, 2) }}%</td>
                     </tr>
                  @endforeach
              </tbody>
          </table>
        </div>
    </div>
@stop