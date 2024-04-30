@extends('admin::layouts.master')

@section('page_title')
    {{ __('admin::app.products.title') }}
@stop

@section('content-wrapper')
    <div class="content full-page dashboard">
        {{-- <h1>{{ __('admin::app.order.title') }}</h1> --}}
        <h1>
            {{ Breadcrumbs::render('products') }}

            {{ __('admin::app.products.title') }}
        </h1>

        <form action="{{route('admin.products.attributes.options', ['id' => $id])}}" method="POST">
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
@stop