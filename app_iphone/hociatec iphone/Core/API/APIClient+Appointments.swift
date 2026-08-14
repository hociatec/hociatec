import Foundation

extension APIClient {
    func appointmentPrestations() async throws -> [AppointmentPrestation] {
        let data: AppointmentPrestationList = try await request(
            path: "api/public/appointments/prestations"
        )
        return data.items
    }

    func appointmentAvailability(prestationId: Int, start: Date, end: Date) async throws -> [AppointmentSlot] {
        let data: AppointmentAvailability = try await request(
            path: "api/public/appointments/availability",
            query: [
                URLQueryItem(name: "start", value: isoFormatter.string(from: start)),
                URLQueryItem(name: "end", value: isoFormatter.string(from: end)),
                URLQueryItem(name: "prestationId", value: "\(prestationId)")
            ],
            authorized: false,
            attachCartToken: false
        )
        return data.slots
    }

    func bookAppointment(prestationId: Int, startAt: Date) async throws -> AppointmentSummary {
        let body: [String: Any] = [
            "prestationId": prestationId,
            "startAt": isoFormatter.string(from: startAt)
        ]
        let appointment: AppointmentSummary = try await request(
            path: "api/appointments",
            method: "POST",
            body: body,
            authorized: true,
            attachCartToken: false
        )
        return appointment
    }

    func cancelAppointment(id: Int) async throws {
        let body: [String: Any] = ["status": "cancelled"]
        do {
            try await send(
                path: "api/appointments/\(id)/status",
                method: "PATCH",
                body: body,
                authorized: true,
                attachCartToken: false
            )
        } catch let APIError.httpStatus(code, _) where code == 404 || code == 405 || (500...599).contains(code) {
            try await send(
                path: "api/appointments/\(id)/cancel",
                method: "POST",
                authorized: true,
                attachCartToken: false
            )
        }
    }

    func rescheduleAppointment(id: Int, startAt: Date) async throws -> AppointmentSummary {
        let data: AppointmentData = try await request(
            path: "api/appointments/\(id)/reschedule",
            method: "PATCH",
            body: ["startAt": isoFormatter.string(from: startAt)],
            authorized: true,
            attachCartToken: false
        )

        return data.appointment
    }

    func myAppointments() async throws -> AppointmentList {
        try await request(
            path: "api/appointments/me",
            authorized: true,
            attachCartToken: false
        )
    }
}
