import Foundation

struct APIEnvelope<T: Decodable>: Decodable {
    let status: String
    let data: T
    let message: String?
    let details: [String]?
}

struct APIErrorPayload: Decodable {
    let status: String?
    let message: String?
    let details: [String]?
}

enum APIError: LocalizedError {
    case transport(Error)
    case invalidResponse
    case httpStatus(Int, String)
    case decoding

    var errorDescription: String? {
        switch self {
        case .transport(let error):
            return error.localizedDescription
        case .invalidResponse:
            return "Réponse invalide du serveur."
        case .httpStatus(_, let message):
            return message
        case .decoding:
            return "Impossible de lire la réponse du serveur."
        }
    }
}

enum SellingType: String, Decodable {
    case sale
    case rental
    case unknown

    init(from decoder: Decoder) throws {
        let container = try decoder.singleValueContainer()
        let raw = (try? container.decode(String.self)) ?? ""
        self = SellingType(rawValue: raw) ?? .unknown
    }
}

struct CategorySummary: Decodable, Equatable, Identifiable {
    let id: Int
    let name: String
    let slug: String
}

struct Product: Decodable, Identifiable {
    let id: Int
    let name: String
    let slug: String
    let sku: String
    let shortDescription: String
    let description: String
    let priceCents: Int
    let sellingType: SellingType
    let effectivePriceCents: Int
    let stock: Int
    let isPublished: Bool
    let isFeaturedHome: Bool
    let imageUrl: String?
    let imageAlt: String?
    let createdAt: Date?
    let updatedAt: Date?
    let category: CategorySummary
}

struct CartItem: Decodable, Identifiable {
    let id: Int
    let product: Product
    let quantity: Int
    let linePriceCents: Int
    let rentalMonths: Int?
}

struct Cart: Decodable {
    let token: String
    let items: [CartItem]
    let totalQuantity: Int
    let totalPriceCents: Int
    let updatedAt: Date?
}

struct ProductListData: Decodable {
    let items: [Product]
}

struct CategoryListData: Decodable {
    let items: [CategorySummary]
}

struct AddressListData: Decodable {
    let items: [UserAddress]
}

struct CartData: Decodable {
    let cart: Cart
}

struct OrderListData: Decodable {
    let items: [OrderSummary]
}

struct OrderData: Decodable {
    let order: OrderSummary
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
    // Keep Identifiable conformance using a stable composite id
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

struct UserAddress: Codable, Identifiable, Equatable {
    let id: Int?
    var label: String
    var address: String
    var postalCode: String
    var city: String
    var isDefault: Bool

    private enum CodingKeys: String, CodingKey {
        case id
        case label = "name"
        case address
        case postalCode
        case city
        case isDefault
    }
}

struct LoginResponse: Decodable {
    let token: String
}

struct AuthSessionData: Decodable {
    let authenticated: Bool
    let id: Int?
    let email: String?
    let firstName: String?
    let lastName: String?
    let roles: [String]?
    let address: String?
    let postalCode: String?
    let city: String?
    let birthDate: String?
    let phoneNumber: String?
    let gender: String?

    var profile: UserProfile? {
        guard authenticated,
              let id,
              let email,
              let firstName,
              let lastName,
              let roles,
              let birthDate,
              let phoneNumber
        else {
            return nil
        }

        return UserProfile(
            id: id,
            email: email,
            firstName: firstName,
            lastName: lastName,
            roles: roles,
            address: address,
            postalCode: postalCode,
            city: city,
            birthDate: birthDate,
            phoneNumber: phoneNumber,
            gender: gender,
            addresses: nil
        )
    }
}

struct CsrfTokenData: Decodable {
    let token: String
}

struct UserProfile: Codable, Identifiable {
    let id: Int
    let email: String
    let firstName: String
    let lastName: String
    let roles: [String]
    let address: String?
    let postalCode: String?
    let city: String?
    let birthDate: String
    let phoneNumber: String
    let gender: String?
    let addresses: [UserAddress]?
    
    var fullName: String {
        "\(firstName) \(lastName)"
    }
}

struct AppointmentPrestation: Decodable, Identifiable {
    let id: Int
    let name: String
    let durationMinutes: Int
    let priceCents: Int
}

struct AppointmentPrestationList: Decodable {
    let items: [AppointmentPrestation]
}

struct AppointmentSlot: Decodable, Identifiable {
    var id: String { "\(startAt.timeIntervalSince1970)" }
    let startAt: Date
    let endAt: Date

    private enum CodingKeys: String, CodingKey {
        case startAt = "start"
        case endAt = "end"
    }
}

struct AppointmentAvailability: Decodable {
    let slots: [AppointmentSlot]
}

struct AppointmentSummary: Decodable, Identifiable {
    let id: Int
    let startAt: Date
    let endAt: Date
    let status: String?
    let statusCode: String?
    let isCancelable: Bool?
    let prestation: AppointmentPrestation
}

extension AppointmentSummary {
    var isCancelledStatus: Bool {
        // Prefer machine-readable status code when available, fallback to label.
        let raw = (statusCode ?? status ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
        let normalized = raw.folding(options: .diacriticInsensitive, locale: .current).lowercased()
        return normalized.contains("cancel") || normalized.contains("annul")
    }

    var canCancel: Bool {
        if let isCancelable {
            return isCancelable
        }
        if isCancelledStatus { return false }
        let raw = (statusCode ?? status ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
        let normalized = raw.folding(options: .diacriticInsensitive, locale: .current).lowercased()
        let isConfirmed = normalized == "confirmed" || normalized.contains("conf")
        return isConfirmed && startAt > Date()
    }
}

struct AppointmentList: Decodable {
    let upcoming: [AppointmentSummary]
    let past: [AppointmentSummary]
}

struct AppointmentData: Decodable {
    let appointment: AppointmentSummary
}

// MARK: - Quotes

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

        // Decode customer either from nested object or from flattened fields
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
        // Support both discountCents and global_discount_cents
        discountCents = try container.decodeIfPresent(Int.self, forKey: .discountCents)
            ?? (try container.decodeIfPresent(Int.self, forKey: .global_discount_cents) ?? 0)
        // Support both shippingCents and shipping_cents
        shippingCents = try container.decodeIfPresent(Int.self, forKey: .shippingCents)
            ?? (try container.decodeIfPresent(Int.self, forKey: .shipping_cents) ?? 0)
        conditions = try container.decodeIfPresent(String.self, forKey: .conditions)
        totals = try container.decode(QuoteTotals.self, forKey: .totals)
        // Support both createdAt and created_at
        createdAt = try container.decodeIfPresent(Date.self, forKey: .createdAt)
            ?? container.decode(Date.self, forKey: .created_at)
        // Support both updatedAt and updated_at (optional)
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
}

struct NewsArticle: Decodable, Identifiable {
    let id: Int
    let title: String
    let slug: String
    let excerpt: String
    let content: String
    let category: String?
    let isPublished: Bool
    let viewsCount: Int
    let publishedAt: Date?
    let createdAt: Date
    let updatedAt: Date
}

struct PaginationMeta: Decodable {
    let page: Int
    let perPage: Int
    let total: Int
    let totalPages: Int
}

struct NewsArticleListData: Decodable {
    let items: [NewsArticle]
    let meta: PaginationMeta
}

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

// MARK: - Trade-ins

struct TradeInMetadata: Decodable {
    let categories: [TradeInOption]
    let conditions: [TradeInOption]
}

struct TradeInOption: Decodable, Identifiable, Hashable {
    let value: String
    let label: String

    var id: String { value }
}

struct TradeInSummary: Decodable, Identifiable {
    let id: Int
    let reference: String
    let status: String
    let statusLabel: String
    let category: String
    let categoryLabel: String
    let productName: String
    let purchasePriceCents: Int
    let purchaseYear: Int
    let brand: String?
    let model: String?
    let conditionGrade: String
    let conditionLabel: String
    let functional: Bool
    let hasAccessories: Bool
    let hasProofOfPurchase: Bool
    let description: String
    let estimatedMinCents: Int?
    let estimatedMaxCents: Int?
    let createdAt: Date
}

struct TradeInRequestPayload {
    let firstName: String
    let lastName: String
    let email: String
    let phone: String
    let category: String
    let productName: String
    let purchasePriceCents: Int
    let purchaseYear: Int
    let brand: String?
    let model: String?
    let serialNumber: String?
    let conditionGrade: String
    let functional: Bool
    let hasAccessories: Bool
    let hasProofOfPurchase: Bool
    let description: String
    let catalogProductId: Int?
    let consent: Bool
}
