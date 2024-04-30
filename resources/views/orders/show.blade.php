@extends('admin::layouts.master')

@section('page_title')
    {{ __('admin::app.order.title') }}
@stop
    
@section('content-wrapper')
<div class="content full-page dashboard">
    <h1>
        {{ Breadcrumbs::render('orders') }}

        {{ __('admin::app.order.title') }}
    </h1>

<div class="row">

<div class="tab-order-detail">

    {{--  --}}
    <div class="info-tab" @if($role != 3) style="width:100%" @endif>
        <h1>Inidirizzo Spedizione</h1>   
        <p>Ordine <span style="font-weight: 600;
            font-size: larger;">{{ $orderDetail->order_id }}</span></p>
        <p>Totale:  {{ $orderDetail->totale }}€</p>
        <p>Nome: {{ $updatedOrderDetail['shipping_firstname'] }} </p>
        <p>Cognome:  {{ $updatedOrderDetail['shipping_lastname'] }}</p>
        <p>Email:  {{ $updatedOrderDetail['customer_email'] }}</p>
        <p>Indirizzo: {{ $updatedOrderDetail['shipping_street'] }}, {{ $updatedOrderDetail['shipping_city'] }} {{ $updatedOrderDetail['shipping_postcode'] }}, {{ $updatedOrderDetail['shipping_country'] }}</p>

        <h1>Commento ordine</h1>
        <div style="font-size: 18px">
            {{ $orderDetail->comment }}
        </div>

        <div style="margin-top: 25px">
            <h1>Cronologia commenti</h1>
            <div>
                @foreach ($comments as $comment)
                @if (strpos($comment->comment, "transazione") === false && strpos($comment->comment, "transaction") === false && strpos($comment->comment, "refunded") === false )
                    <p style="font-size: 18px">{{ $comment->comment }}</p>
                @endif
            @endforeach
            </div>
        </div>
        
    </div>
    
    <form action="/admin/orders/update/{{ $orderDetail->order_id }}" method="POST" style="width:64%; display:flex">
        @csrf
        @method('PUT')
        <div class="info-tab"  @if($role == 3) style="width:50%" @else style="width:100%" @endif>
            <h1>Prodotti Ordinati</h1>
            @php
                $count = 0;
            @endphp
            @foreach ($products as $product)
                @if ($product->product_type == 'bundle')
                <div style="background-color: #F8EAE7; padding: 10px; border: 1px solid;">
                    <div style="text-align: center; padding-top: 10px;">
                    <a  href="{{ $prodsImages[$product->sku] }}" target="_blank">
                        <img width=150 src="{{ $prodsImages[$product->sku] }}" alt="" >
                        </a>
                        <p><strong>NOME DEL SET:&nbsp; </strong> {{ $product->product_name }}&nbsp; &nbsp;<strong>QUANTITA' SET:</strong> &nbsp; {{ $product->quantity }} </p>
                    </div>  
                <table style="width: 100%; box-shadow: 2px; padding: 10px;">
                     <thead style="text-align: left">
                        <th></th>
                        <th>Componente set</th>
                        <th>Quantità</th>
                        @if (($orderDetail->status == "processing" || $orderDetail->status == "anelli") && $role == 3)
                            <th>Difettosi</th>
                        @endif
                        <tbody>
                            @foreach ($bundleDetails as $detail)
                                @if ($product->item_id == $detail['item_id'])
                                    {{-- <li>{{ $detail['option_name']}} X {{ $detail['quantity']}}</li> --}}
                                    <tr>
                                        <td><a href="{{ $optionsImages[$detail['sku_option']] }}" target="_blank"><img width=100 src="{{ $optionsImages[$detail['sku_option']] }}" alt=""></a></td>
                                        <td>{{ $detail['option_name'] }}</td>
                                        <td>{{ $detail['quantity'] }}</td>
                                        <td>
                                            @if ($orderDetail->status == "processing" && $role == 3)
                                                <input type="number" name="difettoso{{ $count }}" min="0" value="0" style="width:45px" required>
                                                <input type="hidden" name="sku{{ $count }}" value='{{ $detail['sku_option'] }}'>
                                                <input type="hidden" name="product-name{{ $count }}" value='{{ $detail['option_name'] }}'>
                                                <input type="hidden" name="product-type{{ $count }}" value='simple'>
                                                <input type="hidden" name="quantity{{ $count }}" value='{{ $detail['quantity'] }}'>
                                                <input type="hidden" name="product{{ $count }}" value="{{ $detail['item_id'] }}">
                                            @endif
                                        </td>
                                    </tr>
                                    @php
                                        $count ++;
                                    @endphp
                                @endif
                            
                            @endforeach
                        </tbody>
                    </thead>
                </table>
                </div>
                @elseif ($product->product_type == 'simple')
                    <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
                        <thead style="text-align: left">
                            <th></th>
                            <th>Nome prodotto</th>
                            <th>Quantità</th>
                            @if (($orderDetail->status == "processing" || $orderDetail->status == "anelli") && $role == 3)
                                <th>Difettosi</th>
                            @endif
                        </thead>
                        <tbody>
                            <tr>
                                <td><a  href="{{ $prodsImages[$product->sku] }}" target="_blank"><img width=100 src="{{ $prodsImages[$product->sku] }}" alt=""></a></td>
                                <td>
                                    
                                    {{ $product->product_name }}
                                    @foreach ($attributes as $attribute)
                                        @if ($attribute->sku === $product->sku)
                                            <br>
                                            {{$attribute->attribute_value}}
                                        @endif
                                       
                                    @endforeach
                                </td>
                                <td>{{ $product->quantity }}</td>
                                <td>
                                    @if (($orderDetail->status == "processing" || $orderDetail->status == "anelli") && $role == 3)
                                        <input type="number" name="difettoso{{ $count }}" min="0" value="0" style="width:45px" required>
                                        <input type="hidden" name="sku{{ $count }}" value='{{ $product->sku }}'>
                                        <input type="hidden" name="product-name{{ $count }}" value='{{ $product->product_name }}'>
                                        <input type="hidden" name="product-type{{ $count }}" value='{{ $product->product_type }}'>
                                        <input type="hidden" name="quantity{{ $count }}" value='{{ $product->quantity }}'>
                                        <input type="hidden" name="product{{ $count }}" value="{{ $product->item_id }}">
                                        @php
                                            $count ++;
                                        @endphp
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                @endif
            @endforeach
            {{-- <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
                <thead style="text-align: left">
                    <th></th>
                    <th>Nome prodotto</th>
                    <th>Quantità</th>
                    @if ($orderDetail->status == "processing" && $role == 3)
                        <th>Difettosi</th>
                    @endif
                </thead>
                <tbody>
                    @php
                        $count = 0;
                    @endphp
                    @foreach ($products as $product)
                        <tr>
                            <td><img width=100 src="{{ $prodsImages[$product->sku] }}" alt=""></td>
                            <td>
                                
                                {{ $product->product_name }}
                            </td>
                            <td>{{ $product->quantity }}</td>
                            <td>
                                @if ($orderDetail->status == "processing" && $role == 3)
                                    <input type="number" name="difettoso{{ $count }}" min="0" value="0" style="width:45px" required>
                                    <input type="hidden" name="sku{{ $count }}" value='{{ $product->sku }}'>
                                    <input type="hidden" name="product-name{{ $count }}" value='{{ $product->product_name }}'>
                                    <input type="hidden" name="product-type{{ $count }}" value='{{ $product->product_type }}'>

                                    @php
                                        $count ++;
                                    @endphp
                                @endif
                            </td>
                        </tr>

                    @endforeach
                </tbody>
            </table> --}}
        </div>

        @if ($role == 3)
        <div class="info-tab" style="width: 50% !important">
            <h1>Evasione Ordine</h1>
            @if ($orderDetail->status == "processing" || $orderDetail->status == "anelli")
                
            <div class="form-group select">
                <label>Problemi di evasione dell'ordine?</label>
                <select name="problemi" id="problemi" class="control" onchange="avoidShipping()">
                    <option value="none" selected>Nessuno</option>
                    @foreach ($reports as $report)
                        <option value="{{ $report->report_code }}">{{ $report->description }}</option>
                    @endforeach 
                </select>
            </div>
                {{-- @foreach ($products as $product)
                    <input type="hidden" name="product{{ $loop->index }}" value="{{ $product->item_id }}">
                    <input type="hidden" name="quantity{{ $loop->index }}" value="{{ $product->quantity }}">
                @endforeach --}}
                <div class="row" id="packaging">

                  {{-- Buttons package --}}
                  <div style="display:flex" id="buttons">
                    <div class="form-group" id="pack-button">
                        <a id="add-packaging" onclick="addPackaging()" class="control">+</a>
                    </div>
                    <div class="form-group" id="pack-button">
                        <a id="remove-packaging" onclick="removePackaging()" class="control">-</a>
                    </div>
                  </div>

                  <div id="packs-variabili">
                    <div style="display:flex" >
                        <div class="form-group select">
                            <select name="package-name1" id="package-name" class="control" required>
                                <option value="scatolina-piccola">SCATOLINA GIOIELLI PICCOLA</option>
                                <option value="scatolina-media">SCATOLINA GIOIELLI MEDIA</option>
                                <option value="scatolina-grande">SCATOLINA GIOIELLI GRANDE</option>
                                <option value="scatolina-piccola-alta">SCATOLINA GIOIELLI PICCOLA ALTA</option>
                            </select>
                        </div>
                        <div class="form-group" id="pack-qty">
                            <input class="control" type="number" id="package-qty" name="package-quantity1" placeholder="Qtà" value="1" min="0" required>
                        </div>
                    </div>
                    
                  </div>

                  <div id="packs-fissi">
                    {{-- Packaging fissi --}}
                    @foreach ($packFissi as $item)
                        <div style="display:flex" >
                            <div class="form-group select">
                                <select name="package-fixed-name{{ $loop->index }}" id="package-name" class="control" disabled>
                                    <option value="{{ $item->sku }}">{{ $item->name }}</option>
                                </select>
                            </div>
                            <div class="form-group" id="pack-qty">
                                <input class="control" type="number" id="package-fixed-qty" name="package-fixed-quantity{{ $loop->index + 1 }}" min="0" value="1" placeholder="Qtà" required>
                            </div>
                        </div>
                    @endforeach
                  </div>
                </div>
            
                
            @endif
    
            <div class="row" id="order-status">  
                    <div class="form-group">
                        <label>Stato Ordine</label>
                        <select name="stato" id="stato" {{-- onchange="showTrackNumberField()" --}} class="control">
                            @if ($orderDetail->status == "processing")
                                <option value="{{ $orderDetail->status }}" selected>In lavorazione</option>
                                <option value="da_spedire">Da spedire</option>
                            @elseif ($orderDetail->status == "anelli")
                                <option value="{{ $orderDetail->status }}" selected>Anelli</option>
                                <option value="da_spedire">Da spedire</option>
                            @elseif ($orderDetail->status == "da_spedire")
                                <option value="{{ $orderDetail->status }}" selected>Da spedire</option>
                            @elseif ($orderDetail->status == "complete")
                                <option value="{{ $orderDetail->status }}" selected>Completato</option>
                            @elseif ($orderDetail->status == "canceled")
                                <option value="{{ $orderDetail->status }}" selected>Cancellato</option>
                            @elseif ($orderDetail->status == "closed")
                                <option value="{{ $orderDetail->status }}" selected>Chiuso</option>
                            @endif
                            
                        </select>
                    </div>
                    
            </div>
    
            {{-- <div id="shipping">
    
            </div> --}}


        {{-- Buttons --}}
        <div class="btn-evas">
            <input type="submit" class="btn btn-primary btn-sm" value="Salva">
            <a href="{{route('admin.orders.operator.search', ['order_id' => Session::get('order_id'),
                        'from' => Session::get('from'),
                        'to' => Session::get('to'),
                        'stato' => Session::get('stato'),
                        'page' => Session::get('order_page')])}}" class="btn btn-secondary btn-sm">Torna alla lista ordini</a>
        </div>
        @endif
    </form>

</div>
</div> 

<!-- Script -->
<script>


    //Aggiungi label scelta packaging
    function addPackaging(){
        const packagings = @json($packagings);
        let packaging = document.getElementById('packs-variabili');
        if (packaging.children.length < packagings.length){
            getPackageSelectionBox();
        }
    }

    //Rimuovi label scelta packaging
    function removePackaging(){
        const packaging = document.getElementById('packs-variabili');

        if (packaging.children.length > 0){
            packaging.lastElementChild.remove();
        }
    }

    //Se ci sono dei problemi nella spedizione, questa viene resa impossibile
    function avoidShipping(){
        let val = document.getElementById("problemi").value;

        const packaging = document.getElementById('packaging');
        const button = document.getElementById('buttons');
        packaging.innerHTML = '';

        if (val != 'none'){
            packaging.style.display = "none";
            document.getElementById('order-status').style.display = "none";
            document.getElementById('package-title').style.display = "none";
            getPackageSelectionBox(false, true);
        }else{
            console.log(packaging);
            packaging.innerHTML = getButtons();
            packaging.innerHTML += '<div id="packs-variabili">';
            packaging.innerHTML += '</div>';

            packaging.innerHTML += '<div id="packs-fissi">';
            packaging.innerHTML += '</div>';

            addFixedPackagings();

            const packs = document.getElementById('packs-varaiabili');
            getPackageSelectionBox(true, true);
            
            packaging.style.display = "block";
            document.getElementById('order-status').style.display = "block";
            document.getElementById('package-title').style.display = "block";

            
        }
    }

    function addFixedPackagings(){
        packs = @json($packFissi);
        console.log(packs);

        const packFissi = document.getElementById('packs-fissi');

        packs.forEach(function(element, index){
            packFissi.innerHTML += '<div style="display:flex" >' +
                            '<div class="form-group select">' +
                                '<select name="package-fixed-name'+ (index + 1) +'" id="package-name" class="control" disabled>' +
                                    '<option value="'+ element.sku +'">'+ element.name +'</option>' +
                                '</select>' + 
                            '</div>' +
                            '<div class="form-group" id="pack-qty">' +
                                '<input class="control" type="number" id="package-fixed-qty" name="package-fixed-quantity'+ (index + 1) +'" min="0" value="1" placeholder="Qtà" required>' +
                            '</div>' +
                        '</div>';
        });
    }

    function getButtons(){
        return '<div style="display:flex" id="buttons"><div class="form-group" id="pack-button">' + 
                        '<a id="add-packaging" onclick="addPackaging()" class="control">+</a>'+
                    '</div>' +
                    '<div class="form-group" id="pack-button">' +
                        '<a id="remove-packaging" onclick="removePackaging()" class="control">-</a>' +
                    '</div></div>';
    }

    function getPackageSelectionBox(required = true, first = false){
        packages = @json($packagings);
        packages = [
            packages[1],
            packages[2],
            packages[3],
            packages[0],
        ];

        console.log(packages);

        const packaging = document.getElementById('packs-variabili');

        if (packaging.children.length > 0){
            let newPackage = packaging.children[packaging.children.length - 1].cloneNode(true);
            
            newPackage.children[0].children[0].name = 'package-name' + (packaging.children.length + 1);
            newPackage.children[1].children[0].name = 'package-quantity' + (packaging.children.length + 1);

            //Select value
            currentValue = packaging.children[packaging.children.length - 1].children[0].children[0].value;

            //Rimuovi elemento gia utilizzato
            for(let i = 0; i < newPackage.children[0].children[0].children.length; i++){
                const currentChild = newPackage.children[0].children[0].children[i];
                if ( currentChild.value === currentValue){
                    newPackage.children[0].children[0].removeChild(currentChild);
                    break;
                }
                
            }

            packaging.appendChild(newPackage);
        }else{
            let package = '<div style="display:flex"><div class="form-group select">'+
                        '<select name="package-name' + (packaging.children.length + 1) + '" id="package-name" class="control">';

        packages.forEach(e => {
            package += '<option value="'+ e.sku +'">' + e.name + '</option>';
        });

        let requiredStr = required ? 'required' : '';

            package += '</select></div>'+
            '<div class="form-group" id="pack-qty"> <input class="control" type="number" id="package-qty" name="package-quantity'+ (packaging.children.length + 1) +'" '+ requiredStr +' placeholder="Qtà" min="1"></div></div>';
            packaging.innerHTML = package;
        }
    }
</script>



@stop