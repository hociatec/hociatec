import Foundation

struct QuoteService: Decodable, Identifiable {
    let id: Int
    let title: String
    let description: String?
    let unit: String?
    let isFeaturedHome: Bool
    let imageUrl: String?
    let imageAlt: String?
    let durationValue: Int?
    let durationUnit: String?
    let durationLabel: String?
    let priceCents: Int
    let vatRate: Double
}

struct QuoteListData: Decodable {
    let items: [QuoteSummary]
}

struct QuoteSummary: Decodable, Identifiable {
    let id: Int
    let number: String?
    let status: String
    let customer: QuoteCustomer
    let items: [QuoteItemSummary]
    let discountCents: Int
    let shippingCents: Int
    let conditions: String?
    let totals: QuoteTotals
    let createdAt: Date
    let updatedAt: Date?

    private enum CodingKeys: String, CodingKey {
        case id
        case number
        case status
        case customer
        case items
        case discountCents
        case global_discount_cents
        case shippingCents
        case shipping_cents
        case conditions
        case totals
        case createdAt
        case created_at
        case updatedAt
        case updated_at
        case customer_name
        case customer_email
        case customer_company
        case customer_address
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        id = try container.decode(Int.self, forKey: .id)
        number = try container.decodeIfPresent(String.self, forKey: .number)
        status = try container.decode(String.self, forKey: .status)

        if let nestedCustomer = try? container.decode(QuoteCustomer.self, forKey: .customer) {
            customer = nestedCustomer
        } else {
            let name = try container.decodeIfPresent(String.self, forKey: .customer_name) ?? ""
            let email = try container.decodeIfPresent(String.self, forKey: .customer_email) ?? ""
            let company = try container.decodeIfPresent(String.self, forKey: .customer_company)
            let address = try container.decodeIfPresent(String.self, forKey: .customer_address)
            customer = QuoteCustomer(name: name, email: email, company: company, address: address)
        }

        items = (try? container.decode([QuoteItemSummary].self, forKey: .items)) ?? []
        discountCents = try container.decodeIfPresent(Int.self, forKey: .discountCents)
            ?? (try container.decodeIfPresent(Int.self, forKey: .global_discount_cents) ?? 0)
        shippingCents = try container.decodeIfPresent(Int.self, forKey: .shippingCents)
            ?? (try container.decodeIfPresent(Int.self, forKey: .shipping_cents) ?? 0)
        conditions = try container.decodeIfPresent(String.self, forKey: .conditions)
        totals = try container.decode(QuoteTotals.self, forKey: .totals)
        createdAt = try container.decodeIfPresent(Date.self, forKey: .createdAt)
            ?? container.decode(Date.self, forKey: .created_at)
        updatedAt = try container.decodeIfPresent(Date.self, forKey: .updatedAt)
            ?? container.decodeIfPresent(Date.self, forKey: .updated_at)
    }
}

struct QuoteCustomer: Decodable {
    let name: String
    let email: String
    let company: String?
    let address: String?
}

struct QuoteItemSummary: Decodable, Identifiable {
    let id: Int
    let name: String
    let description: String?
    let unit: String?
    let quantity: Int
    let unitPriceCents: Int
    let vatRate: Double
    let lineTotals: QuoteLineTotals
}

struct QuoteLineTotals: Decodable {
    let ht: Int
    let vat: Int
    let ttc: Int
}

struct QuoteTotals: Decodable {
    let ht: Int
    let vat: Int
    let ttc: Int
}

struct QuoteServiceList: Decodable {
    let items: [QuoteService]
    let meta: PaginationMeta?
}

struct QuoteRequestItem {
    enum ItemType: String {
        case service
        case product
        case custom
    }

    let serviceId: Int?
    let productId: Int?
    let quantity: Int
    let description: String?
    let name: String?
    let unitPriceCents: Int?
    let type: ItemType?

    func toPayload() -> [String: Any] {
        var payload: [String: Any] = [
            "quantity": quantity,
        ]
        if let serviceId { payload["serviceId"] = serviceId }
        if let productId { payload["productId"] = productId }
        let derivedType: ItemType = type ?? (serviceId != nil ? .service : (productId != nil ? .product : .custom))
        payload["type"] = derivedType.rawValue
        if let name, !name.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            payload["name"] = name
        }
        if let unitPriceCents {
            payload["unitPriceCents"] = unitPriceCents
        }
        payload["description"] = description as Any
        return payload
    }
}

struct QuoteDraftItem: Identifiable {
    let id = UUID()
    var serviceId: Int?
    var productId: Int?
    var quantity: Int = 1
    var description: String = ""
    var title: String?
    var unitPriceCents: Int?
}

extension QuoteDraftItem {
    var isCustom: Bool { serviceId == nil && productId == nil }

    var displayTitle: String {
        let cleaned = (title ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
        return cleaned.isEmpty ? "Ligne" : cleaned
    }

    var lineTotalCents: Int {
        max(0, quantity) * (unitPriceCents ?? 0)
    }
}

extension QuoteSummary {
    var statusLabel: String {
        switch status.trimmingCharacters(in: .whitespacesAndNewlines).lowercased() {
        case "draft", "brouillon":
            return "Brouillon"
        case "sent", "envoye", "envoyé":
            return "Envoyé"
        case "accepted", "accepte", "accepté":
            return "Accepté"
        case "refused", "refuse", "refusé":
            return "Refusé"
        case "expired", "expire", "expiré":
            return "Expiré"
        default:
            return status.capitalized
        }
    }
}
