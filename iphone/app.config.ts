import type { ExpoConfig } from 'expo/config';

const apiBaseUrl = process.env.EXPO_PUBLIC_API_BASE_URL ?? 'https://api.hociatec.fr';
const websiteUrl = process.env.EXPO_PUBLIC_WEBSITE_URL ?? 'https://hociatec.fr';

const config: ExpoConfig = {
  name: 'Hociatec',
  slug: 'hociatec-iphone',
  version: '1.0.0',
  orientation: 'portrait',
  icon: './assets/images/icon.png',
  scheme: 'hociatec',
  userInterfaceStyle: 'light',
  runtimeVersion: {
    policy: 'appVersion',
  },
  ios: {
    supportsTablet: true,
    bundleIdentifier: 'fr.hociatec.iphone',
    buildNumber: '1',
    infoPlist: {
      ITSAppUsesNonExemptEncryption: false,
    },
  },
  android: {
    adaptiveIcon: {
      backgroundColor: '#E6F4FE',
      foregroundImage: './assets/images/android-icon-foreground.png',
      backgroundImage: './assets/images/android-icon-background.png',
      monochromeImage: './assets/images/android-icon-monochrome.png',
    },
    predictiveBackGestureEnabled: false,
    versionCode: 1,
  },
  web: {
    bundler: 'metro',
    output: 'static',
    favicon: './assets/images/favicon.png',
  },
  plugins: [
    'expo-router',
    'expo-dev-client',
    [
      'expo-splash-screen',
      {
        image: './assets/images/splash-icon.png',
        resizeMode: 'contain',
        backgroundColor: '#ffffff',
      },
    ],
  ],
  experiments: {
    typedRoutes: true,
  },
  extra: {
    apiBaseUrl,
    websiteUrl,
    eas: {
      projectId: process.env.EXPO_PUBLIC_EAS_PROJECT_ID ?? undefined,
    },
  },
};

export default config;
