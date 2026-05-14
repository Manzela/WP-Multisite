<style>
/* Hide the up and down arrows in number input */
.quantity-input::-webkit-inner-spin-button,
.quantity-input::-webkit-outer-spin-button {
    -webkit-appearance: none; /* For Chrome/Safari */
    margin: 0; /* Reset margin */
}

.quantity-input {
    -moz-appearance: textfield; /* For Firefox */
}
</style>

@php
    global $product;
    $max_quantity = $product->get_max_purchase_quantity();
    if($max_quantity < 0) // no quantity was set
        $max_quantity = 9999;
@endphp


<div x-data="{ 
    quantity: 1, 
    max: <?php echo $max_quantity ?>,
    updateAddToCartQuantity() {
        const addToCartBtn = document.querySelector('.single_add_to_cart_button');
        if (addToCartBtn) {
            addToCartBtn.setAttribute('data-quantity', this.quantity);
        }
    }
}" 
    class="flex items-center border border-gray-300 rounded-lg w-[80px] h-[48px] mx-0 my-1 {{ is_rtl()? 'rounded-tr-none rounded-br-none' : 'rounded-tl-none rounded-bl-none' }}"
    x-init="$watch('quantity', value => updateAddToCartQuantity())"
>
    <button aria-label="Decrease quantity" 
        class="quantity-button minus bg-transparent border-0 shadow-none text-current cursor-pointer flex items-center justify-center text-sm font-normal m-0 min-w-[30px] opacity-60 rounded-r-md order-1"
        x-on:click="if (quantity > 1) quantity--" 
        :disabled="quantity <= 1" 
        :class="{'opacity-60 cursor-default': quantity <= 1}">−</button>

    <input class="quantity-input flex-1 appearance-textfield bg-transparent border-0 shadow-none text-current text-center text-lg font-semibold leading-none m-0 min-w-[16px] order-2 py-1 px-0"
        type="number" step="1" min="1" :max="max" 
        x-model.number="quantity" 
        aria-label="Product quantity" 
        @wheel.prevent
    />

    <button aria-label="Increase quantity" 
        class="quantity-button plus bg-transparent border-0 shadow-none text-current cursor-pointer flex items-center justify-center text-sm font-normal m-0 min-w-[30px] opacity-60 rounded-l-md order-3" 
        x-on:click="if (quantity < max) quantity++" 
        :disabled="quantity >= max">+</button>
</div>

<!-- Quantity is synced to .single_add_to_cart_button via Alpine's data-quantity binding above -->