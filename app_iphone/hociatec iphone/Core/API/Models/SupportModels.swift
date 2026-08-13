import Foundation

struct SupportAttachment: Decodable, Identifiable, Hashable {
    var id: String { name }
    let name: String
    let originalName: String
    let contentType: String
    let size: Int
    let uploadedAt: Date
}

struct SupportTimelineEntry: Decodable, Identifiable {
    let id: String
    let type: String
    let actor: String
    let visibility: String
    let authorLabel: String
    let subject: String?
    let message: String?
    let status: String?
    let statusLabel: String?
    let attachments: [SupportAttachment]
    let createdAt: Date
}

struct SupportCustomer: Decodable {
    let id: Int
    let name: String
    let email: String
}

struct SupportOrderReference: Decodable {
    let id: Int?
    let number: String?
}

struct SupportRequestSummary: Decodable, Identifiable {
    let id: Int
    let status: String
    let statusLabel: String
    let reason: String
    let subject: String
    let message: String?
    let customer: SupportCustomer
    let order: SupportOrderReference?
    let attachments: [SupportAttachment]
    let awaitingReplyFrom: String?
    let awaitingReplyLabel: String?
    let timeline: [SupportTimelineEntry]
    let createdAt: Date
    let updatedAt: Date
    let resolvedAt: Date?
}

struct SupportRequestListData: Decodable {
    let items: [SupportRequestSummary]
    let meta: PaginationMeta
}

struct SupportRequestItemData: Decodable {
    let item: SupportRequestSummary
}
