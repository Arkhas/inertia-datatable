import { i18n, updateTranslations } from './useTranslation';

// Function to initialize i18n with translations from Laravel
export const initializeI18n = (config: any) => {
  if (!config) {
    console.warn('No config found for datatable');
    return;
  }

  // Check for translations from Laravel's lang directory
  if (config.translations) {
    // Update i18next with translations from Laravel's lang directory
    updateTranslations(config.translations);
  } else {
    console.warn('No translations found in Laravel lang directory');
  }

  // Set the language based on HTML lang attribute or default to English
  const lang = document.documentElement.lang || 'en';
  if (i18n.language !== lang && config.translations && Object.keys(config.translations).includes(lang)) {
    i18n.changeLanguage(lang);
  }
};

// Export i18n instance for direct access
export { i18n };
