import Foundation
import SwiftUI

enum OrderStatusPresentation {
    static let dateFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "fr_FR")
        formatter.dateStyle = .medium
        formatter.timeStyle = .short
        return formatter
    }()

    static func isCancelled(_ status: String) -> Bool {
        let normalized = status.lowercased()
        return normalized.contains("cancel") || normalized.contains("annul")
    }

    static func color(for status: String) -> Color {
        let normalized = status.lowercased()
        if isCancelled(normalized) {
            return .red
        }
        if normalized.contains("pending") {
            return .orange
        }
        return .green
    }
}
