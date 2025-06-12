import { useTranslation as useI18nTranslation } from 'react-i18next';
import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

// Initialize i18next if it hasn't been initialized yet
if (!i18n.isInitialized) {
  i18n
    .use(initReactI18next)
    .init({
      resources: {
        [document.documentElement.lang || 'en']: {
          translation: {
          } // Will be populated from Laravel config
        }
      },
      lng: document.documentElement.lang || 'en', // Use HTML lang attribute or default to English
      fallbackLng: 'en',
      interpolation: {
        escapeValue: false // React already escapes values
      }
    });
}

// Function to update translations from Laravel config
export const updateTranslations = (translations: Record<string, Record<string, string>>) => {
  Object.entries(translations).forEach(([lang, resources]) => {
    i18n.addResourceBundle(lang, 'translation', resources, true, true);
  });
};

// Custom hook that wraps the i18next useTranslation hook
export const useTranslation = () => {
  const { t, i18n: i18nInstance } = useI18nTranslation();

  // Enhanced translation function that handles Laravel-style placeholders
  const translate = (key: string, params?: Record<string, any>) => {
    // Convert Laravel-style placeholders to i18next style if needed
    const i18nextParams: Record<string, any> = {};

    if (params) {
      Object.entries(params).forEach(([paramKey, paramValue]) => {
        i18nextParams[paramKey] = paramValue;
      });
    }

    return t(key, i18nextParams);
  };

  return {
    t: translate,
    i18n: i18nInstance
  };
};

// Export the i18n instance for direct access
export { i18n };
