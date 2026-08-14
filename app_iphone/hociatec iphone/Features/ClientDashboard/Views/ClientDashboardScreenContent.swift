import SwiftUI

struct ClientDashboardScreenContent: View {
    @EnvironmentObject private var account: AccountViewModel

    @ObservedObject var viewModel: ClientDashboardViewModel
    @Binding var showDeleteConfirmation: Bool

    var body: some View {
        Group {
            ClientDashboardHeroSection(firstName: account.profile?.firstName)
            ClientDashboardTopActionsSection()
            ClientDashboardDangerZoneSection(showDeleteConfirmation: $showDeleteConfirmation)
            ClientDashboardStatusSections(
                error: viewModel.error,
                partialError: viewModel.partialError
            )
            ClientDashboardActionsSection(
                isLoading: viewModel.isLoading,
                actions: viewModel.actions
            )
            ClientDashboardLoyaltySection(viewModel: viewModel)
            ClientDashboardQuickAccessSection()
            ClientDashboardServicesSection()
            ClientDashboardProgramsSection()
            ClientDashboardSettingsSection()
        }
    }
}
