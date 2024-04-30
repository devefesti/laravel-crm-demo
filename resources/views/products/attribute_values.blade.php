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

        <form action="{{route('admin.products.attributes.options.add', ['id' => $id])}}" method="POST">
            @csrf
            <input type="hidden" name="attribute_id" value="{{$attribute}}">
            @foreach ($values as $value)
                <input type="radio" name="item" value="{{ $value->option_id }}"/>
                <label for="value">{{ $value->value}}</label>
            @endforeach

            <input type="submit" class="btn btn-sm btn-primary" value="Successivo">
        </form>
        
@stop