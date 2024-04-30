@extends('admin::layouts.master')

@section('page_title')
    {{ __('Ordini influencers') }}
@stop

@section('content-wrapper')
    <div class="content full-page dashboard">
        <h1>
            {{ Breadcrumbs::render('influencers') }}
        
            {{ __('Ordini influencer') }}
        </h1>

        <div class="row">
            <div class="row" class="search-section">
                <form action="{{ route('admin.influencers.orders.search')}}" method="get">
                    @csrf
                <h1>Ricerca ordini influencer</h1>

                <div class="col-md-3" style="display:flex;  flex-wrap: nowrap; ">
                    <div class="form-group" style="margin-right: 10px;">
                        <input class="control" type="text" name="nome" placeholder="Nome" @if(isset($search) && $search['nome'] !== null) value="{{ $search['nome'] }}" @endif>
                    </div>
                    <div class="form-group" style="margin-right: 10px;">
                        <input class="control" type="text" name="cognome" placeholder="Cognome" @if(isset($search) && $search['cognome'] !== null) value="{{ $search['cognome'] }}" @endif>
                    </div>
                    <div class="form-group" style="margin-right: 10px;">
                        <input class="control" type="email" name="email" placeholder="Email" @if(isset($search) && $search['email'] !== null) value="{{ $search['email'] }}" @endif>
                    </div>
                    <div class="form-group"  style="margin-right: 10px;">
                        <input class="control" type="date" name="data" id="from" format="dd-mm-yyyy" @if(isset($search['data']) && $search['data'] != null) value="{{ $search['data'] }}" @endif placeholder="Data ordine">
                    </div>
                    <div class="form-group select"  style="margin-right: 10px;">
                        <select class="control" name="stato">
                            <option value="" @if(isset($search['stato']) && $search['stato'] == null) selected @endif>Seleziona stato</option>
                            <option value="processing" @if(isset($search['stato']) && $search['stato'] == 'processing') selected @endif>In lavorazione</option>
                            <option value="anelli" @if(isset($search['stato']) && $search['stato'] == 'anelli') selected @endif>Anelli</option>
                            <option value="da_spedire" @if(isset($search['stato']) && $search['stato'] == 'da_spedire') selected @endif>Da spedire</option>
                            <option value="complete" @if(isset($search['stato']) && $search['stato'] == 'complete') selected @endif>Completato</option>
                            <option value="canceled" @if(isset($search['stato']) && $search['stato'] == 'canceled') selected @endif>Cancellato</option>
                            <option value="closed" @if(isset($search['stato']) && $search['stato'] == 'closed') selected @endif>Chiuso</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-right: 10px;">
                        <input class="control" type="number" name="pages" placeholder="Per page" @if(isset($search) && $search['pages'] !== null) value="{{ $search['pages'] }}" @endif>
                    </div>
                    <div style="margin-right: 10px;"><button type="submit" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Cerca</button></div>

                    @if(isset($search) && $search != null)
                        <div style="margin-right: 10px;"><a href="{{route('admin.influencers.orders')}}" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Clear</a></div>
                    @endif
                    {{-- <div class="form-group select" style="margin-right: 10px;"><input class="control" type="number" name="order_id" id="order_id" @if(isset($search['order_id']) && $search['order_id'] != null) value="{{ $search['order_id'] }}" @endif placeholder="Numero ordine"></div>
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
                    @endif --}}
                </div>
                </form>
            
            </div>
            <a href="{{ route('admin.influencers.orders.create') }}" class="btn btn-md btn-primary">{{ __('Crea ordine') }}</a>
        {{-- <h1>{{ __('admin::app.order.title') }}</h1> --}}

        <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
            <thead style="text-align: left">
                <th>Nome</th>
                <th>Cognome</th>
                <th>Email</th>
                <th>Stato</th>
                <th>Data</th>
                <th>Totale</th>
                <th>Azioni</th>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>{{$order->nome}}</td>
                        <td>{{$order->cognome}}</td>
                        <td>{{$order->email}}</td>
                        <td>
                            @if ($order->stato == 'processing')
                                In lavorazione
                            @elseif ($order->stato == 'da_spedire')
                                Da spedire
                            @elseif ($order->stato == 'complete')
                                Completato
                            @elseif ($order->stato == 'anelli')
                                Anelli
                            @elseif ($order->stato == 'canceled')
                                Cancellato
                            @elseif ($order->stato == 'closed')
                                Chiuso
                            @endif
                        </td>
                        <td>{{$order->data}}</td>
                        <td>{{$order->totale}} €</td>
                        <td class="action" style="display:flex">
                            <a href="{{ route('admin.influencers.orders.edit', ['id' => $order->id]) }}" title="Edit" data-method="GET">
                                <i data-route="{{ route('admin.influencers.orders.edit', ['id' => $order->id]) }}" class="icon pencil-icon"></i>
                            </a>

                            <form action="{{ route('admin.influencers.orders.destroy', ['id' => $order->id]) }}" method="POST">
                                @csrf
                                @method('delete')
                                <button type="submit" title="Delete" data-action="admin/products/{{$order->id}}" style="border: unset; background:none" data-method="DELETE">
                                    <i style="vertical-align: unset; cursor:pointer" data-route="admin/products/{{$order->id}}" class="icon trash-icon"></i>
                                </button>
                            </form>
                            {{-- <form action="{{ route('admin.influencers.destroy', ['id' => $order->id]) }}" method="POST">
                                @csrf
                                @method('delete')
                                <button type="submit" title="Delete" data-action="admin/products/{{$order->id}}" style="border: unset; background:none" data-method="DELETE">
                                    <i style="vertical-align: unset; cursor:pointer" data-route="admin/products/{{$order->id}}" class="icon trash-icon"></i>
                                </button>
                            </form> --}}
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