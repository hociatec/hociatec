import SwiftUI

struct ClientDashboardActionDestinationView: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var account: AccountViewModel

    let action: ClientDashboardAction

    var body: some View {
        switch action.destination {
        case .pendingReviews:
            PendingReviewsView()
                .environmentObject(container)
        case .appointments:
            MyAppointmentsView(service: container.services.appointments)
        case .quotes:
            MyQuotesListView(viewModel: container.makeMyQuotesViewModel())
        case .favorites:
            FavoritesScreen(service: container.services.favorites)
        case .orders:
            OrdersView(service: container.services.orders)
        case .trainings:
            ClientTrainingsView(service: container.services.training)
        case .communicationPreferences:
            CommunicationPreferencesView(service: container.services.workspace)
        case .addresses:
            AddressesManagerView(account: account)
        case .support:
            MySupportRequestsView(service: container.services.support)
        case .vouchers:
            MyVouchersView(service: container.services.vouchers)
        case .audits:
            MyAuditsView(service: container.services.audits)
        case .tradeIns:
            MyTradeInsView(service: container.services.tradeIn)
        }
    }
}
