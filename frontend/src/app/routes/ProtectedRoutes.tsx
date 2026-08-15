import { Navigate } from 'react-router';

import {
  AddressesPage,
  CheckoutSuccessPage,
  ClientDashboardPage,
  CommunicationPreferencesPage,
  MyAppointmentsPage,
  MyAuditDetailPage,
  MyAuditsPage,
  MyFavoritesPage,
  MyOrdersPage,
  MyRentalsPage,
  MySupportRequestsPage,
  MyQuoteDetailPage,
  MyQuotesPage,
  MyTradeInsPage,
  MyTrainingDetailPage,
  MyTrainingsPage,
  MyVouchersPage,
  OrderDetailPage,
  ProfilePage,
  ProfileAccessSessionsPage,
  RequestAuditPage,
} from './ClientRoutePages';
import { BetaDashboardPage } from './PublicRoutePages';
import type { AppRouteDefinition } from './RouteDefinition';
import { protectedElement } from './RouteDefinition';
import { isFeatureEnabled } from '@/shared/config/featureFlags';

export const protectedRoutes: AppRouteDefinition[] = [
  ...(isFeatureEnabled('betaProgram')
    ? [
        { path: '/beta', element: protectedElement(<BetaDashboardPage />) },
      ]
    : []),
  { path: '/quotes/me', element: protectedElement(<MyQuotesPage />) },
  { path: '/quotes/me/:quoteId', element: protectedElement(<MyQuoteDetailPage />) },
  { path: '/orders/me', element: protectedElement(<MyOrdersPage />) },
  { path: '/support/me', element: protectedElement(<MySupportRequestsPage />) },
  { path: '/vouchers/me', element: protectedElement(<MyVouchersPage />) },
  { path: '/trainings/me', element: protectedElement(<MyTrainingsPage />) },
  { path: '/trainings/me/:enrollmentId', element: protectedElement(<MyTrainingDetailPage />) },
  { path: '/orders/:orderId', element: protectedElement(<OrderDetailPage />) },
  { path: '/checkout/success', element: protectedElement(<CheckoutSuccessPage />) },
  { path: '/mon-espace', element: protectedElement(<ClientDashboardPage />) },
  { path: '/profile', element: protectedElement(<ProfilePage />) },
  { path: '/profile/access-sessions', element: protectedElement(<ProfileAccessSessionsPage />) },
  { path: '/profile/communication-preferences', element: protectedElement(<CommunicationPreferencesPage />) },
  { path: '/favorites', element: protectedElement(<MyFavoritesPage />) },
  { path: '/reprises', element: protectedElement(<MyTradeInsPage />) },
  { path: '/profile/addresses', element: protectedElement(<AddressesPage />) },
  { path: '/appointments/me', element: protectedElement(<MyAppointmentsPage />) },
  { path: '/locations/me', element: protectedElement(<MyRentalsPage />) },
  { path: '/audits/request', element: protectedElement(<RequestAuditPage />) },
  { path: '/audits/me', element: protectedElement(<MyAuditsPage />) },
  { path: '/audits/me/:auditId', element: protectedElement(<MyAuditDetailPage />) },
  {
    path: '/appointments/admin',
    element: protectedElement(<Navigate to="/admin/appointments/motifs" replace />),
  },
];
