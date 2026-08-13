import SwiftUI
#if canImport(UIKit)
import UIKit
#endif

struct MyAppointmentsView: View {
    private let service: AppointmentServing
    @StateObject private var viewModel: MyAppointmentsViewModel

    @State private var tab: AppointmentTabFilter = .upcoming

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
        .task { await viewModel.load(force: true) }
        .refreshable { await viewModel.load(force: true) }
        .overlay(alignment: .top) {
            if let message = viewModel.successMessage {
                AppointmentSuccessBanner(message: message)
                    .padding(.top, 8)
                    .onAppear {
#if canImport(UIKit)
                        UIAccessibility.post(notification: .announcement, argument: message)
#endif
                        DispatchQueue.main.asyncAfter(deadline: .now() + 2.0) {
                            if viewModel.successMessage == message { viewModel.successMessage = nil }
                        }
                    }
            }
        }
        .environment(\.locale, Locale(identifier: "fr_FR"))
    }
}
