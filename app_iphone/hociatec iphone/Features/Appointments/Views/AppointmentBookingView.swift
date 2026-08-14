import SwiftUI

struct AppointmentBookingView: View {
    @EnvironmentObject private var account: AccountViewModel
    @StateObject private var viewModel: AppointmentBookingViewModel
    private let onCompleted: ((AppointmentSummary) -> Void)?

    init(
        service: AppointmentServing,
        mode: AppointmentBookingViewModel.Mode = .booking,
        onCompleted: ((AppointmentSummary) -> Void)? = nil
    ) {
        _viewModel = StateObject(wrappedValue: AppointmentBookingViewModel(service: service, mode: mode))
        self.onCompleted = onCompleted
    }

    var body: some View {
        Form {
            AppointmentBookingGuestNoticeSection(isLoggedIn: account.isLoggedIn)
            AppointmentBookingProgressSection(step: viewModel.step)
            stepContent
        }
        .navigationTitle(viewModel.isRescheduling ? "Reporter" : "Rendez-vous")
        .task { await viewModel.initialize() }
        .onChangeCompat(viewModel.selectedPrestationId) { _ in
            Task { await viewModel.didChangePrestation() }
        }
        .environment(\.locale, Locale(identifier: "fr_FR"))
        .environment(\.calendar, Calendar(identifier: .gregorian))
        .feedbackDialog(error: $viewModel.error, success: $viewModel.successMessage)
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
                viewModel: viewModel,
                onCompleted: onCompleted
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
