import SwiftUI

struct AppointmentBookingView: View {
    @EnvironmentObject private var account: AccountViewModel
    @StateObject private var viewModel: AppointmentBookingViewModel
    @State private var startDate = Date()

    init(service: AppointmentServing) {
        _viewModel = StateObject(wrappedValue: AppointmentBookingViewModel(service: service))
    }

    var body: some View {
        Form {
            AppointmentBookingGuestNoticeSection(isLoggedIn: account.isLoggedIn)
            AppointmentBookingPrestationSection(
                prestations: viewModel.prestations,
                selectedPrestationId: $viewModel.selectedPrestationId,
                selectedPrestation: selectedPrestation,
                isLoading: viewModel.isLoading
            )
            AppointmentBookingStartDateSection(startDate: $startDate)
            AppointmentBookingSlotsSection(
                slotsByDay: slotsByDay,
                sortedDays: sortedDays,
                selectedPrestation: selectedPrestation,
                isLoading: viewModel.isLoading,
                error: viewModel.error,
                viewModel: viewModel
            )
            AppointmentBookingSuccessSection(successMessage: viewModel.successMessage)
        }
        .navigationTitle("Rendez-vous")
        .task { await viewModel.initialize(startDate: startDate) }
        .onChangeCompat(viewModel.selectedPrestationId) { _ in
            Task { await viewModel.loadSlots(startDate: startDate) }
        }
        .onChangeCompat(startDate) { newDate in
            Task { await viewModel.loadSlots(startDate: newDate) }
        }
        .environment(\.locale, Locale(identifier: "fr_FR"))
        .environment(\.calendar, Calendar(identifier: .gregorian))
    }

    private var selectedPrestation: AppointmentPrestation? {
        AppointmentBookingPresentation.selectedPrestation(
            prestations: viewModel.prestations,
            selectedPrestationId: viewModel.selectedPrestationId
        )
    }

    private var slotsByDay: [Date: [AppointmentSlot]] {
        AppointmentBookingPresentation.slotsByDay(
            slots: viewModel.slots,
            startDate: startDate
        )
    }

    private var sortedDays: [Date] {
        AppointmentBookingPresentation.sortedDays(from: slotsByDay)
    }
}
