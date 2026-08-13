import Foundation

@MainActor
final class ProductsViewModel: ObservableObject {
    @Published var products: [Product] = []
    @Published var categories: [CategorySummary] = []
    @Published var selectedCategory: CategorySummary?
    @Published var selectedSellingType: SellingType? = nil
    @Published var sort: SortOption = .relevance
    @Published var search = ""
    @Published var isLoading = false
    @Published var isLoadingCategories = false
    @Published var error: String?

    private let service: ProductServing

    init(service: ProductServing, initialSellingType: SellingType? = nil) {
        self.service = service
        self.selectedSellingType = initialSellingType
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil

        do {
            let items = try await service.products(
                search: search.isEmpty ? nil : search,
                categorySlug: selectedCategory?.slug,
                sellingType: selectedSellingType
            )
            products = applySorting(on: items)
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
            categories = try await service.categories()
            if let current = selectedCategory {
                selectedCategory = categories.first(where: { $0.id == current.id || $0.slug == current.slug })
            }
        } catch let err {
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
