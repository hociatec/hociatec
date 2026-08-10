import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AuthCookies {
  const AuthCookies({
    required this.accessToken,
    required this.refreshToken,
  });

  final String? accessToken;
  final String? refreshToken;

  bool get hasAccessToken => accessToken != null && accessToken!.isNotEmpty;
  bool get hasRefreshToken => refreshToken != null && refreshToken!.isNotEmpty;
}

class AuthSessionStore {
  AuthSessionStore(this._preferences);

  static const _accessCookieKey = 'hociatec.auth.access_cookie';
  static const _refreshCookieKey = 'hociatec.auth.refresh_cookie';

  final SharedPreferences _preferences;

  AuthCookies readCookies() {
    return AuthCookies(
      accessToken: _preferences.getString(_accessCookieKey),
      refreshToken: _preferences.getString(_refreshCookieKey),
    );
  }

  Future<void> writeCookies({
    String? accessToken,
    String? refreshToken,
  }) async {
    if (accessToken != null) {
      await _preferences.setString(_accessCookieKey, accessToken);
    }

    if (refreshToken != null) {
      await _preferences.setString(_refreshCookieKey, refreshToken);
    }
  }

  Future<void> clear() async {
    await _preferences.remove(_accessCookieKey);
    await _preferences.remove(_refreshCookieKey);
  }
}

final authSessionStoreProvider = Provider<AuthSessionStore>((ref) {
  throw UnimplementedError('AuthSessionStore must be overridden at bootstrap.');
});
