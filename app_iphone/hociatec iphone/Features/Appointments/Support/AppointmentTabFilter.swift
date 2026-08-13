import Foundation

enum AppointmentTabFilter: String, CaseIterable, Identifiable {
    case upcoming = "À venir"
    case past = "Passés"
    case cancelled = "Annulés"

    var id: String { rawValue }
    var label: String { rawValue }
}
