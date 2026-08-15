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
    @Published var minPrice: Double? = nil
    @Published var maxPrice: Double? = nil
    @Published var inStockOnly = false
    @Published var availableFacets: ProductSearchFacets = .empty
    @Published var sort: ProductSortOption = .releaseYearDesc
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
                minPrice: minPrice,
                maxPrice: maxPrice,
                inStock: inStockOnly ? true : nil,
                sort: effectiveSort,
                page: page,
                perPage: Self.pageSize
            )
            guard requestID == loadRequestID else { return }
            products = result.items
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

    func updateSort(_ newSort: ProductSortOption) {
        sort = newSort
        page = 1
    }

    func applySearch() {
        if !search.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty, sort == .releaseYearDesc {
            sort = .relevance
        }
        page = 1
    }

    func clearBrandFilter() {
        selectedBrand = nil
    }

    func clearAttributeFilter(code: String) {
        selectedAttributeFilters.removeValue(forKey: code)
    }

    func clearPriceRange() {
        minPrice = nil
        maxPrice = nil
    }

    func clearInStockFilter() {
        inStockOnly = false
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
    case priceAsc = "price_asc"
    case priceDesc = "price_desc"
    case releaseYearDesc = "release_year_desc"
    case releaseYearAsc = "release_year_asc"
    case nameAsc = "name_asc"
    case stockDesc = "stock_desc"
    case stockAsc = "stock_asc"
    case nameDesc = "name_desc"
    case createdDesc = "created_desc"

    var id: String { rawValue }
}

private extension ProductsViewModel {
    var effectiveSort: ProductSortOption {
        let hasSearch = !search.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
        return hasSearch && sort == .releaseYearDesc ? .relevance : sort
    }
}
