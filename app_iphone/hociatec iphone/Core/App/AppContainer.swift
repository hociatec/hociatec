import SwiftUI
import Combine
import Foundation

/// Conteneur d’injection simple pour partager API + session dans toute l’app.
final class AppContainer: ObservableObject {
    let objectWillChange = ObservableObjectPublisher()
    let session: SessionStore
    let services: AppServices
    let cart: CartViewModel
    let account: AccountViewModel
    let productsUseCases: ProductsUseCases
    let quotesUseCases: QuotesUseCases
    let accountUseCases: AccountUseCases

    init() {
        let session = SessionStore()
        self.session = session
        let apiClient = APIClient(sessionStore: session)
        let services = AppServices(apiClient: apiClient)
        self.services = services

        let accountRepository = AccountRepositoryLive(service: services.account)
        let accountUseCases = AccountUseCases(
            login: LoginUseCase(repository: accountRepository),
            logout: LogoutUseCase(repository: accountRepository),
            loadProfile: LoadAccountProfileUseCase(repository: accountRepository),
            updateProfile: UpdateAccountProfileUseCase(repository: accountRepository),
            deleteAccount: DeleteAccountUseCase(repository: accountRepository),
            register: RegisterAccountUseCase(repository: accountRepository),
            loadAddresses: LoadAccountAddressesUseCase(repository: accountRepository),
            createAddress: CreateAccountAddressUseCase(repository: accountRepository),
            updateAddress: UpdateAccountAddressUseCase(repository: accountRepository),
            deleteAddress: DeleteAccountAddressUseCase(repository: accountRepository),
            setDefaultAddress: SetDefaultAccountAddressUseCase(repository: accountRepository)
        )
        self.accountUseCases = accountUseCases

        let productsRepository = ProductsRepositoryLive(
            productsService: services.products,
            favoritesService: services.favorites
        )
        let productsUseCases = ProductsUseCases(
            loadProductList: LoadProductListUseCase(repository: productsRepository),
            loadProducts: LoadProductsUseCase(repository: productsRepository),
            loadCategories: LoadProductCategoriesUseCase(repository: productsRepository),
            loadProductDetail: LoadProductDetailUseCase(repository: productsRepository),
            loadProductReviews: LoadProductReviewsUseCase(repository: productsRepository),
            loadFavoriteStatus: LoadProductFavoriteStatusUseCase(repository: productsRepository),
            toggleFavorite: ToggleProductFavoriteUseCase(repository: productsRepository)
        )
        self.productsUseCases = productsUseCases

        let quotesRepository = QuotesRepositoryLive(
            quoteService: services.quotes,
            productService: services.products
        )
        let quotesUseCases = QuotesUseCases(
            loadServices: LoadQuoteServicesUseCase(repository: quotesRepository),
            searchProducts: SearchQuoteProductsUseCase(repository: quotesRepository),
            submitQuote: SubmitQuoteUseCase(repository: quotesRepository),
            loadMyQuotes: LoadMyQuotesUseCase(repository: quotesRepository),
            downloadQuotePdf: DownloadQuotePdfUseCase(repository: quotesRepository),
            deleteQuote: DeleteQuoteUseCase(repository: quotesRepository)
        )
        self.quotesUseCases = quotesUseCases

        let cartVM = CartViewModel(service: services.cart)
        self.cart = cartVM
        self.account = AccountViewModel(useCases: accountUseCases, session: session)
    }

    func makeProductsViewModel(initialSellingType: SellingType? = nil) -> ProductsViewModel {
        ProductsViewModel(useCases: productsUseCases, initialSellingType: initialSellingType)
    }

    func makeProductDetailViewModel(product: Product) -> ProductDetailViewModel {
        ProductDetailViewModel(product: product, useCases: productsUseCases)
    }

    func makeQuoteViewModel() -> QuoteViewModel {
        QuoteViewModel(useCases: quotesUseCases, account: account)
    }

    func makeMyQuotesViewModel() -> MyQuotesViewModel {
        MyQuotesViewModel(useCases: quotesUseCases)
    }
}
