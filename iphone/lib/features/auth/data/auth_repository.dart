import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/features/auth/data/auth_session_store.dart';

class AuthUser {
  const AuthUser({
    required this.id,
    required this.email,
    required this.firstName,
    required this.lastName,
    required this.roles,
  });

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    return AuthUser(
      id: (json['id'] as num?)?.toInt() ?? 0,
      email: (json['email'] as String?)?.trim() ?? '',
      firstName: (json['firstName'] as String?)?.trim() ?? '',
      lastName: (json['lastName'] as String?)?.trim() ?? '',
      roles: ((json['roles'] as List?) ?? const <dynamic>[])
          .whereType<String>()
          .toList(growable: false),
    );
  }

  final int id;
  final String email;
  final String firstName;
  final String lastName;
  final List<String> roles;

  String get displayName => '$firstName $lastName'.trim();
}

class AuthRepository {
  const AuthRepository(this._client, this._store);

  final ApiClient _client;
  final AuthSessionStore _store;

  Future<String> login({
    required String email,
    required String password,
  }) async {
    final response = await _client.post<Map<String, dynamic>>(
      '/api/auth/login',
      data: <String, dynamic>{
        'email': email,
        'password': password,
      },
    );

    return (response.data?['message'] as String?)?.trim() ??
        'Connexion reussie.';
  }

  Future<void> logout() async {
    try {
      await _client.post<Map<String, dynamic>>('/api/auth/logout');
    } finally {
      await _store.clear();
    }
  }

  Future<AuthUser?> fetchCurrentUser() async {
    final response = await _client.get<Map<String, dynamic>>('/api/auth/me');
    final data = response.data?['data'];
    if (data is! Map<String, dynamic>) {
      return null;
    }

    if (data['authenticated'] == false) {
      return null;
    }

    return AuthUser.fromJson(data);
  }
}

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(
    ref.watch(apiClientProvider),
    ref.watch(authSessionStoreProvider),
  );
});

final currentAuthUserProvider = FutureProvider<AuthUser?>((ref) {
  return ref.watch(authRepositoryProvider).fetchCurrentUser();
});
