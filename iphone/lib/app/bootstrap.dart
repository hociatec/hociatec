import 'dart:async';

import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/app/app.dart';

void bootstrap() {
  WidgetsFlutterBinding.ensureInitialized();

  runZonedGuarded(
    () => runApp(const ProviderScope(child: HociatecMobileApp())),
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
