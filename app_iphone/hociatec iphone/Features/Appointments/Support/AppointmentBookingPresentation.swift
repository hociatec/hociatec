import Foundation

enum AppointmentBookingPresentation {
    private static let calendar = Calendar(identifier: .gregorian)

    static func selectedPrestation(
        prestations: [AppointmentPrestation],
        selectedPrestationId: Int?
    ) -> AppointmentPrestation? {
        prestations.first(where: { $0.id == selectedPrestationId })
    }

    static func slotsByDay(
        slots: [AppointmentSlot],
        startDate: Date
    ) -> [Date: [AppointmentSlot]] {
        let startLocal = calendar.startOfDay(for: startDate)
        let filtered = slots.filter { calendar.startOfDay(for: $0.startAt) >= startLocal }

        return Dictionary(grouping: filtered) { slot in
            calendar.startOfDay(for: slot.startAt)
        }
    }

    static func sortedDays(from slotsByDay: [Date: [AppointmentSlot]]) -> [Date] {
        slotsByDay.keys.sorted()
    }

    static func timeRange(for slot: AppointmentSlot) -> String {
        "\(timeFormatter.string(from: slot.startAt)) - \(timeFormatter.string(from: slot.endAt))"
    }
}
