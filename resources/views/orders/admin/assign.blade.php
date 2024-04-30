@extends('admin::layouts.master')

@section('content-wrapper')

<div class="content full-page dashboard">
    <h3>
        {{ Breadcrumbs::render('orders') }}

        {{ __('admin::app.order.title') }}
    </h3>

<div class="row" class="search-section">
    <form action="{{ route('admin.orders.management.assign.search') }}" method="get">
        @csrf
    <h1>Ricerca Ordini da assegnare:</h1>
    <div class="col-md-3" style="display:flex;  flex-wrap: nowrap; ">
        <div class="form-group select" style="margin-right: 10px;"><input class="control" type="number" name="order_id" id="order_id" placeholder="Numero ordine" @if(isset($search['order_id']) && $search['order_id'] != null) value="{{ $search['order_id'] }}" @endif></div>
        <div class="form-group"  style="margin-right: 10px;"><input class="control" type="date" name="from" id="from" format="dd-mm-yyyy" @if(isset($search['from']) && $search['from'] != null) value="{{ $search['from'] }}" @endif placeholder="Dal"></div>
        <div class="form-group"  style="margin-right: 10px;"><input class="control" type="date" name="to" id="to" format="dd-mm-yyyy" @if(isset($search['to']) && $search['from'] != null) value="{{ $search['to'] }}" @endif placeholder="Al"></div>
        <div class="form-group"  style="margin-right: 10px; max-width: 120px"><input class="control" type="number" name="pages" id="pages" @if(isset($search['pages']) && $search['pages'] != null) value="{{ $search['pages'] }}" placeholder="Per page" @endif></div>
        <div style="margin-right: 2px;"><button type="submit" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Invia</button></div>
        @if(isset($search) && $search != null)
        <div style="margin-right: 10px;"><a href="/admin/orders/management/assign" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Clear</a></div>
        @endif
    </div>
    </form>

</div>
  


    <h1>Assegnazione Ordini agli operatori</h1>

    <form action="{{ route('admin.orders.management.assign.save') }}" method="post">
        @csrf
    <div class="row">

        <label for="operator"> Seleziona Operatore: </label>
        <div class="form-group select">
            <select name="operator" id="operator" class="control" style="width: 33%;">
                @foreach ($users as $user)
                <option name="user-{{$user->id }}" id="user-{{$user->id}}" value="{{$user->name }}">{{ $user->name }}</option>
                 @endforeach
                </select>
        </div>

    </div>

    <div class="row">
      <p>Seleziona ordini da assegnare:</p>
      <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
        <thead style="text-align: left">
            <th><input type="checkbox" id="select-all-checkbox" onclick="selectAll()">
            </th>
            <th>
                @if (Session::has('param') && Session::get('param') == 'order_id')
                    <a href="{{route('admin.management.orders.sort', ['param' => 'order_id', 'order' => Session::get('order') ])}}">Codice ordine</a>
                    @if (Session::has('param') && Session::get('param') === 'order_id')
                        @if (Session::get('order') === 'asc')
                            &uarr; 
                        @else
                            &darr;
                        @endif
                    @endif
                @else
                    <a href="{{route('admin.management.orders.sort', ['param' => 'order_id', 'order' => 'asc' ])}}">Codice ordine</a>
                @endif  
            </th>
            <th>Totale ordine</th>
            <th>Stato</th>
            <th>Email cliente</th>
            <th>Nome</th>
            <th>Cognome</th>
            <th>
                @if (Session::has('param') && Session::get('param') == 'order_date')
                    <a href="{{route('admin.management.orders.sort', ['param' => 'order_date', 'order' => Session::get('order') ])}}">Data</a>
                    @if (Session::has('param') && Session::get('param') === 'order_date')
                        @if (Session::get('order') === 'asc')
                            &uarr; 
                        @else
                            &darr;
                        @endif
                    @endif
                @else
                    <a href="{{route('admin.management.orders.sort', ['param' => 'order_date', 'order' => 'asc' ])}}">Data</a>
                @endif 
            </th>
            {{-- <th>Dettagli ordine</th> --}}
        </thead>
        <tbody>
            @foreach($orders as $item)
                <tr>
                    <input type="hidden" name="order_state" value="{{ $item['status'] }}" id="{{ $item['status'] }}">
                    <input type="hidden" name="order_id" value="{{ $item['order_id'] }}" id="{{ $item['order_id'] }}">
                    <td>
                        <input type="checkbox" name="selectedItems[]" id="{{ $item['order_id'] }}" value="{{ $item['order_id'] }}" onchange="countChecked()" />
                    </td>
                    <td>{{ $item['order_id'] }}</td>
                    <td>{{ $item['totale'] }} €</td>
                    <td>
                        @if($item['status'] == 'da_spedire')
                            Da spedire
                        @elseif ($item['status'] == 'processing')
                            In lavorazione
                        @elseif ($item['status'] == 'anelli')
                            Anelli
                        @endif
                    </td>
                    <td>{{ $item['email'] }}</td>
                    <td>{{ $item['firstname'] }}</td>
                    <td>{{ $item['lastname'] }}</td>
                    <td>
                        <?php
                            $dateTime = new DateTime($item['order_date']);
                            $dateTime->add(new DateInterval('PT2H'));
                            $formattedDate = $dateTime->format('d-m-Y H:i:s');
                            echo $formattedDate;  
                        ?>
                    </td>
                    {{-- <td>
                        <a href="/admin/orders/{{ $item['order_id'] }}/details">
                            <button>Dettagli ordine</button>
                        </a>
                    </td> --}}
                </tr>
            @endforeach
        </tbody>
    </table>
   
    <button type="submit" class="btn btn-md btn-primary" style="float: right; margin: 10px;" id="assign">Assegna Ordini</button>
    <a href="/admin/orders/management" class="btn btn-md btn-primary" style="float: right; margin: 10px;">Annulla</a>
    {{-- <span style="float: right; margin: 10px;">Test</span> --}}
    <div style="max-height: 50px; text-align: center;" class="tab-pagination">
        {{ $orders->withQueryString()->links() }}
    </div>
    </form>

    </div>
</div>

<script>
    var totalChecked = 0;

    function selectAll() {
        var checkBox = document.getElementById("select-all-checkbox");
        const allcbCheckbox = document.getElementById('select-all-checkbox');
        if (checkBox.checked == true){
                const checkboxes = document.querySelectorAll('[name="selectedItems[]"]');
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = allcbCheckbox.checked;
                    //totalChecked ++;
                });

                totalChecked = checkboxes.length;
        } else {
        const checkboxes = document.querySelectorAll('[name="selectedItems[]"]');
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = allcbCheckbox.checked;
                });

                totalChecked = 0;
        }

        updateBtnText();
    } 

    function countChecked(){
        totalChecked = 0;
        const checkboxes = document.querySelectorAll('[name="selectedItems[]"]');

        checkboxes.forEach(function (checkbox) {
            if (checkbox.checked){
                totalChecked ++;
            }
        });

        updateBtnText();
        
    }

    function updateBtnText(){
        const assignBtn = document.getElementById('assign');

        if ( totalChecked > 1) {
            assignBtn.innerHTML = 'Assegna Ordini (' + totalChecked + ')';
        } else {
            assignBtn.innerHTML = 'Assegna Ordini';
        }
    }
  </script>

@stop