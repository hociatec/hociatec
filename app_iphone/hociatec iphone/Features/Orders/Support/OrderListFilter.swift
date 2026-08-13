import Foundation

enum OrderListFilter: String, CaseIterable, Identifiable {
    case all
    case pending
    case completed
    case cancelled

    var id: String { rawValue }

    var label: String {
        switch self {
        case .all: return "Toutes"
        case .pending: return "En attente"
        case .completed: return "Terminées"
        case .cancelled: return "Annulées"
        }
    }

    func matches(_ order: OrderSummary) -> Bool {
        switch self {
        case .all:
            return true
        case .pending:
            return order.status.lowercased() == "pending"
        case .completed:
            return !OrderStatusPresentation.isCancelled(order.status) && order.status.lowercased() != "pending"
        case .cancelled:
            return OrderStatusPresentation.isCancelled(order.status)
        }
    }
}
