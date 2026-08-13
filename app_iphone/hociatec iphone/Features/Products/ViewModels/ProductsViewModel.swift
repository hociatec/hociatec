import Foundation
import Combine

@MainActor
final class ProductsViewModel: ObservableObject {
    static let pageSize = 12

    @Published var products: [Product] = []
    @Published var categories: [CategorySummary] = []
    @Published var selectedCategory: CategorySummary?
    @Published var selectedSellingType: SellingType? = nil
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

    init(useCases: ProductsUseCases, initialSellingType: SellingType? = nil) {
        self.loadProductListUseCase = useCases.loadProductList
        self.loadCategoriesUseCase = useCases.loadCategories
        self.selectedSellingType = initialSellingType
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil

        do {
            let result = try await loadProductListUseCase.execute(
                search: search.isEmpty ? nil : search,
                categorySlug: selectedCategory?.slug,
                sellingType: selectedSellingType,
                page: page,
                perPage: Self.pageSize
            )
            products = applySorting(on: result.items)
            totalResults = result.meta.total
            totalPages = max(1, result.meta.totalPages)
        } catch let err {
            self.error = err.localizedDescription
            if force {
                products = []
                totalResults = 0
                totalPages = 1
            }
        }

        isLoading = false
    }

    func loadCategoriesIfNeeded() async {
        if isLoadingCategories || !categories.isEmpty { return }
        isLoadingCategories = true
        defer { isLoadingCategories = false }
        do {
            categories = try await loadCategoriesUseCase.execute()
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

    func updateSort(_ newSort: ProductSortOption) {
        sort = newSort
        products = applySorting(on: products)
    }

    func applySearch() {
        page = 1
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
