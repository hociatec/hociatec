class AppConfig {
  const AppConfig({
    required this.apiBaseUrl,
    required this.connectTimeout,
    required this.receiveTimeout,
  });

  factory AppConfig.fromEnvironment() {
    return const AppConfig(
      apiBaseUrl: String.fromEnvironment(
        'API_BASE_URL',
        defaultValue: 'https://api.hociatec.fr',
      ),
      connectTimeout: Duration(
        milliseconds: int.fromEnvironment(
          'API_CONNECT_TIMEOUT_MS',
          defaultValue: 15000,
        ),
      ),
      receiveTimeout: Duration(
        milliseconds: int.fromEnvironment(
          'API_RECEIVE_TIMEOUT_MS',
          defaultValue: 15000,
        ),
      ),
    );
  }

  final String apiBaseUrl;
  final Duration connectTimeout;
  final Duration receiveTimeout;
}
