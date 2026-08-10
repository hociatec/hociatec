import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';
import 'package:hociatec_mobile/features/cart/domain/cart_snapshot.dart';

class CartRepository {
  const CartRepository(this._client);

  final ApiClient _client;

  Future<CartSnapshot> fetch() async {
    final response = await _client.get<Map<String, dynamic>>('/api/public/cart');
    return _readCart(response.data, 'Impossible de charger le panier.');
  }

  Future<CartSnapshot> addItem(int productId, {int quantity = 1}) async {
    final response = await _client.post<Map<String, dynamic>>(
      '/api/public/cart/items',
      data: <String, dynamic>{
        'productId': productId,
        'quantity': quantity,
      },
    );

    return _readCart(response.data, "Impossible d'ajouter le produit au panier.");
  }

  Future<CartSnapshot> removeItem(int productId) async {
    final response = await _client.delete<Map<String, dynamic>>(
      '/api/public/cart/items/$productId',
    );

    return _readCart(response.data, 'Impossible de retirer le produit du panier.');
  }

  CartSnapshot _readCart(Object? raw, String fallbackMessage) {
    final payload = unwrapApiDataMap(raw, fallbackMessage);
    final cart = payload['cart'];

    if (cart is Map<String, dynamic>) {
      return CartSnapshot.fromJson(cart);
    }

    throw ApiException(fallbackMessage);
  }
}

final cartRepositoryProvider = Provider<CartRepository>((ref) {
  final client = ref.watch(apiClientProvider);
  return CartRepository(client);
});
