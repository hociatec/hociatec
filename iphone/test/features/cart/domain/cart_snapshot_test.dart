import 'package:flutter_test/flutter_test.dart';
import 'package:hociatec_mobile/features/cart/domain/cart_snapshot.dart';

void main() {
  test('parses cart snapshot and aggregates quantity', () {
    final snapshot = CartSnapshot.fromJson(<String, dynamic>{
      'token': 'abc123',
      'items': <Map<String, dynamic>>[
        <String, dynamic>{
          'id': 1,
          'quantity': 2,
          'product': <String, dynamic>{'id': 10},
        },
        <String, dynamic>{
          'id': 2,
          'quantity': 1,
          'product': <String, dynamic>{'id': 11},
        },
      ],
    });

    expect(snapshot.token, 'abc123');
    expect(snapshot.totalQuantity, 3);
    expect(snapshot.containsProduct(10), isTrue);
    expect(snapshot.containsProduct(99), isFalse);
    expect(snapshot.quantityForProduct(10), 2);
  });

  test('uses explicit total quantity when present', () {
    final snapshot = CartSnapshot.fromJson(<String, dynamic>{
      'token': 'abc123',
      'totalQuantity': 9,
      'items': <Map<String, dynamic>>[
        <String, dynamic>{
          'id': 1,
          'quantity': 2,
          'product': <String, dynamic>{'id': 10},
        },
      ],
    });

    expect(snapshot.totalQuantity, 9);
  });
}
