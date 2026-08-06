import js from '@eslint/js';
import { relative } from 'node:path';
import reactHooks from 'eslint-plugin-react-hooks';
import reactRefresh from 'eslint-plugin-react-refresh';
import tseslint from 'typescript-eslint';

const featureEntrypoints = new Set(['publicApi', 'adminApi', 'typesApi', 'uiApi']);

const resolveSourceFeature = (filename) => {
  const location = relative(import.meta.dirname, filename).replaceAll('\\', '/');
  const match = location.match(/^src\/features\/([^/]+)\//);

  return match?.[1] ?? null;
};

const architecturePlugin = {
  rules: {
    'feature-boundaries': {
      meta: {
        type: 'problem',
        docs: {
          description: 'enforce shared and feature import boundaries',
        },
        messages: {
          featureBoundary:
            'Les imports inter-features doivent passer par publicApi.ts/adminApi.ts/typesApi.ts/uiApi.ts.',
          sharedBoundary: 'shared ne doit jamais dépendre des fonctionnalités.',
        },
        schema: [],
      },
      create(context) {
        const filename = context.filename ?? context.getFilename();
        const location = relative(import.meta.dirname, filename).replaceAll('\\', '/');
        const sourceFeature = resolveSourceFeature(filename);
        const isSharedFile = location.startsWith('src/shared/');

        const checkSpecifier = (node, value) => {
          if (typeof value !== 'string' || !value.startsWith('@/features/')) return;
          if (value.endsWith('.css')) return;

          const [, targetFeature = '', targetPath = ''] =
            value.match(/^@\/features\/([^/]+)(?:\/([^/]+))?/) ?? [];

          if (isSharedFile) {
            context.report({ node, messageId: 'sharedBoundary' });
            return;
          }

          if (
            sourceFeature &&
            targetFeature &&
            sourceFeature !== targetFeature &&
            !featureEntrypoints.has(targetPath)
          ) {
            context.report({ node, messageId: 'featureBoundary' });
          }
        };

        return {
          ImportDeclaration(node) {
            checkSpecifier(node.source, node.source.value);
          },
          ExportAllDeclaration(node) {
            checkSpecifier(node.source, node.source.value);
          },
          ExportNamedDeclaration(node) {
            if (node.source) checkSpecifier(node.source, node.source.value);
          },
          ImportExpression(node) {
            if (node.source.type === 'Literal') {
              checkSpecifier(node.source, node.source.value);
            }
          },
        };
      },
    },
  },
};

export default tseslint.config(
  {
    ignores: ['dist', '.ssg', 'node_modules'],
  },
  js.configs.recommended,
  ...tseslint.configs.recommended,
  {
    files: ['**/*.{ts,tsx}'],
    languageOptions: {
      parserOptions: {
        projectService: true,
        tsconfigRootDir: import.meta.dirname,
      },
    },
    plugins: {
      architecture: architecturePlugin,
      'react-hooks': reactHooks,
      'react-refresh': reactRefresh,
    },
    rules: {
      'architecture/feature-boundaries': 'error',
      ...reactHooks.configs.recommended.rules,
      '@typescript-eslint/no-explicit-any': 'off',
      '@typescript-eslint/no-unused-vars': ['warn', {
        argsIgnorePattern: '^_',
        varsIgnorePattern: '^_',
        caughtErrorsIgnorePattern: '^_',
      }],
      'no-empty': ['warn', { allowEmptyCatch: true }],
      'preserve-caught-error': 'off',
      'react-hooks/exhaustive-deps': 'off',
      'react-hooks/immutability': 'off',
      'react-hooks/static-components': 'off',
      'react-hooks/set-state-in-effect': 'off',
      'react-refresh/only-export-components': 'off',
      'no-restricted-syntax': [
        'error',
        {
          selector: "CallExpression[callee.object.name='Math'][callee.property.name='random']",
          message: 'Utiliser shared/lib/random pour une génération compatible crypto.getRandomValues.',
        },
        {
          selector: "NewExpression[callee.name='MutationObserver']",
          message: 'Préférer des rôles ARIA déclaratifs via les composants UI partagés.',
        },
      ],
    },
  },
);
