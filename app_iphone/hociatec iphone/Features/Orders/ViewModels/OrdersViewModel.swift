import Foundation

@MainActor
final class OrdersViewModel: ObservableObject {
    @Published var orders: [OrderSummary] = []
    @Published var isLoading = false
    @Published var error: String?
    @Published var cancellingOrderID: Int?

    private let service: OrderServing

    init(service: OrderServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil

        do {
            orders = try await service.myOrders()
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
    }

    func cancel(order: OrderSummary) async -> OrderSummary? {
        guard order.status == "pending" else { return nil }
        cancellingOrderID = order.id
        defer { cancellingOrderID = nil }

        do {
            let updated = try await service.cancelOrder(id: order.id)
            if let index = orders.firstIndex(where: { $0.id == updated.id }) {
                orders[index] = updated
            }
            return updated
        } catch let err {
            self.error = err.localizedDescription
            return nil
        }
    }

    func detail(for id: Int) async -> OrderSummary? {
        do {
            let detail = try await service.order(id: id)
            if let idx = orders.firstIndex(where: { $0.id == detail.id }) {
                orders[idx] = detail
            }
            return detail
        } catch let err {
            self.error = err.localizedDescription
            return nil
        }
    }
}
