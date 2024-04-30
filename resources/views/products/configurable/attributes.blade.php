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

        <form action="options/" method="POST">
            @csrf
            <div>
                @foreach ($attributes as $attribute)
                    <input type="radio" name="attribute" value="{{ $attribute->attribute_id }}">
                    <label for="html">{{ $attribute->attribute_code }}</label><br>
                @endforeach
            </div>
    
    
            <div>
                <button class="btn btn-sm btn-primary">Successivo</a>
            </div>
        </form>
        
        {{-- <div class="row">
            <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
                <thead style="text-align: left">
                    <th>Codice prodotto</th>
                    <th>Nome</th>
                    <th>Configurazioni</th>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>
                                <a href="configurations/{{ $product->id }}/list" class="btn btn-sm btn-primary">Configurazioni</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div> --}}
@stop