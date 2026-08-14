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

    @Test
    func newsListKeepsLatestForcedLoadResultWhenOlderRequestFinishesLast() async throws {
        let service = MockNewsService()
        let oldArticle = sampleNewsArticle(id: 1, title: "Ancien", slug: "ancien")
        let newArticle = sampleNewsArticle(id: 2, title: "Nouveau", slug: "nouveau")
        await service.setArticleListResponses([
            .pending("first"),
            .success(NewsArticleListData(
                items: [newArticle],
                meta: PaginationMeta(page: 1, perPage: 9, total: 1, totalPages: 1)
            ))
        ])

        let viewModel = NewsListViewModel(service: service)

        let firstLoad = Task { await viewModel.load() }
        while await !service.hasPendingArticleListContinuation(for: "first") {
            await Task.yield()
        }

        let secondLoad = Task { await viewModel.load(force: true) }
        await secondLoad.value

        await service.resolvePendingArticleListContinuation(
            for: "first",
            with: .success(NewsArticleListData(
                items: [oldArticle],
                meta: PaginationMeta(page: 1, perPage: 9, total: 1, totalPages: 1)
            ))
        )
        await firstLoad.value

        #expect(viewModel.articles.map(\.id) == [newArticle.id])
        #expect(viewModel.articles.first?.title == "Nouveau")
    }

    @Test
    func servicesCatalogKeepsLatestForcedLoadResultWhenOlderRequestFinishesLast() async throws {
        let service = MockServiceCatalogService()
        let oldService = sampleQuoteService(id: 1, title: "Ancien service")
        let newService = sampleQuoteService(id: 2, title: "Nouveau service")
        await service.setQuoteServiceResponses([
            .pending("first"),
            .success(QuoteServiceList(
                items: [newService],
                meta: PaginationMeta(page: 1, perPage: 7, total: 1, totalPages: 1)
            ))
        ])

        let viewModel = ServicesCatalogViewModel(serviceCatalog: service)

        let firstLoad = Task { await viewModel.load() }
        while await !service.hasPendingQuoteServiceContinuation(for: "first") {
            await Task.yield()
        }

        let secondLoad = Task { await viewModel.load(force: true) }
        await secondLoad.value

        await service.resolvePendingQuoteServiceContinuation(
            for: "first",
            with: .success(QuoteServiceList(
                items: [oldService],
                meta: PaginationMeta(page: 1, perPage: 7, total: 1, totalPages: 1)
            ))
        )
        await firstLoad.value

        #expect(viewModel.services.map(\.id) == [newService.id])
        #expect(viewModel.services.first?.title == "Nouveau service")
    }

    @Test
    func productReviewsKeepLatestForcedReloadResultWhenOlderRequestFinishesLast() async throws {
        let productService = MockProductService()
        let oldReview = sampleReview(id: 1, comment: "Ancien avis")
        let newReview = sampleReview(id: 2, comment: "Nouvel avis")
        await productService.setReviewResponses([
            .pending("first"),
            .success(ReviewListData(
                items: [newReview],
                meta: ReviewListMeta(page: 1, perPage: 20, total: 1, average: 5)
            ))
        ])

        let viewModel = ProductReviewsViewModel(productSlug: "routeur", productSku: "SKU-1")
        let orderService = MockOrderService()

        let firstLoad = Task {
            await viewModel.load(
                productService: productService,
                orderService: orderService,
                page: 1,
                replace: true,
                isLoggedIn: false
            )
        }
        while await !productService.hasPendingReviewContinuation(for: "first") {
            await Task.yield()
        }

        let secondLoad = Task {
            await viewModel.load(
                productService: productService,
                orderService: orderService,
                page: 1,
                replace: true,
                isLoggedIn: false,
                force: true
            )
        }
        await secondLoad.value

        await productService.resolvePendingReviewContinuation(
            for: "first",
            with: .success(ReviewListData(
                items: [oldReview],
                meta: ReviewListMeta(page: 1, perPage: 20, total: 1, average: 3)
            ))
        )
        await firstLoad.value

        #expect(viewModel.reviews.map(\.id) == [newReview.id])
        #expect(viewModel.reviews.first?.comment == "Nouvel avis")
        #expect(viewModel.average == 5)
    }

    @Test
    func trainingsCatalogKeepsLatestForcedLoadResultWhenOlderRequestFinishesLast() async throws {
        let service = MockTrainingService()
        let oldTraining = sampleTraining(id: 1, title: "Ancienne formation", slug: "ancienne-formation")
        let newTraining = sampleTraining(id: 2, title: "Nouvelle formation", slug: "nouvelle-formation")
        await service.setTrainingListResponses([
            .pending("first"),
            .success(TrainingListData(
                items: [newTraining],
                meta: PaginationMeta(page: 1, perPage: 8, total: 1, totalPages: 1)
            ))
        ])

        let viewModel = TrainingsCatalogViewModel(service: service)

        let firstLoad = Task { await viewModel.load() }
        while await !service.hasPendingTrainingListContinuation(for: "first") {
            await Task.yield()
        }

        let secondLoad = Task { await viewModel.load(force: true) }
        await secondLoad.value

        await service.resolvePendingTrainingListContinuation(
            for: "first",
            with: .success(TrainingListData(
                items: [oldTraining],
                meta: PaginationMeta(page: 1, perPage: 8, total: 1, totalPages: 1)
            ))
        )
        await firstLoad.value

        #expect(viewModel.trainings.map(\.id) == [newTraining.id])
        #expect(viewModel.trainings.first?.title == "Nouvelle formation")
    }

    @Test
    func auditsViewModelKeepsLatestForcedLoadResultWhenOlderRequestFinishesLast() async throws {
        let service = MockAuditService()
        let oldAudit = sampleAuditListItem(id: 1, number: "AUD-001", statusLabel: "Ancien état")
        let newAudit = sampleAuditListItem(id: 2, number: "AUD-002", statusLabel: "Nouvel état")
        await service.setAuditListResponses([
            .pending("first"),
            .success(AuditListData(
                items: [newAudit],
                meta: PaginationMeta(page: 1, perPage: 20, total: 1, totalPages: 1)
            ))
        ])

        let viewModel = AuditsViewModel(service: service)

        let firstLoad = Task { await viewModel.load() }
        while await !service.hasPendingAuditListContinuation(for: "first") {
            await Task.yield()
        }

        let secondLoad = Task { await viewModel.load(force: true) }
        await secondLoad.value

        await service.resolvePendingAuditListContinuation(
            for: "first",
            with: .success(AuditListData(
                items: [oldAudit],
                meta: PaginationMeta(page: 1, perPage: 20, total: 1, totalPages: 1)
            ))
        )
        await firstLoad.value

        #expect(viewModel.items.map(\.id) == [newAudit.id])
        #expect(viewModel.items.first?.number == "AUD-002")
    }

    @Test
    func supportReplyInvalidatesOlderDetailResponse() async throws {
        let staleDetail = sampleSupportRequest(id: 10, subject: "Ancien sujet", timelineMessage: "Ancien message")
        let updatedDetail = sampleSupportRequest(id: 10, subject: "Sujet mis a jour", timelineMessage: "Réponse envoyée")
        let service = MockSupportService()
        await service.setDetailResponses([
            .pending("first")
        ])
        await service.setReplyResult(updatedDetail)

        let viewModel = SupportViewModel(service: service)

        let firstLoad = Task { await viewModel.loadDetail(id: 10) }
        while await !service.hasPendingDetailContinuation(for: "first") {
            await Task.yield()
        }

        let replySuccess = await viewModel.reply(
            id: 10,
            subject: nil,
            message: "Réponse envoyée",
            attachments: []
        )

        #expect(replySuccess)
        #expect(viewModel.selectedItem?.subject == updatedDetail.subject)
        #expect(viewModel.successMessage == "Réponse envoyée.")

        await service.resolvePendingDetailContinuation(
            for: "first",
            with: .success(staleDetail)
        )
        await firstLoad.value

        #expect(viewModel.selectedItem?.subject == updatedDetail.subject)
        #expect(viewModel.selectedItem?.timeline.first?.message == updatedDetail.timeline.first?.message)
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

    private func sampleNewsArticle(id: Int, title: String, slug: String) -> NewsArticle {
        NewsArticle(
            id: id,
            title: title,
            slug: slug,
            excerpt: "Résumé",
            content: "Contenu",
            category: "Infos",
            isPublished: true,
            viewsCount: 10,
            publishedAt: Date(),
            createdAt: Date(),
            updatedAt: Date()
        )
    }

    private func sampleQuoteService(id: Int, title: String) -> QuoteService {
        QuoteService(
            id: id,
            title: title,
            description: "Description",
            unit: "heure",
            isFeaturedHome: false,
            imageUrl: nil,
            imageAlt: nil,
            durationValue: 1,
            durationUnit: "hour",
            durationLabel: "1 heure",
            priceCents: 1000,
            vatRate: 20
        )
    }

    private func sampleReview(id: Int, comment: String) -> Review {
        Review(
            id: id,
            score: 5,
            comment: comment,
            createdAt: Date(),
            author: ReviewAuthor(id: id, displayName: "Client \(id)"),
            orderItemId: id
        )
    }

    private func sampleTraining(id: Int, title: String, slug: String) -> Training {
        Training(
            id: id,
            title: title,
            slug: slug,
            shortDescription: "Résumé",
            objective: "Objectif",
            audience: "Audience",
            category: "Numerique",
            durationMinutes: 120,
            priceCents: 15000,
            availableFormats: ["remote"],
            isActive: true,
            roadmap: [],
            categoryDetails: TrainingCategoryReference(id: 1, name: "Numérique", slug: "numerique"),
            availableFormatDetails: [TrainingFormatOption(value: "remote", label: "À distance")]
        )
    }

    private func sampleAuditListItem(id: Int, number: String, statusLabel: String) -> AuditListItem {
        AuditListItem(
            id: id,
            number: number,
            type: "accessibility",
            status: "open",
            typeLabel: "Accessibilité",
            statusLabel: statusLabel,
            url: "https://example.com",
            createdAt: Date()
        )
    }

    private func sampleSupportRequest(id: Int, subject: String, timelineMessage: String) -> SupportRequestSummary {
        SupportRequestSummary(
            id: id,
            status: "open",
            statusLabel: "Ouvert",
            reason: "other",
            subject: subject,
            message: "Message initial",
            customer: SupportCustomer(id: 1, name: "Client Test", email: "client@test.fr"),
            order: SupportOrderReference(id: 1, number: "CMD-001"),
            attachments: [],
            awaitingReplyFrom: "customer",
            awaitingReplyLabel: "Client",
            timeline: [
                SupportTimelineEntry(
                    id: "entry-\(id)",
                    type: "message",
                    actor: "customer",
                    visibility: "public",
                    authorLabel: "Client Test",
                    subject: subject,
                    message: timelineMessage,
                    status: "open",
                    statusLabel: "Ouvert",
                    attachments: [],
                    createdAt: Date()
                )
            ],
            createdAt: Date(),
            updatedAt: Date(),
            resolvedAt: nil
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

private enum MockNewsArticleListResponse {
    case success(NewsArticleListData)
    case pending(String)
}

private enum MockQuoteServiceListResponse {
    case success(QuoteServiceList)
    case pending(String)
}

private enum MockReviewListResponse {
    case success(ReviewListData)
    case pending(String)
}

private enum MockTrainingListResponse {
    case success(TrainingListData)
    case pending(String)
}

private enum MockAuditListResponse {
    case success(AuditListData)
    case pending(String)
}

private enum MockSupportDetailResponse {
    case success(SupportRequestSummary)
    case pending(String)
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

private actor MockNewsService: NewsServing {
    var articleListResponses: [MockNewsArticleListResponse] = []
    var articleListContinuations: [String: (Result<NewsArticleListData, Error>) -> Void] = [:]

    func setArticleListResponses(_ responses: [MockNewsArticleListResponse]) {
        articleListResponses = responses
    }

    func hasPendingArticleListContinuation(for key: String) -> Bool {
        articleListContinuations[key] != nil
    }

    func resolvePendingArticleListContinuation(for key: String, with result: Result<NewsArticleListData, Error>) {
        articleListContinuations[key]?(result)
        articleListContinuations[key] = nil
    }

    func latestNews(limit: Int) async throws -> [NewsArticle] { [] }

    func newsArticles(page: Int, perPage: Int, query: String?) async throws -> NewsArticleListData {
        guard !articleListResponses.isEmpty else {
            return NewsArticleListData(
                items: [],
                meta: PaginationMeta(page: page, perPage: perPage, total: 0, totalPages: 1)
            )
        }

        let response = articleListResponses.removeFirst()
        switch response {
        case let .success(data):
            return data
        case let .pending(key):
            return try await withCheckedThrowingContinuation { continuation in
                articleListContinuations[key] = { result in
                    continuation.resume(with: result)
                }
            }
        }
    }

    func newsArticle(slug: String) async throws -> NewsArticle {
        throw SampleError(message: "Unused")
    }

    func newsComments(slug: String, page: Int, perPage: Int) async throws -> NewsCommentListData {
        NewsCommentListData(
            items: [],
            meta: PaginationMeta(page: page, perPage: perPage, total: 0, totalPages: 1)
        )
    }

    func createNewsComment(slug: String, content: String) async throws -> NewsComment {
        throw SampleError(message: "Unused")
    }
}

private actor MockServiceCatalogService: ServiceCatalogServing {
    var quoteServiceResponses: [MockQuoteServiceListResponse] = []
    var quoteServiceContinuations: [String: (Result<QuoteServiceList, Error>) -> Void] = [:]

    func setQuoteServiceResponses(_ responses: [MockQuoteServiceListResponse]) {
        quoteServiceResponses = responses
    }

    func hasPendingQuoteServiceContinuation(for key: String) -> Bool {
        quoteServiceContinuations[key] != nil
    }

    func resolvePendingQuoteServiceContinuation(for key: String, with result: Result<QuoteServiceList, Error>) {
        quoteServiceContinuations[key]?(result)
        quoteServiceContinuations[key] = nil
    }

    nonisolated func assetURL(for path: String?) -> URL? { nil }

    func quoteServices(page: Int?, perPage: Int?, query: String?) async throws -> QuoteServiceList {
        let currentPage = page ?? 1
        let currentPerPage = perPage ?? 7

        guard !quoteServiceResponses.isEmpty else {
            return QuoteServiceList(
                items: [],
                meta: PaginationMeta(page: currentPage, perPage: currentPerPage, total: 0, totalPages: 1)
            )
        }

        let response = quoteServiceResponses.removeFirst()
        switch response {
        case let .success(data):
            return data
        case let .pending(key):
            return try await withCheckedThrowingContinuation { continuation in
                quoteServiceContinuations[key] = { result in
                    continuation.resume(with: result)
                }
            }
        }
    }

    func publicService(id: Int) async throws -> QuoteService {
        throw SampleError(message: "Unused")
    }
}

private actor MockProductService: ProductServing {
    var reviewResponses: [MockReviewListResponse] = []
    var reviewContinuations: [String: (Result<ReviewListData, Error>) -> Void] = [:]

    func setReviewResponses(_ responses: [MockReviewListResponse]) {
        reviewResponses = responses
    }

    func hasPendingReviewContinuation(for key: String) -> Bool {
        reviewContinuations[key] != nil
    }

    func resolvePendingReviewContinuation(for key: String, with result: Result<ReviewListData, Error>) {
        reviewContinuations[key]?(result)
        reviewContinuations[key] = nil
    }

    nonisolated func assetURL(for path: String?) -> URL? { nil }
    func featuredProducts() async throws -> [Product] { [] }

    func productList(search: String?, categorySlug: String?, sellingType: SellingType?, page: Int?, perPage: Int?) async throws -> ProductListData {
        ProductListData(items: [], meta: PaginationMeta(page: page ?? 1, perPage: perPage ?? 12, total: 0, totalPages: 1))
    }

    func products(search: String?, categorySlug: String?, sellingType: SellingType?) async throws -> [Product] { [] }
    func categories() async throws -> [CategorySummary] { [] }
    func product(slug: String) async throws -> Product { throw SampleError(message: "Unused") }

    func productReviews(slug: String, page: Int, perPage: Int) async throws -> ReviewListData {
        guard !reviewResponses.isEmpty else {
            return ReviewListData(
                items: [],
                meta: ReviewListMeta(page: page, perPage: perPage, total: 0, average: nil)
            )
        }

        let response = reviewResponses.removeFirst()
        switch response {
        case let .success(data):
            return data
        case let .pending(key):
            return try await withCheckedThrowingContinuation { continuation in
                reviewContinuations[key] = { result in
                    continuation.resume(with: result)
                }
            }
        }
    }
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

private actor MockTrainingService: TrainingServing {
    var trainingListResponses: [MockTrainingListResponse] = []
    var pendingTrainingListContinuations: [String: (Result<TrainingListData, Error>) -> Void] = [:]

    func setTrainingListResponses(_ responses: [MockTrainingListResponse]) {
        trainingListResponses = responses
    }

    func hasPendingTrainingListContinuation(for key: String) -> Bool {
        pendingTrainingListContinuations[key] != nil
    }

    func resolvePendingTrainingListContinuation(for key: String, with result: Result<TrainingListData, Error>) {
        pendingTrainingListContinuations[key]?(result)
        pendingTrainingListContinuations[key] = nil
    }

    func trainingCategories() async throws -> [TrainingCategory] { [] }
    func trainings(page: Int, perPage: Int, query: String?, category: String?) async throws -> TrainingListData {
        if !trainingListResponses.isEmpty {
            let response = trainingListResponses.removeFirst()
            switch response {
            case let .success(data):
                return data
            case let .pending(key):
                return try await withCheckedThrowingContinuation { continuation in
                    pendingTrainingListContinuations[key] = { result in continuation.resume(with: result) }
                }
            }
        }

        return TrainingListData(items: [], meta: PaginationMeta(page: page, perPage: perPage, total: 0, totalPages: 1))
    }
    func training(slug: String) async throws -> TrainingDetailData { throw SampleError(message: "Unused") }
    func enroll(sessionId: Int, startsAt: Date) async throws -> TrainingEnrollmentCheckoutResult { throw SampleError(message: "Unused") }
    func myEnrollments(page: Int, perPage: Int) async throws -> TrainingEnrollmentListData {
        TrainingEnrollmentListData(items: [], meta: PaginationMeta(page: page, perPage: perPage, total: 0, totalPages: 1))
    }
}

private actor MockAuditService: AuditServing {
    var auditListResponses: [MockAuditListResponse] = []
    var pendingAuditListContinuations: [String: (Result<AuditListData, Error>) -> Void] = [:]

    func setAuditListResponses(_ responses: [MockAuditListResponse]) {
        auditListResponses = responses
    }

    func hasPendingAuditListContinuation(for key: String) -> Bool {
        pendingAuditListContinuations[key] != nil
    }

    func resolvePendingAuditListContinuation(for key: String, with result: Result<AuditListData, Error>) {
        pendingAuditListContinuations[key]?(result)
        pendingAuditListContinuations[key] = nil
    }

    func auditMetadata() async throws -> AuditMetadata {
        AuditMetadata(types: [AuditOption(value: "accessibility", label: "Accessibilité")], statuses: [])
    }

    func createAudit(type: String, url: String, objectives: String?) async throws -> AuditCreateResponse {
        AuditCreateResponse(id: 1, number: "AUD-001")
    }

    func myAudits(page: Int, perPage: Int) async throws -> AuditListData {
        if !auditListResponses.isEmpty {
            let response = auditListResponses.removeFirst()
            switch response {
            case let .success(data):
                return data
            case let .pending(key):
                return try await withCheckedThrowingContinuation { continuation in
                    pendingAuditListContinuations[key] = { result in continuation.resume(with: result) }
                }
            }
        }

        return AuditListData(items: [], meta: PaginationMeta(page: page, perPage: perPage, total: 0, totalPages: 1))
    }

    func myAudit(id: Int) async throws -> AuditDetail { throw SampleError(message: "Unused") }
    func myAuditPdf(id: Int) async throws -> Data { Data() }
    func myAuditSummaryPdf(id: Int) async throws -> Data { Data() }
}

private actor MockSupportService: SupportServing {
    var detailResponses: [MockSupportDetailResponse] = []
    var pendingDetailContinuations: [String: (Result<SupportRequestSummary, Error>) -> Void] = [:]
    var replyResult: SupportRequestSummary?

    func setDetailResponses(_ responses: [MockSupportDetailResponse]) {
        detailResponses = responses
    }

    func hasPendingDetailContinuation(for key: String) -> Bool {
        pendingDetailContinuations[key] != nil
    }

    func resolvePendingDetailContinuation(for key: String, with result: Result<SupportRequestSummary, Error>) {
        pendingDetailContinuations[key]?(result)
        pendingDetailContinuations[key] = nil
    }

    func setReplyResult(_ value: SupportRequestSummary) {
        replyResult = value
    }

    func mySupportRequests(page: Int, perPage: Int) async throws -> SupportRequestListData {
        SupportRequestListData(items: [], meta: PaginationMeta(page: page, perPage: perPage, total: 0, totalPages: 1))
    }

    func mySupportRequest(id: Int) async throws -> SupportRequestSummary {
        if !detailResponses.isEmpty {
            let response = detailResponses.removeFirst()
            switch response {
            case let .success(item):
                return item
            case let .pending(key):
                return try await withCheckedThrowingContinuation { continuation in
                    pendingDetailContinuations[key] = { result in continuation.resume(with: result) }
                }
            }
        }

        throw SampleError(message: "Unused")
    }

    func createSupportRequest(subject: String, reason: String, message: String, orderId: Int?, attachments: [MultipartUploadFile]) async throws -> SupportRequestSummary {
        throw SampleError(message: "Unused")
    }

    func replySupportRequest(id: Int, subject: String?, message: String, attachments: [MultipartUploadFile]) async throws -> SupportRequestSummary {
        if let replyResult {
            return replyResult
        }

        throw SampleError(message: "Unused")
    }

    func mySupportAttachment(id: Int, name: String) async throws -> Data {
        Data()
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
