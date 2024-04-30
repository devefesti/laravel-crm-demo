@extends('admin::layouts.master')

@section('page_title')
    {{ __('Ordine influencer') }}
@stop

@section('content-wrapper')
    <div class="content full-page dashboard">
        <h1>
            {{ Breadcrumbs::render('influencers') }}
        
            {{ __('Ordine influencer') }}
        </h1>

        <div class="row">
            <div class="tab-order-detail">
                <div class="info-tab">
                    <h1>Inidirizzo Spedizione</h1>   
                    <p>Ordine influencer <span style="font-weight: 600;
                       font-size: larger;">{{ $order->id }}</span></p>
                    <p>Totale:  {{ $order->totale }}€</p>
                    <p>Nome:  {{ $influencer->nome }}</p>
                    <p>Cognome: {{ $influencer->cognome }} </p>
                    <p>Email:  {{ $influencer->email }}</p>
                    <p>Indirizzo: {{ $influencer->indirizzo }}</p>

                    <div style="margin-top: 25px">
                        <h1>Note dell'ordine</h1>
                        <div>
                            <p style="font-size: 18px">{{ $order->nota }}</p>
                        </div>
                    </div>
                    
                </div>

                <div class="info-tab">
                    <h1>Prodotti ordinati</h1>   
                    <table style="width: 100%; box-shadow: 2px; padding: 10px;">
                        <thead style="text-align: left">
                            <th></th>
                            <th>Nome prodotto</th>
                            <th>Quantità</th>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td>
                                        <img width="100" src="{{ $images[$product->sku]}}" alt="{{ $product->sku }}">                     
                                    </td>
                                    <td>{{ $product->product_name }}</td>
                                    <td>{{ $product->quantity }}</td>
                                </tr>
                            @endforeach
                            
                        </tbody>
                    </table>
                </div>
                <div class="info-tab">
                    <h1>Evasione ordine</h1>   
                    <form action="{{ route('admin.influencers.orders.update', ['id' => $order->id]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row" id="order-status">  
                            <div class="form-group">
                                <label>Stato Ordine</label>
                                <select name="stato" id="stato" {{-- onchange="showTrackNumberField()" --}} class="control">
                                    <option value="processing" @if ($order->stato === 'processing') selected @endif>In lavorazione</option>
                                    <option value="anelli" @if ($order->stato === 'anelli') selected @endif>Anelli</option>
                                    <option value="da_spedire" @if ($order->stato === 'da_spedire') selected @endif>Da spedire</option>
                                    <option value="closed" @if ($order->stato === 'closed') selected @endif>Chiuso</option>
                                    <option value="canceled" @if ($order->stato === 'canceled') selected @endif>Cancellato</option>
                                </select>
                            </div>
                            
                            {{-- Buttons --}}
                            <div class="btn-evas">
                                <input type="submit" class="btn btn-primary btn-sm" value="Salva">
                                <a href="{{route('admin.influencers.orders.search',
                                ['nome' => Session::get('nome_inf_ord'),
                                'cognome' => Session::get('cognome_inf_ord'),
                                'email' => Session::get('email_inf_ord'),
                                'data' => Session::get('data_inf_ord'),
                                'stato' => Session::get('stato_inf_ord'),
                                'pages' => Session::get('pages_inf_ord'),
                                'page' => Session::get('page_inf_ord')])}}" class="btn btn-secondary btn-sm">Torna alla lista ordini</a>
                            </div>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
@stop