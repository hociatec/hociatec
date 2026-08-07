import type { Language } from '@/shared/i18n/LanguageProvider';
import { useTranslation } from '@/shared/i18n/LanguageProvider';

export const SiteLanguageSwitcher = () => {
  const { language, setLanguage, options, t } = useTranslation();

  const switchLanguage = (nextLanguage: Language) => {
    setLanguage(nextLanguage);
  };

  return (
    <div className="site-header__language-switcher" aria-label={t('header.language.switcherAria')}>
      {options.map((option) => {
        const isActive = option.code === language;

        return (
          <button
            key={option.code}
            type="button"
            className={`site-header__language-switcher-button${
              isActive ? ' site-header__language-switcher-button--active' : ''
            }`}
            onClick={() => switchLanguage(option.code)}
            aria-pressed={isActive}
            aria-label={t(option.ariaLabelKey)}
            title={t(option.labelKey)}
            disabled={isActive}
          >
            <span className="site-header__language-switcher-flag" aria-hidden="true">
              {option.flag}
            </span>
            <span className="sr-only">{t(option.labelKey)}</span>
          </button>
        );
      })}
    </div>
  );
};
