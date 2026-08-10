import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hociatec_mobile/app/app.dart';

void main() {
  testWidgets('renders the four bottom navigation tabs', (tester) async {
    await tester.pumpWidget(const ProviderScope(child: HociatecMobileApp()));

    expect(find.text('Accueil'), findsWidgets);
    expect(find.text('Recherche'), findsOneWidget);
    expect(find.text('Catalogue'), findsOneWidget);
    expect(find.text('Prestations'), findsOneWidget);
  });

  testWidgets('switches between tabs', (tester) async {
    await tester.pumpWidget(const ProviderScope(child: HociatecMobileApp()));

    await tester.tap(find.text('Catalogue'));
    await tester.pumpAndSettle();
    expect(
      find.text('Fondation de l\'écran catalogue prête à être développée.'),
      findsOneWidget,
    );

    await tester.tap(find.text('Prestations'));
    await tester.pumpAndSettle();
    expect(
      find.text('Fondation de l\'écran prestations prête à être développée.'),
      findsOneWidget,
    );
  });
}
