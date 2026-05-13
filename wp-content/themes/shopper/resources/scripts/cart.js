document.addEventListener('DOMContentLoaded', function() {
    const originalFetch = window.fetch;
    window.fetch = async function(...args) {
        const [resource, config] = args;
        const response = await originalFetch(resource, config);

        // Handle both batch API endpoints
        if (typeof resource === 'string' && 
            (resource.includes('/wc/store/v1/batch') || 
             resource.includes('/wp-json/wc/store/v1/batch'))) {
            try {
                const responseData = await response.clone().json();
                
                // Update cart count
                if (responseData.responses && responseData.responses[0]?.body) {
                    const itemsCount = responseData.responses[0].body.items_count;
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = itemsCount;
                        if (itemsCount === 0) {
                            cartCountElement.classList.add('hidden');
                        } else {
                            cartCountElement.classList.remove('hidden');
                        }
                    }

                    // Handle empty cart state
                    if (itemsCount === 0 && document.body.classList.contains('woocommerce-cart')) {
                        const emptyCartResponse = await fetch('/cart/', {
                            headers: {
                                'Accept': 'text/html'
                            }
                        });
                        const emptyCartHtml = await emptyCartResponse.text();
                        
                        // Extract the main content area from the response
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(emptyCartHtml, 'text/html');
                        const mainContent = doc.querySelector('main');  // or your main content selector
                        
                        if (mainContent) {
                            // Replace the current cart content with empty cart content
                            const currentMain = document.querySelector('main');  // or your main content selector
                            if (currentMain) {
                                currentMain.innerHTML = mainContent.innerHTML;
                                // Update the URL without reloading
                                window.history.pushState({}, '', '/cart/');
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Error handling cart update:', error);
            }
        }

        return response;
    };
});


// ... existing imports ...

/**
 * Handle WooCommerce Store API requests and cart state
 * make sure to redirect smoothly to the cart page
 */
const originalFetch = window.fetch;
window.fetch = async function(...args) {
  const [resource, config] = args;
  
  // Check if this is a Store API batch request
  if (typeof resource === 'string' && resource.includes('/wp-json/wc/store/v1/batch')) {
    try {
      const response = await originalFetch(resource, config);
      const data = await response.clone().json();
      
      // Check if the cart is empty after the batch operation
      if (data.responses && data.responses.some(r => r.body && r.body.items_count === 0)) {
        // If we're on the cart page, replace content with empty cart template
        if (document.body.classList.contains('woocommerce-cart')) {
          const emptyCartResponse = await fetch('/cart/', {
            headers: {
              'Accept': 'text/html'
            }
          });
          const emptyCartHtml = await emptyCartResponse.text();
          
          // Extract the main content area from the response
          const parser = new DOMParser();
          const doc = parser.parseFromString(emptyCartHtml, 'text/html');
          const mainContent = doc.querySelector('.main');
          
          if (mainContent) {
            // Replace the current cart content with empty cart content
            document.querySelector('.main').innerHTML = mainContent.innerHTML;
            
            // Update the URL without reloading
            window.history.pushState({}, '', '/cart/');
          }
        }
      }
      
      return response;
    } catch (error) {
      console.error('Error intercepting Store API request:', error);
      return originalFetch(resource, config);
    }
  }
  
  return originalFetch(resource, config);
};
