// Show cart popup with product details
function showCartPopup(productId, variationId = '') {
  const popup = document.getElementById('cart-popup');
  const productImage = popup.querySelector('.product-image');
  const productTitle = popup.querySelector('.product-title');
  const productVariation = popup.querySelector('.product-variation');
  const productQuantity = popup.querySelector('.product-quantity');
  const productPrice = popup.querySelector('.product-price');

  // Get the last added product data from the cart fragment
  jQuery.ajax({
    url: wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'get_cart_item'),
    type: 'POST',
    data: {
      product_id: productId,
      variation_id: variationId
    },
    success: function(response) {
      if (response.success && response.data) {
        const item = response.data;

        // Set product image
        if (item.image) {
          productImage.innerHTML = `<img src="${item.image}" alt="${item.title}" class="w-full h-full object-cover">`;
        }

        // Set product details
        productTitle.textContent = item.title;
        productPrice.innerHTML = 
          document.querySelector('#single-product .variation-price')?.querySelector('ins span')?.textContent // sale price
        || document.querySelector('#single-product .variation-price')?.querySelector('.woocommerce-Price-amount')?.textContent // regular price
        || item.price;
        
        // Get the currently added quantity (not from the cart)
        const quantityInput = document.querySelector('.quantity-input');
        const currentQuantity = parseInt(quantityInput?.value) || 1;

        // Show quantity if more than 1
        if(currentQuantity > 1) {
          productQuantity.textContent = `${getTranslation('Quantity', 'woocommerce')}: ${currentQuantity}`;
          productQuantity.classList.remove('hidden');
        } else {
          productQuantity.classList.add('hidden');
        }

        // Show variations if they exist
        if (item.variation_data) {
          const variations = [];
          Object.entries(item.variation_data).forEach(([key, value]) => {
            if (value) {
              variations.push(`${key}: ${value}`);
            }
          });
          if (variations.length) {
            productVariation.innerHTML = variations.join('<br>');
            productVariation.classList.remove('hidden');
          }
        } else {
          productVariation.classList.add('hidden');
        }

        // Show the popup
        popup.classList.remove('hidden');
      }
    }
  });
}

// Hide cart popup
function hideCartPopup() {
  const popup = document.getElementById('cart-popup');
  popup.classList.add('hidden');
}

// Setup event listeners
document.addEventListener('DOMContentLoaded', () => {
  const popup = document.getElementById('cart-popup');
  const continueButton = document.getElementById('continue-shopping');
  const closeButton = document.getElementById('close-popup');

  // Handle continue shopping button
  continueButton?.addEventListener('click', hideCartPopup);
  closeButton?.addEventListener('click', hideCartPopup);

  // Close popup when clicking outside
  popup?.addEventListener('click', (e) => {
    if (e.target === popup) {
      hideCartPopup();
    }
  });

  // Show popup after successful add to cart
  jQuery(document.body).on('added_to_cart', (e, fragments, cart_hash, $button) => {
    showCartPopup($button.data('product_id'), $button.data('variation_id'));
  });
});