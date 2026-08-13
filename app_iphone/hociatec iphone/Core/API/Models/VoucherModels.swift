import Foundation

struct VoucherListItem: Decodable, Identifiable {
    let id: Int
    let name: String
    let code: String
    let description: String?
    let discountType: String
    let discountValue: Int
    let isActive: Bool
    let startsAt: Date?
    let endsAt: Date?
    let sentAt: Date?
    let createdAt: Date
    let updatedAt: Date
}

struct VoucherListData: Decodable {
    let items: [VoucherListItem]
    let meta: PaginationMeta
}
