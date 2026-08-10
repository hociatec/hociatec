import 'dart:async';

import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/app/app.dart';
import 'package:hociatec_mobile/features/auth/data/auth_session_store.dart';
import 'package:shared_preferences/shared_preferences.dart';

void bootstrap() {
  WidgetsFlutterBinding.ensureInitialized();

  runZonedGuarded(
    () async {
      final preferences = await SharedPreferences.getInstance();

      runApp(
        ProviderScope(
          overrides: <Override>[
            authSessionStoreProvider.overrideWithValue(
              AuthSessionStore(preferences),
            ),
          ],
          child: const HociatecMobileApp(),
        ),
      );
    },
    (error, stackTrace) {
      FlutterError.reportError(
        FlutterErrorDetails(
          exception: error,
          stack: stackTrace,
          library: 'bootstrap',
          context: ErrorDescription('Unhandled application error'),
        ),
      );
    },
  );
}
