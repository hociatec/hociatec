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
