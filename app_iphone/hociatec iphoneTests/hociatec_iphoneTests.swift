import Foundation
import Testing
@testable import hociatec_iphone

@MainActor
struct hociatec_iphoneTests {
    @Test
    func sessionStoreDoesNotRestorePersistedAuthWhenRememberSessionIsDisabled() {
        let defaults = UserDefaults.standard
        defaults.set("jwt-stale", forKey: "hociatec.jwt")
        defaults.set(false, forKey: "hociatec.rememberSession")

        let profileData = try? JSONEncoder().encode(sampleProfile())
        defaults.set(profileData, forKey: "hociatec.profile")

        let session = SessionStore()

        #expect(session.jwtToken == nil)
        #expect(session.profile == nil)
        #expect(session.rememberSession == false)

        defaults.removeObject(forKey: "hociatec.jwt")
        defaults.removeObject(forKey: "hociatec.profile")
        defaults.removeObject(forKey: "hociatec.rememberSession")
    }

    @Test
    func benignCancellationDetectsTransportWrappedURLCancellation() {
        let wrappedCancellation = APIError.transport(
            NSError(domain: NSURLErrorDomain, code: NSURLErrorCancelled)
        )

        #expect(wrappedCancellation.isBenignCancellation)
    }

    @Test
    func loginSuccessDoesNotSurfaceAddressLoadingFailure() async throws {
        let session = makeCleanSession()
        let expectedProfile = sampleProfile()
        let repository = MockAccountRepository()
        repository.onLogin = {
            session.jwtToken = "jwt-test"
        }
        repository.profileToReturn = expectedProfile
        repository.addressesError = SampleError(message: "Adresse indisponible")

        let viewModel = AccountViewModel(
            useCases: makeUseCases(repository: repository),
            session: session
        )
        viewModel.email = expectedProfile.email
        viewModel.password = "Secret123"

        await viewModel.login()

        #expect(viewModel.error == nil)
        #expect(viewModel.profile?.id == expectedProfile.id)
        #expect(viewModel.isLoggedIn)
        #expect(viewModel.addresses.isEmpty)
    }

    @Test
    func loadAddressesCanFailSilentlyWhenRequested() async throws {
        let session = makeCleanSession()
        session.jwtToken = "jwt-test"
        session.profile = sampleProfile()

        let repository = MockAccountRepository()
        repository.addressesError = SampleError(message: "Erreur adresses")

        let viewModel = AccountViewModel(
            useCases: makeUseCases(repository: repository),
            session: session
        )

        await viewModel.loadAddresses(reportErrors: false)

        #expect(viewModel.error == nil)
        #expect(viewModel.addresses.isEmpty)
    }

    @Test
    func addressMutationReturnsLocalErrorWithoutPollutingGlobalAccountError() async throws {
        let session = makeCleanSession()
        session.jwtToken = "jwt-test"
        session.profile = sampleProfile()

        let repository = MockAccountRepository()
        repository.createAddressError = SampleError(message: "Création impossible")

        let viewModel = AccountViewModel(
            useCases: makeUseCases(repository: repository),
            session: session
        )

        let error = await viewModel.addAddress(
            type: "personal",
            label: "Maison",
            address: "1 rue de Paris",
            addressComplement: nil,
            postalCode: "75001",
            company: nil,
            companySiren: nil,
            companyVatNumber: nil,
            city: "Paris",
            isDefault: true,
            reportErrors: false
        )

        #expect(error == "Création impossible")
        #expect(viewModel.error == nil)
    }

    @Test
    func productsViewModelKeepsLatestForcedLoadResultWhenOlderRequestFinishesLast() async throws {
        let repository = MockProductsRepository()
        let category = sampleCategory()
        let oldProduct = sampleProduct(id: 1, name: "Ancien", slug: "ancien", category: category)
        let newProduct = sampleProduct(id: 2, name: "Nouveau", slug: "nouveau", category: category)

        await repository.setListResponses([
            .pending("first"),
            .success(ProductListData(
                items: [newProduct],
                meta: PaginationMeta(page: 1, perPage: 12, total: 1, totalPages: 1)
            ))
        ])

        let viewModel = ProductsViewModel(
            useCases: ProductsUseCases(
                loadProductList: LoadProductListUseCase(repository: repository),
                loadProducts: LoadProductsUseCase(repository: repository),
                loadCategories: LoadProductCategoriesUseCase(repository: repository),
                loadProductDetail: LoadProductDetailUseCase(repository: repository),
                loadProductReviews: LoadProductReviewsUseCase(repository: repository),
                loadFavoriteStatus: LoadProductFavoriteStatusUseCase(repository: repository),
                toggleFavorite: ToggleProductFavoriteUseCase(repository: repository)
            )
        )

        let firstLoad = Task { await viewModel.load() }
        while await !repository.hasPendingContinuation(for: "first") {
            await Task.yield()
        }

        let secondLoad = Task { await viewModel.load(force: true) }
        await secondLoad.value

        await repository.resolvePendingContinuation(
            for: "first",
            with: .success(ProductListData(
            items: [oldProduct],
            meta: PaginationMeta(page: 1, perPage: 12, total: 1, totalPages: 1)
        )))
        await firstLoad.value

        #expect(viewModel.products.map(\.id) == [newProduct.id])
        #expect(viewModel.products.first?.name == "Nouveau")
    }

    @Test
    func clientDashboardKeepsLatestLoadResultWhenOlderRequestFinishesLast() async throws {
        let firstAppointment = sampleAppointment(id: 1, offsetDays: 2)
        let secondAppointment = sampleAppointment(id: 2, offsetDays: 5)
        let appointmentService = MockAppointmentService()
        await appointmentService.setDashboardAppointmentResponses([
            .pending("first"),
            .success(AppointmentList(upcoming: [secondAppointment], past: []))
        ])

        let viewModel = ClientDashboardViewModel(
            quoteService: MockQuoteService(),
            appointmentService: appointmentService,
            orderService: MockOrderService(),
            trainingService: MockTrainingService(),
            workspaceService: MockWorkspaceService()
        )

        let firstLoad = Task { await viewModel.load() }
        while await !appointmentService.hasPendingDashboardContinuation(for: "first") {
            await Task.yield()
        }

        let secondLoad = Task { await viewModel.load(force: true) }
        await secondLoad.value

        await appointmentService.resolvePendingDashboardContinuation(
            for: "first",
            with: .success(AppointmentList(upcoming: [firstAppointment], past: []))
        )
        await firstLoad.value

        #expect(viewModel.actions.contains(where: { $0.id == "appointments" }))
        #expect(viewModel.actions.first(where: { $0.id == "appointments" })?.detail == DateFormatters.frDateTime.string(from: secondAppointment.startAt))
    }

    @Test
    func myAppointmentsCancelRefreshesListAndPublishesSuccessMessage() async throws {
        let service = MockAppointmentService()
        let appointment = sampleAppointment(id: 10, offsetDays: 3)
        let cancelledAppointment = sampleAppointment(id: 10, offsetDays: 3, status: "cancelled", statusCode: "cancelled", isCancelable: false)
        await service.setMyAppointmentsResponses([
            .immediate(AppointmentList(upcoming: [cancelledAppointment], past: []))
        ])
        await service.setCancelledAppointment(cancelledAppointment)

        let viewModel = MyAppointmentsViewModel(service: service)
        let success = await viewModel.cancel(appointmentID: appointment.id)

        #expect(success)
        #expect(viewModel.successMessage == "Rendez-vous annulé.")
        #expect(viewModel.upcoming.first?.isCancelledStatus == true)
    }

    @Test
    func cartAddPublishesGlobalSuccessDialog() async throws {
        let product = sampleProduct(id: 99, name: "Routeur", slug: "routeur", category: sampleCategory())
        let cart = Cart(
            token: "cart-token",
            items: [CartItem(id: 1, product: product, quantity: 1, linePriceCents: 1000, rentalMonths: nil)],
            totalQuantity: 1,
            totalPriceCents: 1000,
            updatedAt: nil
        )
        let service = MockCartService()
        service.cartToReturn = cart

        let viewModel = CartViewModel(service: service)
        await viewModel.add(product: product)

        #expect(viewModel.globalDialog?.title == "Succès")
        #expect(viewModel.globalDialog?.message == "Routeur ajouté au panier.")
        #expect(viewModel.cart?.totalQuantity == 1)
    }

    private func makeUseCases(repository: AccountRepository) -> AccountUseCases {
        AccountUseCases(
            login: LoginUseCase(repository: repository),
            logout: LogoutUseCase(repository: repository),
            loadProfile: LoadAccountProfileUseCase(repository: repository),
            updateProfile: UpdateAccountProfileUseCase(repository: repository),
            deleteAccount: DeleteAccountUseCase(repository: repository),
            register: RegisterAccountUseCase(repository: repository),
            loadAddresses: LoadAccountAddressesUseCase(repository: repository),
            createAddress: CreateAccountAddressUseCase(repository: repository),
            updateAddress: UpdateAccountAddressUseCase(repository: repository),
            deleteAddress: DeleteAccountAddressUseCase(repository: repository),
            setDefaultAddress: SetDefaultAccountAddressUseCase(repository: repository)
        )
    }

    private func makeCleanSession() -> SessionStore {
        let session = SessionStore()
        session.clearSession()
        session.cartToken = nil
        session.loginEmail = nil
        session.profile = nil
        session.rememberSession = false
        return session
    }

    private func sampleProfile() -> UserProfile {
        Self.sampleProfile()
    }

    private static func sampleProfile() -> UserProfile {
        UserProfile(
            id: 42,
            email: "test@hociatec.fr",
            firstName: "Test",
            lastName: "Client",
            roles: ["ROLE_USER"],
            address: nil,
            postalCode: nil,
            city: nil,
            birthDate: "1990-01-01",
            phoneNumber: "0600000000",
            gender: "autre",
            addresses: nil
        )
    }

    private func sampleCategory() -> CategorySummary {
        CategorySummary(id: 7, name: "Ordinateurs", slug: "ordinateurs")
    }

    private func sampleAppointmentPrestation() -> AppointmentPrestation {
        AppointmentPrestation(id: 3, name: "Diagnostic", durationMinutes: 60, priceCents: 4900)
    }

    private func sampleAppointment(
        id: Int,
        offsetDays: Int,
        status: String? = "confirmed",
        statusCode: String? = "confirmed",
        isCancelable: Bool? = true
    ) -> AppointmentSummary {
        let startAt = Calendar(identifier: .gregorian).date(byAdding: .day, value: offsetDays, to: Date()) ?? Date()
        let endAt = Calendar(identifier: .gregorian).date(byAdding: .minute, value: 60, to: startAt) ?? startAt

        return AppointmentSummary(
            id: id,
            startAt: startAt,
            endAt: endAt,
            status: status,
            statusCode: statusCode,
            isCancelable: isCancelable,
            isReschedulable: true,
            prestation: sampleAppointmentPrestation()
        )
    }

    private func sampleProduct(id: Int, name: String, slug: String, category: CategorySummary) -> Product {
        Product(
            id: id,
            name: name,
            slug: slug,
            sku: "SKU-\(id)",
            shortDescription: "Résumé",
            description: "Description",
            priceCents: 1000,
            sellingType: .sale,
            sellingTypeLabel: "Vente",
            priceUnitLabel: nil,
            effectivePriceCents: 1000,
            brand: "Hociatec",
            variantsCount: nil,
            variantColors: nil,
            variantStorages: nil,
            storageCapacity: nil,
            memoryRam: nil,
            color: nil,
            stock: 5,
            isPublished: true,
            isFeaturedHome: false,
            imageUrl: nil,
            imageAlt: nil,
            createdAt: nil,
            updatedAt: nil,
            category: category
        )
    }
}

private struct SampleError: LocalizedError {
    let message: String

    var errorDescription: String? { message }
}

private enum MockProductListResponse {
    case success(ProductListData)
    case pending(String)
}

private enum MockAppointmentListResponse {
    case immediate(AppointmentList)
    case pending(String)
    case success(AppointmentList)
}

@MainActor
private final class MockAccountRepository: AccountRepository {
    var onLogin: (() -> Void)?
    var profileToReturn = UserProfile(
        id: 1,
        email: "default@hociatec.fr",
        firstName: "Default",
        lastName: "User",
        roles: ["ROLE_USER"],
        address: nil,
        postalCode: nil,
        city: nil,
        birthDate: "1990-01-01",
        phoneNumber: "0600000000",
        gender: "autre",
        addresses: nil
    )
    var addressesToReturn: [UserAddress] = []
    var addressesError: Error?
    var createAddressError: Error?

    func login(email: String, password: String) async throws {
        onLogin?()
    }

    func logout() async {}

    func fetchProfile() async throws -> UserProfile {
        profileToReturn
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
        profileToReturn
    }

    func deleteAccount() async throws {}

    func register(
        email: String,
        password: String,
        confirmPassword: String,
        firstName: String,
        lastName: String,
        birthDate: String,
        phoneNumber: String,
        gender: String
    ) async throws {}

    func listAddresses() async throws -> [UserAddress] {
        if let addressesError {
            throw addressesError
        }

        return addressesToReturn
    }

    func createAddress(
        type: String,
        label: String,
        address: String,
        addressComplement: String?,
        postalCode: String,
        company: String?,
        companySiren: String?,
        companyVatNumber: String?,
        city: String,
        isDefault: Bool
    ) async throws {
        if let createAddressError {
            throw createAddressError
        }
    }

    func updateAddress(
        id: Int,
        type: String,
        label: String,
        address: String,
        addressComplement: String?,
        postalCode: String,
        company: String?,
        companySiren: String?,
        companyVatNumber: String?,
        city: String,
        isDefault: Bool
    ) async throws {}

    func deleteAddress(id: Int) async throws {}

    func setDefaultAddress(id: Int) async throws {}
}

private actor MockProductsRepository: ProductsRepository {
    var listResponses: [MockProductListResponse] = []
    var pendingContinuations: [String: (Result<ProductListData, Error>) -> Void] = [:]

    func setListResponses(_ responses: [MockProductListResponse]) {
        listResponses = responses
    }

    func hasPendingContinuation(for key: String) -> Bool {
        pendingContinuations[key] != nil
    }

    func resolvePendingContinuation(for key: String, with result: Result<ProductListData, Error>) {
        pendingContinuations[key]?(result)
        pendingContinuations[key] = nil
    }

    func fetchProductList(
        search: String?,
        categorySlug: String?,
        sellingType: SellingType?,
        page: Int,
        perPage: Int
    ) async throws -> ProductListData {
        guard !listResponses.isEmpty else {
            return ProductListData(items: [], meta: PaginationMeta(page: page, perPage: perPage, total: 0, totalPages: 1))
        }

        let response = listResponses.removeFirst()
        switch response {
        case let .success(data):
            return data
        case let .pending(key):
            return try await withCheckedThrowingContinuation { continuation in
                pendingContinuations[key] = { result in
                    continuation.resume(with: result)
                }
            }
        }
    }

    func fetchProducts(search: String?, categorySlug: String?, sellingType: SellingType?) async throws -> [Product] {
        []
    }

    func fetchCategories() async throws -> [CategorySummary] {
        []
    }

    func fetchProduct(slug: String) async throws -> Product {
        throw SampleError(message: "Unused")
    }

    func fetchReviews(slug: String, page: Int, perPage: Int) async throws -> ReviewListData {
        ReviewListData(items: [], meta: PaginationMeta(page: page, perPage: perPage, total: 0, totalPages: 1))
    }

    func fetchFavorites() async throws -> [FavoriteEntry] {
        []
    }

    func addFavorite(productId: Int) async throws {}

    func removeFavorite(productId: Int) async throws {}
}

private actor MockAppointmentService: AppointmentServing {
    var dashboardResponses: [MockAppointmentListResponse] = []
    var dashboardContinuations: [String: (Result<AppointmentList, Error>) -> Void] = [:]
    var myAppointmentResponses: [MockAppointmentListResponse] = []
    var cancelledAppointment: AppointmentSummary?

    func setDashboardAppointmentResponses(_ responses: [MockAppointmentListResponse]) {
        dashboardResponses = responses
    }

    func hasPendingDashboardContinuation(for key: String) -> Bool {
        dashboardContinuations[key] != nil
    }

    func resolvePendingDashboardContinuation(for key: String, with result: Result<AppointmentList, Error>) {
        dashboardContinuations[key]?(result)
        dashboardContinuations[key] = nil
    }

    func setMyAppointmentsResponses(_ responses: [MockAppointmentListResponse]) {
        myAppointmentResponses = responses
    }

    func setCancelledAppointment(_ appointment: AppointmentSummary) {
        cancelledAppointment = appointment
    }

    func appointmentPrestations() async throws -> [AppointmentPrestation] { [] }
    func appointmentAvailability(prestationId: Int, start: Date, end: Date) async throws -> [AppointmentSlot] { [] }
    func bookAppointment(prestationId: Int, startAt: Date) async throws -> AppointmentSummary { throw SampleError(message: "Unused") }
    func rescheduleAppointment(id: Int, startAt: Date) async throws -> AppointmentSummary { throw SampleError(message: "Unused") }
    func cancelAppointment(id: Int) async throws {}

    func myAppointments() async throws -> AppointmentList {
        if !myAppointmentResponses.isEmpty {
            let response = myAppointmentResponses.removeFirst()
            switch response {
            case let .immediate(list), let .success(list):
                return list
            case let .pending(key):
                return try await withCheckedThrowingContinuation { continuation in
                    dashboardContinuations[key] = { result in continuation.resume(with: result) }
                }
            }
        }

        if !dashboardResponses.isEmpty {
            let response = dashboardResponses.removeFirst()
            switch response {
            case let .immediate(list), let .success(list):
                return list
            case let .pending(key):
                return try await withCheckedThrowingContinuation { continuation in
                    dashboardContinuations[key] = { result in continuation.resume(with: result) }
                }
            }
        }

        return AppointmentList(upcoming: [], past: [])
    }
}

private struct MockQuoteService: QuoteServing {
    func quoteServices(page: Int?, perPage: Int?, query: String?) async throws -> QuoteServiceList {
        QuoteServiceList(items: [], meta: nil)
    }
    func createQuote(name: String, email: String, company: String?, address: String?, items: [QuoteRequestItem]) async throws -> QuoteSummary {
        throw SampleError(message: "Unused")
    }
    func myQuotes() async throws -> [QuoteSummary] { [] }
    func myQuotePdf(id: Int) async throws -> Data { Data() }
    func deleteQuote(id: Int) async throws {}
}

private struct MockOrderService: OrderServing {
    func myOrders() async throws -> [OrderSummary] { [] }
    func order(id: Int) async throws -> OrderSummary { throw SampleError(message: "Unused") }
    func cancelOrder(id: Int) async throws -> OrderSummary { throw SampleError(message: "Unused") }
    func checkoutSessionStatus(stripeSessionId: String) async throws -> CheckoutSessionStatusData { throw SampleError(message: "Unused") }
    func cancelCheckoutSession(stripeSessionId: String) async throws -> CheckoutSessionStatusData { throw SampleError(message: "Unused") }
    func pendingReviews() async throws -> [PendingReviewItem] { [] }
    func createReview(orderId: Int, orderItemId: Int, score: Int, comment: String?) async throws -> Review { throw SampleError(message: "Unused") }
}

private struct MockTrainingService: TrainingServing {
    func trainingCategories() async throws -> [TrainingCategory] { [] }
    func trainings(page: Int, perPage: Int, query: String?, category: String?) async throws -> TrainingListData {
        TrainingListData(items: [], meta: PaginationMeta(page: page, perPage: perPage, total: 0, totalPages: 1))
    }
    func training(slug: String) async throws -> TrainingDetailData { throw SampleError(message: "Unused") }
    func enroll(sessionId: Int, startsAt: Date) async throws -> TrainingEnrollmentCheckoutResult { throw SampleError(message: "Unused") }
    func myEnrollments(page: Int, perPage: Int) async throws -> TrainingEnrollmentListData {
        TrainingEnrollmentListData(items: [], meta: PaginationMeta(page: page, perPage: perPage, total: 0, totalPages: 1))
    }
}

private struct MockWorkspaceService: WorkspaceServing {
    func accountNotifications() async throws -> [AccountNotificationItem] { [] }
    func accountNotificationsReadState() async throws -> AccountNotificationsReadState {
        AccountNotificationsReadState(seenSignature: "", seenKeys: [], dismissedKeys: [])
    }
    func markAccountNotificationsSeen(keys: [String]) async throws -> AccountNotificationsReadState {
        AccountNotificationsReadState(seenSignature: "", seenKeys: keys, dismissedKeys: [])
    }
    func dismissAccountNotification(key: String) async throws -> AccountNotificationsReadState {
        AccountNotificationsReadState(seenSignature: "", seenKeys: [key], dismissedKeys: [key])
    }
    func dismissAccountNotifications(keys: [String]) async throws -> AccountNotificationsReadState {
        AccountNotificationsReadState(seenSignature: "", seenKeys: keys, dismissedKeys: keys)
    }
    func communicationPreferences() async throws -> CommunicationPreferencesData {
        CommunicationPreferencesData(preferences: [], choices: [])
    }
    func updateCommunicationPreferences(preferences: [String]) async throws -> CommunicationPreferencesData {
        CommunicationPreferencesData(preferences: preferences, choices: [])
    }
    func loyaltyBalance() async throws -> LoyaltyBalance {
        LoyaltyBalance(points: 0, euroCents: 0, pointsPerEuroEarned: 10, pointsPerEuroConverted: 100)
    }
    func convertLoyalty(points: Int) async throws -> LoyaltyConversionData {
        LoyaltyConversionData(loyalty: LoyaltyBalance(points: 0, euroCents: 0, pointsPerEuroEarned: 10, pointsPerEuroConverted: 100), voucher: VoucherSummary(id: 1, code: "CODE", name: "Bon", discountType: "percent", discountValue: 10, description: nil))
    }
}

private final class MockCartService: CartServing {
    var cartToReturn = Cart(token: "empty", items: [], totalQuantity: 0, totalPriceCents: 0, updatedAt: nil)

    func fetchCart() async throws -> Cart { cartToReturn }
    func addToCart(productId: Int, quantity: Int, rentalMonths: Int?) async throws -> Cart { cartToReturn }
    func updateCart(productId: Int, quantity: Int, rentalMonths: Int?, currentRentalMonths: Int?) async throws -> Cart { cartToReturn }
    func removeFromCart(productId: Int) async throws -> Cart { cartToReturn }
    func clearCart() async throws -> Cart { cartToReturn }
    func checkout() async throws -> CheckoutResult { CheckoutResult(order: nil, checkoutURL: nil, checkoutSessionId: nil) }
}
