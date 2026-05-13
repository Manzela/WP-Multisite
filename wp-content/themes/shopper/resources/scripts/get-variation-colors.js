document.addEventListener('DOMContentLoaded', async function() {
    // Handle variation buttons
    const buttons = document.querySelectorAll('.variation-button[data-attribute="pa_color"]');
    for(const button of buttons) {
        getDominantColor(button);
    }

    // Handle store page color attributes
    const colorAttributeContainers = document.querySelectorAll('#color-attributes');
    for(const container of colorAttributeContainers) {
        const colorDivs = container.querySelectorAll('div[style*="background-color"]');
        for(const colorDiv of colorDivs) {
            getDominantColor(colorDiv);
        }
    }

    async function getDominantColor(element) {
        // Get the raw CSS value
        const backgroundColor = element.style.backgroundColor;
        if(backgroundColor && isValidColor(backgroundColor))
            return;

        // Get product images based on context
        const imageUrls = element.closest('.variation-button') 
            ? getProductImages() 
            : getStoreProductImage(element);

        console.log("imageUrls", imageUrls);

        if (imageUrls.length > 0) {
            const dominantColor = await getMostDominantColorFromImages(imageUrls);
            console.log('dominantColor', dominantColor);
            if(dominantColor)
                element.style.backgroundColor = dominantColor;
        }
    }

    function getStoreProductImage(element) {
        const imageUrls = [];
        
        // Find the product container
        const productContainer = element.closest('.product');
        if (!productContainer) return imageUrls;

        // Get the product image
        const productImage = productContainer.querySelector('img');
        if (productImage && productImage.src) {
            imageUrls.push(productImage.src);
        }

        return imageUrls;
    }
});

function getProductImages() {
    const imageUrls = [];
    
    // Get the main product image first
    const mainImage = document.querySelector('#main-product-image');
    if (mainImage) {
        const mainImageUrl = mainImage.getAttribute('src');
        if (mainImageUrl) {
            imageUrls.push(mainImageUrl);
        }
    }
    
    // Get all gallery thumbnails that have data-full-image-url
    const galleryThumbnails = document.querySelectorAll('#product-thumbnails-image');
    
    // Add all full-size image URLs from thumbnails
    galleryThumbnails.forEach(thumbnail => {
        const fullSizeUrl = thumbnail.getAttribute('data-full-image-url');
        if (fullSizeUrl) {
            imageUrls.push(fullSizeUrl);
        }
    });

    return imageUrls;
}

function isValidColor(color) {
    const validColors = [
        // Basic colors
        'black', 'white', 'gray', 'red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink', 'brown',
        
        // Extended standard colors
        'silver', 'gold', 'beige', 'maroon', 'olive', 'teal', 'navy',
        
        // Additional valid CSS colors
        'aqua', 'azure', 'bisque', 'coral', 'crimson', 'cyan', 'fuchsia',
        'indigo', 'ivory', 'khaki', 'lavender', 'lime', 'linen', 'magenta',
        'plum', 'salmon', 'sienna', 'tan', 'thistle', 'tomato', 'turquoise',
        'violet'
    ];

    return validColors.includes(color.toLowerCase());
}


async function getMostDominantColorFromImages(imageUrls) {
    const colorFrequencyMap = new Map();

    function updateColorFrequency(colors) {
        colors.forEach(({ color, percentage }) => {
            colorFrequencyMap.set(color, (colorFrequencyMap.get(color) || 0) + parseFloat(percentage));
        });
    }

    // Process all images
    await Promise.all(imageUrls.map(async (imageUrl) => {
        try {
            const colors = await extract_colors_from_image(imageUrl);
            updateColorFrequency(colors);
        } catch (error) {
            console.error(`Error processing image ${imageUrl}:`, error);
        }
    }));

    // Find most dominant color
    let mostDominantColor = null;
    let maxFrequency = 0;

    colorFrequencyMap.forEach((frequency, color) => {
        if (frequency > maxFrequency) {
            maxFrequency = frequency;
            mostDominantColor = color;
        }
    });

    return mostDominantColor;
}

function extract_colors_from_image(imageUrl) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.crossOrigin = "Anonymous";  // Add this to handle CORS
        
        // create a canvas element
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        image.onload = function() {
            // set canvas dimensions to the size of the loaded image
            canvas.width = image.naturalWidth;
            canvas.height = image.naturalHeight;

            // draw the image onto the canvas
            ctx.drawImage(image, 0, 0, image.naturalWidth, image.naturalHeight);

            try {
                // get the image data from the canvas
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;

                // track color frequencies and total colors counted
                const colorMap = new Map();
                let totalColorsCount = 0;
                const whiteThreshold = 220; // define threshold for ignoring whites

                for(let i = 0; i < data.length; i += 4) {
                    const r = data[i];
                    const g = data[i + 1];
                    const b = data[i + 2];

                    // ignore colors considered close to white
                    if(r > whiteThreshold && g > whiteThreshold && b > whiteThreshold) continue;

                    // convert RGB to HEX
                    const hex = `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1).toUpperCase()}`;

                    // count the colors
                    colorMap.set(hex, (colorMap.get(hex) || 0) + 1);
                    totalColorsCount++;
                }

                // sort colors by frequency (dominance)
                const sortedColorList = [...colorMap.entries()]
                    .sort((a, b) => b[1] - a[1])
                    .map(([color, count]) => {
                        // calculate percentage of each color
                        const percentage = ((count / totalColorsCount) * 100).toFixed(2);
                        return { color, percentage };
                    });
                resolve(sortedColorList);
            } catch (error) {
                reject(`Error processing image data: ${error.message}`);
            }
        };

        image.onerror = function() {
            reject('Error loading image');
        };

        // Set the source after setting up event handlers
        image.src = imageUrl;
    });
}