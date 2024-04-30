@extends('admin::layouts.master')

@section('page_title')
    {{ __('admin::app.products.title_configurable') }}
@stop

@section('content-wrapper')
    <div class="content full-page dashboard">
        {{-- <h1>{{ __('admin::app.order.title') }}</h1> --}}
        <h1>
            {{ Breadcrumbs::render('configurable') }}

            {{ __('admin::app.products.title_configurable') }}
        </h1>

        <div class="row">
            <div class="row" class="search-section">
                <form action="{{ route('admin.products.configurable.search')}}" method="get">
                    @csrf
                <h1>Ricerca prodotti configurabili</h1>

                <div class="col-md-3" style="display:flex;  flex-wrap: nowrap; ">
                    <div class="form-group" style="margin-right: 10px;">
                        <input class="control" type="text" name="nome" placeholder="Nome prodotto" @if(isset($search) && $search['nome'] !== null) value="{{ $search['nome'] }}" @endif>
                    </div>
                    <div class="form-group" style="margin-right: 10px;">
                        <input class="control" type="number" name="pages" placeholder="Per page" @if(isset($search) && $search['pages'] !== null) value="{{ $search['pages'] }}" @endif>
                    </div>
                    <div style="margin-right: 10px;"><button type="submit" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Cerca</button></div>

                    @if(isset($search) && $search != null)
                        <div style="margin-right: 10px;"><a href="{{route('admin.products.configurable')}}" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Clear</a></div>
                    @endif
                </div>
                </form>
            
            </div>
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
                                <a href="{{ route('admin.products.configurable.list', ['id' => $product->id ])}}" class="btn btn-sm btn-primary">Configurazioni</a>
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
