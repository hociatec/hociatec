import Foundation

struct OrderService: OrderServing {
    let api: APIClient

    func myOrders() async throws -> [OrderSummary] { try await api.myOrders() }
    func order(id: Int) async throws -> OrderSummary { try await api.order(id: id) }
    func cancelOrder(id: Int) async throws -> OrderSummary { try await api.cancelOrder(id: id) }
    func pendingReviews() async throws -> [PendingReviewItem] { try await api.pendingReviews() }
    func createReview(orderId: Int, orderItemId: Int, score: Int, comment: String?) async throws -> Review {
        try await api.createReview(orderId: orderId, orderItemId: orderItemId, score: score, comment: comment)
    }
}
