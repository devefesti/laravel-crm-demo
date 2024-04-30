@extends('admin::layouts.master')

@section('content-wrapper')

<div class="content full-page dashboard">

<h1>Cambia operatore</h1>
<form action="{{ route('admin.orders.management.assign.edit') }}" method="post">
    @csrf
    <input type="hidden" name="order_id" value="{{ $order->order_id}}">
    <p>Codice ordine: {{ $order->order_id}}</p>
    <label for="operator"> Seleziona Operatore: </label>
        <div class="form-group select">
            <select name="operator" id="operator" class="control" style="width: 33%;">
                @foreach ($operators as $user)
                <option name="user-{{$user->id }}" id="user-{{$user->id}}" value="{{$user->name }}"  @if($user->name == $order->operator) selected @endif>{{ $user->name }}</option>
                 @endforeach
                </select>
        </div>

        <button type="submit" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Cambia Operatore</button>
        <a href="{{route('admin.orders.management.assigned.search', [
            'operator' => Session::get('operator_man'),
            'order_id' => Session::get('order_id_man'),
            'from' => Session::get('from_man'),
            'to' => Session::get('to_man'),
            'stato' => Session::get('stato_man'),
            'page' => Session::get('page_man')])}}" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Annulla</a>
</form>


</div>
@stop