jQuery(document).ready(function($) {
    if( window.location.pathname.endsWith('my-account') || window.location.pathname.endsWith('my-account/') ) {

        // get user info:
        const firstName = userInfo.first_name;
        const lastName = userInfo.last_name;
        const email = userInfo.email;
        const shippingAddress = userInfo.shipping_address;

        // modify the html elements
        var content = $('.woocommerce-MyAccount-content');
        var firstP = content.children('p').eq(0);
        var secondP = content.children('p').eq(1);

        if(shippingAddress)
            firstP.after(`
                <p class="border-t pt-2">
                    <div class="flex flex-col">
                        <span>${shippingAddress.first_name} ${shippingAddress.last_name}</span>
                        <span>${shippingAddress.address_1}</span>
                        <span>${shippingAddress.address_2}</span>
                        <span>${shippingAddress.city}</span>
                        <span>${shippingAddress.postcode}</span>
                        <span>${shippingAddress.country}</span>
                        <span>${shippingAddress.state}</span>
                    </div>
                </p>
            `);
        
        firstP.html(`${getTranslation('Hi', 'sage')} <strong>${firstName} ${lastName}</strong>  (${email})`);
        secondP.replaceWith('');
    }

});


jQuery(document).ready(function($) {
    if(window.location.pathname.includes('my-account/edit-address')) {
        // get the edit links and replace the text with an edit-icon
        $('.woocommerce-MyAccount-content header.woocommerce-Address-title a.edit').html(
            `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>`
        );
    }
});


jQuery(document).ready(function($) {
    if (window.location.pathname.includes('my-account/view-order')) {

        // remove order number from the title
        $('.page-header h1').text(getTranslation('Order', 'woocommerce'));
        
        var i=0;
        // add image next to the product's name
        $('.woocommerce-table--order-details td.woocommerce-table__product-name').each(function() {
            var productContent = $(this).html(); // get the current HTML content (name, quantity, attributes etc.)
            var productName = $(this).children('a').eq(0)[0].text;

            // search for the matching image
            const isRTL = $('body').hasClass('rtl');
            ordersData.forEach(product => {
                if(product.product_name === productName) {
                    $(this).html(`
                        <div class="flex">
                            <div><img src=${product.product_image} alt="Product Image" class="max-w-[100px] max-h-[100px] ${isRTL? 'ml-4' : 'mr-4'}" /></div>
                            <div>${productContent}</div>
                        </div>
                    `);
                }
            });
        });
    }
});
