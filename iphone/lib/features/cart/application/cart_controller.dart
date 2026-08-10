import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/cart/application/cart_action_result.dart';
import 'package:hociatec_mobile/features/cart/data/cart_repository.dart';
import 'package:hociatec_mobile/features/cart/domain/cart_snapshot.dart';

final pendingCartProductIdsProvider = StateProvider<Set<int>>((ref) {
  return <int>{};
});

class CartController extends AsyncNotifier<CartSnapshot?> {
  @override
  Future<CartSnapshot?> build() async {
    try {
      return await ref.watch(cartRepositoryProvider).fetch();
    } catch (_) {
      return null;
    }
  }

  bool isProductInCart(int productId) {
    return state.valueOrNull?.containsProduct(productId) ?? false;
  }

  int quantityForProduct(int productId) {
    return state.valueOrNull?.quantityForProduct(productId) ?? 0;
  }

  bool isPending(int productId) {
    return ref.watch(pendingCartProductIdsProvider).contains(productId);
  }

  Future<void> addProduct(int productId) async {
    await _runProductMutation(
      productId,
      () => ref.read(cartRepositoryProvider).addItem(productId),
    );
  }

  Future<void> removeProduct(int productId) async {
    await _runProductMutation(
      productId,
      () => ref.read(cartRepositoryProvider).removeItem(productId),
    );
  }

  Future<CartActionResult> toggleProduct(int productId) async {
    if (isProductInCart(productId)) {
      await removeProduct(productId);
      return const CartActionResult(CartActionResultKind.removed);
    }

    await addProduct(productId);
    return const CartActionResult(CartActionResultKind.added);
  }

  Future<void> refreshCart() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() => ref.read(cartRepositoryProvider).fetch());
  }

  Future<void> _runProductMutation(
    int productId,
    Future<CartSnapshot> Function() action,
  ) async {
    final previous = state.valueOrNull;
    _setPending(productId, true);

    try {
      final cart = await action();
      state = AsyncData(cart);
    } catch (error, stackTrace) {
      state = AsyncError(error, stackTrace);
      if (previous != null) {
        state = AsyncData(previous);
      }
      rethrow;
    } finally {
      _setPending(productId, false);
    }
  }

  void _setPending(int productId, bool pending) {
    final current = ref.read(pendingCartProductIdsProvider);
    final next = <int>{...current};
    if (pending) {
      next.add(productId);
    } else {
      next.remove(productId);
    }
    ref.read(pendingCartProductIdsProvider.notifier).state = next;
  }
}

final cartControllerProvider =
    AsyncNotifierProvider<CartController, CartSnapshot?>(CartController.new);
