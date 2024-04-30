<?php

namespace Webkul\Product\Repositories;

use Illuminate\Container\Container;
use Webkul\Core\Eloquent\Repository;
use Webkul\Attribute\Repositories\AttributeValueRepository;
use Webkul\Product\Models\Product;

class ProductRepository extends Repository
{
    /**
     * AttributeValueRepository object
     *
     * @var \Webkul\Attribute\Repositories\AttributeValueRepository
     */
    protected $attributeValueRepository;

    /**
     * Create a new repository instance.
     *
     * @param  \Webkul\Attribute\Repositories\AttributeValueRepository  $attributeValueRepository
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function __construct(
        AttributeValueRepository $attributeValueRepository,
        Container $container
    )
    {
        $this->attributeValueRepository = $attributeValueRepository;

        parent::__construct($container);
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Webkul\Product\Contracts\Product';
    }

    /**
     * @param array $data
     * @return \Webkul\Product\Contracts\Product
     */
    public function create(array $data)
    {
        if ($data['prod_type'] === '1'){
            if ((int)$data['quantity'] < (int)$data['qty_ecommerce']){
                $data['quantity'] = '0';
            }else{
                $data['quantity'] = strval((int)$data['quantity'] - (int)$data['qty_ecommerce']);
            }

            if ((int)$data['quantity'] < (int)$data['qty_store']){
                $data['quantity'] = '0';
            }else{
                $data['quantity'] = strval((int)$data['quantity'] - (int)$data['qty_store']);
            }

            $data['qty_ecommerce'] = strval((int)$data['qty_ecommerce'] - (int)$data['qty_diffettosi']);
        }

        $product = parent::create($data);

        $this->attributeValueRepository->save($data, $product->id);

        return $product;
    }

    /**
     * @param array  $data
     * @param int    $id
     * @param string $attribute
     * @return \Webkul\Product\Contracts\Product
     */
    public function update(array $data, $id, $attribute = "id")
    {
        if ($data['prod_type'] === '2'){
            $data['price'] = 0.0;
            $data['qty_ecommerce'] = '0';
            $data['qty_store'] = '0';
        }


        
        if ($data['prod_type'] === '1'){
            
            $prod = $this->findOrFail($id);
            $qty_ecom = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                ->where('attributes.code', 'qty_ecommerce')
                ->where('sku', '=', $prod->sku)
                ->select('products.name as name', 'products.sku as sku', 'attribute_values.text_value as value', 'products.price as price')
                ->first();

            $qty_store = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                ->where('attributes.code', 'qty_store')
                ->where('sku', '=', $prod->sku )
                ->select('products.name as name', 'products.sku as sku', 'attribute_values.text_value as value', 'products.price as price')
                ->first();
            
            if ((int)$data['quantity'] >= ((int)$data['qty_ecommerce'] - (int)$qty_ecom->value)){
                //CASO 1: Quantità scorte invariata, quantità ecommerce aumentata
                if (((int)$prod->quantity === (int)$data['quantity']) && ((int)$data['qty_ecommerce'] > (int)$qty_ecom->value)){
                    $data['quantity'] = strval((int)$data['quantity'] - ((int)$data['qty_ecommerce'] - (int)$qty_ecom->value));
                }

                //CASO 2: Quantità scorte aumentata, quantità ecommerce invariata
                if (((int)$prod->quantity < (int)$data['quantity']) && ((int)$data['qty_ecommerce'] === (int)$qty_ecom->value)){
                    //Nothing here
                }

                //CASO 3: Quantità scorte aumentata, quantità ecommerce aumentata
                if (((int)$prod->quantity < (int)$data['quantity']) && ((int)$data['qty_ecommerce']) > (int)$qty_ecom->value){
                    $data['quantity'] = strval((int)$data['quantity'] - ((int)$data['qty_ecommerce'] - (int)$qty_ecom->value));
                } 

                //CASO 4: Quantità scorte invariata, quantità ecommerce diminuita
                /* if (((int)$prod->quantity === (int)$data['quantity']) && ((int)$data['qty_ecommerce']) < (int)$qty_ecom->value){
                    $data['quantity'] = strval((int)$data['quantity'] + ((int)$qty_ecom->value - (int)$data['qty_ecommerce']));
                }  */
            }else{
                $data['quantity'] = "0";
            }    
            
            if ((int)$data['quantity'] >= ((int)$data['qty_store'] - (int)$qty_store->value)){
                //CASO 1: Quantità scorte invariata, quantità ecommerce aumentata
                if (/* ((int)$prod->quantity === (int)$data['quantity']) && */ ((int)$data['qty_store'] > (int)$qty_store->value)){
                    $data['quantity'] = strval((int)$data['quantity'] - ((int)$data['qty_store'] - (int)$qty_store->value));
                }

                //CASO 2: Quantità scorte aumentata, quantità ecommerce invariata
                if (((int)$prod->quantity < (int)$data['quantity']) && ((int)$data['qty_store'] === (int)$qty_store->value)){
                    //Nothing here
                }

                //CASO 3: Quantità scorte aumentata, quantità ecommerce aumentata
                /* if (((int)$prod->quantity < (int)$data['quantity']) && ((int)$data['qty_store']) > (int)$qty_store->value){
                    $data['quantity'] = strval((int)$data['quantity'] - ((int)$data['qty_store'] - (int)$qty_store->value));
                }  */

                //CASO 4: Quantità scorte invariata, quantità ecommerce diminuita
                /* if (((int)$prod->quantity === (int)$data['quantity']) && ((int)$data['qty_ecommerce']) < (int)$qty_ecom->value){
                    $data['quantity'] = strval((int)$data['quantity'] + ((int)$qty_ecom->value - (int)$data['qty_ecommerce']));
                }  */
            }else{
                $data['quantity'] = "0";
            }

            $qty_dif = Product::join('attribute_values', 'attribute_values.entity_id', '=', 'products.id')
                ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                ->where('attributes.code', 'qty_diffettosi')
                ->where('sku', '=', $prod->sku)
                ->select('products.name as name', 'products.sku as sku', 'attribute_values.text_value as value', 'products.price as price')
                ->first();

            $data['qty_ecommerce'] = strval((int)$data['qty_ecommerce'] - ((int)$data['qty_diffettosi'] - (int)$qty_dif->value));
 
            //$data['quantity'] = strval((int)$data['quantity'] - (int)$data['qty_ecommerce']);
        }
        
        $product = parent::update($data, $id);

        $this->attributeValueRepository->save($data, $id);

        return $product;
    }

    /**
     * Retreives customers count based on date
     *
     * @return number
     */
    public function getProductCount($startDate, $endDate)
    {
        return $this
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get()
                ->count();
    }

    public static function syncCreate(array $data){
        dd($data->sku);
        $product = parent::create($data);

        $this->attributeValueRepository->save($data, $product->id);

        return $product;
    }
}