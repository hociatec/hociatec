import Foundation

enum OrderListSortOption: String, CaseIterable, Identifiable {
    case dateDesc
    case dateAsc

    var id: String { rawValue }

    var label: String {
        switch self {
        case .dateDesc: return "Date ↓"
        case .dateAsc: return "Date ↑"
        }
    }

    func sort(_ orders: [OrderSummary]) -> [OrderSummary] {
        switch self {
        case .dateAsc:
            return orders.sorted { $0.createdAt < $1.createdAt }
        case .dateDesc:
            return orders.sorted { $0.createdAt > $1.createdAt }
        }
    }
}
