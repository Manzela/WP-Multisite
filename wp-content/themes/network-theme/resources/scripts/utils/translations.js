/**
 * Generic translation handler that tries multiple translation sources
 * using the translations defined in the PHP file "app/wc-template-hooks.php"
 * @param {string} text - Text to translate
 * @param {string} domain - Translation domain
 * @returns {string} - Translated text
 */
export function getTranslation(text, domain) {
  return translations[domain][text];
};