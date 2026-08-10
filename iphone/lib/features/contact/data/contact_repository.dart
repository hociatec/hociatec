import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';

class ContactRepository {
  const ContactRepository(this._client);

  final ApiClient _client;

  Future<String> submit({
    required String name,
    required String email,
    required String subject,
    required String message,
  }) async {
    final response = await _client.post<Map<String, dynamic>>(
      '/api/public/contact',
      data: <String, dynamic>{
        'name': name,
        'email': email,
        'subject': subject,
        'message': message,
      },
    );

    return (response.data?['message'] as String?)?.trim() ??
        'Votre message a ete envoye.';
  }
}

final contactRepositoryProvider = Provider<ContactRepository>((ref) {
  return ContactRepository(ref.watch(apiClientProvider));
});
