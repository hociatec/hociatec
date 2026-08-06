module.exports = {
  extends: ['stylelint-config-standard'],
  plugins: ['stylelint-order'],
  rules: {
    'at-rule-no-unknown': [
      true,
      {
        ignoreAtRules: ['apply', 'custom-variant', 'layer', 'plugin', 'tailwind', 'theme'],
      },
    ],
    'at-rule-empty-line-before': null,
    'declaration-no-important': [
      true,
      {
        severity: 'warning',
      },
    ],
    'color-function-notation': null,
    'color-function-alias-notation': null,
    'alpha-value-notation': null,
    'color-hex-length': null,
    'custom-property-empty-line-before': null,
    'declaration-property-value-disallowed-list': null,
    'declaration-block-no-redundant-longhand-properties': null,
    'declaration-property-value-keyword-no-deprecated': null,
    'font-family-name-quotes': null,
    'hue-degree-notation': null,
    'import-notation': 'string',
    'keyframes-name-pattern': null,
    'lightness-notation': null,
    'media-feature-range-notation': null,
    'no-descending-specificity': null,
    'no-duplicate-selectors': null,
    'order/properties-alphabetical-order': [
      true,
      {
        severity: 'warning',
      },
    ],
    'property-no-deprecated': null,
    'rule-empty-line-before': null,
    'selector-class-pattern': null,
    'value-keyword-case': null,
  },
};
