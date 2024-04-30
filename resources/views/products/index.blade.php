@extends('admin::layouts.master')

@section('page_title')
    {{ __('admin::app.products.title_defective') }}
@stop

@section('content-wrapper')
    <div class="content full-page dashboard">
        {{-- <h1>{{ __('admin::app.order.title') }}</h1> --}}
        <h1>
            {{ Breadcrumbs::render('defective-products') }}

            {{ __('admin::app.products.title_defective') }}
        </h1>

    
        <div class="row" class="search-section">
            <form action="{{ route('admin.products.defective.search') }}">
            <h1>Ricerca prodotti difettosi:</h1>
            <div class="col-md-3" style="display:flex;  flex-wrap: nowrap; ">
                <div class="form-group"  style="margin-right: 10px;"><input class="control" type="text" name="name" id="from" placeholder="Nome prodotto" @if (isset($name) && $name != null) value="{{ $name }}" @endif></div>
                <div class="form-group"  style="margin-right: 10px;"><input class="control" type="text" name="sku" id="from" placeholder="SKU" @if (isset($sku) && $sku != null) value="{{ $sku }}" @endif></div>
                <div style="margin-right: 10px;"><button type="submit" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Cerca</button></div>
                @if ((isset($name) && $name != null) || isset($sku) && $sku != null)
                    <div style="margin-right: 10px;"><a href="{{ route('admin.products.defective') }}" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Clear</a></div>
                @endif
            </div>
            </form>
        
        </div>

    <div class="row">
        <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
            <thead style="text-align: left">
                <th>Codice prodotto</th>
                <th>Nome</th>
                <th>Prezzo</th>
                <th>Difettosi</th>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td>{{ $product->sku }}</td>
                        <td>{{ $product->name }}</td>
                        <td>{{ rtrim(rtrim($product->price, '0'), '.') }} €</td>
                        <td>{{ $product->value}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@stop
