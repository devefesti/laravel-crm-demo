@extends('admin::layouts.master')

@section('page_title')
    {{ __('admin::app.products.title') }}
@stop
    
@section('content-wrapper')
<div class="content full-page dashboard">
    <h1>
        {{ Breadcrumbs::render('products') }}

        {{ __('admin::app.products.title') }}
    </h1> 



    <div class="row" class="search-section">
        <form action="{{ route('admin.products.search') }}" method="get">
            @csrf
            <h1>Ricerca prodotti:</h1>
            <div class="col-md-3" style="display:flex;  flex-wrap: nowrap; ">
                <div class="form-group"  style="margin-right: 10px;"><input class="control" type="text" name="name" id="name" @if(isset($search['name']) && $search['name'] != null) value="{{ $search['name'] }}" @endif placeholder="Nome prodotto"></div>
                <div class="form-group select"  style="margin-right: 10px;">
                    <select class="control" name="tipo">
                        <option value="" @if(isset($search['tipo']) && $search['tipo'] == null) selected @endif>Seleziona tipo prodotto</option>
                        <option value="1" @if(isset($search['tipo']) && $search['tipo'] == '1') selected @endif>Semplice</option>
                        <option value="2" @if(isset($search['tipo']) && $search['tipo'] == '2') selected @endif>Packaging</option>
                        <option value="3" @if(isset($search['tipo']) && $search['tipo'] == '3') selected @endif>Bundle</option>
                        <option value="4" @if(isset($search['tipo']) && $search['tipo'] == '4') selected @endif>Configurabile</option>
                    </select>
                </div>
                <div class="form-group"  style="margin-right: 10px;"><input class="control" type="number" name="pages" id="to" @if(isset($search['pages']) && $search['pages'] != null) value="{{ $search['pages'] }}" @endif placeholder="Per page"></div>

                <div style="margin-right: 10px;"><button type="submit" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Cerca</button></div>

                @if(isset($search) && $search != null)
                <div style="margin-right: 10px;"><a href="{{ route('admin.products.index') }}" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Clear</a></div>
                @endif
            </div>
            
            
        </form>
    
    </div>
                    
    <div class="row">
        <a href="{{ route('admin.products.create') }}" class="btn btn-md btn-primary">{{ __('admin::app.products.create-title') }}</a>
        <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
            <thead style="text-align: left">
                <th>SKU</th>
                <th>Tipo</th>
                <th>Nome</th>
                <th>Scorte</th>
                <th>E-commerce</th>
                <th>Qtà store fisico</th>
                <th>Difettosi</th>
                <th>Prezzo</th>
                <th>Opzione</th>
                <th>Azioni</th>
            </thead>
            <tbody>
                @foreach ($products as $product)
                      
                    <tr>
                        <td>{{ $product->sku }}</td>
                        <td>
                            @php
                                $type = \Webkul\Attribute\Models\AttributeValue::select('integer_value')->where('entity_id', $product->id)->where('attribute_id', 33)->first();
                            @endphp

                            @if ($type !== null)
                                @switch($type->integer_value)
                                    @case(1)
                                        Semplice
                                        @break
                                    @case(2)
                                        Package
                                        @break
                                    @case(3)
                                        Bundle
                                        @break
                                    @case(4)
                                        Configurabile
                                        @break
                                    @default
                                        
                                @endswitch
                            @endif
                        </td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->quantity }}</td>
                        <td>
                            @php
                                $qty_ecom = \Webkul\Attribute\Models\AttributeValue::select('text_value')->where('entity_id', $product->id)->where('attribute_id', 36)->first();
                            @endphp

                            @if ($qty_ecom !== null)
                                {{ $qty_ecom->text_value }}
                            @endif
                        </td>
                        <td>
                            @php
                                $qty_store = \Webkul\Attribute\Models\AttributeValue::select('text_value')->where('entity_id', $product->id)->where('attribute_id', 37)->first();
                            @endphp

                            @if ($qty_store !== null)
                                {{ $qty_store->text_value }}
                            @endif
                        </td>
                        <td>
                            @php
                                $qty_dif = \Webkul\Attribute\Models\AttributeValue::select('text_value')->where('entity_id', $product->id)->where('attribute_id', 35)->first();
                            @endphp

                            @if ($qty_dif !== null)
                                {{ $qty_dif->text_value }}
                            @endif
                        </td>
                        @if ( $product->price != null)
                            <td>{{ rtrim(rtrim($product->price, '0'), '.') }} €</td>
                        @else
                            <td>0 €</td>
                        @endif 

                        <td>
                            @php
                                $attributes_count = \App\Models\ProductAttribute::where('product_sku', $product->sku)->get()->count();
                            @endphp

                            @if (/* $attributes_count === 0  &&  */$product->prod_type === 1)
                                <a href="{{route('admin.products.attributes', ['id' => $product->id])}}" class="btn btn-sm btn-primary">Configurazione</a>
                            @endif
                            
                        </td>
                        <td class="action" style="display:flex; height: 61px;">
                            <a href="/admin/products/edit/{{$product->id}}" title="Edit" data-action="http://127.0.0.1:8000/admin/products/edit/1825" data-method="GET">
                                <i data-route="http://127.0.0.1:8000/admin/products/edit/{{$product->id}}" class="icon pencil-icon"></i>
                            </a>

                            <form action="/admin/products/{{$product->id}}" method="POST">
                                @csrf
                                @method('POST')
                                <button type="submit" title="Delete"  style="border: unset; background:none" data-method="DELETE">
                                    <i style="vertical-align: unset; cursor:pointer"  class="icon trash-icon"></i>
                                </button>
                            </form>
                            
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="max-height: 50px; text-align: center;" class="tab-pagination">
            {{ $products->withQueryString()->links() }}
        </div>
    </div>

@stop