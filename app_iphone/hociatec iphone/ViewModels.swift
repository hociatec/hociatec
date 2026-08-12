import Foundation
import Combine
import SwiftUI

@MainActor
final class HomeViewModel: ObservableObject {
    @Published var featured: [Product] = []
    @Published var services: [QuoteService] = []
    @Published var news: [NewsArticle] = []
    @Published var isLoading = false
    @Published var error: String?

    private let api: APIClient

    init(api: APIClient) {
        self.api = api
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil

        do {
            async let featuredProducts = api.featuredProducts()
            async let availableServices = api.quoteServices()
            async let latestArticles = api.latestNews(limit: 3)

            featured = try await featuredProducts
            services = selectFeaturedServices(from: try await availableServices)
            news = try await latestArticles
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
    }

    func cancel(appointmentID: Int) async {
        isLoading = true
        error = nil
        do {
            try await api.cancelAppointment(id: appointmentID)
            await load(force: true)
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    private func selectFeaturedServices(from services: [QuoteService], limit: Int = 6) -> [QuoteService] {
        let explicit = services.filter { $0.isFeaturedHome }
        if !explicit.isEmpty {
            return Array(explicit.prefix(limit))
        }

        let defaultMatches = [
            "vente de materiel informatique",
            "reparation d'ordinateurs",
            "maintenance informatique",
            "creation de sites web",
            "formation numerique",
            "informatique professionnelle"
        ]

        func normalize(_ value: String?) -> String {
            (value ?? "")
                .folding(options: .diacriticInsensitive, locale: .current)
                .lowercased()
        }

        return services
            .compactMap { service -> (QuoteService, Int)? in
                let title = normalize(service.title)
                guard let rank = defaultMatches.firstIndex(where: { title.contains($0) }) else {
                    return nil
                }
                return (service, rank)
            }
            .sorted { $0.1 < $1.1 }
            .prefix(limit)
            .map(\.0)
    }
}

@MainActor
final class ProductsViewModel: ObservableObject {
    @Published var products: [Product] = []
    @Published var categories: [CategorySummary] = []
    @Published var selectedCategory: CategorySummary?
    @Published var selectedCategoryIds: Set<Int> = []
    @Published var selectedSellingType: SellingType? = nil
    @Published var sort: SortOption = .relevance
    @Published var search = ""
    @Published var isLoading = false
    @Published var isLoadingCategories = false
    @Published var error: String?

    private let api: APIClient

    init(api: APIClient) {
        self.api = api
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil

        do {
            let slugs: [String]? = {
                if !selectedCategoryIds.isEmpty {
                    let selected = categories.filter { selectedCategoryIds.contains($0.id) }
                    return selected.map { $0.slug }
                } else if let slug = selectedCategory?.slug { return [slug] } else { return nil }
            }()
            let items = try await api.products(
                search: search.isEmpty ? nil : search,
                categorySlugs: slugs,
                sellingType: selectedSellingType
            )

            // Appliquer aussi le filtrage côté client pour s’assurer du résultat, même si l’API ignore un filtre.
            var filtered = items
            if !selectedCategoryIds.isEmpty {
                filtered = filtered.filter { selectedCategoryIds.contains($0.category.id) }
            }
            if let selectedSellingType {
                filtered = filtered.filter { $0.sellingType == selectedSellingType }
            }
            products = applySorting(on: filtered)
        } catch let err {
            self.error = err.localizedDescription
            if force {
                products = []
            }
        }

        isLoading = false
    }

    func loadCategoriesIfNeeded() async {
        if isLoadingCategories || !categories.isEmpty { return }
        isLoadingCategories = true
        defer { isLoadingCategories = false }
        do {
            categories = try await api.categories()
            if let current = selectedCategory {
                selectedCategoryIds = [current.id]
            }
        } catch let err {
            // Non bloquant pour les produits : on logue l'erreur dans `error` si aucune autre.
            if error == nil {
                error = err.localizedDescription
            }
        }
    }

    func applySorting(on items: [Product]) -> [Product] {
        switch sort {
        case .relevance:
            return items
        case .priceLowHigh:
            return items.sorted { $0.effectivePriceCents < $1.effectivePriceCents }
        case .priceHighLow:
            return items.sorted { $0.effectivePriceCents > $1.effectivePriceCents }
        case .newest:
            return items.sorted { ($0.createdAt ?? .distantPast) > ($1.createdAt ?? .distantPast) }
        }
    }

    func updateSort(_ newSort: SortOption) {
        sort = newSort
        products = applySorting(on: products)
    }
}

enum SortOption: String, CaseIterable, Identifiable {
    case relevance
    case priceLowHigh
    case priceHighLow
    case newest

    var id: String { rawValue }

    var label: String {
        switch self {
        case .relevance: return "Pertinence"
        case .priceLowHigh: return "Prix ↑"
        case .priceHighLow: return "Prix ↓"
        case .newest: return "Nouveautés"
        }
    }
}

enum MyAppointmentsFilter: String, CaseIterable, Identifiable {
    case all
    case confirmed
    case cancelled

    var id: String { rawValue }

    var label: String {
        switch self {
        case .all: return "Tous"
        case .confirmed: return "Confirmés"
        case .cancelled: return "Annulés"
        }
    }
}

@MainActor
final class CartViewModel: ObservableObject {
    @Published var cart: Cart?
    @Published var isLoading = false
    @Published var error: String?
    @Published var statusMessage: String?

    private let api: APIClient

    init(api: APIClient) {
        self.api = api
    }

    func refresh() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        statusMessage = nil

        do {
            cart = try await api.fetchCart()
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
    }

    func add(product: Product, quantity: Int = 1, rentalMonths: Int? = nil) async {
        isLoading = true
        error = nil
        statusMessage = nil
        do {
            cart = try await api.addToCart(
                productId: product.id,
                quantity: quantity,
                rentalMonths: rentalMonths
            )
            if product.sellingType == .rental, let rentalMonths {
                statusMessage = "\(product.name) loué pour \(rentalMonths) mois."
            } else {
                statusMessage = "\(product.name) ajouté au panier."
            }
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func update(item: CartItem, quantity: Int, rentalMonths: Int? = nil) async {
        // Never send negative quantities
        guard quantity >= 0 else { return }

        // Determine current values from freshest cart if available
        let current: CartItem? = cart?.items.first(where: { $0.product.id == item.product.id })
        let currentQty = current?.quantity ?? item.quantity
        let currentMonths = current?.rentalMonths ?? item.rentalMonths
        let desiredMonths = rentalMonths ?? currentMonths

        let quantityChanged = quantity != currentQty
        let monthsChanged: Bool = {
            switch (currentMonths, desiredMonths) {
            case (nil, nil):
                return false
            case let (a?, b?):
                return a != b
            default:
                return true
            }
        }()

        // No-op if nothing changes.
        if !quantityChanged && !monthsChanged {
            return
        }

        let isIncreasing = quantityChanged && quantity > currentQty

        // Stock checks only apply when quantity changes.
        if quantityChanged {
            // If we are trying to increase the quantity, verify against known stock (no call if at or above stock)
            if isIncreasing {
                let maxStock = cart?.items.first(where: { $0.product.id == item.product.id })?.product.stock ?? item.product.stock
                if quantity > maxStock {
                    self.statusMessage = "Stock insuffisant pour \(item.product.name). Quantité max: \(maxStock)."
                    return
                }
            } else {
                // Not increasing, still ensure not exceeding known local stock
                let localMax = cart?.items.first(where: { $0.product.id == item.product.id })?.product.stock ?? item.product.stock
                if quantity > localMax {
                    self.statusMessage = "Stock insuffisant pour \(item.product.name). Quantité max: \(localMax)."
                    return
                }
            }
        }

        // Snapshot previous cart to restore on unexpected server adjustments
        let previousCart = cart

        isLoading = true
        error = nil
        statusMessage = nil
        defer { isLoading = false }

        do {
            let updatedCart = try await api.updateCart(
                productId: item.product.id,
                quantity: quantity,
                rentalMonths: desiredMonths,
                currentRentalMonths: currentMonths
            )

            // Detect destructive decrease of total quantity on increase attempts
            if isIncreasing {
                let previousTotal = previousCart?.totalQuantity ?? 0
                if updatedCart.totalQuantity < previousTotal {
                    // Keep previous state and inform user
                    self.cart = previousCart
                    self.statusMessage = "Stock insuffisant pour \(item.product.name). L’article n’a pas été augmenté."
                    return
                }
            }

            // Post-validation: if server adjusted or removed the specific item
            if isIncreasing {
                if let updatedItem = updatedCart.items.first(where: { $0.product.id == item.product.id }) {
                    if updatedItem.quantity < quantity {
                        self.cart = updatedCart
                        self.statusMessage = "Stock insuffisant pour \(item.product.name). Quantité ajustée à \(updatedItem.quantity)."
                        return
                    }
                } else {
                    self.cart = previousCart
                    self.statusMessage = "Stock insuffisant pour \(item.product.name). L’article n’a pas été augmenté."
                    return
                }
            }

            // Normal path
            self.cart = updatedCart
        } catch {
            // Restore previous state and surface a friendly error
            self.cart = previousCart
            self.error = error.localizedDescription
            self.statusMessage = "La mise à jour du panier a échoué. Réessayez ou vérifiez le stock disponible."
        }
    }

    func remove(item: CartItem) async {
        isLoading = true
        error = nil
        statusMessage = nil

        do {
            cart = try await api.removeFromCart(productId: item.product.id)
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
    }

    func clear() async {
        isLoading = true
        error = nil
        statusMessage = nil

        do {
            cart = try await api.clearCart()
            statusMessage = "Panier vidé."
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
    }

    func checkout() async -> OrderSummary? {
        isLoading = true
        error = nil
        statusMessage = nil
        defer { isLoading = false }

        do {
            let order = try await api.checkout()
            statusMessage = "Commande créée (\(order.number))."
            cart = nil
            return order
        } catch let err {
            self.error = err.localizedDescription
            return nil
        }
    }
}

@MainActor
final class AccountViewModel: ObservableObject {
    static let birthDateFormatter: DateFormatter = {
        let df = DateFormatter()
        df.calendar = Calendar(identifier: .iso8601)
        df.locale = Locale(identifier: "en_US_POSIX")
        df.timeZone = TimeZone(secondsFromGMT: 0)
        df.dateFormat = "yyyy-MM-dd"
        return df
    }()

    @Published var email = ""
    @Published var password = ""
    @Published var isLoading = false
    @Published var error: String?
    @Published var statusMessage: String?
    @Published var profile: UserProfile?

    @Published var firstName: String = ""
    @Published var lastName: String = ""
    @Published var address: String? = nil
    @Published var postalCode: String? = nil
    @Published var city: String? = nil
    @Published var birthDate: String = ""
    @Published var phoneNumber: String = ""
    @Published var gender: String = "autre"
    @Published var roles: [String] = []
    @Published var addresses: [UserAddress] = []

    private let api: APIClient
    private let session: SessionStore

    init(api: APIClient, session: SessionStore) {
        self.api = api
        self.session = session
        self.profile = session.profile
        self.email = session.profile?.email ?? session.loginEmail ?? ""
        if let p = session.profile {
            self.firstName = p.firstName
            self.lastName = p.lastName
            self.address = p.address
            self.postalCode = p.postalCode
            self.city = p.city
            self.birthDate = normalizedBirthDate(p.birthDate)
            self.phoneNumber = p.phoneNumber
            self.gender = normalizedGender(p.gender ?? "autre")
            self.roles = p.roles
            self.addresses = p.addresses ?? []
        } else {
            self.gender = "autre"
            self.addresses = []
        }
    }

    var isLoggedIn: Bool {
        session.jwtToken != nil
    }

    func loadProfileIfPossible() async {
        guard isLoggedIn else { return }
        await loadProfile()
    }

    func loadProfile() async {
        isLoading = true
        error = nil
        do {
            let profile = try await api.profile()
            self.apply(profile: profile)
            session.profile = profile
            await loadAddresses()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func login() async {
        guard !email.isEmpty, !password.isEmpty else {
            error = "Renseignez vos identifiants."
            return
        }

        isLoading = true
        error = nil
        defer {
            isLoading = false
            password = ""
        }

        do {
            _ = try await api.login(email: email, password: password)
            session.storeCredentials(email: email, password: password)
            let profile = try await api.profile()
            self.apply(profile: profile)
            session.profile = profile
            await loadAddresses()
        } catch let err {
            self.error = err.localizedDescription
        }
    }

    private func apply(profile p: UserProfile) {
        self.profile = p
        self.email = p.email
        self.firstName = p.firstName
        self.lastName = p.lastName
        self.address = p.address
        self.postalCode = p.postalCode
        self.city = p.city
        self.birthDate = normalizedBirthDate(p.birthDate)
        self.phoneNumber = p.phoneNumber
        self.gender = normalizedGender(p.gender ?? "autre")
        self.roles = p.roles
        if let addresses = p.addresses {
            self.addresses = addresses
        }
    }

    func updateProfile() async {
        isLoading = true
        error = nil
        do {
            let updated = try await api.updateProfile(
                firstName: firstName,
                lastName: lastName,
                email: email,
                address: address,
                postalCode: postalCode,
                city: city,
                birthDate: normalizedBirthDate(birthDate),
                phoneNumber: phoneNumber,
                gender: normalizedGender(gender)
            )
            self.apply(profile: updated)
            session.profile = updated
            session.loginEmail = updated.email
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func deleteAccount() async {
        isLoading = true
        error = nil
        do {
            try await api.deleteAccount()
            await logout()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }
    
    func register(
        firstName: String,
        lastName: String,
        email: String,
        password: String,
        confirmPassword: String,
        birthDate: Date,
        phoneNumber: String,
        gender: String
    ) async -> Bool {
        isLoading = true
        error = nil
        statusMessage = nil
        let birthISO = AccountViewModel.birthDateFormatter.string(from: birthDate)
        do {
            try await api.register(
                email: email,
                password: password,
                confirmPassword: confirmPassword,
                firstName: firstName,
                lastName: lastName,
                birthDate: birthISO,
                phoneNumber: phoneNumber,
                gender: normalizedGender(gender)
            )
            session.loginEmail = email
            statusMessage = "Compte créé. Vérifiez votre e-mail pour activer le compte avant de vous connecter."
            isLoading = false
            return true
        } catch let err {
            self.error = err.localizedDescription
            isLoading = false
            return false
        }
    }

    func refreshProfile() async {
        isLoading = true
        error = nil
        do {
            let p = try await api.profile()
            self.apply(profile: p)
            session.profile = p
            await loadAddresses()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func addAddress(label: String, address: String, postalCode: String, city: String, isDefault: Bool) async {
        isLoading = true
        error = nil
        do {
            try await api.createAddress(label: label, address: address, postalCode: postalCode, city: city, isDefault: isDefault)
            await refreshProfile()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func updateAddress(_ addr: UserAddress) async {
        guard let id = addr.id else { return }
        isLoading = true
        error = nil
        do {
            try await api.updateAddress(id: id, label: addr.label, address: addr.address, postalCode: addr.postalCode, city: addr.city, isDefault: addr.isDefault)
            await refreshProfile()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func deleteAddress(id: Int) async {
        isLoading = true
        error = nil
        do {
            try await api.deleteAddress(id: id)
            await refreshProfile()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func makeDefaultAddress(id: Int) async {
        isLoading = true
        error = nil
        do {
            try await api.setDefaultAddress(id: id)
            await refreshProfile()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func logout() async {
        await api.logout()
        profile = nil
        error = nil
        statusMessage = nil
        password = ""
        addresses = []
        gender = "autre"
    }

    private func loadAddresses() async {
        guard isLoggedIn else {
            addresses = []
            return
        }

        do {
            let items = try await api.listAddresses()
            addresses = items
        } catch let err {
            self.error = err.localizedDescription
        }
    }

    private func normalizedBirthDate(_ value: String) -> String {
        if let date = AccountViewModel.birthDateFormatter.date(from: value) {
            return AccountViewModel.birthDateFormatter.string(from: date)
        }
        return AccountViewModel.birthDateFormatter.string(from: Date())
    }

    private func normalizedGender(_ value: String) -> String {
        let cleaned = value.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
        switch cleaned {
        case "homme":
            return "homme"
        case "femme":
            return "femme"
        case "autre":
            return "autre"
        default:
            return "autre"
        }
    }
}

@MainActor
final class OrdersViewModel: ObservableObject {
    @Published var orders: [OrderSummary] = []
    @Published var isLoading = false
    @Published var error: String?
    @Published var cancellingOrderID: Int?
    
    private let api: APIClient
    
    init(api: APIClient) {
        self.api = api
    }
    
    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil
        
        do {
            orders = try await api.myOrders()
        } catch let err {
            self.error = err.localizedDescription
        }
        
        isLoading = false
    }
    
    func cancel(order: OrderSummary) async -> OrderSummary? {
        guard order.status == "pending" else { return nil }
        cancellingOrderID = order.id
        defer { cancellingOrderID = nil }
        
        do {
            let updated = try await api.cancelOrder(id: order.id)
            if let index = orders.firstIndex(where: { $0.id == updated.id }) {
                orders[index] = updated
            }
            return updated
        } catch let err {
            self.error = err.localizedDescription
            return nil
        }
    }

    func detail(for id: Int) async -> OrderSummary? {
        do {
            let detail = try await api.order(id: id)
            if let idx = orders.firstIndex(where: { $0.id == detail.id }) {
                orders[idx] = detail
            }
            return detail
        } catch let err {
            self.error = err.localizedDescription
            return nil
        }
    }
}

@MainActor
final class AppointmentBookingViewModel: ObservableObject {
    @Published var prestations: [AppointmentPrestation] = []
    @Published var slots: [AppointmentSlot] = []
    @Published var selectedPrestationId: Int?
    @Published var isLoading = false
    @Published var isBooking = false
    @Published var error: String?
    @Published var successMessage: String?
    @Published var bookingMessage: String?

    private let api: APIClient
    private let calendar = Calendar(identifier: .gregorian)

    init(api: APIClient) {
        self.api = api
    }

    func initialize(startDate: Date) async {
        if prestations.isEmpty {
            await loadPrestations()
        }
        await loadSlots(startDate: startDate)
    }

    func loadPrestations() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        successMessage = nil

        do {
            prestations = try await api.appointmentPrestations()
            if selectedPrestationId == nil {
                selectedPrestationId = prestations.first?.id
            }
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
    }

    func loadSlots(startDate: Date) async {
        guard let prestationId = selectedPrestationId else { return }
        guard !isLoading else { return }
        isLoading = true
        error = nil
        successMessage = nil

        let start = calendar.startOfDay(for: startDate)
        let end = calendar.date(byAdding: .day, value: 14, to: start) ?? start

        do {
            slots = try await api.appointmentAvailability(prestationId: prestationId, start: start, end: end)
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
    }

    func book(slot: AppointmentSlot) async -> AppointmentSummary? {
        bookingMessage = nil
        guard let prestationId = selectedPrestationId else {
            error = "Choisissez une prestation avant de réserver."
            return nil
        }
        guard !isBooking else { return nil }

        isBooking = true
        error = nil
        successMessage = nil
        defer { isBooking = false }

        do {
            let appointment = try await api.bookAppointment(prestationId: prestationId, startAt: slot.startAt)
            successMessage = "Rendez-vous confirmé."
            bookingMessage = "Rendez-vous confirmé avec succès."
            return appointment
        } catch let err {
            self.error = err.localizedDescription
            return nil
        }
    }
}

@MainActor
final class QuoteViewModel: ObservableObject {
    @Published var services: [QuoteService] = []
    @Published var items: [QuoteDraftItem] = []
    @Published var name: String = ""
    @Published var email: String = ""
    @Published var company: String = ""
    @Published var address: String = ""
    @Published var searchText: String = ""
    @Published var productResults: [Product] = []
    @Published var isLoadingServices = false
    @Published var isSearching = false
    @Published var isSubmitting = false
    @Published var error: String?
    @Published var successMessage: String?

    private let api: APIClient
    private let account: AccountViewModel

    init(api: APIClient, account: AccountViewModel) {
        self.api = api
        self.account = account
        prefill()
    }

    func prefillFromAccount() {
        prefill()
    }

    func loadServices() async {
        if isLoadingServices { return }
        isLoadingServices = true
        error = nil
        do {
            services = try await api.quoteServices()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoadingServices = false
    }
    
    func searchProducts() async {
        let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !query.isEmpty else {
            productResults = []
            return
        }
        isSearching = true
        defer { isSearching = false }
        do {
            productResults = try await api.products(search: query)
        } catch let err {
            error = err.localizedDescription
        }
    }
    
    var matchingServices: [QuoteService] {
        let query = searchText.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
        guard !query.isEmpty else { return [] }
        return services.filter { $0.title.lowercased().contains(query) || ($0.description.lowercased().contains(query)) }
    }
    
    var hasServicesLoaded: Bool {
        !services.isEmpty
    }

    func submit() async {
        let validItems = items.compactMap { draft -> QuoteRequestItem? in
            guard draft.quantity > 0 else { return nil }
            if let serviceId = draft.serviceId {
                return QuoteRequestItem(
                    serviceId: serviceId,
                    productId: nil,
                    quantity: draft.quantity,
                    description: draft.description.isEmpty ? nil : draft.description,
                    name: draft.title,
                    unitPriceCents: draft.unitPriceCents,
                    type: .service
                )
            } else if let productId = draft.productId {
                return QuoteRequestItem(
                    serviceId: nil,
                    productId: productId,
                    quantity: draft.quantity,
                    description: draft.description.isEmpty ? nil : draft.description,
                    name: draft.title,
                    unitPriceCents: draft.unitPriceCents,
                    type: .product
                )
            } else if draft.isCustom {
                let title = (draft.title ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
                guard !title.isEmpty else { return nil }
                guard let unitPriceCents = draft.unitPriceCents, unitPriceCents >= 0 else { return nil }
                return QuoteRequestItem(
                    serviceId: nil,
                    productId: nil,
                    quantity: draft.quantity,
                    description: draft.description.isEmpty ? nil : draft.description,
                    name: title,
                    unitPriceCents: unitPriceCents,
                    type: .custom
                )
            }
            return nil
        }

        guard !validItems.isEmpty else {
            error = "Ajoutez au moins une ligne valide."
            return
        }
        isSubmitting = true
        error = nil
        successMessage = nil
        defer { isSubmitting = false }

        do {
            _ = try await api.createQuote(
                name: name,
                email: email,
                company: company.isEmpty ? nil : company,
                address: address.isEmpty ? nil : address,
                items: validItems
            )
            successMessage = "Demande de devis envoyée."
            items = []
        } catch let err {
            self.error = err.localizedDescription
        }
    }

    private func prefill() {
        if let profile = account.profile {
            name = "\(profile.firstName) \(profile.lastName)"
            email = profile.email
            company = ""
            address = profile.address ?? ""
        } else {
            name = ""
            email = account.email
        }
    }
    
    func addLine() {
        let first = services.first
        items.append(QuoteDraftItem(serviceId: first?.id, productId: nil, quantity: 1, description: "", title: first?.title, unitPriceCents: first?.priceCents))
    }

    func addServiceLine(_ service: QuoteService) {
        items.append(
            QuoteDraftItem(
                serviceId: service.id,
                productId: nil,
                quantity: 1,
                description: "",
                title: service.title,
                unitPriceCents: service.priceCents
            )
        )
    }

    func addProductLine(_ product: Product) {
        items.append(
            QuoteDraftItem(
                serviceId: nil,
                productId: product.id,
                quantity: 1,
                description: product.shortDescription,
                title: product.name,
                unitPriceCents: product.effectivePriceCents
            )
        )
    }

    func addCustomLine(title: String, unitPriceCents: Int, quantity: Int = 1, description: String = "") {
        let cleanTitle = title.trimmingCharacters(in: .whitespacesAndNewlines)
        items.append(
            QuoteDraftItem(
                serviceId: nil,
                productId: nil,
                quantity: max(1, quantity),
                description: description,
                title: cleanTitle.isEmpty ? "Ligne" : cleanTitle,
                unitPriceCents: max(0, unitPriceCents)
            )
        )
    }
    
    func removeLine(id: UUID) {
        items.removeAll { $0.id == id }
    }
}

@MainActor
final class TradeInViewModel: ObservableObject {
    @Published var categories: [TradeInOption] = []
    @Published var conditions: [TradeInOption] = []
    @Published var selectedCategory: String = ""
    @Published var selectedCondition: String = ""
    @Published var firstName: String = ""
    @Published var lastName: String = ""
    @Published var email: String = ""
    @Published var productName: String = ""
    @Published var brand: String = ""
    @Published var model: String = ""
    @Published var serialNumber: String = ""
    @Published var purchasePrice: String = ""
    @Published var purchaseYear: String = ""
    @Published var phone: String = ""
    @Published var description: String = ""
    @Published var functional: Bool = true
    @Published var hasAccessories: Bool = false
    @Published var hasProofOfPurchase: Bool = false
    @Published var consent: Bool = false
    @Published var ribFileName: String?
    @Published var ribData: Data?
    @Published var isLoading = false
    @Published var isSubmitting = false
    @Published var error: String?
    @Published var successMessage: String?

    private let api: APIClient
    private let account: AccountViewModel

    init(api: APIClient, account: AccountViewModel) {
        self.api = api
        self.account = account
        self.firstName = account.profile?.firstName ?? account.firstName
        self.lastName = account.profile?.lastName ?? account.lastName
        self.email = account.profile?.email ?? account.email
        self.phone = account.profile?.phoneNumber ?? account.phoneNumber
    }

    func loadMetadata() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let metadata = try await api.tradeInMetadata()
            categories = metadata.categories
            conditions = metadata.conditions
            if selectedCategory.isEmpty {
                selectedCategory = metadata.categories.first?.value ?? ""
            }
            if selectedCondition.isEmpty {
                selectedCondition = metadata.conditions.first?.value ?? ""
            }
        } catch {
            self.error = error.localizedDescription
        }
    }

    func setRib(fileName: String, data: Data) {
        ribFileName = fileName
        ribData = data
    }

    func submit() async -> Bool {
        let trimmedFirstName = firstName.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedLastName = lastName.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedEmail = email.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedProductName = productName.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedPhone = phone.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedDescription = description.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedBrand = brand.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedModel = model.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedSerial = serialNumber.trimmingCharacters(in: .whitespacesAndNewlines)

        guard !trimmedFirstName.isEmpty else {
            error = "Renseignez votre prénom."
            return false
        }
        guard !trimmedLastName.isEmpty else {
            error = "Renseignez votre nom."
            return false
        }
        guard !trimmedEmail.isEmpty, trimmedEmail.contains("@") else {
            error = "Renseignez un e-mail valide."
            return false
        }
        guard !selectedCategory.isEmpty else {
            error = "Choisissez une catégorie."
            return false
        }
        guard !selectedCondition.isEmpty else {
            error = "Choisissez un état."
            return false
        }
        guard !trimmedProductName.isEmpty else {
            error = "Renseignez le produit."
            return false
        }
        guard let purchasePriceCents = TradeInMoneyParser.cents(from: purchasePrice) else {
            error = "Renseignez un prix d’achat valide."
            return false
        }
        guard let purchaseYearValue = Int(purchaseYear), (1980...2100).contains(purchaseYearValue) else {
            error = "Renseignez une année d’achat valide."
            return false
        }
        guard !trimmedPhone.isEmpty else {
            error = "Renseignez votre téléphone."
            return false
        }
        guard !trimmedDescription.isEmpty else {
            error = "Décrivez l’état du produit."
            return false
        }
        guard consent else {
            error = "Vous devez accepter le traitement de la demande."
            return false
        }
        guard let selectedRibData = ribData, let ribFileName, !selectedRibData.isEmpty else {
            error = "Ajoutez votre RIB en PDF."
            return false
        }

        isSubmitting = true
        error = nil
        successMessage = nil
        defer { isSubmitting = false }

        do {
            let payload = TradeInRequestPayload(
                firstName: trimmedFirstName,
                lastName: trimmedLastName,
                email: trimmedEmail,
                phone: trimmedPhone,
                category: selectedCategory,
                productName: trimmedProductName,
                purchasePriceCents: purchasePriceCents,
                purchaseYear: purchaseYearValue,
                brand: trimmedBrand.isEmpty ? nil : trimmedBrand,
                model: trimmedModel.isEmpty ? nil : trimmedModel,
                serialNumber: trimmedSerial.isEmpty ? nil : trimmedSerial,
                conditionGrade: selectedCondition,
                functional: functional,
                hasAccessories: hasAccessories,
                hasProofOfPurchase: hasProofOfPurchase,
                description: trimmedDescription,
                catalogProductId: nil,
                consent: true
            )
            let created = try await api.createTradeIn(
                payload: payload,
                ribFilename: ribFileName,
                ribData: selectedRibData,
                authorized: account.isLoggedIn
            )
            successMessage = "Demande enregistrée (\(created.reference))."
            ribData = nil
            self.ribFileName = nil
            productName = ""
            brand = ""
            model = ""
            serialNumber = ""
            purchasePrice = ""
            purchaseYear = ""
            description = ""
            hasAccessories = false
            hasProofOfPurchase = false
            consent = false
            return true
        } catch {
            self.error = error.localizedDescription
            return false
        }
    }
}

private enum TradeInMoneyParser {
    static func cents(from input: String) -> Int? {
        let cleaned = input
            .replacingOccurrences(of: "€", with: "")
            .replacingOccurrences(of: " ", with: "")
            .trimmingCharacters(in: .whitespacesAndNewlines)
            .replacingOccurrences(of: ",", with: ".")
        guard !cleaned.isEmpty else { return nil }
        guard let value = Double(cleaned), value >= 0 else { return nil }
        return Int((value * 100).rounded())
    }
}

@MainActor
final class MyQuotesViewModel: ObservableObject {
    @Published var quotes: [QuoteSummary] = []
    @Published var isLoading = false
    @Published var error: String?
    private let api: APIClient

    init(api: APIClient) {
        self.api = api
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil
        do {
            quotes = try await api.myQuotes()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func delete(id: Int) async {
        isLoading = true
        error = nil
        do {
            try await api.deleteQuote(id: id)
            quotes.removeAll { $0.id == id }
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }
}

@MainActor
final class MyAppointmentsViewModel: ObservableObject {
    @Published var upcoming: [AppointmentSummary] = []
    @Published var past: [AppointmentSummary] = []
    @Published var isLoading = false
    @Published var error: String?
    @Published var successMessage: String? = nil

    private let api: APIClient

    init(api: APIClient) {
        self.api = api
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil

        do {
            let list = try await api.myAppointments()
            upcoming = list.upcoming
            past = list.past
        } catch let err {
            self.error = err.localizedDescription
        }

        isLoading = false
    }

    func cancel(appointmentID: Int) async -> Bool {
        guard !isLoading else { return false }
        isLoading = true
        error = nil
        successMessage = nil
        defer { isLoading = false }
        do {
            try await api.cancelAppointment(id: appointmentID)
            // After cancelling, refresh the list from backend to reflect new status.
            let list = try await api.myAppointments()
            upcoming = list.upcoming
            past = list.past
            successMessage = "Rendez-vous annulé."
            return true
        } catch {
            self.error = error.localizedDescription
            return false
        }
    }
}

@MainActor
final class FavoritesViewModel: ObservableObject {
    @Published var items: [Product] = []
    @Published var isLoading = false
    @Published var error: String?

    private let api: APIClient

    init(api: APIClient) {
        self.api = api
    }

    func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }
        do {
            let favs = try await api.listFavorites()
            items = favs.map { $0.product }
        } catch {
            self.error = error.localizedDescription
        }
    }

    func add(product: Product) async {
        do {
            _ = try await api.addFavorite(productId: product.id)
            await load()
        } catch {
            self.error = error.localizedDescription
        }
    }

    func remove(product: Product) async {
        do {
            _ = try await api.removeFavorite(productId: product.id)
            await load()
        } catch {
            self.error = error.localizedDescription
        }
    }
}
