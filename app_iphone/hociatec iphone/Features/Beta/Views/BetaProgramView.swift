import SwiftUI

struct BetaProgramView: View {
    @EnvironmentObject private var account: AccountViewModel
    @StateObject private var viewModel: BetaProgramViewModel

    init(service: BetaServing) {
        _viewModel = StateObject(wrappedValue: BetaProgramViewModel(service: service))
    }

    var body: some View {
        List {
            if account.isLoggedIn {
                BetaProgramLoggedInContent(viewModel: viewModel)
            } else {
                BetaProgramPublicContent()
            }
        }
        .navigationTitle("Programme bêta")
        .task {
            if account.isLoggedIn {
                await viewModel.load()
            }
        }
        .refreshable {
            if account.isLoggedIn {
                await viewModel.load(force: true)
            }
        }
        .feedbackDialog(error: $viewModel.error, success: $viewModel.statusMessage)
    }
}
