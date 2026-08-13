import Foundation

func nonEmptyText(_ value: String?) -> String? {
    guard let trimmed = value?.trimmingCharacters(in: .whitespacesAndNewlines), !trimmed.isEmpty else {
        return nil
    }
    return trimmed
}

func trainingDurationLabel(_ minutes: Int) -> String {
    if minutes >= 60 {
        let hours = Double(minutes) / 60.0
        if hours.rounded() == hours {
            return "\(Int(hours)) h"
        }
        return String(format: "%.1f h", hours).replacingOccurrences(of: ".", with: ",")
    }
    return "\(minutes) min"
}

let trainingDateTimeFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateStyle = .medium
    formatter.timeStyle = .short
    return formatter
}()

let trainingDateFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateStyle = .medium
    formatter.timeStyle = .none
    return formatter
}()

let trainingISODateFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.calendar = Calendar(identifier: .gregorian)
    formatter.locale = Locale(identifier: "en_US_POSIX")
    formatter.timeZone = TimeZone(secondsFromGMT: 0)
    formatter.dateFormat = "yyyy-MM-dd"
    return formatter
}()

func trainingLatestStartTime(session: TrainingSession, durationMinutes: Int) -> String {
    guard
        let dayEnd = trainingTimeComponents(from: session.dailyEndTime),
        let latest = Calendar(identifier: .gregorian).date(
            byAdding: .minute,
            value: -durationMinutes,
            to: dayEnd
        )
    else {
        return session.dailyEndTime
    }

    return trainingTimeFormatter.string(from: latest)
}

func trainingAvailableDates(for session: TrainingSession) -> [Date] {
    let calendar = Calendar(identifier: .gregorian)
    let start = calendar.startOfDay(for: session.startsAt)
    let end = calendar.startOfDay(for: session.endsAt)
    guard start <= end else { return [] }

    var dates: [Date] = []
    var current = start

    while current <= end {
        if session.includeWeekends || !calendar.isDateInWeekend(current) {
            dates.append(current)
        }
        guard let next = calendar.date(byAdding: .day, value: 1, to: current) else { break }
        current = next
    }

    return dates
}

func trainingAvailableStartTimes(for session: TrainingSession, durationMinutes: Int) -> [String] {
    let calendar = Calendar(identifier: .gregorian)
    guard
        let start = trainingTimeComponents(from: session.dailyStartTime),
        let latest = trainingTimeComponents(from: trainingLatestStartTime(session: session, durationMinutes: durationMinutes))
    else {
        return [session.dailyStartTime]
    }

    var times: [String] = []
    var current = start

    while current <= latest {
        times.append(trainingTimeFormatter.string(from: current))
        guard let next = calendar.date(byAdding: .minute, value: 30, to: current) else { break }
        current = next
    }

    return times
}

func trainingEndDateTime(dateKey: String, time: String, durationMinutes: Int) -> Date? {
    guard let startsAt = trainingStartDateTime(dateKey: dateKey, time: time) else { return nil }
    return Calendar(identifier: .gregorian).date(byAdding: .minute, value: durationMinutes, to: startsAt)
}

func trainingStartDateTime(dateKey: String, time: String) -> Date? {
    guard let date = trainingISODateFormatter.date(from: dateKey) else { return nil }
    let calendar = Calendar(identifier: .gregorian)
    let dateComponents = calendar.dateComponents([.year, .month, .day], from: date)
    guard let timeComponents = trainingClockComponents(from: time) else { return nil }

    var components = DateComponents()
    components.year = dateComponents.year
    components.month = dateComponents.month
    components.day = dateComponents.day
    components.hour = timeComponents.hour
    components.minute = timeComponents.minute

    return calendar.date(from: components)
}

private let trainingTimeFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.calendar = Calendar(identifier: .gregorian)
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.timeZone = TimeZone(secondsFromGMT: 0)
    formatter.dateFormat = "HH:mm"
    return formatter
}()

private func trainingTimeComponents(from value: String) -> Date? {
    trainingTimeFormatter.date(from: value)
}

private func trainingClockComponents(from value: String) -> DateComponents? {
    guard let date = trainingTimeComponents(from: value) else { return nil }
    return Calendar(identifier: .gregorian).dateComponents([.hour, .minute], from: date)
}
