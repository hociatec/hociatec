import Foundation

extension APIClient {
    func myOrders() async throws -> [OrderSummary] {
        let data: OrderListData = try await request(
            path: "api/orders/me",
            authorized: true
        )
        return data.items
    }

    func pendingReviews() async throws -> [PendingReviewItem] {
        let data: PendingReviewListData = try await request(
            path: "api/orders/me/pending-reviews",
            authorized: true,
            attachCartToken: false
        )
        return data.items
    }

    func order(id: Int) async throws -> OrderSummary {
        let data: OrderData = try await request(
            path: "api/orders/\(id)",
            authorized: true
        )
        return data.order
    }

    func cancelOrder(id: Int) async throws -> OrderSummary {
        let data: OrderData = try await request(
            path: "api/orders/\(id)/cancel",
            method: "POST",
            authorized: true
        )
        return data.order
    }

    func createReview(orderId: Int, orderItemId: Int, score: Int, comment: String?) async throws -> Review {
        var body: [String: Any] = ["score": score]
        if let comment, !comment.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            body["comment"] = comment
        }
        let data: ReviewData = try await request(
            path: "api/orders/\(orderId)/items/\(orderItemId)/review",
            method: "POST",
            body: body,
            authorized: true,
            attachCartToken: false
        )
        return data.review
    }

    func checkout() async throws -> CheckoutResult {
        let data: CheckoutResponseData = try await request(
            path: "api/orders/checkout",
            method: "POST",
            body: ["clientPlatform": "ios"],
            authorized: true,
            attachCartToken: true
        )

        if let order = data.order {
            return CheckoutResult(order: order, checkoutURL: nil, checkoutSessionId: nil)
        }

        let url = data.checkoutUrl.flatMap(URL.init(string:))
        if data.mode == "redirect", url != nil {
            return CheckoutResult(order: nil, checkoutURL: url, checkoutSessionId: data.checkoutSessionId)
        }

        throw APIError.invalidResponse
    }

    func checkoutSessionStatus(stripeSessionId: String) async throws -> CheckoutSessionStatusData {
        try await request(
            path: "api/orders/checkout/sessions/\(stripeSessionId)",
            authorized: true,
            attachCartToken: false
        )
    }
}
