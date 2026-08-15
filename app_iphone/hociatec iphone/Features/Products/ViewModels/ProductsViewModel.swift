import Foundation
import Combine

@MainActor
final class ProductsViewModel: ObservableObject {
    static let pageSize = 12

    @Published var products: [Product] = []
    @Published var categories: [CategorySummary] = []
    @Published var selectedCategory: CategorySummary?
    @Published var selectedSellingType: SellingType? = nil
    @Published var selectedBrand: String? = nil
    @Published var selectedAttributeFilters: [String: String] = [:]
    @Published var availableFacets: ProductSearchFacets = .empty
    @Published var sort: ProductSortOption = .relevance
    @Published var search = ""
    @Published var page = 1
    @Published var totalPages = 1
    @Published var totalResults = 0
    @Published var isLoading = false
    @Published var error: String?

    private let loadProductListUseCase: LoadProductListUseCase
    private let loadCategoriesUseCase: LoadProductCategoriesUseCase
    private var isLoadingCategories = false
    private var loadRequestID = 0
    private var categoriesRequestID = 0
    private var hasLoadedOnce = false

    init(useCases: ProductsUseCases, initialSellingType: SellingType? = nil) {
        self.loadProductListUseCase = useCases.loadProductList
        self.loadCategoriesUseCase = useCases.loadCategories
        self.selectedSellingType = initialSellingType
    }

    func load(force: Bool = false) async {
        if (isLoading || hasLoadedOnce) && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil

        do {
            let result = try await loadProductListUseCase.execute(
                search: search.isEmpty ? nil : search,
                categorySlug: selectedCategory?.slug,
                sellingType: selectedSellingType,
                brand: selectedBrand,
                attributeFilters: selectedAttributeFilters,
                page: page,
                perPage: Self.pageSize
            )
            guard requestID == loadRequestID else { return }
            products = applySorting(on: result.items)
            totalResults = result.meta.total
            totalPages = max(1, result.meta.totalPages)
            availableFacets = result.facets ?? .empty
            hasLoadedOnce = true
        } catch let err {
            guard requestID == loadRequestID else { return }
            self.error = err.localizedDescription
        }

        if requestID == loadRequestID {
            isLoading = false
        }
    }

    func loadCategoriesIfNeeded() async {
        if isLoadingCategories || !categories.isEmpty { return }
        categoriesRequestID += 1
        let requestID = categoriesRequestID
        isLoadingCategories = true
        do {
            let loadedCategories = try await loadCategoriesUseCase.execute()
            guard requestID == categoriesRequestID else { return }
            categories = loadedCategories
            if let current = selectedCategory {
                selectedCategory = categories.first(where: { $0.id == current.id || $0.slug == current.slug })
            }
        } catch let err {
            guard requestID == categoriesRequestID else { return }
            if error == nil {
                error = err.localizedDescription
            }
        }
        if requestID == categoriesRequestID {
            isLoadingCategories = false
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

    func updateSort(_ newSort: ProductSortOption) {
        sort = newSort
        products = applySorting(on: products)
    }

    func applySearch() {
        page = 1
    }

    func clearBrandFilter() {
        selectedBrand = nil
    }

    func clearAttributeFilter(code: String) {
        selectedAttributeFilters.removeValue(forKey: code)
    }

    func previousPage() {
        guard page > 1 else { return }
        page -= 1
    }

    func nextPage() {
        guard page < totalPages else { return }
        page += 1
    }
}

enum ProductSortOption: String, CaseIterable, Identifiable {
    case relevance
    case priceLowHigh
    case priceHighLow
    case newest

    var id: String { rawValue }
}
