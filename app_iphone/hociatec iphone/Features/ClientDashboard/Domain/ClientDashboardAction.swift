import Foundation

struct ClientDashboardAction: Identifiable {
    enum Destination {
        case pendingReviews
        case appointments
        case quotes
        case favorites
        case orders
        case trainings
        case communicationPreferences
        case addresses
        case profile
        case support
        case vouchers
        case audits
        case tradeIns
        case beta
    }

    let id: String
    let title: String
    let detail: String
    let destination: Destination
}
