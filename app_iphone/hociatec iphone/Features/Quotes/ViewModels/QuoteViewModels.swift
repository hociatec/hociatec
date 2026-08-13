import Foundation
import Combine

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

    private let loadServicesUseCase: LoadQuoteServicesUseCase
    private let searchProductsUseCase: SearchQuoteProductsUseCase
    private let submitQuoteUseCase: SubmitQuoteUseCase
    private let account: AccountViewModel

    init(useCases: QuotesUseCases, account: AccountViewModel) {
        self.loadServicesUseCase = useCases.loadServices
        self.searchProductsUseCase = useCases.searchProducts
        self.submitQuoteUseCase = useCases.submitQuote
        self.account = account
        prefill()
    }
}

@MainActor
final class MyQuotesViewModel: ObservableObject {
    @Published var quotes: [QuoteSummary] = []
    @Published var isLoading = false
    @Published var error: String?
    private let loadMyQuotesUseCase: LoadMyQuotesUseCase
    private let deleteQuoteUseCase: DeleteQuoteUseCase

    init(useCases: QuotesUseCases) {
        self.loadMyQuotesUseCase = useCases.loadMyQuotes
        self.deleteQuoteUseCase = useCases.deleteQuote
    }
}
