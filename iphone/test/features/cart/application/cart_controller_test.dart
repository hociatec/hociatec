import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/features/cart/application/cart_action_result.dart';
import 'package:hociatec_mobile/features/cart/application/cart_controller.dart';
import 'package:hociatec_mobile/features/cart/data/cart_repository.dart';
import 'package:hociatec_mobile/features/cart/domain/cart_snapshot.dart';

class _FakeCartRepository extends CartRepository {
  _FakeCartRepository({
    required this.fetchResult,
    this.addResult,
    this.addError,
    this.removeResult,
  }) : super(ApiClient(Dio()));

  final CartSnapshot fetchResult;
  final CartSnapshot? addResult;
  final Object? addError;
  final CartSnapshot? removeResult;

  @override
  Future<CartSnapshot> fetch() async => fetchResult;

  @override
  Future<CartSnapshot> addItem(int productId, {int quantity = 1}) async {
    if (addError != null) throw addError!;
    return addResult ?? fetchResult;
  }

  @override
  Future<CartSnapshot> removeItem(int productId) async {
    return removeResult ?? fetchResult;
  }
}

void main() {
  CartSnapshot snapshot({
    required int productId,
    required int quantity,
  }) {
    return CartSnapshot(
      token: 'token',
      totalQuantity: quantity,
      items: <CartItemSnapshot>[
        CartItemSnapshot(
          id: 1,
          productId: productId,
          quantity: quantity,
        ),
      ],
    );
  }

  test('toggleProduct adds item when product is absent', () async {
    final container = ProviderContainer(
      overrides: <Override>[
        cartRepositoryProvider.overrideWithValue(
          _FakeCartRepository(
            fetchResult: const CartSnapshot(token: 'token', items: <CartItemSnapshot>[], totalQuantity: 0),
            addResult: snapshot(productId: 42, quantity: 1),
          ),
        ),
      ],
    );
    addTearDown(container.dispose);

    await container.read(cartControllerProvider.future);
    final controller = container.read(cartControllerProvider.notifier);

    final result = await controller.toggleProduct(42);

    expect(result.kind, CartActionResultKind.added);
    expect(container.read(cartControllerProvider).valueOrNull?.containsProduct(42), isTrue);
    expect(container.read(pendingCartProductIdsProvider), isEmpty);
  });

  test('toggleProduct removes item when product is present', () async {
    final container = ProviderContainer(
      overrides: <Override>[
        cartRepositoryProvider.overrideWithValue(
          _FakeCartRepository(
            fetchResult: snapshot(productId: 42, quantity: 1),
            removeResult: const CartSnapshot(token: 'token', items: <CartItemSnapshot>[], totalQuantity: 0),
          ),
        ),
      ],
    );
    addTearDown(container.dispose);

    await container.read(cartControllerProvider.future);
    final controller = container.read(cartControllerProvider.notifier);

    final result = await controller.toggleProduct(42);

    expect(result.kind, CartActionResultKind.removed);
    expect(container.read(cartControllerProvider).valueOrNull?.containsProduct(42), isFalse);
    expect(container.read(pendingCartProductIdsProvider), isEmpty);
  });

  test('addProduct restores previous state after failure', () async {
    final previous = snapshot(productId: 42, quantity: 1);
    final container = ProviderContainer(
      overrides: <Override>[
        cartRepositoryProvider.overrideWithValue(
          _FakeCartRepository(
            fetchResult: previous,
            addError: StateError('boom'),
          ),
        ),
      ],
    );
    addTearDown(container.dispose);

    await container.read(cartControllerProvider.future);
    final controller = container.read(cartControllerProvider.notifier);

    await expectLater(
      controller.addProduct(10),
      throwsA(isA<StateError>()),
    );

    expect(container.read(cartControllerProvider).valueOrNull, same(previous));
    expect(container.read(pendingCartProductIdsProvider), isEmpty);
  });
}
