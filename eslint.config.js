// Flat ESLint config (CommonJS) for ESLint v9+
// Using CommonJS to avoid Node ESM warning without setting "type": "module".
/* eslint-env node */
/* global require */
const js = require('@eslint/js');

module.exports = [
  // Global ignores (flat config replacement for .eslintignore)
  {
    ignores: [
      'vendor/**',
      '**/vendor/**',
      'node_modules/**',
      '**/*.min.js',
      '**/*.min.js.map',
      'coverage/**',
      'index.min.js',
      'index.min.js.iife.js.map'
    ]
  },
  js.configs.recommended,
  {
    files: ['**/*.js'],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'script',
      globals: {
        Drupal: 'readonly',
        jQuery: 'readonly',
        Vue: 'readonly',
        ScbdDrupalScbdFieldJs: 'readonly',
        document: 'readonly',
        window: 'readonly',
        console: 'readonly',
        module: 'readonly'
      }
    },
    linterOptions: {
      reportUnusedDisableDirectives: 'error'
    },
    rules: {
      semi: ['error', 'always'],
      quotes: ['error', 'single'],
      'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }]
    }
  }
  ,
  {
    files: ['**/__tests__/**/*.js','**/*.test.js'],
    languageOptions: {
      globals: {
        describe: 'readonly',
        test: 'readonly',
        expect: 'readonly',
        jest: 'readonly',
        global: 'readonly',
        module: 'readonly',
        require: 'readonly'
      }
    }
  },
  {
    files: ['**/*.js'],
    rules: {
      camelcase: ['error', {
        properties: 'never',
        ignoreDestructuring: true,
        allow: ['^scbd_', '^drupal_', '^field_']
      }]
    }
  }
];
