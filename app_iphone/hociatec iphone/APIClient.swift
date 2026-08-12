import Foundation
import Combine

/// Client HTTP léger pour l’API hociatec.fr.
final class APIClient: ObservableObject {
    let baseURL = URL(string: "https://hociatec.fr")!

    private let session: URLSession
    private let decoder: JSONDecoder
    private let sessionStore: SessionStore
    private let isoFormatter: ISO8601DateFormatter

    init(sessionStore: SessionStore, session: URLSession = .shared) {
        self.sessionStore = sessionStore
        self.session = session

        let decoder = JSONDecoder()
        decoder.dateDecodingStrategy = .iso8601
        self.decoder = decoder

        let formatter = ISO8601DateFormatter()
        formatter.formatOptions = [.withInternetDateTime]
        formatter.timeZone = .init(secondsFromGMT: 0)
        self.isoFormatter = formatter
    }

    func featuredProducts() async throws -> [Product] {
        let data: ProductListData = try await request(
            path: "api/public/catalog/products",
            query: [URLQueryItem(name: "homepage", value: "1")]
        )
        return data.items
    }

    func products(search: String? = nil, categorySlugs: [String]? = nil, sellingType: SellingType? = nil) async throws -> [Product] {
        func fetch(categorySlug: String?) async throws -> [Product] {
            var query: [URLQueryItem] = []
            if let search, !search.isEmpty {
                query.append(.init(name: "q", value: search))
            }
            if let categorySlug, !categorySlug.isEmpty {
                // Backend expects a single category slug under `category`.
                query.append(.init(name: "category", value: categorySlug))
            }
            if let sellingType {
                query.append(.init(name: "sellingType", value: sellingType.rawValue))
            }

            let data: ProductListData = try await request(
                path: "api/public/catalog/products",
                query: query.isEmpty ? nil : query
            )
            return data.items
        }

        let slugs = (categorySlugs ?? []).filter { !$0.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty }
        if slugs.count <= 1 {
            return try await fetch(categorySlug: slugs.first)
        }

        // Backend only supports one category filter; fetch per category and merge.
        // Do it sequentially to avoid rate limiting and make behavior predictable.
        var byId: [Int: Product] = [:]
        var lastError: Error?
        for slug in slugs {
            do {
                let items = try await fetch(categorySlug: slug)
                for p in items { byId[p.id] = p }
            } catch {
                lastError = error
                continue
            }
        }
        if !byId.isEmpty {
            return Array(byId.values)
        }
        if let lastError { throw lastError }
        return []
    }

    func product(slug: String) async throws -> Product {
        try await request(path: "api/public/catalog/products/\(slug)")
    }

    func productReviews(slug: String, page: Int = 1, perPage: Int = 10) async throws -> ReviewListData {
        let data: ReviewListData = try await request(
            path: "api/public/catalog/products/\(slug)/reviews",
            query: [
                URLQueryItem(name: "page", value: String(page)),
                URLQueryItem(name: "perPage", value: String(perPage))
            ],
            authorized: true,
            attachCartToken: false
        )
        // Backend may return meta (total/average) but hide items unless auth is valid/fresh.
        // If we have credentials stored, try a one-shot token refresh and retry once.
        if data.meta.total > 0, data.items.isEmpty, await refreshAuthTokenIfPossible() {
            let retried: ReviewListData = try await request(
                path: "api/public/catalog/products/\(slug)/reviews",
                query: [
                    URLQueryItem(name: "page", value: String(page)),
                    URLQueryItem(name: "perPage", value: String(perPage))
                ],
                authorized: true,
                attachCartToken: false
            )
            return retried
        }
        return data
    }

    func categories() async throws -> [CategorySummary] {
        let data: CategoryListData = try await request(
            path: "api/public/catalog/categories"
        )
        return data.items
    }

    func fetchCart() async throws -> Cart {
        let data: CartData = try await request(
            path: "api/public/cart",
            method: "GET",
            query: nil,
            authorized: false,
            attachCartToken: true
        )
        return data.cart
    }

    func addToCart(productId: Int, quantity: Int, rentalMonths: Int? = nil) async throws -> Cart {
        var body: [String: Any] = [
            "productId": productId,
            "quantity": max(1, quantity)
        ]
        if let token = sessionStore.cartToken {
            body["cartToken"] = token
        }
        if let rentalMonths {
            body["rentalMonths"] = rentalMonths
        }

        let data: CartData = try await request(
            path: "api/public/cart/items",
            method: "POST",
            body: body,
            authorized: false,
            attachCartToken: true
        )
        return data.cart
    }

    func updateCart(productId: Int, quantity: Int, rentalMonths: Int? = nil, currentRentalMonths: Int? = nil) async throws -> Cart {
        var body: [String: Any] = [
            "quantity": max(0, quantity)
        ]
        if let token = sessionStore.cartToken {
            body["cartToken"] = token
        }
        if let rentalMonths {
            body["rentalMonths"] = rentalMonths
        }
        if let currentRentalMonths {
            body["currentRentalMonths"] = currentRentalMonths
        }

        let data: CartData = try await request(
            path: "api/public/cart/items/\(productId)",
            method: "PATCH",
            body: body,
            authorized: false,
            attachCartToken: true
        )
        return data.cart
    }

    func removeFromCart(productId: Int) async throws -> Cart {
        let data: CartData = try await request(
            path: "api/public/cart/items/\(productId)",
            method: "DELETE",
            authorized: false,
            attachCartToken: true
        )
        return data.cart
    }
    
    func clearCart() async throws -> Cart {
        let data: CartData = try await request(
            path: "api/public/cart",
            method: "DELETE",
            authorized: false,
            attachCartToken: true
        )
        return data.cart
    }

    func login(email: String, password: String) async throws -> String {
        let payload = ["email": email, "password": password]
        let (data, response) = try await rawRequest(
            path: "api/auth/login",
            method: "POST",
            body: payload,
            authorized: false,
            attachCartToken: false
        )

        guard let http = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }

        if !(200..<300).contains(http.statusCode) {
            if let errorPayload = try? decoder.decode(APIErrorPayload.self, from: data) {
                throw APIError.httpStatus(http.statusCode, errorPayload.message ?? "Identifiants incorrects.")
            }
            throw APIError.httpStatus(http.statusCode, "Identifiants incorrects.")
        }

        let tokenResponse = try decoder.decode(LoginResponse.self, from: data)
        sessionStore.jwtToken = tokenResponse.token
        return tokenResponse.token
    }

    func profile() async throws -> UserProfile {
        let profile: UserProfile = try await request(
            path: "api/auth/me",
            authorized: true
        )
        sessionStore.profile = profile
        return profile
    }

    func assetURL(for path: String?) -> URL? {
        guard var path, !path.isEmpty else { return nil }

        if path.hasPrefix("http") {
            return URL(string: path)
        }

        if path.hasPrefix("/") {
            path.removeFirst()
        }

        return baseURL.appendingPathComponent(path)
    }

    // MARK: - Core HTTP helpers

    private func request<T: Decodable>(
        path: String,
        method: String = "GET",
        query: [URLQueryItem]? = nil,
        body: [String: Any]? = nil,
        authorized: Bool = false,
        attachCartToken: Bool = false,
        attempt: Int = 0
    ) async throws -> T {
        let (data, response) = try await rawRequest(
            path: path,
            method: method,
            query: query,
            body: body,
            authorized: authorized,
            attachCartToken: attachCartToken
        )

        guard let http = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }

#if DEBUG
        print("[API] \(method) \(http.url?.absoluteString ?? path) -> \(http.statusCode)")
#endif

        captureCartToken(from: http)
        
        if http.statusCode == 401, authorized, attempt == 0 {
            if await refreshAuthTokenIfPossible() {
                return try await request(
                    path: path,
                    method: method,
                    query: query,
                    body: body,
                    authorized: authorized,
                    attachCartToken: attachCartToken,
                    attempt: attempt + 1
                )
            }
        }

        if !(200..<300).contains(http.statusCode) {
            if let errorPayload = try? decoder.decode(APIErrorPayload.self, from: data) {
                throw APIError.httpStatus(http.statusCode, errorPayload.message ?? "Erreur \(http.statusCode)")
            }

            throw APIError.httpStatus(http.statusCode, "Erreur \(http.statusCode)")
        }

        do {
            let envelope = try decoder.decode(APIEnvelope<T>.self, from: data)
            return envelope.data
        } catch {
            throw APIError.decoding
        }
    }

    private func rawRequest(
        path: String,
        method: String,
        query: [URLQueryItem]? = nil,
        body: [String: Any]? = nil,
        authorized: Bool,
        attachCartToken: Bool
    ) async throws -> (Data, URLResponse) {
        guard var components = URLComponents(url: baseURL.appendingPathComponent(path), resolvingAgainstBaseURL: false) else {
            throw APIError.invalidResponse
        }

        if let query {
            components.queryItems = query
        }

        guard let url = components.url else {
            throw APIError.invalidResponse
        }

        var request = URLRequest(url: url)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Accept")

#if DEBUG
        print("[API] \(method) \(url.absoluteString)")
#endif

        if let body {
            do {
                request.httpBody = try JSONSerialization.data(withJSONObject: body, options: [])
            } catch {
                throw APIError.transport(error)
            }
            request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        }

        // Attach auth token for authorized calls, and also for product reviews (same behavior as the website).
        let shouldAttachAuth = authorized || path.contains("api/public/catalog/products/") && path.hasSuffix("/reviews")
        if shouldAttachAuth, let token = sessionStore.jwtToken, !token.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }

        if attachCartToken, let token = sessionStore.cartToken {
            request.setValue(token, forHTTPHeaderField: "X-Cart-Token")
        }

        do {
            return try await session.data(for: request)
        } catch {
            throw APIError.transport(error)
        }
    }

    private func captureCartToken(from response: HTTPURLResponse) {
        if let headerToken = response.value(forHTTPHeaderField: "X-Cart-Token"), !headerToken.isEmpty {
            sessionStore.cartToken = headerToken
        }
    }
    
    private func refreshAuthTokenIfPossible() async -> Bool {
        guard let credentials = sessionStore.storedCredentials else {
            return false
        }
        
        do {
            _ = try await login(email: credentials.email, password: credentials.password)
            return true
        } catch {
            sessionStore.clearSession()
            return false
        }
    }
    
    // MARK: - Account
    
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
        var body: [String: Any] = [
            "score": score
        ]
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
    
    func checkout() async throws -> OrderSummary {
        // Backend returns the order directly in `data` for this endpoint (no `order` wrapper).
        let order: OrderSummary = try await request(
            path: "api/orders/checkout",
            method: "POST",
            authorized: true,
            attachCartToken: true
        )
        return order
    }
    
    func sendContact(
        name: String,
        email: String,
        subject: String,
        message: String
    ) async throws {
        try await send(
            path: "api/public/contact",
            method: "POST",
            body: [
                "name": name,
                "email": email,
                "subject": subject,
                "message": message
            ],
            authorized: false,
            attachCartToken: false
        )
    }

    func updateProfile(
        firstName: String,
        lastName: String,
        email: String,
        address: String?,
        postalCode: String?,
        city: String?,
        birthDate: String,
        phoneNumber: String,
        gender: String
    ) async throws -> UserProfile {
        var body: [String: Any] = [
            "firstName": firstName,
            "lastName": lastName,
            "email": email,
            "birthDate": birthDate,
            "phoneNumber": phoneNumber
        ]
        if let address { body["address"] = address }
        if let postalCode { body["postalCode"] = postalCode }
        if let city { body["city"] = city }
        body["gender"] = gender

        do {
            let profile: UserProfile = try await request(
                path: "api/auth/profile",
                method: "PUT",
                body: body,
                authorized: true,
                attachCartToken: false
            )
            sessionStore.profile = profile
            return profile
        }
    }

    func deleteAccount() async throws {
        let _: APIEnvelope<APIErrorPayload?> = try await request(
            path: "api/auth/profile",
            method: "DELETE",
            authorized: true,
            attachCartToken: false
        )
        // Clear session locally
        sessionStore.clearSession()
    }
    
    func register(
        email: String,
        password: String,
        confirmPassword: String,
        firstName: String,
        lastName: String,
        birthDate: String,
        phoneNumber: String,
        gender: String
    ) async throws -> UserProfile {
        let body: [String: Any] = [
            "email": email,
            "password": password,
            "confirmPassword": confirmPassword,
            "firstName": firstName,
            "lastName": lastName,
            "birthDate": birthDate,
            "phoneNumber": phoneNumber,
            "gender": gender
        ]
        
        let profile: UserProfile = try await request(
            path: "api/auth/register",
            method: "POST",
            body: body,
            authorized: false,
            attachCartToken: false
        )
        return profile
    }
    
    // MARK: - Appointments

    func appointmentPrestations() async throws -> [AppointmentPrestation] {
        let data: AppointmentPrestationList = try await request(
            path: "api/public/appointments/prestations"
        )
        return data.items
    }

    func appointmentAvailability(prestationId: Int, start: Date, end: Date) async throws -> [AppointmentSlot] {
        let data: AppointmentAvailability = try await request(
            path: "api/public/appointments/availability",
            query: [
                URLQueryItem(name: "start", value: isoFormatter.string(from: start)),
                URLQueryItem(name: "end", value: isoFormatter.string(from: end)),
                URLQueryItem(name: "prestationId", value: "\(prestationId)")
            ],
            authorized: false,
            attachCartToken: false
        )
        return data.slots
    }

    func bookAppointment(prestationId: Int, startAt: Date) async throws -> AppointmentSummary {
        let body: [String: Any] = [
            "prestationId": prestationId,
            "startAt": isoFormatter.string(from: startAt)
        ]
        let appointment: AppointmentSummary = try await request(
            path: "api/appointments",
            method: "POST",
            body: body,
            authorized: true,
            attachCartToken: false
        )
        return appointment
    }

    /// Annule un rendez-vous existant.
    /// - Primary: `PATCH /api/appointments/{id}/status` with `{ "status": "cancelled" }`
    /// - Fallback: `POST /api/appointments/{id}/cancel` (some deployments are inconsistent or /status may fail with 5xx)
    func cancelAppointment(id: Int) async throws {
        let body: [String: Any] = ["status": "cancelled"]
        do {
            // We don't rely on the response payload here; any 2xx means "accepted".
            try await send(
                path: "api/appointments/\(id)/status",
                method: "PATCH",
                body: body,
                authorized: true,
                attachCartToken: false
            )
        } catch let APIError.httpStatus(code, _) where code == 404 || code == 405 || (500...599).contains(code) {
            // Fallback endpoint is also available in the backend snapshot.
            try await send(
                path: "api/appointments/\(id)/cancel",
                method: "POST",
                authorized: true,
                attachCartToken: false
            )
        }
    }

    func myAppointments() async throws -> AppointmentList {
        return try await request(
            path: "api/appointments/me",
            authorized: true,
            attachCartToken: false
        )
    }

    // MARK: - Generic sender for endpoints without envelope decoding
    private func send(
        path: String,
        method: String,
        query: [URLQueryItem]? = nil,
        body: [String: Any]? = nil,
        authorized: Bool = false,
        attachCartToken: Bool = false,
        attempt: Int = 0
    ) async throws {
        let (data, response) = try await rawRequest(
            path: path,
            method: method,
            query: query,
            body: body,
            authorized: authorized,
            attachCartToken: attachCartToken
        )

        guard let http = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }

        captureCartToken(from: http)
        
        if http.statusCode == 401, authorized, attempt == 0 {
            if await refreshAuthTokenIfPossible() {
                return try await send(
                    path: path,
                    method: method,
                    query: query,
                    body: body,
                    authorized: authorized,
                    attachCartToken: attachCartToken,
                    attempt: attempt + 1
                )
            }
        }

        if !(200..<300).contains(http.statusCode) {
            if let errorPayload = try? decoder.decode(APIErrorPayload.self, from: data) {
                throw APIError.httpStatus(http.statusCode, errorPayload.message ?? "Erreur \(http.statusCode)")
            }
            throw APIError.httpStatus(http.statusCode, "Erreur \(http.statusCode)")
        }
    }

    // MARK: - Addresses

    func createAddress(
        label: String,
        address: String,
        postalCode: String,
        city: String,
        isDefault: Bool
    ) async throws {
        let body: [String: Any] = [
            "name": label,
            "address": address,
            "postalCode": postalCode,
            "city": city,
            "isDefault": isDefault
        ]
        try await send(
            path: "api/addresses",
            method: "POST",
            body: body,
            authorized: true,
            attachCartToken: false
        )
    }

    func updateAddress(
        id: Int,
        label: String,
        address: String,
        postalCode: String,
        city: String,
        isDefault: Bool
    ) async throws {
        let body: [String: Any] = [
            "name": label,
            "address": address,
            "postalCode": postalCode,
            "city": city
        ]
        try await send(
            path: "api/addresses/\(id)",
            method: "PUT",
            body: body,
            authorized: true,
            attachCartToken: false
        )
        if isDefault {
            try await setDefaultAddress(id: id)
        }
    }

    func deleteAddress(id: Int) async throws {
        try await send(
            path: "api/addresses/\(id)",
            method: "DELETE",
            authorized: true,
            attachCartToken: false
        )
    }

    func setDefaultAddress(id: Int) async throws {
        try await send(
            path: "api/addresses/\(id)/default",
            method: "PUT",
            authorized: true,
            attachCartToken: false
        )
    }

    func listAddresses() async throws -> [UserAddress] {
        let data: AddressListData = try await request(
            path: "api/addresses/me",
            authorized: true,
            attachCartToken: false
        )
        return data.items
    }
    
    // MARK: - Quotes
    
    func quoteServices() async throws -> [QuoteService] {
        let data: QuoteServiceList = try await request(
            path: "api/public/quotes/services"
        )
        return data.items
    }
    
    func createQuote(
        name: String,
        email: String,
        company: String?,
        address: String?,
        items: [QuoteRequestItem]
    ) async throws -> QuoteSummary {
        var customer: [String: Any] = [
            "name": name,
            "email": email
        ]
        if let company { customer["company"] = company }
        if let address { customer["address"] = address }
        
        let body: [String: Any] = [
            "customer": customer,
            "items": items.map { $0.toPayload() }
        ]
        return try await request(
            path: "api/public/quotes",
            method: "POST",
            body: body,
            authorized: false,
            attachCartToken: false
        )
    }
    
    func myQuotes() async throws -> [QuoteSummary] {
        let data: QuoteListData = try await request(
            path: "api/quotes/me",
            authorized: true,
            attachCartToken: false
        )
        return data.items
    }
    
    func deleteQuote(id: Int) async throws {
        try await send(
            path: "api/quotes/me/\(id)",
            method: "DELETE",
            authorized: true,
            attachCartToken: false
        )
    }
    
    // MARK: - Favorites

    struct FavoriteEntry: Decodable {
        let addedAt: Date
        let product: Product
    }

    struct FavoriteListData: Decodable {
        let items: [FavoriteEntry]
    }

    struct AddFavoriteResponse: Decodable {
        let favorite: FavoriteEntry
        let alreadyFavorite: Bool
    }

    struct RemoveFavoriteResponse: Decodable {
        let removed: Bool
    }

    func listFavorites() async throws -> [FavoriteEntry] {
        let data: FavoriteListData = try await request(
            path: "api/favorites",
            authorized: true,
            attachCartToken: false
        )
        return data.items
    }

    @discardableResult
    func addFavorite(productId: Int) async throws -> AddFavoriteResponse {
        let resp: AddFavoriteResponse = try await request(
            path: "api/favorites/\(productId)",
            method: "POST",
            authorized: true,
            attachCartToken: false
        )
        return resp
    }

    func removeFavorite(productId: Int) async throws -> Bool {
        let resp: RemoveFavoriteResponse = try await request(
            path: "api/favorites/\(productId)",
            method: "DELETE",
            authorized: true,
            attachCartToken: false
        )
        return resp.removed
    }
}
