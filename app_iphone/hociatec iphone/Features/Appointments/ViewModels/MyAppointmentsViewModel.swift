import Foundation
import Combine

@MainActor
final class MyAppointmentsViewModel: ObservableObject {
    @Published var upcoming: [AppointmentSummary] = []
    @Published var past: [AppointmentSummary] = []
    @Published var isLoading = false
    @Published var error: String?
    @Published var successMessage: String? = nil

    private let service: AppointmentServing
    private var loadRequestID = 0
    private var mutationRequestID = 0

    init(service: AppointmentServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil

        do {
            let list = try await service.myAppointments()
            guard requestID == loadRequestID else { return }
            apply(list: list)
        } catch let err {
            guard requestID == loadRequestID else { return }
            self.error = err.localizedDescription
        }

        if requestID == loadRequestID {
            isLoading = false
        }
    }

    func cancel(appointmentID: Int) async -> Bool {
        guard !isLoading else { return false }
        mutationRequestID += 1
        let requestID = mutationRequestID
        isLoading = true
        error = nil
        successMessage = nil
        do {
            try await service.cancelAppointment(id: appointmentID)
            let list = try await service.myAppointments()
            guard requestID == mutationRequestID else { return false }
            apply(list: list)
            successMessage = "Rendez-vous annulé."
            isLoading = false
            return true
        } catch {
            guard requestID == mutationRequestID else { return false }
            self.error = error.localizedDescription
            isLoading = false
            return false
        }
    }

    private func apply(list: AppointmentList) {
        upcoming = list.upcoming
        past = list.past
    }

    var upcomingFiltered: [AppointmentSummary] {
        upcoming.filter { !$0.isCancelledStatus }.sorted { $0.startAt < $1.startAt }
    }

    var pastFiltered: [AppointmentSummary] {
        past.filter { !$0.isCancelledStatus }.sorted { $0.startAt > $1.startAt }
    }

    var allAppointments: [AppointmentSummary] {
        (upcomingFiltered + pastFiltered).sorted { lhs, rhs in
            if lhs.startAt == rhs.startAt {
                return lhs.id > rhs.id
            }

            let lhsUpcoming = lhs.startAt >= Date()
            let rhsUpcoming = rhs.startAt >= Date()

            if lhsUpcoming != rhsUpcoming {
                return lhsUpcoming && !rhsUpcoming
            }

            return lhsUpcoming ? lhs.startAt < rhs.startAt : lhs.startAt > rhs.startAt
        }
    }

    func nextUpcoming() -> AppointmentSummary? {
        upcomingFiltered.first
    }

    func appointments(for tab: AppointmentTabFilter) -> [AppointmentSummary] {
        switch tab {
        case .all:
            return allAppointments
        case .upcoming:
            return upcomingFiltered
        case .past:
            return pastFiltered
        }
    }

    func visibleAppointments(for tab: AppointmentTabFilter) -> [AppointmentSummary] {
        let items = appointments(for: tab)
        if tab == .upcoming, let next = nextUpcoming() {
            return items.filter { $0.id != next.id }
        }
        return items
    }

    func emptyStateMessage(for tab: AppointmentTabFilter) -> String {
        switch tab {
        case .all: return "Aucun rendez-vous."
        case .upcoming: return "Aucun rendez-vous à venir."
        case .past: return "Aucun rendez-vous passé."
        }
    }
}
