@extends('admin::layouts.master')

@section('page_title')
    {{ __('Ordini influencers') }}
@stop

@section('content-wrapper')
    <div class="content full-page dashboard">
        <h1>
            {{ Breadcrumbs::render('influencers') }}
        
            {{ __('Ordini influencer') }}
        </h1>

        <form action="{{ route('admin.influencers.orders.store') }}" method="POST">
            @csrf
            <div class="page-content">
                <div class="form-container">
                    <div class="panel">
                        <div class="panel-header">
                            <button type="submit" class="btn btn-md btn-primary">
                                Crea ordine
                            </button>
                            <a href="{{route('admin.influencers.orders')}}">Indietro</a>
                        </div>
                        <div class="panel-body">
                            <div class="form-group select">
                                <label for="cognome" class="required">Influencer</label>
                                <select name="influencer" id="" class="control" required>
                                    @foreach ($influencers as $influencer)
                                        <option value="{{ $influencer->id }}">{{$influencer->nome . ' ' . $influencer->cognome }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group text">
                                <label for="cognome" class="required">Scegli prodotti</label>
                                <input type="text" name="cognome" id="product" value="" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" onkeyup="filterProducts('product', 'myDropdown', 'selected-products')">

                                <div id="myDropdown" class="dropdown-content" style="display:none; z-index:10">

                                </div>
                                <div id="selected-products">
                                    <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
                                        <thead style="text-align: left">
                                          <th>Prodotto</th>
                                          <th>Quantità</th>
                                          <th>Rimuovi</th>
                                        </thead>
                                        <tbody id="products">
                            
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="cognome" class="required">Packaging fissi</label>
                            </div>
                            
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

                                <div class="form-group">
                                    <label for="cognome" class="required">Packaging variabili</label>
                                </div>
                                
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

                                  <div class="form-group textarea">
                                    <label for="cognome">Nota ordine</label>
                                    <textarea name="nota" class="control" id="description" v-validate="''" data-vv-as="Description"></textarea>
                                    </div>
                            </div>
                            
                            
                            
                            {{-- <div class="form-group text">
                                <label for="name" class="required">Nome</label>
                                <input type="text" name="nome" id="name" value="" data-vv-as="Nome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>
                            <div class="form-group text">
                                <label for="cognome" class="required">Cognome</label>
                                <input type="text" name="cognome" id="name" value="" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>
                            <div class="form-group text">
                                <label for="cognome" class="required">Email</label>
                                <input type="email" name="email" id="name" value="" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>
                            <div class="form-group text">
                                <label for="cognome" class="required">Telefono</label>
                                <input type="tel" name="telefono" id="name" value="" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>
                            <div class="form-group text">
                                <label for="cognome" class="required">Materiale</label>
                                <input type="text" name="materiale" id="name" value="" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>

                            <div class="form-group textarea">
                                <label for="cognome">Descrizione</label>
                                <textarea name="descrizione" class="control" id="description" v-validate="''" data-vv-as="Description"></textarea>
                            </div>
                            
                            <div class="form-group text">
                                <label for="cognome" class="required">Indirizzo</label>
                                <input type="text" name="indirizzo" id="name" value="" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div> --}}
                        </div>
                    </div>
                </div>
            </div>
            
        </form>
    </div>

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

        if (packaging.children.length > 1){
            packaging.lastElementChild.remove();
        }
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
            '<div class="form-group" id="pack-qty"> <input class="control" type="number" id="package-qty" name="package-quantity'+ (packaging.children.length + 1) +'" '+ requiredStr +' placeholder="Qtà" min="0" value="1"></div></div>';
            packaging.innerHTML = package;
        }
    }

        //Filtra i prodotti in base al testo digitato per bundle products
        function filterProducts(text_input, dropdownId, optionsDiv){
            const text = document.getElementById(text_input).value;

            const prods = @json($products);

            const dropdown = document.getElementById(dropdownId);

            dropdown.innerHTML = '';
            prods.forEach(element => {
            if (text != '' && element.name.toLowerCase().includes(text)){
                dropdown.innerHTML += '<a onclick="getItem(\'' + element.sku + '\',\'' + text_input + '\',\'' + dropdownId + '\', \'' + optionsDiv + '\')">' + element.name + '</a>';  
            }
            
            });

            dropdown.style.display = 'block';
            
        }

        //recupera il singolo item cliccato e lo aggiunge al dropdown
    function getItem(sku, text_input, dropdownId, optionsDiv){
        const prods = @json($products);

        const dropdown = document.getElementById(dropdownId);
        //const selectedOptions = document.getElementById(optionsDiv);
        const table = document.getElementById('products');
        //selectedOptions.style.display = 'block';
        document.getElementById(text_input).value = '';

        dropdown.style.display = 'none';
        
        
        let optionsCount = table.getElementsByTagName('tr').length;
        prods.forEach(element => {
          if(element.sku === sku){
            table.innerHTML += '<tr id="'+ optionsCount +'"></tr>'
            const option = document.getElementById(optionsCount);
            option.innerHTML += '<td>'+ element.name +'</td>';
            option.innerHTML += '<td><input type="number" name="qty' + optionsCount + '" value="" required></td>';
/*             option.innerHTML += '<td><input type="number" name="max' + optionsCount + '" value="" /></td>';
            option.innerHTML += '<td><input type="number" name="min' + optionsCount + '" value="" /></td>';
            option.innerHTML += '<td><input type="checkbox" name="change' + optionsCount + '"/></td>'; */
            option.innerHTML += '<td><a class="btn btn-sm btn-danger" onclick="removeItem(' + optionsCount + ', \'' + 'options' + '\')">Rimuovi</a></td>';
            option.innerHTML += '<input type="hidden" name="option' + optionsCount + '" value="' + element.sku + '"/>';
            option.innerHTML += '<input type="hidden" name="price' + optionsCount + '" value="' + element.price + '"/>';
            option.innerHTML += '<input type="hidden" name="name' + optionsCount + '" value="' + element.name + '"/>';
          }
        }); 

        document.getElementById(optionsDiv).style.display = 'block';
        
    }

    function removeItem(option, optionsTr){
      const itemInfo = document.getElementById(option);
      //itemInfo.innerHTML = '';

      const table = document.getElementById('products');
      table.removeChild(itemInfo);

      if (table.children.length !== 0) {
        products = [];

        table.children.forEach(function (element, index){
          let prodName = element.children[0].innerText;
          let prodSku = element.children[3].value;
          let prodPrice = element.children[4].value;
          let prodQty = element.children[1].children[0].value === '' ? 0 : element.children[1].children[0].value;

          product = {
            'name': prodName,
            'sku' : prodSku,
            'price' : prodPrice,
            'qty' : prodQty,
          };

          products.push(product);

        });

        table.innerHTML = '';
        
        products.forEach(function(element, index){
          table.innerHTML += '<tr id="'+ index +'"></tr>'
          const option = document.getElementById(index);
          option.innerHTML += '<td>'+ element.name +'</td>';
          option.innerHTML += '<td><input type="number" name="qty' + index + '" value="'+element.qty+'" required></td>';
          /* option.innerHTML += '<td><input type="number" name="max' + index + '" value="'+element.max+'" /></td>';
          option.innerHTML += '<td><input type="number" name="min' + index + '" value="'+element.min+'" /></td>';

          if (element.change === 0){
            option.innerHTML += '<td><input type="checkbox" name="change' + index + '"/></td>';
          }else{
            option.innerHTML += '<td><input type="checkbox" name="change' + index + '" checked/></td>';
          } */

          option.innerHTML += '<td><a class="btn btn-sm btn-danger" onclick="removeItem(' + index + ', \'' + 'options' + '\')">Rimuovi</a></td>';
          option.innerHTML += '<input type="hidden" name="option' + index + '" value="' + element.sku + '"/>';
          option.innerHTML += '<input type="hidden" name="price' + index + '" value="' + element.price + '"/>';
          option.innerHTML += '<input type="hidden" name="name' + index + '" value="' + element.name + '"/>';

        });
      }else{
        document.getElementById('selected-options').style.display = 'none';
      }
    }
    </script>
@stop