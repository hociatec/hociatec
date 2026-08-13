import SwiftUI

struct AppointmentBookingView: View {
    @EnvironmentObject private var account: AccountViewModel
    @StateObject private var viewModel: AppointmentBookingViewModel

    init(service: AppointmentServing) {
        _viewModel = StateObject(wrappedValue: AppointmentBookingViewModel(service: service))
    }

    var body: some View {
        Form {
            AppointmentBookingGuestNoticeSection(isLoggedIn: account.isLoggedIn)
            AppointmentBookingProgressSection(step: viewModel.step)
            stepContent
            AppointmentBookingSuccessSection(successMessage: viewModel.successMessage)
        }
        .navigationTitle("Rendez-vous")
        .task { await viewModel.initialize() }
        .onChangeCompat(viewModel.selectedPrestationId) { _ in
            Task { await viewModel.didChangePrestation() }
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

    @ViewBuilder
    private var stepContent: some View {
        switch viewModel.step {
        case .prestation:
            AppointmentBookingPrestationSection(
                prestations: viewModel.prestations,
                selectedPrestationId: $viewModel.selectedPrestationId,
                selectedPrestation: selectedPrestation,
                isLoading: viewModel.isLoading,
                error: viewModel.error
            ) {
                Task { await viewModel.goToDaySelection() }
            }
        case .day:
            AppointmentBookingCalendarSection(
                visibleMonth: viewModel.visibleMonth,
                availableDays: AppointmentBookingPresentation.availableDays(from: viewModel.slots),
                selectedDate: viewModel.selectedDate,
                isLoading: viewModel.isLoading,
                error: viewModel.error,
                onSelectDay: viewModel.selectDay
            ) {
                Task { await viewModel.showPreviousMonth() }
            } onNextMonth: {
                Task { await viewModel.showNextMonth() }
            } onCurrentMonth: {
                Task { await viewModel.showCurrentMonth() }
            } onBack: {
                viewModel.backToPrestationSelection()
            }
        case .slot:
            AppointmentBookingSlotsSection(
                slots: selectedDaySlots,
                selectedDate: viewModel.selectedDate,
                selectedPrestation: selectedPrestation,
                isLoading: viewModel.isLoading,
                error: viewModel.error,
                viewModel: viewModel
            ) {
                viewModel.backToDaySelection()
            }
        }
    }

    private var selectedDaySlots: [AppointmentSlot] {
        guard let selectedDate = viewModel.selectedDate else { return [] }
        return AppointmentBookingPresentation.daySlots(for: selectedDate, from: viewModel.slots)
    }
}
