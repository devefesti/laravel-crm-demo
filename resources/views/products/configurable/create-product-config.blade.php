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

        <div>
            <form action="/admin/products/configurations/{{$id}}/attributes/options/create" method="POST">
                @csrf
                <input type="hidden" name="attribute_id" value="{{ $attribute_id }}">
                <input type="hidden" name="attribute_code" value="{{ $attribute_code }}">
                <input type="hidden" name="sku" value="{{ $baseSku }}">

                <div class="row">
                    <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
                        <thead style="text-align: left">
                            <th>Codice prodotto</th>
                            <th>Nome</th>
                            <th>Prezzo</th>
                            <th>Quantità</th>
                        </thead>
                        <tbody>
                            @foreach ($options as $option)
                                <tr>
                                    <td>
                                        {{ $baseSku }}-{{ $option->value }}
                                        <input type="hidden" name="sku{{$loop->index}}" value="{{ $baseSku }}-{{ $option->value }}">
                                    </td>
                                    <td>
                                        <input type="text" name='name{{$loop->index}}' required>
                                    </td>
                                    <td>
                                        <input type="number" name='price{{$loop->index}}' step="0.01" inputmode="numeric" required>
                                    </td>
                                    <td>
                                        <input type="number" name='quantity{{$loop->index}}' required>
                                    </td>
                                </tr>
                                <input type="hidden" name="value{{$loop->index}}" value="{{$option->option_id}}">
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button class="btn btn-sm btn-primary">Crea prodotti</button>
            </form>
        </div>
        
@stop