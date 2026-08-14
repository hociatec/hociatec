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

    let loadServicesUseCase: LoadQuoteServicesUseCase
    let searchProductsUseCase: SearchQuoteProductsUseCase
    let submitQuoteUseCase: SubmitQuoteUseCase
    let account: AccountViewModel
    var servicesRequestID = 0
    var productSearchRequestID = 0

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
    @Published var sharedFile: TemporarySharedFile?
    let loadMyQuotesUseCase: LoadMyQuotesUseCase
    let downloadQuotePdfUseCase: DownloadQuotePdfUseCase
    let deleteQuoteUseCase: DeleteQuoteUseCase
    var loadRequestID = 0
    var shareRequestID = 0

    init(useCases: QuotesUseCases) {
        self.loadMyQuotesUseCase = useCases.loadMyQuotes
        self.downloadQuotePdfUseCase = useCases.downloadQuotePdf
        self.deleteQuoteUseCase = useCases.deleteQuote
    }

    func shareQuotePdf(quote: QuoteSummary) async {
        shareRequestID += 1
        let requestID = shareRequestID
        error = nil

        do {
            let data = try await downloadQuotePdfUseCase.execute(id: quote.id)
            guard requestID == shareRequestID else { return }
            sharedFile = try TemporarySharedFileFactory.create(
                data: data,
                fileName: "\(quote.number ?? "devis-\(quote.id)").pdf"
            )
        } catch {
            guard requestID == shareRequestID else { return }
            self.error = error.localizedDescription
        }
    }
}
