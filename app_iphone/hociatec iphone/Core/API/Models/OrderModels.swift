import Foundation

struct OrderListData: Decodable {
    let items: [OrderSummary]
}

struct OrderData: Decodable {
    let order: OrderSummary
}

struct CheckoutResponseData: Decodable {
    let order: OrderSummary?
    let mode: String?
    let checkoutUrl: String?
    let checkoutSessionId: String?
}

struct CheckoutResult {
    let order: OrderSummary?
    let checkoutURL: URL?
    let checkoutSessionId: String?

    var requiresRedirect: Bool {
        checkoutURL != nil
    }
}

struct CheckoutSessionStatusData: Decodable {
    let status: String
    let checkoutSessionId: String
    let orderId: Int?
    let order: OrderSummary?
}

struct OrderSummary: Decodable, Identifiable {
    let id: Int
    let number: String
    let status: String
    let statusLabel: String
    let totalPriceCents: Int
    let createdAt: Date
    let shipping: OrderShipping
    let items: [OrderLineItem]
}

struct OrderShipping: Decodable {
    let name: String
    let address: String
    let postalCode: String
    let city: String
}

struct OrderLineItem: Decodable, Identifiable {
    var id: String { "\(backendId ?? 0)-\(productSku)-\(productName)-\(quantity)" }

    let backendId: Int?
    let productName: String
    let productSku: String
    let quantity: Int
    let unitPriceCents: Int
    let linePriceCents: Int
    let canReview: Bool?
    let review: Review?

    private enum CodingKeys: String, CodingKey {
        case orderItemId
        case legacyId = "id"
        case productName
        case productSku
        case quantity
        case unitPriceCents
        case linePriceCents
        case canReview
        case review
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        backendId = try container.decodeIfPresent(Int.self, forKey: .orderItemId)
            ?? container.decodeIfPresent(Int.self, forKey: .legacyId)
        productName = try container.decode(String.self, forKey: .productName)
        productSku = try container.decode(String.self, forKey: .productSku)
        quantity = try container.decode(Int.self, forKey: .quantity)
        unitPriceCents = try container.decode(Int.self, forKey: .unitPriceCents)
        linePriceCents = try container.decode(Int.self, forKey: .linePriceCents)
        canReview = try container.decodeIfPresent(Bool.self, forKey: .canReview)
        review = try container.decodeIfPresent(Review.self, forKey: .review)
    }
}
