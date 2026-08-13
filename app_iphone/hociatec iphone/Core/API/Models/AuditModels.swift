import Foundation

struct AuditMetadata: Decodable {
    let types: [AuditOption]
    let statuses: [AuditOption]
}

struct AuditOption: Decodable, Identifiable, Hashable {
    let value: String
    let label: String

    var id: String { value }
}

struct AuditListItem: Decodable, Identifiable {
    let id: Int
    let number: String
    let type: String
    let status: String
    let typeLabel: String
    let statusLabel: String
    let url: String
    let createdAt: Date
}

struct AuditChecklistItem: Decodable, Identifiable {
    let id: Int
    let category: String
    let key: String
    let label: String
    let position: Int
    let level: String?
    let isCompliant: Bool?
    let comment: String?
}

struct AuditEvent: Decodable, Identifiable {
    let id: Int
    let type: String
    let message: String?
    let createdAt: Date
}

struct AuditDetail: Decodable, Identifiable {
    let id: Int
    let number: String
    let type: String
    let typeLabel: String
    let status: String
    let statusLabel: String
    let url: String
    let objectives: String?
    let createdAt: Date
    let items: [AuditChecklistItem]
    let events: [AuditEvent]
}

struct AuditListData: Decodable {
    let items: [AuditListItem]
    let meta: PaginationMeta
}

struct AuditCreateResponse: Decodable {
    let id: Int
    let number: String
}
