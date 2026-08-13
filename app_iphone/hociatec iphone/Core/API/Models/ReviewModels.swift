import Foundation

struct ReviewAuthor: Decodable {
    let id: Int
    let displayName: String
}

struct Review: Decodable, Identifiable {
    let id: Int
    let score: Int
    let comment: String?
    let createdAt: Date
    let author: ReviewAuthor
    let orderItemId: Int?
}

struct ReviewData: Decodable {
    let review: Review
}

struct ProductRef: Decodable {
    let id: Int
    let name: String
    let sku: String
}

struct PendingReviewItem: Decodable, Identifiable {
    var id: String { "\(orderId)-\(orderItemId)" }
    let orderId: Int
    let orderNumber: String
    let orderCreatedAt: Date
    let orderItemId: Int
    let product: ProductRef
}

struct PendingReviewListData: Decodable {
    let items: [PendingReviewItem]
}

struct ReviewListMeta: Decodable {
    let page: Int
    let perPage: Int
    let total: Int
    let average: Double?
}

struct ReviewListData: Decodable {
    let items: [Review]
    let meta: ReviewListMeta
}
