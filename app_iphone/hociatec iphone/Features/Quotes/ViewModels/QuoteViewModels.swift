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

    func prefillFromAccount() {
        prefill()
    }

    func loadServices() async {
        if isLoadingServices { return }
        isLoadingServices = true
        error = nil
        do {
            services = try await loadServicesUseCase.execute(query: nil)
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
            productResults = try await searchProductsUseCase.execute(query: query)
        } catch let err {
            error = err.localizedDescription
        }
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
            _ = try await submitQuoteUseCase.execute(
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

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil
        do {
            quotes = try await loadMyQuotesUseCase.execute()
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }

    func delete(id: Int) async {
        isLoading = true
        error = nil
        do {
            try await deleteQuoteUseCase.execute(id: id)
            quotes.removeAll { $0.id == id }
        } catch let err {
            self.error = err.localizedDescription
        }
        isLoading = false
    }
}
