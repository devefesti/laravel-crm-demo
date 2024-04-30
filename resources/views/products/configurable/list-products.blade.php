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
        
        <form action="/admin/products/configurations/{{$id}}/add-products/" method="POST">
            <button type="submit" class="btn btn-sm btn-primary">Aggiungi configurazioni</button>
            @csrf
            <input type="hidden" value="{{ $parent_sku }}" name="parent_sku">
            <div class="row">
                <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
                    <thead style="text-align: left">
                        <th></th>
                        <th>Codice prodotto</th>
                        <th>Nome</th>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    <input type="checkbox" value="{{ $product->sku }}" name="selectedItems[]">
                                </td>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>
@stop