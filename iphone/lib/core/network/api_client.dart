import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/config/app_config_provider.dart';
import 'package:hociatec_mobile/features/cart/data/cart_token_store.dart';

class ApiClient {
  ApiClient(this._dio);

  final Dio _dio;

  Future<Response<T>> get<T>(
    String path, {
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) {
    return _dio.get<T>(
      path,
      queryParameters: queryParameters,
      options: options,
    );
  }

  Future<Response<T>> post<T>(
    String path, {
    Object? data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) {
    return _dio.post<T>(
      path,
      data: data,
      queryParameters: queryParameters,
      options: options,
    );
  }

  Future<Response<T>> patch<T>(
    String path, {
    Object? data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) {
    return _dio.patch<T>(
      path,
      data: data,
      queryParameters: queryParameters,
      options: options,
    );
  }

  Future<Response<T>> delete<T>(
    String path, {
    Object? data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) {
    return _dio.delete<T>(
      path,
      data: data,
      queryParameters: queryParameters,
      options: options,
    );
  }
}

final dioProvider = Provider<Dio>((ref) {
  final config = ref.watch(appConfigProvider);
  final cartTokenStore = ref.watch(cartTokenStoreProvider);

  final dio = Dio(
    BaseOptions(
      baseUrl: config.apiBaseUrl,
      connectTimeout: config.connectTimeout,
      receiveTimeout: config.receiveTimeout,
      headers: const <String, String>{
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ),
  );

  dio.interceptors.add(
    InterceptorsWrapper(
      onRequest: (options, handler) async {
        final cartToken = await cartTokenStore.read();
        if (cartToken != null && cartToken.isNotEmpty) {
          options.headers.putIfAbsent('X-Cart-Token', () => cartToken);
        }
        handler.next(options);
      },
      onResponse: (response, handler) async {
        final cartToken = response.headers.value('x-cart-token');
        if (cartToken != null && cartToken.isNotEmpty) {
          await cartTokenStore.write(cartToken);
        }
        handler.next(response);
      },
      onError: (error, handler) async {
        if (error.response?.statusCode == 404 || error.response?.statusCode == 400) {
          final message = error.response?.data;
          final normalized = message is Map<String, dynamic>
              ? ((message['message'] as String?) ?? '').toLowerCase()
              : '';
          if (normalized.contains('token') || normalized.contains('panier')) {
            await cartTokenStore.clear();
          }
        }
        handler.next(error);
      },
    ),
  );

  return dio;
});

final apiClientProvider = Provider<ApiClient>((ref) {
  final dio = ref.watch(dioProvider);

  return ApiClient(dio);
});
