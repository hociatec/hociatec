import SwiftUI

struct ClientDashboardScreenContent: View {
    @EnvironmentObject private var account: AccountViewModel

    @ObservedObject var viewModel: ClientDashboardViewModel
    @Binding var showDeleteConfirmation: Bool

    var body: some View {
        Group {
            ClientDashboardHeroSection(firstName: account.profile?.firstName)
            ClientDashboardStatusSections(
                error: viewModel.error,
                partialError: viewModel.partialError
            )
            ClientDashboardActionsSection(
                isLoading: viewModel.isLoading,
                actions: viewModel.actions
            )
            ClientDashboardLoyaltySection(viewModel: viewModel)
            ClientDashboardAccountSection()
            ClientDashboardInformationSection()
            ClientDashboardSecuritySection(showDeleteConfirmation: $showDeleteConfirmation)
        }
    }
}
