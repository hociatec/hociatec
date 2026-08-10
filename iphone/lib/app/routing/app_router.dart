import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:hociatec_mobile/features/about/presentation/about_screen.dart';
import 'package:hociatec_mobile/features/catalog/presentation/catalog_screen.dart';
import 'package:hociatec_mobile/features/catalog/presentation/product_detail_screen.dart';
import 'package:hociatec_mobile/features/home/presentation/home_screen.dart';
import 'package:hociatec_mobile/features/news/presentation/news_detail_screen.dart';
import 'package:hociatec_mobile/features/search/presentation/search_screen.dart';
import 'package:hociatec_mobile/features/services/presentation/service_detail_screen.dart';
import 'package:hociatec_mobile/features/services/presentation/services_screen.dart';
import 'package:hociatec_mobile/shared/presentation/navigation/app_shell.dart';

enum AppTab {
  home(
    label: 'Accueil',
    path: '/accueil',
    icon: Icons.home_outlined,
    selectedIcon: Icons.home,
  ),
  search(
    label: 'Recherche',
    path: '/recherche',
    icon: Icons.search_outlined,
    selectedIcon: Icons.search,
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
  about(
    label: 'A propos',
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
  return GoRouter(
    initialLocation: AppTab.home.path,
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
        path: '/prestations/:id',
        builder: (context, state) => ServiceDetailScreen(
          id: int.tryParse(state.pathParameters['id'] ?? '') ?? 0,
        ),
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
