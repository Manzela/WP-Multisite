/**
 * This script is used to handle the gallery of the product.
 * It is used to handle the thumbnail images and the main image.
 * single-product.blade.php is used to display the product. it also serve the variable product template.
 * Author: Edward Ziadeh
 * Date: 2024-11-04
 */

document.addEventListener('DOMContentLoaded', () => {
    if (document.body.classList.contains('single-product')) {
        
        function initProductGallery() {
            const thumbnails = document.querySelectorAll('.gallery-thumbnail');
            const mainImage = document.getElementById('main-product-image');
            let defaultImageUrl = mainImage ? mainImage.src : null;
        
            function updateGalleryImage(imageUrl, isVariation = false) {
                if (!imageUrl || !mainImage) return;

                // Store default image URL if not already stored
                if (!defaultImageUrl) {
                    defaultImageUrl = mainImage.src;
                }

                mainImage.src = imageUrl;
                mainImage.srcset = ''; // Clear srcset to ensure full size image is used
                
                // Update active thumbnail state
                thumbnails.forEach(thumb => {
                    const isMatch = thumb.dataset.fullImageUrl === imageUrl;
                    thumb.classList.toggle('border-[var(--color-primary)]', isMatch);
                    
                    // If this is a variation image and no thumbnail matches, deactivate all thumbnails
                    if (isVariation && !Array.from(thumbnails).some(t => t.dataset.fullImageUrl === imageUrl)) {
                        thumb.classList.remove('border-[var(--color-primary)]');
                    }
                });
            }

            // Reset gallery to default image
            function resetGallery() {
                if (defaultImageUrl) {
                    updateGalleryImage(defaultImageUrl);
                    
                    // Reactivate first thumbnail
                    if (thumbnails.length > 0) {
                        thumbnails.forEach(t => t.classList.remove('border-[var(--color-primary)]'));
                        thumbnails[0].classList.add('border-[var(--color-primary)]');
                    }
                }
            }

            // Handle thumbnail clicks
            thumbnails.forEach(thumb => {
                thumb.addEventListener('click', () => {
                    thumbnails.forEach(t => t.classList.remove('border-[var(--color-primary)]'));
                    thumb.classList.add('border-[var(--color-primary)]');
                    updateGalleryImage(thumb.dataset.fullImageUrl);
                });
            });

            // Set first thumbnail as active by default
            if (thumbnails.length > 0) {
                thumbnails[0].classList.add('border-[var(--color-primary)]');
            }

            // Listen for variation changes
            document.addEventListener('variation_selected', function(e) {
                const variation = e.detail;
                if (variation && variation.image && variation.image.full_src) {
                    updateGalleryImage(variation.image.full_src);
                }
            });
        }

        // Initialize the product gallery
        initProductGallery();
    }
});
