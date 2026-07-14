import { Linking, Pressable, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { API_BASE_URL, WEBSITE_URL } from '@/src/config/app';

export default function AccountScreen() {
  const openUrl = async (url: string) => {
    await Linking.openURL(url);
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <View style={styles.screen}>
        <View style={styles.card}>
          <Text style={styles.eyebrow}>Compte</Text>
          <Text style={styles.title}>Espace client Hociatec</Text>
          <Text style={styles.text}>
            Cette base native est prête pour accueillir la connexion mobile, le profil, les devis,
            les commandes et les rendez-vous du client.
          </Text>
          <View style={styles.statusBadge}>
            <Text style={styles.statusBadgeText}>État actuel : portail mobile en préparation</Text>
          </View>
          <View style={styles.infoBox}>
            <Text style={styles.infoTitle}>Portails disponibles</Text>
            <Text style={styles.infoText}>Site public : {WEBSITE_URL}</Text>
            <Text style={styles.infoText}>API : {API_BASE_URL}</Text>
          </View>
          <View style={styles.actions}>
            <Pressable style={styles.primaryButton} onPress={() => void openUrl(`${WEBSITE_URL}/login`)}>
              <Text style={styles.primaryButtonText}>Ouvrir la connexion web</Text>
            </Pressable>
            <Pressable style={styles.secondaryButton} onPress={() => void openUrl(WEBSITE_URL)}>
              <Text style={styles.secondaryButtonText}>Ouvrir le site Hociatec</Text>
            </Pressable>
          </View>
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#eef6fb',
  },
  screen: {
    flex: 1,
    padding: 20,
    backgroundColor: '#eef6fb',
  },
  card: {
    borderRadius: 28,
    backgroundColor: '#0c2436',
    padding: 24,
    gap: 12,
  },
  eyebrow: {
    color: '#88d6ff',
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 1.8,
    textTransform: 'uppercase',
  },
  title: {
    color: '#ffffff',
    fontSize: 28,
    lineHeight: 32,
    fontWeight: '900',
  },
  text: {
    color: '#d5e7f3',
    fontSize: 15,
    lineHeight: 23,
  },
  statusBadge: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    backgroundColor: '#17364c',
    paddingHorizontal: 14,
    paddingVertical: 8,
    marginTop: 4,
  },
  statusBadgeText: {
    color: '#f3fbff',
    fontSize: 13,
    fontWeight: '700',
  },
  infoBox: {
    borderRadius: 18,
    backgroundColor: '#143247',
    padding: 16,
    gap: 6,
  },
  infoTitle: {
    color: '#ffffff',
    fontSize: 15,
    fontWeight: '800',
  },
  infoText: {
    color: '#d5e7f3',
    fontSize: 13,
    lineHeight: 19,
  },
  actions: {
    gap: 10,
    marginTop: 4,
  },
  primaryButton: {
    borderRadius: 18,
    backgroundColor: '#38bdf8',
    paddingHorizontal: 16,
    paddingVertical: 14,
    alignItems: 'center',
  },
  primaryButtonText: {
    color: '#0c2436',
    fontSize: 14,
    fontWeight: '800',
  },
  secondaryButton: {
    borderRadius: 18,
    borderWidth: 1,
    borderColor: '#3c5d74',
    paddingHorizontal: 16,
    paddingVertical: 14,
    alignItems: 'center',
  },
  secondaryButtonText: {
    color: '#f3fbff',
    fontSize: 14,
    fontWeight: '700',
  },
});
