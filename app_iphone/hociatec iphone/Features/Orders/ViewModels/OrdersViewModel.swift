import Foundation
import Combine

@MainActor
final class OrdersViewModel: ObservableObject {
    @Published var orders: [OrderSummary] = []
    @Published var isLoading = false
    @Published var error: String?
    @Published var cancellingOrderID: Int?

    private let service: OrderServing
    private var loadRequestID = 0
    private var detailRequestID = 0

    init(service: OrderServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil

        do {
            let loadedOrders = try await service.myOrders()
            guard requestID == loadRequestID else { return }
            orders = loadedOrders
        } catch let err {
            guard requestID == loadRequestID else { return }
            self.error = err.localizedDescription
        }

        if requestID == loadRequestID {
            isLoading = false
        }
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
        detailRequestID += 1
        let requestID = detailRequestID
        do {
            let detail = try await service.order(id: id)
            guard requestID == detailRequestID else { return nil }
            if let idx = orders.firstIndex(where: { $0.id == detail.id }) {
                orders[idx] = detail
            }
            return detail
        } catch let err {
            guard requestID == detailRequestID else { return nil }
            self.error = err.localizedDescription
            return nil
        }
    }
}
