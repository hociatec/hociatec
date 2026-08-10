class CartSnapshot {
  const CartSnapshot({
    required this.token,
    required this.items,
    required this.totalQuantity,
  });

  factory CartSnapshot.fromJson(Map<String, dynamic> json) {
    final items = (json['items'] as List? ?? const <dynamic>[])
        .whereType<Map>()
        .map((item) => CartItemSnapshot.fromJson(item.cast<String, dynamic>()))
        .toList(growable: false);

    return CartSnapshot(
      token: (json['token'] as String?)?.trim() ?? '',
      items: items,
      totalQuantity: (json['totalQuantity'] as num?)?.toInt() ?? items.fold<int>(0, (sum, item) => sum + item.quantity),
    );
  }

  final String token;
  final List<CartItemSnapshot> items;
  final int totalQuantity;

  bool containsProduct(int productId) {
    return items.any((item) => item.productId == productId && item.quantity > 0);
  }

  int quantityForProduct(int productId) {
    return items
        .where((item) => item.productId == productId)
        .fold<int>(0, (sum, item) => sum + item.quantity);
  }
}

class CartItemSnapshot {
  const CartItemSnapshot({
    required this.id,
    required this.productId,
    required this.quantity,
  });

  factory CartItemSnapshot.fromJson(Map<String, dynamic> json) {
    final product = json['product'];
    final productMap = product is Map<String, dynamic> ? product : const <String, dynamic>{};

    return CartItemSnapshot(
      id: (json['id'] as num?)?.toInt() ?? 0,
      productId: (productMap['id'] as num?)?.toInt() ?? 0,
      quantity: (json['quantity'] as num?)?.toInt() ?? 0,
    );
  }

  final int id;
  final int productId;
  final int quantity;
}
