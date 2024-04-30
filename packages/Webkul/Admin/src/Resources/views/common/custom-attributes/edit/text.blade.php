<input
    @if ($attribute->code == 'bundle_option')
        onkeyup="filterProducts('bundle_option', 'myDropdown', 'selected-options')"
    @endif
    type="text"
    name="{{ $attribute->code }}"
    class="control"
    id="{{ $attribute->code }}"
    value="{{ old($attribute->code) ?: $value }}"
    @if ($attribute->code == 'sku') v-validate="{{$validations}}" @else v-validate="'{{$validations}}'" @endif
    data-vv-as="&quot;{{ $attribute->name }}&quot;"
/>

    @php
        $products = \Webkul\Product\Models\Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->select('products.sku', 'products.name', 'products.price', 'products.quantity')
            ->where('attributes.code', 'prod_type')
            ->where('integer_value', '<>', 3)
            ->where('integer_value', '<>', 4)
            ->where('price', '>', 0)
            ->get();     

    @endphp


    @if ($attribute->code == 'bundle_option')
        <div id="myDropdown" class="dropdown-content" style="display:none; z-index:10">

        </div>
        <div id="selected-options">

        </div>
    @endif

<script>

    //Visualizza le bundle options
    if (window.location.href.includes('/products/edit/')){
        const element = document.getElementById('prod_type');
        let product_type = element.value;
        const currentProduct = @json($product);

        //Disabilita la scelta sul tipo di prodotto
        element.disabled = true;
        document.getElementsByClassName('panel-body')[0].innerHTML += '<input type="hidden" name="prod_type" value="' + currentProduct.prod_type + '"/>';

        if (product_type === '3'){
            if (currentProduct.dynamic_price !== null){
              document.getElementsByClassName('panel-body')[0].innerHTML += '<input type="hidden" name="dynamic_price" value="' + currentProduct.dynamic_price + '"/>';
            }
            
            const fieldsToremove = ['qty_diffettosi', 'qty_store'];
            for (let i = 0; i < fieldsToremove.length ; i++){
              
              let label = 'label[for="'+ fieldsToremove[i] +'"]';
              const element_label = document.querySelector(label);

              if (element_label != null){
                element_label.style.display = 'none';
                document.getElementById(fieldsToremove[i]).value = null;
                document.getElementById(fieldsToremove[i]).style.display = 'none';
                document.getElementById(fieldsToremove[i]).disabled = true;
              }
            } 

            const selectedOptions = document.getElementById('selected-options');
            let multilineString = `
            <table style="background-color: white; width: 100%; box-shadow: 2px; padding: 10px;">
            <thead style="text-align: left">
              <th>Opzione</th>
              <th>Quantità</th>
              <th>Massimo</th>
              <th>Minimo</th>
              <th>Definito dall'utente</th>
              <th>Rimuovi</th>
            </thead>
            <tbody id="options">

            </tbody>
          </table>`;
            selectedOptions.innerHTML = multilineString;

            window.onload = loadBundleOptions;
            //setFieldsConfigurable();
            toggleConfigurableFields();
        }else if(product_type === '1'){
            setFieldsConfigurable('block', false, product_type);
            setFieldsPackage('block', false, product_type);
            setFieldsBundle('block', false, product_type);
            hideBundleFields(product_type);
            hideBundleOptions();
            toggleConfigurableFields();
        }else if(product_type === '4'){
            setFieldsPackage('block', false, product_type);
            setFieldsBundle('block', false, product_type);
            hideBundleFields(product_type);
            hideBundleOptions();
            setFieldsConfigurable();
            toggleConfigurableFields('block');
        }else{
            setFieldsBundle('block', false, product_type);
            setFieldsConfigurable('block', false, product_type);
            setFieldsPackage();
            hideBundleOptions();
            toggleConfigurableFields();
        }
        
    }

    function loadBundleOptions(){
        const bundleOptions = @json($bundleOptions);
        const bundleInfo = @json($bundleInfo);

        if (bundleOptions.length > 0){
          const selectedOptions = document.getElementById('selected-options');
          selectedOptions.style.display = 'block';
        }
        const table = document.getElementById('options');

        bundleOptions.forEach(function(element,index) {
          table.innerHTML += '<tr id="'+ index +'"></tr>';
          const option = document.getElementById(index);


          option.innerHTML += '<td>'+ element.option_name +'</td>';
          option.innerHTML += '<td><input type="number" name="qty' + index + '"/ value="'+element.qty+'" required></td>';
          if (bundleInfo[index] !== undefined){
            option.innerHTML += '<td><input type="number" name="max' + index + '" value="'+bundleInfo[index][4]+'" /></td>';
            option.innerHTML += '<td><input type="number" name="min' + index + '" value="'+bundleInfo[index][3]+'" /></td>';
          }else{
            option.innerHTML += '<td><input type="number" name="max' + index + '" value="0" /></td>';
            option.innerHTML += '<td><input type="number" name="min' + index + '" value="0" /></td>';
          }
          

          if (element.can_change_qty === 0){
            option.innerHTML += '<td><input type="checkbox" name="change' + index + '"/></td>';
          }else{
            option.innerHTML += '<td><input type="checkbox" name="change' + index + '" checked/></td>';
          }

          option.innerHTML += '<td><a class="btn btn-sm btn-danger" onclick="removeItem(' + index + ', \'' + 'options' + '\')">Rimuovi</a></td>';

          option.innerHTML += '<input type="hidden" name="option' + index + '" value="' + element.sku_option + '"/>';
          option.innerHTML += '<input type="hidden" name="price' + index + '" value="' + element.option_price + '"/>';
          option.innerHTML += '<input type="hidden" name="name' + index + '" value="' + element.option_name + '"/>';
        }); 

        disableDynamicPrice();
    }

    //Disabilita il dynamic price
    function disableDynamicPrice(){
      const dynamicPrice = document.getElementById('dynamic_price');
      const sliderElement = document.querySelector('.switch .slider.round');
      dynamicPrice.disabled = true;
      sliderElement.classList.toggle('disabled');

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
        const table = document.getElementById('options');
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
            option.innerHTML += '<td><input type="number" name="max' + optionsCount + '" value="" /></td>';
            option.innerHTML += '<td><input type="number" name="min' + optionsCount + '" value="" /></td>';
            option.innerHTML += '<td><input type="checkbox" name="change' + optionsCount + '"/></td>';
            option.innerHTML += '<td><a class="btn btn-sm btn-danger" onclick="removeItem(' + optionsCount + ', \'' + 'options' + '\')">Rimuovi</a></td>';
            option.innerHTML += '<input type="hidden" name="option' + optionsCount + '" value="' + element.sku + '"/>';
            option.innerHTML += '<input type="hidden" name="price' + optionsCount + '" value="' + element.price + '"/>';
            option.innerHTML += '<input type="hidden" name="name' + optionsCount + '" value="' + element.name + '"/>';
          }
        }); 

        document.getElementById(optionsDiv).style.display = 'block';
        
    }

    //Rimuove uno specifico item dal bundle
    function removeItem(option, optionsTr){
      const itemInfo = document.getElementById(option);
      //itemInfo.innerHTML = '';

      const table = document.getElementById('options');
      table.removeChild(itemInfo);

      if (table.children.length !== 0) {
        products = [];

        table.children.forEach(function (element, index){
          let prodName = element.children[0].innerText;
          let prodSku = element.children[6].value;
          let prodPrice = element.children[7].value;
          let prodQty = element.children[1].children[0].value === '' ? 0 : element.children[1].children[0].value;
          let prodChange = element.children[4].children[0].checked === true ? 1 : 0;
          let maxQty = element.children[2].children[0].value === '' ? 0 : element.children[2].children[0].value;
          let minQty = element.children[3].children[0].value === '' ? 0 : element.children[3].children[0].value;

          product = {
            'name': prodName,
            'sku' : prodSku,
            'price' : prodPrice,
            'qty' : prodQty,
            'change' : prodChange,
            'max' : maxQty,
            'min' : minQty
          };

          products.push(product);

        });

        table.innerHTML = '';
        
        products.forEach(function(element, index){
          table.innerHTML += '<tr id="'+ index +'"></tr>'
          const option = document.getElementById(index);
          option.innerHTML += '<td>'+ element.name +'</td>';
          option.innerHTML += '<td><input type="number" name="qty' + index + '" value="'+element.qty+'" required></td>';
          option.innerHTML += '<td><input type="number" name="max' + index + '" value="'+element.max+'" /></td>';
          option.innerHTML += '<td><input type="number" name="min' + index + '" value="'+element.min+'" /></td>';

          if (element.change === 0){
            option.innerHTML += '<td><input type="checkbox" name="change' + index + '"/></td>';
          }else{
            option.innerHTML += '<td><input type="checkbox" name="change' + index + '" checked/></td>';
          }

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