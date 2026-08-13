import Foundation

struct AppointmentService: AppointmentServing {
    let api: APIClient

    func appointmentPrestations() async throws -> [AppointmentPrestation] { try await api.appointmentPrestations() }
    func appointmentAvailability(prestationId: Int, start: Date, end: Date) async throws -> [AppointmentSlot] {
        try await api.appointmentAvailability(prestationId: prestationId, start: start, end: end)
    }
    func bookAppointment(prestationId: Int, startAt: Date) async throws -> AppointmentSummary {
        try await api.bookAppointment(prestationId: prestationId, startAt: startAt)
    }
    func cancelAppointment(id: Int) async throws { try await api.cancelAppointment(id: id) }
    func myAppointments() async throws -> AppointmentList { try await api.myAppointments() }
}
