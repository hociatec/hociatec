import Foundation

enum AppointmentTabFilter: String, CaseIterable, Identifiable {
    case all = "Tout"
    case upcoming = "À venir"
    case past = "Passés"

    var id: String { rawValue }
    var label: String { rawValue }
}
