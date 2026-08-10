import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/config/app_config_provider.dart';
import 'package:hociatec_mobile/features/auth/data/auth_session_store.dart';
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
  final authSessionStore = ref.watch(authSessionStoreProvider);
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

        final cookies = authSessionStore.readCookies();
        final cookieHeader = _buildCookieHeader(
          path: options.path,
          cookies: cookies,
        );
        if (cookieHeader.isNotEmpty) {
          options.headers['Cookie'] = cookieHeader;
        }

        handler.next(options);
      },
      onResponse: (response, handler) async {
        final cartToken = response.headers.value('x-cart-token');
        if (cartToken != null && cartToken.isNotEmpty) {
          await cartTokenStore.write(cartToken);
        }

        await _persistAuthCookies(
          response.headers['set-cookie'],
          authSessionStore,
        );

        handler.next(response);
      },
      onError: (error, handler) async {
        if (error.response?.statusCode == 404 ||
            error.response?.statusCode == 400) {
          final message = error.response?.data;
          final normalized = message is Map<String, dynamic>
              ? ((message['message'] as String?) ?? '').toLowerCase()
              : '';
          if (normalized.contains('token') || normalized.contains('panier')) {
            await cartTokenStore.clear();
          }
        }

        final requestPath = error.requestOptions.path;
        final isRefreshRequest = requestPath == '/api/auth/refresh';
        final isLoginRequest = requestPath == '/api/auth/login';
        final alreadyRetried =
            error.requestOptions.extra['auth_retry'] as bool? ?? false;

        if (error.response?.statusCode == 401 &&
            !isRefreshRequest &&
            !isLoginRequest &&
            !alreadyRetried) {
          final cookies = authSessionStore.readCookies();
          if (cookies.hasRefreshToken) {
            try {
              final refreshResponse = await dio.post<Map<String, dynamic>>(
                '/api/auth/refresh',
                data: <String, dynamic>{
                  'refreshToken': cookies.refreshToken,
                },
                options: Options(
                  headers: <String, String>{
                    'Cookie':
                        'hociatec_refresh=${cookies.refreshToken}; ${cookies.hasAccessToken ? 'hociatec_access=${cookies.accessToken}' : ''}',
                  },
                ),
              );

              await _persistAuthCookies(
                refreshResponse.headers['set-cookie'],
                authSessionStore,
              );

              final retryCookies = authSessionStore.readCookies();
              final retryRequest = error.requestOptions;
              retryRequest.extra['auth_retry'] = true;
              final retryCookieHeader = _buildCookieHeader(
                path: retryRequest.path,
                cookies: retryCookies,
              );
              if (retryCookieHeader.isNotEmpty) {
                retryRequest.headers['Cookie'] = retryCookieHeader;
              }

              final retryResponse = await dio.fetch<dynamic>(retryRequest);
              handler.resolve(retryResponse);
              return;
            } on DioException {
              await authSessionStore.clear();
            }
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

String _buildCookieHeader({
  required String path,
  required AuthCookies cookies,
}) {
  final values = <String>[];

  if (cookies.hasAccessToken) {
    values.add('hociatec_access=${cookies.accessToken}');
  }

  if (path.startsWith('/api/auth') && cookies.hasRefreshToken) {
    values.add('hociatec_refresh=${cookies.refreshToken}');
  }

  return values.join('; ');
}

Future<void> _persistAuthCookies(
  List<String>? rawCookies,
  AuthSessionStore authSessionStore,
) async {
  if (rawCookies == null || rawCookies.isEmpty) {
    return;
  }

  String? accessToken;
  String? refreshToken;

  for (final cookie in rawCookies) {
    final parts = cookie.split(';');
    if (parts.isEmpty) {
      continue;
    }

    final pair = parts.first.split('=');
    if (pair.length < 2) {
      continue;
    }

    final name = pair.first.trim();
    final value = pair.sublist(1).join('=').trim();

    if (name == 'hociatec_access') {
      accessToken = value.isEmpty ? null : value;
    } else if (name == 'hociatec_refresh') {
      refreshToken = value.isEmpty ? null : value;
    }
  }

  if (accessToken == null && refreshToken == null) {
    return;
  }

  await authSessionStore.writeCookies(
    accessToken: accessToken,
    refreshToken: refreshToken,
  );
}
