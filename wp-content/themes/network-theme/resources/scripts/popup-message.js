// Cookie helper functions are available globally from event-tracker-plugin:
// getCookie, setCookie, deleteCookie, hasMarketingConsent

// Wait for DOM and ensure functions are available
function initPopupWhenReady() {
  if (typeof hasMarketingConsent !== 'function') {
    // Function not available yet, wait and try again
    setTimeout(initPopupWhenReady, 50);
    return;
  }
  
  // If no marketing consent, don't proceed with popup logic
  if (!hasMarketingConsent()) {
    return;
  }
  
  initPopup();
}

function initPopup() {

  // Original popup logic continues only if marketing consent is given
  const popup = document.getElementById('popup');
  const closeButton = document.getElementById('close-popup-message');
  
  const firstPopupTime = getCookie('firstPopupTime');
  const currentTime = new Date().getTime();
  const twentyFourHours = 24 * 60 * 60 * 1000; // 24 hours in milliseconds

  // Check if we need to reset (if 24 hours have passed since first show)
  if (firstPopupTime && currentTime - parseInt(firstPopupTime) > twentyFourHours) {
    // Reset everything if 24 hours have passed since first show
    deleteCookie('firstPopupTime');
    deleteCookie('popupShownCount');
  }

  // Check if we should show the popup
  const popupShownCount = parseInt(getCookie('popupShownCount') || '0');
  const maxShows = parseInt(popup?.dataset.maxShows || '0');
  
  if (popupShownCount < maxShows) {
    // Show popup after 2 seconds
    setTimeout(() => {
      popup?.classList.remove('hidden');
      
      // If this is the first show, set the firstPopupTime
      if (!firstPopupTime) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (1 * 24 * 60 * 60 * 1000)); // 1 day
        setCookie('firstPopupTime', currentTime.toString(), expires);
      }
      
      // Increment and save the shown count
      const expires = new Date();
      expires.setTime(expires.getTime() + (1 * 24 * 60 * 60 * 1000)); // 1 day
      setCookie('popupShownCount', (popupShownCount + 1).toString(), expires);
    }, 2000);
  }

  // Close popup when clicking the close button
  closeButton?.addEventListener('click', () => {
    popup?.classList.add('hidden');
  });

  // Close popup when clicking outside
  popup?.addEventListener('click', (e) => {
    if (e.target === popup) {
      popup.classList.add('hidden');
    }
  });
}

// Start when DOM is ready
document.addEventListener('DOMContentLoaded', initPopupWhenReady);
