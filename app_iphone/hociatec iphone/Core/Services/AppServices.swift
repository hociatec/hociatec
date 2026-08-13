import Foundation

protocol AssetServing {
    func assetURL(for path: String?) -> URL?
}

protocol AccountServing {
    func profile() async throws -> UserProfile
    func login(email: String, password: String) async throws -> String
    func logout() async
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
    ) async throws -> UserProfile
    func deleteAccount() async throws
    func register(
        email: String,
        password: String,
        confirmPassword: String,
        firstName: String,
        lastName: String,
        birthDate: String,
        phoneNumber: String,
        gender: String
    ) async throws
    func requestPasswordReset(email: String) async throws
    func resetPassword(token: String, password: String, confirmPassword: String) async throws
    func createAddress(label: String, address: String, postalCode: String, city: String, isDefault: Bool) async throws
    func updateAddress(id: Int, label: String, address: String, postalCode: String, city: String, isDefault: Bool) async throws
    func deleteAddress(id: Int) async throws
    func setDefaultAddress(id: Int) async throws
    func listAddresses() async throws -> [UserAddress]
}

protocol CartServing {
    func fetchCart() async throws -> Cart
    func addToCart(productId: Int, quantity: Int, rentalMonths: Int?) async throws -> Cart
    func updateCart(productId: Int, quantity: Int, rentalMonths: Int?, currentRentalMonths: Int?) async throws -> Cart
    func removeFromCart(productId: Int) async throws -> Cart
    func clearCart() async throws -> Cart
    func checkout() async throws -> OrderSummary
}

protocol ProductServing: AssetServing {
    func featuredProducts() async throws -> [Product]
    func products(search: String?, categorySlug: String?, sellingType: SellingType?) async throws -> [Product]
    func categories() async throws -> [CategorySummary]
    func product(slug: String) async throws -> Product
    func productReviews(slug: String, page: Int, perPage: Int) async throws -> ReviewListData
}

protocol FavoritesServing {
    func listFavorites() async throws -> [FavoriteEntry]
    func addFavorite(productId: Int) async throws -> AddFavoriteResponse
    func removeFavorite(productId: Int) async throws -> Bool
}

protocol OrderServing {
    func myOrders() async throws -> [OrderSummary]
    func order(id: Int) async throws -> OrderSummary
    func cancelOrder(id: Int) async throws -> OrderSummary
    func pendingReviews() async throws -> [PendingReviewItem]
    func createReview(orderId: Int, orderItemId: Int, score: Int, comment: String?) async throws -> Review
}

protocol AppointmentServing {
    func appointmentPrestations() async throws -> [AppointmentPrestation]
    func appointmentAvailability(prestationId: Int, start: Date, end: Date) async throws -> [AppointmentSlot]
    func bookAppointment(prestationId: Int, startAt: Date) async throws -> AppointmentSummary
    func cancelAppointment(id: Int) async throws
    func myAppointments() async throws -> AppointmentList
}

protocol QuoteServing {
    func quoteServices(page: Int?, perPage: Int?, query: String?) async throws -> QuoteServiceList
    func createQuote(name: String, email: String, company: String?, address: String?, items: [QuoteRequestItem]) async throws -> QuoteSummary
    func myQuotes() async throws -> [QuoteSummary]
    func deleteQuote(id: Int) async throws
}

protocol NewsServing {
    func latestNews(limit: Int) async throws -> [NewsArticle]
    func newsArticles(page: Int, perPage: Int, query: String?) async throws -> NewsArticleListData
    func newsArticle(slug: String) async throws -> NewsArticle
    func newsComments(slug: String, page: Int, perPage: Int) async throws -> NewsCommentListData
    func createNewsComment(slug: String, content: String) async throws -> NewsComment
}

protocol ServiceCatalogServing: AssetServing {
    func quoteServices(page: Int?, perPage: Int?, query: String?) async throws -> QuoteServiceList
    func publicService(id: Int) async throws -> QuoteService
}

protocol TrainingServing {
    func trainingCategories() async throws -> [TrainingCategory]
    func trainings(page: Int, perPage: Int, query: String?, category: String?) async throws -> TrainingListData
    func training(slug: String) async throws -> TrainingDetailData
}

protocol TradeInServing {
    func tradeInMetadata() async throws -> TradeInMetadata
    func createTradeIn(payload: TradeInRequestPayload, ribFilename: String, ribData: Data, authorized: Bool) async throws -> TradeInRequestRecord
}

protocol ContactServing {
    func sendContact(name: String, email: String, subject: String, message: String) async throws
}

final class AppServices {
    let assets: AssetServing
    let account: AccountServing
    let cart: CartServing
    let products: ProductServing
    let favorites: FavoritesServing
    let orders: OrderServing
    let appointments: AppointmentServing
    let quotes: QuoteServing
    let news: NewsServing
    let serviceCatalog: ServiceCatalogServing
    let training: TrainingServing
    let tradeIn: TradeInServing
    let contact: ContactServing

    init(apiClient: APIClient) {
        let account = AccountService(api: apiClient)
        let cart = CartService(api: apiClient)
        let products = ProductService(api: apiClient)
        let favorites = FavoritesService(api: apiClient)
        let orders = OrderService(api: apiClient)
        let appointments = AppointmentService(api: apiClient)
        let quotes = QuoteServiceLayer(api: apiClient)
        let news = NewsService(api: apiClient)
        let serviceCatalog = ServiceCatalogService(api: apiClient)
        let training = TrainingService(api: apiClient)
        let tradeIn = TradeInService(api: apiClient)
        let contact = ContactService(api: apiClient)

        self.assets = products
        self.account = account
        self.cart = cart
        self.products = products
        self.favorites = favorites
        self.orders = orders
        self.appointments = appointments
        self.quotes = quotes
        self.news = news
        self.serviceCatalog = serviceCatalog
        self.training = training
        self.tradeIn = tradeIn
        self.contact = contact
    }
}

private struct AccountService: AccountServing {
    let api: APIClient
    func profile() async throws -> UserProfile { try await api.profile() }
    func login(email: String, password: String) async throws -> String { try await api.login(email: email, password: password) }
    func logout() async { await api.logout() }
    func updateProfile(firstName: String, lastName: String, email: String, address: String?, postalCode: String?, city: String?, birthDate: String, phoneNumber: String, gender: String) async throws -> UserProfile {
        try await api.updateProfile(firstName: firstName, lastName: lastName, email: email, address: address, postalCode: postalCode, city: city, birthDate: birthDate, phoneNumber: phoneNumber, gender: gender)
    }
    func deleteAccount() async throws { try await api.deleteAccount() }
    func register(email: String, password: String, confirmPassword: String, firstName: String, lastName: String, birthDate: String, phoneNumber: String, gender: String) async throws {
        try await api.register(email: email, password: password, confirmPassword: confirmPassword, firstName: firstName, lastName: lastName, birthDate: birthDate, phoneNumber: phoneNumber, gender: gender)
    }
    func requestPasswordReset(email: String) async throws { try await api.requestPasswordReset(email: email) }
    func resetPassword(token: String, password: String, confirmPassword: String) async throws { try await api.resetPassword(token: token, password: password, confirmPassword: confirmPassword) }
    func createAddress(label: String, address: String, postalCode: String, city: String, isDefault: Bool) async throws { try await api.createAddress(label: label, address: address, postalCode: postalCode, city: city, isDefault: isDefault) }
    func updateAddress(id: Int, label: String, address: String, postalCode: String, city: String, isDefault: Bool) async throws { try await api.updateAddress(id: id, label: label, address: address, postalCode: postalCode, city: city, isDefault: isDefault) }
    func deleteAddress(id: Int) async throws { try await api.deleteAddress(id: id) }
    func setDefaultAddress(id: Int) async throws { try await api.setDefaultAddress(id: id) }
    func listAddresses() async throws -> [UserAddress] { try await api.listAddresses() }
}

private struct CartService: CartServing {
    let api: APIClient
    func fetchCart() async throws -> Cart { try await api.fetchCart() }
    func addToCart(productId: Int, quantity: Int, rentalMonths: Int?) async throws -> Cart { try await api.addToCart(productId: productId, quantity: quantity, rentalMonths: rentalMonths) }
    func updateCart(productId: Int, quantity: Int, rentalMonths: Int?, currentRentalMonths: Int?) async throws -> Cart { try await api.updateCart(productId: productId, quantity: quantity, rentalMonths: rentalMonths, currentRentalMonths: currentRentalMonths) }
    func removeFromCart(productId: Int) async throws -> Cart { try await api.removeFromCart(productId: productId) }
    func clearCart() async throws -> Cart { try await api.clearCart() }
    func checkout() async throws -> OrderSummary { try await api.checkout() }
}

private struct ProductService: ProductServing {
    let api: APIClient
    func assetURL(for path: String?) -> URL? { api.assetURL(for: path) }
    func featuredProducts() async throws -> [Product] { try await api.featuredProducts() }
    func products(search: String?, categorySlug: String?, sellingType: SellingType?) async throws -> [Product] { try await api.products(search: search, categorySlug: categorySlug, sellingType: sellingType) }
    func categories() async throws -> [CategorySummary] { try await api.categories() }
    func product(slug: String) async throws -> Product { try await api.product(slug: slug) }
    func productReviews(slug: String, page: Int, perPage: Int) async throws -> ReviewListData { try await api.productReviews(slug: slug, page: page, perPage: perPage) }
}

private struct FavoritesService: FavoritesServing {
    let api: APIClient
    func listFavorites() async throws -> [FavoriteEntry] { try await api.listFavorites() }
    func addFavorite(productId: Int) async throws -> AddFavoriteResponse { try await api.addFavorite(productId: productId) }
    func removeFavorite(productId: Int) async throws -> Bool { try await api.removeFavorite(productId: productId) }
}

private struct OrderService: OrderServing {
    let api: APIClient
    func myOrders() async throws -> [OrderSummary] { try await api.myOrders() }
    func order(id: Int) async throws -> OrderSummary { try await api.order(id: id) }
    func cancelOrder(id: Int) async throws -> OrderSummary { try await api.cancelOrder(id: id) }
    func pendingReviews() async throws -> [PendingReviewItem] { try await api.pendingReviews() }
    func createReview(orderId: Int, orderItemId: Int, score: Int, comment: String?) async throws -> Review { try await api.createReview(orderId: orderId, orderItemId: orderItemId, score: score, comment: comment) }
}

private struct AppointmentService: AppointmentServing {
    let api: APIClient
    func appointmentPrestations() async throws -> [AppointmentPrestation] { try await api.appointmentPrestations() }
    func appointmentAvailability(prestationId: Int, start: Date, end: Date) async throws -> [AppointmentSlot] { try await api.appointmentAvailability(prestationId: prestationId, start: start, end: end) }
    func bookAppointment(prestationId: Int, startAt: Date) async throws -> AppointmentSummary { try await api.bookAppointment(prestationId: prestationId, startAt: startAt) }
    func cancelAppointment(id: Int) async throws { try await api.cancelAppointment(id: id) }
    func myAppointments() async throws -> AppointmentList { try await api.myAppointments() }
}

private struct QuoteServiceLayer: QuoteServing {
    let api: APIClient
    func quoteServices(page: Int?, perPage: Int?, query: String?) async throws -> QuoteServiceList { try await api.quoteServices(page: page, perPage: perPage, query: query) }
    func createQuote(name: String, email: String, company: String?, address: String?, items: [QuoteRequestItem]) async throws -> QuoteSummary { try await api.createQuote(name: name, email: email, company: company, address: address, items: items) }
    func myQuotes() async throws -> [QuoteSummary] { try await api.myQuotes() }
    func deleteQuote(id: Int) async throws { try await api.deleteQuote(id: id) }
}

private struct NewsService: NewsServing {
    let api: APIClient
    func latestNews(limit: Int) async throws -> [NewsArticle] { try await api.latestNews(limit: limit) }
    func newsArticles(page: Int, perPage: Int, query: String?) async throws -> NewsArticleListData { try await api.newsArticles(page: page, perPage: perPage, query: query) }
    func newsArticle(slug: String) async throws -> NewsArticle { try await api.newsArticle(slug: slug) }
    func newsComments(slug: String, page: Int, perPage: Int) async throws -> NewsCommentListData { try await api.newsComments(slug: slug, page: page, perPage: perPage) }
    func createNewsComment(slug: String, content: String) async throws -> NewsComment { try await api.createNewsComment(slug: slug, content: content) }
}

private struct ServiceCatalogService: ServiceCatalogServing {
    let api: APIClient
    func assetURL(for path: String?) -> URL? { api.assetURL(for: path) }
    func quoteServices(page: Int?, perPage: Int?, query: String?) async throws -> QuoteServiceList { try await api.quoteServices(page: page, perPage: perPage, query: query) }
    func publicService(id: Int) async throws -> QuoteService { try await api.publicService(id: id) }
}

private struct TrainingService: TrainingServing {
    let api: APIClient
    func trainingCategories() async throws -> [TrainingCategory] { try await api.trainingCategories() }
    func trainings(page: Int, perPage: Int, query: String?, category: String?) async throws -> TrainingListData { try await api.trainings(page: page, perPage: perPage, query: query, category: category) }
    func training(slug: String) async throws -> TrainingDetailData { try await api.training(slug: slug) }
}

private struct TradeInService: TradeInServing {
    let api: APIClient
    func tradeInMetadata() async throws -> TradeInMetadata { try await api.tradeInMetadata() }
    func createTradeIn(payload: TradeInRequestPayload, ribFilename: String, ribData: Data, authorized: Bool) async throws -> TradeInRequestRecord { try await api.createTradeIn(payload: payload, ribFilename: ribFilename, ribData: ribData, authorized: authorized) }
}

private struct ContactService: ContactServing {
    let api: APIClient
    func sendContact(name: String, email: String, subject: String, message: String) async throws {
        try await api.sendContact(name: name, email: email, subject: subject, message: message)
    }
}
