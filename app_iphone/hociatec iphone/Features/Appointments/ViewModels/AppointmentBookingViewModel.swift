import Foundation
import Combine

@MainActor
final class AppointmentBookingViewModel: ObservableObject {
    enum Mode {
        case booking
        case rescheduling(AppointmentSummary)
    }

    enum Step: Int {
        case prestation = 1
        case day = 2
        case slot = 3
    }

    @Published var prestations: [AppointmentPrestation] = []
    @Published var slots: [AppointmentSlot] = []
    @Published var selectedPrestationId: Int?
    @Published var step: Step = .prestation
    @Published var visibleMonth = Date()
    @Published var selectedDate: Date?
    @Published var selectedSlot: AppointmentSlot?
    @Published var isLoading = false
    @Published var isBooking = false
    @Published var error: String?
    @Published var successMessage: String?
    @Published var bookingMessage: String?

    private let service: AppointmentServing
    private let mode: Mode
    private let calendar = Calendar(identifier: .gregorian)
    private var prestationsRequestID = 0
    private var availabilityRequestID = 0

    init(service: AppointmentServing, mode: Mode = .booking) {
        self.service = service
        self.mode = mode

        if case let .rescheduling(appointment) = mode {
            self.selectedPrestationId = appointment.prestation.id
            self.step = .day
        }
    }

    func initialize() async {
        let hadSelection = selectedPrestationId != nil
        if prestations.isEmpty {
            await loadPrestations()
        }
        if hadSelection {
            await loadAvailabilityForVisibleMonth()
        }
    }

    func loadPrestations() async {
        guard !isLoading else { return }
        prestationsRequestID += 1
        let requestID = prestationsRequestID
        isLoading = true
        error = nil
        successMessage = nil

        do {
            let loadedPrestations = try await service.appointmentPrestations()
            guard requestID == prestationsRequestID else { return }
            prestations = loadedPrestations
            if selectedPrestationId == nil {
                selectedPrestationId = prestations.first?.id
            }
        } catch let err {
            guard requestID == prestationsRequestID else { return }
            self.error = err.localizedDescription
        }

        if requestID == prestationsRequestID {
            isLoading = false
        }
    }

    func didChangePrestation() async {
        selectedDate = nil
        selectedSlot = nil
        step = .prestation
        successMessage = nil
        bookingMessage = nil
        await loadAvailabilityForVisibleMonth()
    }

    func goToDaySelection() async {
        step = .day
        if slots.isEmpty {
            await loadAvailabilityForVisibleMonth()
        }
    }

    func backToPrestationSelection() {
        step = .prestation
        selectedDate = nil
        selectedSlot = nil
    }

    func selectDay(_ day: Date) {
        selectedDate = calendar.startOfDay(for: day)
        selectedSlot = nil
        step = .slot
    }

    func backToDaySelection() {
        step = .day
        selectedSlot = nil
    }

    func showPreviousMonth() async {
        updateVisibleMonth(byAdding: -1)
        await loadAvailabilityForVisibleMonth()
    }

    func showNextMonth() async {
        updateVisibleMonth(byAdding: 1)
        await loadAvailabilityForVisibleMonth()
    }

    func showCurrentMonth() async {
        visibleMonth = Date()
        await loadAvailabilityForVisibleMonth()
    }

    func loadAvailabilityForVisibleMonth() async {
        guard let prestationId = selectedPrestationId else { return }
        guard !isLoading else { return }
        availabilityRequestID += 1
        let requestID = availabilityRequestID
        isLoading = true
        error = nil
        successMessage = nil

        let monthStart = calendar.date(
            from: calendar.dateComponents([.year, .month], from: visibleMonth)
        ) ?? visibleMonth
        let today = calendar.startOfDay(for: Date())
        let start = max(today, calendar.startOfDay(for: monthStart))
        let end = calendar.date(byAdding: .month, value: 1, to: monthStart) ?? start

        do {
            let loadedSlots = try await service.appointmentAvailability(prestationId: prestationId, start: start, end: end)
            guard requestID == availabilityRequestID else { return }
            slots = loadedSlots
            if let selectedDate, AppointmentBookingPresentation.daySlots(for: selectedDate, from: slots).isEmpty {
                self.selectedDate = nil
                if step == .slot {
                    step = .day
                }
            }
        } catch let err {
            guard requestID == availabilityRequestID else { return }
            self.error = err.localizedDescription
        }

        if requestID == availabilityRequestID {
            isLoading = false
        }
    }

    func book(slot: AppointmentSlot) async -> AppointmentSummary? {
        bookingMessage = nil
        guard let prestationId = selectedPrestationId else {
            error = "Choisissez une prestation avant de réserver."
            return nil
        }
        guard !isBooking else { return nil }

        isBooking = true
        error = nil
        successMessage = nil
        defer { isBooking = false }

        do {
            let appointment: AppointmentSummary

            switch mode {
            case .booking:
                appointment = try await service.bookAppointment(prestationId: prestationId, startAt: slot.startAt)
                successMessage = "Rendez-vous confirmé."
                bookingMessage = "Rendez-vous confirmé avec succès."
            case let .rescheduling(currentAppointment):
                appointment = try await service.rescheduleAppointment(id: currentAppointment.id, startAt: slot.startAt)
                successMessage = "Rendez-vous reporté."
                bookingMessage = "Le rendez-vous a été reporté avec succès."
            }

            selectedSlot = slot
            return appointment
        } catch let err {
            self.error = err.localizedDescription
            return nil
        }
    }

    var isRescheduling: Bool {
        if case .rescheduling = mode {
            return true
        }

        return false
    }

    private func updateVisibleMonth(byAdding months: Int) {
        visibleMonth = calendar.date(byAdding: .month, value: months, to: visibleMonth) ?? visibleMonth
    }
}
