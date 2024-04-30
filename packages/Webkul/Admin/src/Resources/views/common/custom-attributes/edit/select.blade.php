<select v-validate="'{{$validations}}'" class="control" id="{{ $attribute->code }}" name="{{ $attribute->code }}" data-vv-as="&quot;{{ $attribute->name }}&quot;"
    @if($attribute->code == 'prod_type') onchange="hideFields()" @endif>

    @php
        $options = $attribute->lookup_type
            ? app('Webkul\Attribute\Repositories\AttributeRepository')->getLookUpOptions($attribute->lookup_type)
            : $attribute->options()->orderBy('sort_order')->get();

        $selectedOption = old($attribute->code) ?: $value;
    @endphp

    <option value="" selected="selected" disabled="disabled">{{ __('admin::app.settings.attributes.select') }}</option>

    @if($attribute->code == 'attribute') 
        @php
            $attributes = \App\Models\MagentoAttributes::where('attribute_code', 'color')->orWhere('attribute_code', 'like', 'nc%')->get();
        @endphp

        @foreach ($attributes as $attribute)
            <option value="{{$attribute->attribute_id}}">{{$attribute->attribute_code}}</option>
        @endforeach
    @endif
    @foreach ($options as $option)
        <option value="{{ $option->id }}" {{ $option->id == $selectedOption ? 'selected' : ''}}>
            {{ $option->name }}
        </option>
    @endforeach

</select>

<script>
    window.onload = hideFields;

    //Nasconde i campi quando il prodotto è di tipo 'PACKAGE'(2), altrimenti li visualizza
    function hideFields(){
        const element = document.getElementById('prod_type');
        let product_type = element.value;

        //Se package viene scelto come opzione allora nascondo tutti i campi non richiesti
        if (product_type === '2'){
            setFieldsBundle('block', false, product_type);
            setFieldsConfigurable('block', false, product_type);
            setFieldsPackage();
            hideBundleOptions();
            toggleConfigurableFields();
        }else if(product_type === '3'){
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
            
            setFieldsConfigurable('block', false, product_type);
            setFieldsPackage('block', false, product_type);
            setFieldsBundle();
            toggleConfigurableFields();
        }else if(product_type === '1'){
            setFieldsConfigurable('block', false, product_type);
            setFieldsPackage('block', false, product_type);
            setFieldsBundle('block', false, product_type);
            hideBundleFields(product_type);
            hideBundleOptions();
            toggleConfigurableFields();
        }else if (product_type === '4'){
            //setFieldsConfigurable('block', false, product_type);
            setFieldsPackage('block', false, product_type);
            setFieldsBundle('block', false, product_type);
            hideBundleFields(product_type);
            hideBundleOptions();
            setFieldsConfigurable();
            toggleConfigurableFields('block');
        } else {
            //setFieldsConfigurable();
            setFieldsPackage('block', false, product_type);
            setFieldsBundle('block', false, product_type);
            hideBundleFields(product_type);
            hideBundleOptions();
            toggleConfigurableFields();
        }
    }

    function toggleConfigurableFields(display = 'none'){
        const text_input = document.getElementById('product_config');
        let label = 'label[for="product_config"]';
        const element_label = document.querySelector(label);
        if (element_label != null){
            element_label.style.display = display;
            text_input.style.display = display;
        }

        if (display === 'none'){
            const allConfigurations = document.getElementById('selected-configurations');
            allConfigurations.innerHTML = '';
            allConfigurations.style.display = display;
        }
    }

    function hideBundleFields(product_type){
        const fieldsToremove = ['switch', 'dynamic_price', 'bundle_option', 'max_options'];

        for (let i = 0; i < fieldsToremove.length ; i++){
            if (fieldsToremove[i] === 'switch'){
                document.getElementsByClassName(fieldsToremove[i])[0].style.display = 'none';
            }
            let label = 'label[for="'+ fieldsToremove[i] +'"]';
            const element_label = document.querySelector(label);
            if (element_label != null){
                element_label.style.display = 'none';
                if (product_type === '1'){
                    document.getElementById(fieldsToremove[i]).value = null;
                }
                document.getElementById(fieldsToremove[i]).style.display = 'none';
                document.getElementById(fieldsToremove[i]).disabled = true;
            }
        }
    }

    function setFieldsPackage(display = 'none', disabled = true, product_type = '2'){
        const fieldsToremove = ['price', 'qty_ecommerce', 'qty_store', 'switch', 'dynamic_price', 'bundle_option', 'qty_diffettosi', 'max_options'];

        for (let i = 0; i < fieldsToremove.length ; i++){
            if (fieldsToremove[i] === 'switch'){
                document.getElementsByClassName(fieldsToremove[i])[0].style.display = display;
            }
            let label = 'label[for="'+ fieldsToremove[i] +'"]';
            const element_label = document.querySelector(label);
            if (element_label != null){
                element_label.style.display = display;
                if (product_type === '2'){
                    document.getElementById(fieldsToremove[i]).value = null;
                }
                document.getElementById(fieldsToremove[i]).style.display = display;
                document.getElementById(fieldsToremove[i]).disabled = disabled;
            }
        }

    }

    function setFieldsBundle(display = 'none', disabled = true, product_type = '3'){
        const fieldsToremove = ['qty_diffettosi', 'qty_store'];
        for (let i = 0; i < fieldsToremove.length ; i++){
            let label = 'label[for="'+ fieldsToremove[i] +'"]';
            const element_label = document.querySelector(label);
            if (element_label != null){
                element_label.style.display = display;
                if (product_type === '3'){
                    document.getElementById(fieldsToremove[i]).value = null;
                }
                document.getElementById(fieldsToremove[i]).style.display = display;
                document.getElementById(fieldsToremove[i]).disabled = disabled;
            }
            
        }
    }

    function setFieldsConfigurable(display = 'none', disabled = true, product_type = '4'){
        const fieldsToremove = ['qty_diffettosi', 'qty_store', 'qty_difettosi', 'price', 'quantity', 'qty_ecommerce', 'max_options'];
        for (let i = 0; i < fieldsToremove.length ; i++){
            let label = 'label[for="'+ fieldsToremove[i] +'"]';
            const element_label = document.querySelector(label);
            if (element_label != null){
                element_label.style.display = display;
                if (product_type === '4'){
                    document.getElementById(fieldsToremove[i]).value = null;
                }
                document.getElementById(fieldsToremove[i]).style.display = display;
                document.getElementById(fieldsToremove[i]).disabled = disabled;
            }
            
        }
    }

    //Nasconde e riumuove le opzioni del bundle
    function hideBundleOptions(){
        const allOptions = document.getElementById('selected-options');
        allOptions.innerHTML = '';
        allOptions.style.display = 'none';
    }

</script>