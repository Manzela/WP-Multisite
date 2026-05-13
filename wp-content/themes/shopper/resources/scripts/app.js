import domReady from '@roots/sage/client/dom-ready';
import { getTranslation } from './utils/translations';

import Alpine from 'alpinejs'
import collapse from '@alpinejs/collapse'
Alpine.plugin(collapse)
window.Alpine = Alpine
 
Alpine.start()

// Make getTranslation globally available
window.getTranslation = getTranslation;

//import './policies-accordion';
import './sidebar-filter';
import './product';
import './gallery';
import './cartpopup';
import './my-account';
import './cart';
import './popup-message';

/**
 * Application entrypoint
 */
domReady(async () => {
  // ...
});

/**
 * @see {@link https://webpack.js.org/api/hot-module-replacement/}
 */
if (import.meta.webpackHot) import.meta.webpackHot.accept(console.error);
