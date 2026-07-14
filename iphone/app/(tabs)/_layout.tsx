import { Text } from 'react-native';
import type { ColorValue } from 'react-native';
import { Tabs } from 'expo-router';

const ACTIVE_COLOR = '#0f7cc0';
const INACTIVE_COLOR = '#7a8a99';
const TAB_BAR_BACKGROUND = '#fbfdff';

const TabIcon = ({ symbol, color }: { symbol: string; color: ColorValue }) => (
  <Text
    style={{
      color,
      fontSize: 18,
      fontWeight: '700',
    }}>
    {symbol}
  </Text>
);

export default function TabLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: ACTIVE_COLOR,
        tabBarInactiveTintColor: INACTIVE_COLOR,
        tabBarStyle: {
          backgroundColor: TAB_BAR_BACKGROUND,
          borderTopColor: '#d7e5ef',
          height: 92,
          paddingTop: 10,
          paddingBottom: 22,
        },
        tabBarLabelStyle: {
          fontSize: 12,
          fontWeight: '700',
        },
      }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'Accueil',
          tabBarIcon: ({ color }) => <TabIcon symbol="⌂" color={color} />,
        }}
      />
      <Tabs.Screen
        name="products"
        options={{
          title: 'Produits',
          tabBarIcon: ({ color }) => <TabIcon symbol="◫" color={color} />,
        }}
      />
      <Tabs.Screen
        name="account"
        options={{
          title: 'Compte',
          tabBarIcon: ({ color }) => <TabIcon symbol="◉" color={color} />,
        }}
      />
    </Tabs>
  );
}
