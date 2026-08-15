import SwiftUI

struct MyAppointmentsView: View {
    private let service: AppointmentServing
    @StateObject private var viewModel: MyAppointmentsViewModel

    @State private var tab: AppointmentTabFilter = .all

    init(service: AppointmentServing) {
        self.service = service
        _viewModel = StateObject(wrappedValue: MyAppointmentsViewModel(service: service))
    }

    var body: some View {
        List {
            MyAppointmentsContent(
                service: service,
                viewModel: viewModel,
                tab: $tab
            )
        }
        .navigationTitle("Mes rendez-vous")
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
        .environment(\.locale, Locale(identifier: "fr_FR"))
        .overlay(alignment: .bottom) {
            if viewModel.isLoading && !(viewModel.upcoming.isEmpty && viewModel.past.isEmpty) {
                InlineLoadingStatus(message: "Actualisation des rendez-vous…")
                    .padding(.horizontal, 16)
                    .padding(.bottom, 8)
                    .background(.thinMaterial, in: Capsule())
                    .padding(.bottom, 8)
            }
        }
        .feedbackDialog(error: $viewModel.error, success: $viewModel.successMessage)
    }
}
