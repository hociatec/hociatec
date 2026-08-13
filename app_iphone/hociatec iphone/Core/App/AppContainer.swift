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

    init() {
        let session = SessionStore()
        self.session = session
        let apiClient = APIClient(sessionStore: session)
        let services = AppServices(apiClient: apiClient)
        self.services = services

        let productsRepository = ProductsRepositoryLive(
            productsService: services.products,
            favoritesService: services.favorites
        )
        self.productsUseCases = ProductsUseCases(
            loadProducts: LoadProductsUseCase(repository: productsRepository),
            loadCategories: LoadProductCategoriesUseCase(repository: productsRepository),
            loadProductDetail: LoadProductDetailUseCase(repository: productsRepository),
            loadProductReviews: LoadProductReviewsUseCase(repository: productsRepository),
            loadFavoriteStatus: LoadProductFavoriteStatusUseCase(repository: productsRepository),
            toggleFavorite: ToggleProductFavoriteUseCase(repository: productsRepository)
        )

        let quotesRepository = QuotesRepositoryLive(
            quoteService: services.quotes,
            productService: services.products
        )
        self.quotesUseCases = QuotesUseCases(
            loadServices: LoadQuoteServicesUseCase(repository: quotesRepository),
            searchProducts: SearchQuoteProductsUseCase(repository: quotesRepository),
            submitQuote: SubmitQuoteUseCase(repository: quotesRepository),
            loadMyQuotes: LoadMyQuotesUseCase(repository: quotesRepository),
            deleteQuote: DeleteQuoteUseCase(repository: quotesRepository)
        )

        let cartVM = CartViewModel(service: services.cart)
        self.cart = cartVM
        self.account = AccountViewModel(service: services.account, session: session)
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
