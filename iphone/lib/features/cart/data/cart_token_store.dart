import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

class CartTokenStore {
  CartTokenStore();

  static const String _tokenKey = 'hociatec.cart_token';

  Future<String?> read() async {
    final preferences = await SharedPreferences.getInstance();
    return preferences.getString(_tokenKey);
  }

  Future<void> write(String token) async {
    final preferences = await SharedPreferences.getInstance();
    await preferences.setString(_tokenKey, token);
  }

  Future<void> clear() async {
    final preferences = await SharedPreferences.getInstance();
    await preferences.remove(_tokenKey);
  }
}

final cartTokenStoreProvider = Provider<CartTokenStore>((ref) {
  return CartTokenStore();
});
