import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:hociatec_mobile/features/about/presentation/about_screen.dart';
import 'package:hociatec_mobile/features/account/presentation/account_screen.dart';
import 'package:hociatec_mobile/features/appointments/presentation/appointment_request_screen.dart';
import 'package:hociatec_mobile/features/appointments/presentation/my_appointments_screen.dart';
import 'package:hociatec_mobile/features/audits/presentation/audit_request_screen.dart';
import 'package:hociatec_mobile/features/audits/presentation/my_audits_screen.dart';
import 'package:hociatec_mobile/features/auth/data/auth_session_store.dart';
import 'package:hociatec_mobile/features/auth/presentation/forgot_password_screen.dart';
import 'package:hociatec_mobile/features/auth/presentation/login_screen.dart';
import 'package:hociatec_mobile/features/auth/presentation/register_screen.dart';
import 'package:hociatec_mobile/features/catalog/presentation/catalog_listing_screen.dart';
import 'package:hociatec_mobile/features/catalog/presentation/catalog_screen.dart';
import 'package:hociatec_mobile/features/catalog/presentation/product_detail_screen.dart';
import 'package:hociatec_mobile/features/contact/presentation/contact_screen.dart';
import 'package:hociatec_mobile/features/favorites/presentation/favorites_screen.dart';
import 'package:hociatec_mobile/features/home/presentation/home_screen.dart';
import 'package:hociatec_mobile/features/news/presentation/news_detail_screen.dart';
import 'package:hociatec_mobile/features/news/presentation/news_screen.dart';
import 'package:hociatec_mobile/features/orders/presentation/orders_screen.dart';
import 'package:hociatec_mobile/features/quotes/presentation/my_quotes_screen.dart';
import 'package:hociatec_mobile/features/quotes/presentation/quote_request_screen.dart';
import 'package:hociatec_mobile/features/search/presentation/search_screen.dart';
import 'package:hociatec_mobile/features/services/presentation/service_detail_screen.dart';
import 'package:hociatec_mobile/features/services/presentation/services_screen.dart';
import 'package:hociatec_mobile/features/trade_ins/presentation/my_trade_ins_screen.dart';
import 'package:hociatec_mobile/features/trade_ins/presentation/trade_in_request_screen.dart';
import 'package:hociatec_mobile/features/trainings/presentation/my_trainings_screen.dart';
import 'package:hociatec_mobile/features/trainings/presentation/training_catalog_screen.dart';
import 'package:hociatec_mobile/features/vouchers/presentation/vouchers_screen.dart';
import 'package:hociatec_mobile/shared/presentation/navigation/app_shell.dart';

enum AppTab {
  home(
    label: 'Accueil',
    path: '/accueil',
    icon: Icons.home_outlined,
    selectedIcon: Icons.home,
  ),
  catalog(
    label: 'Catalogue',
    path: '/catalogue',
    icon: Icons.grid_view_outlined,
    selectedIcon: Icons.grid_view_rounded,
  ),
  services(
    label: 'Prestations',
    path: '/prestations',
    icon: Icons.design_services_outlined,
    selectedIcon: Icons.design_services,
  ),
  search(
    label: 'Recherche',
    path: '/recherche',
    icon: Icons.search_outlined,
    selectedIcon: Icons.search,
  ),
  about(
    label: 'À propos',
    path: '/a-propos',
    icon: Icons.info_outline,
    selectedIcon: Icons.info,
  );

  const AppTab({
    required this.label,
    required this.path,
    required this.icon,
    required this.selectedIcon,
  });

  final String label;
  final String path;
  final IconData icon;
  final IconData selectedIcon;
}

final appRouterProvider = Provider<GoRouter>((ref) {
  final authSessionStore = ref.watch(authSessionStoreProvider);

  return GoRouter(
    initialLocation: AppTab.home.path,
    redirect: (context, state) {
      final authCookies = authSessionStore.readCookies();
      final hasSession =
          authCookies.hasAccessToken || authCookies.hasRefreshToken;
      final requiresAuth = state.matchedLocation == '/compte' ||
          state.matchedLocation.startsWith('/compte/');

      if (requiresAuth && !hasSession) {
        return '/connexion';
      }

      return null;
    },
    routes: <RouteBase>[
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) {
          return AppShell(navigationShell: navigationShell);
        },
        branches: <StatefulShellBranch>[
          StatefulShellBranch(
            routes: <RouteBase>[
              GoRoute(
                path: AppTab.home.path,
                name: AppTab.home.name,
                pageBuilder: (context, state) =>
                    const NoTransitionPage<void>(child: HomeScreen()),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: <RouteBase>[
              GoRoute(
                path: AppTab.catalog.path,
                name: AppTab.catalog.name,
                pageBuilder: (context, state) =>
                    const NoTransitionPage<void>(child: CatalogScreen()),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: <RouteBase>[
              GoRoute(
                path: AppTab.services.path,
                name: AppTab.services.name,
                pageBuilder: (context, state) =>
                    const NoTransitionPage<void>(child: ServicesScreen()),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: <RouteBase>[
              GoRoute(
                path: AppTab.search.path,
                name: AppTab.search.name,
                pageBuilder: (context, state) =>
                    const NoTransitionPage<void>(child: SearchScreen()),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: <RouteBase>[
              GoRoute(
                path: AppTab.about.path,
                name: AppTab.about.name,
                pageBuilder: (context, state) =>
                    const NoTransitionPage<void>(child: AboutScreen()),
              ),
            ],
          ),
        ],
      ),
      GoRoute(
        path: '/catalogue/produits/:slug',
        builder: (context, state) => ProductDetailScreen(
          slug: state.pathParameters['slug'] ?? '',
        ),
      ),
      GoRoute(
        path: '/catalogue/vente',
        builder: (context, state) => const CatalogListingScreen(
          title: 'Vente',
          sellingType: 'sale',
        ),
      ),
      GoRoute(
        path: '/catalogue/location',
        builder: (context, state) => const CatalogListingScreen(
          title: 'Location',
          sellingType: 'rental',
        ),
      ),
      GoRoute(
        path: '/catalogue/reprise',
        builder: (context, state) => const TradeInRequestScreen(),
      ),
      GoRoute(
        path: '/catalogue/formation',
        builder: (context, state) => const TrainingCatalogScreen(),
      ),
      GoRoute(
        path: '/prestations/:id',
        builder: (context, state) => ServiceDetailScreen(
          id: int.tryParse(state.pathParameters['id'] ?? '') ?? 0,
        ),
      ),
      GoRoute(
        path: '/prestations/rendez-vous',
        builder: (context, state) => const AppointmentRequestScreen(),
      ),
      GoRoute(
        path: '/prestations/devis',
        builder: (context, state) => const QuoteRequestScreen(),
      ),
      GoRoute(
        path: '/prestations/audit',
        builder: (context, state) => const AuditRequestScreen(),
      ),
      GoRoute(
        path: '/actualites',
        builder: (context, state) => const NewsScreen(),
      ),
      GoRoute(
        path: '/connexion',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/inscription',
        builder: (context, state) => const RegisterScreen(),
      ),
      GoRoute(
        path: '/mot-de-passe-oublie',
        builder: (context, state) => const ForgotPasswordScreen(),
      ),
      GoRoute(
        path: '/contact',
        builder: (context, state) => const ContactScreen(),
      ),
      GoRoute(
        path: '/compte',
        builder: (context, state) => const AccountScreen(),
      ),
      GoRoute(
        path: '/compte/commandes',
        builder: (context, state) => const OrdersScreen(),
      ),
      GoRoute(
        path: '/compte/devis',
        builder: (context, state) => const MyQuotesScreen(),
      ),
      GoRoute(
        path: '/compte/rendez-vous',
        builder: (context, state) => const MyAppointmentsScreen(),
      ),
      GoRoute(
        path: '/compte/audits',
        builder: (context, state) => const MyAuditsScreen(),
      ),
      GoRoute(
        path: '/compte/formations',
        builder: (context, state) => const MyTrainingsScreen(),
      ),
      GoRoute(
        path: '/compte/bons',
        builder: (context, state) => const VouchersScreen(),
      ),
      GoRoute(
        path: '/compte/reprises',
        builder: (context, state) => const MyTradeInsScreen(),
      ),
      GoRoute(
        path: '/compte/favoris',
        builder: (context, state) => const FavoritesScreen(),
      ),
      GoRoute(
        path: '/actualites/:slug',
        builder: (context, state) => NewsDetailScreen(
          slug: state.pathParameters['slug'] ?? '',
        ),
      ),
    ],
  );
});
