import Foundation
import Combine

enum MyAppointmentsFilter: String, CaseIterable, Identifiable {
    case all
    case confirmed
    case cancelled

    var id: String { rawValue }

    var label: String {
        switch self {
        case .all: return "Tous"
        case .confirmed: return "Confirmés"
        case .cancelled: return "Annulés"
        }
    }
}

@MainActor
final class AppointmentBookingViewModel: ObservableObject {
    @Published var prestations: [AppointmentPrestation] = []
    @Published var slots: [AppointmentSlot] = []
    @Published var selectedPrestationId: Int?
    @Published var isLoading = false
    @Published var isBooking = false
    @Published var error: String?
    @Published var successMessage: String?
    @Published var bookingMessage: String?

    private let service: AppointmentServing
    private let calendar = Calendar(identifier: .gregorian)

    init(service: AppointmentServing) {
        self.service = service
    }

    func initialize(startDate: Date) async {
        if prestations.isEmpty {
            await loadPrestations()
        }
        await loadSlots(startDate: startDate)
    }

    func loadPrestations() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        successMessage = nil

        do {
            prestations = try await service.appointmentPrestations()
            if selectedPrestationId == nil {
                selectedPrestationId = prestations.first?.id
            }
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
    }

    func loadSlots(startDate: Date) async {
        guard let prestationId = selectedPrestationId else { return }
        guard !isLoading else { return }
        isLoading = true
        error = nil
        successMessage = nil

        let start = calendar.startOfDay(for: startDate)
        let end = calendar.date(byAdding: .day, value: 14, to: start) ?? start

        do {
            slots = try await service.appointmentAvailability(prestationId: prestationId, start: start, end: end)
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
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
            let appointment = try await service.bookAppointment(prestationId: prestationId, startAt: slot.startAt)
            successMessage = "Rendez-vous confirmé."
            bookingMessage = "Rendez-vous confirmé avec succès."
            return appointment
        } catch let err {
            self.error = err.localizedDescription
            return nil
        }
    }
}

@MainActor
final class MyAppointmentsViewModel: ObservableObject {
    @Published var upcoming: [AppointmentSummary] = []
    @Published var past: [AppointmentSummary] = []
    @Published var isLoading = false
    @Published var error: String?
    @Published var successMessage: String? = nil

    private let service: AppointmentServing

    init(service: AppointmentServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil

        do {
            let list = try await service.myAppointments()
            upcoming = list.upcoming
            past = list.past
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
    }

    func cancel(appointmentID: Int) async -> Bool {
        guard !isLoading else { return false }
        isLoading = true
        error = nil
        successMessage = nil
        defer { isLoading = false }
        do {
            try await service.cancelAppointment(id: appointmentID)
            let list = try await service.myAppointments()
            upcoming = list.upcoming
            past = list.past
            successMessage = "Rendez-vous annulé."
            return true
        } catch {
            self.error = error.localizedDescription
            return false
        }
    }
}
