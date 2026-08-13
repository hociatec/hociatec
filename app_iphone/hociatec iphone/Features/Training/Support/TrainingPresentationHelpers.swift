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
