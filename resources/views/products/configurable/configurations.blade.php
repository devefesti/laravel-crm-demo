@extends('admin::layouts.master')

@section('page_title')
    {{ __('admin::app.products.title_configurations') }}
@stop

@section('content-wrapper')
    <div class="content full-page dashboard">
        {{-- <h1>{{ __('admin::app.order.title') }}</h1> --}}
        <h1>
            {{ Breadcrumbs::render('configurations') }}

            {{ __('admin::app.products.title_configurations') }}
        </h1>

        <div style="display:flex">
            @if ($countAtrtributeConfigurations != 0)
                <div>
                    <a href="show-products/" class="btn btn-sm btn-primary">Aggiungi prodotti</a>
                </div>
            @else
                <div>
                    <a href="attributes/" class="btn btn-sm btn-primary">Aggiungi attributi</a>
                </div>
            @endif
    
        </div>

        @if ($count != 0)
            <div class="row">
                <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
                    <thead style="text-align: left">
                        <th>Codice prodotto</th>
                        <th>Nome</th>
                        <th>Prezzo</th>
                        <th>Quantità</th>
                        <th>Elimina</th>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->price}}</td>
                                <td>{{ $product->quantity}}</td>
                                <td>
                                    <a href="/admin/products/configurations/{{$id}}/{{ $product->sku }}" class="btn btn-sm btn-primary">Rimuovi</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <span>Nessuna configurazione prodotto presente</span>
        @endif
        
@stop