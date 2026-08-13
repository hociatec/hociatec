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
        slots: [AppointmentSlot]
    ) -> [Date: [AppointmentSlot]] {
        Dictionary(grouping: slots) { slot in
            calendar.startOfDay(for: slot.startAt)
        }
    }

    static func sortedDays(from slotsByDay: [Date: [AppointmentSlot]]) -> [Date] {
        slotsByDay.keys.sorted()
    }

    static func availableDays(from slots: [AppointmentSlot]) -> [Date] {
        sortedDays(from: slotsByDay(slots: slots))
    }

    static func daySlots(for date: Date, from slots: [AppointmentSlot]) -> [AppointmentSlot] {
        let selectedDay = calendar.startOfDay(for: date)
        return slots
            .filter { calendar.startOfDay(for: $0.startAt) == selectedDay }
            .sorted { $0.startAt < $1.startAt }
    }

    static func monthTitle(for date: Date) -> String {
        monthFormatter.string(from: date)
    }

    static func weekdaySymbols() -> [String] {
        calendar.veryShortWeekdaySymbols.rotatedFromMonday()
    }

    static func monthGridDays(for month: Date) -> [Date] {
        let startOfMonth = calendar.date(
            from: calendar.dateComponents([.year, .month], from: month)
        ) ?? month
        let weekday = calendar.component(.weekday, from: startOfMonth)
        let mondayBasedOffset = (weekday + 5) % 7
        let firstVisible = calendar.date(byAdding: .day, value: -mondayBasedOffset, to: startOfMonth) ?? startOfMonth

        return (0..<42).compactMap {
            calendar.date(byAdding: .day, value: $0, to: firstVisible)
        }
    }

    static func isSameMonth(_ lhs: Date, _ rhs: Date) -> Bool {
        calendar.isDate(lhs, equalTo: rhs, toGranularity: .month)
    }

    static func isSameDay(_ lhs: Date, _ rhs: Date) -> Bool {
        calendar.isDate(lhs, inSameDayAs: rhs)
    }

    static func isPastDay(_ date: Date) -> Bool {
        calendar.startOfDay(for: date) < calendar.startOfDay(for: Date())
    }

    static func timeRange(for slot: AppointmentSlot) -> String {
        "\(timeFormatter.string(from: slot.startAt)) - \(timeFormatter.string(from: slot.endAt))"
    }
}

private let monthFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateFormat = "LLLL yyyy"
    return formatter
}()

private extension Array where Element == String {
    func rotatedFromMonday() -> [String] {
        guard count == 7 else { return self }
        return Array(self[1...]) + [self[0]]
    }
}
