import Foundation

struct ProductService: ProductServing {
    let api: APIClient

    func assetURL(for path: String?) -> URL? { api.assetURL(for: path) }
    func featuredProducts() async throws -> [Product] { try await api.featuredProducts() }
    func products(search: String?, categorySlug: String?, sellingType: SellingType?) async throws -> [Product] {
        try await api.products(search: search, categorySlug: categorySlug, sellingType: sellingType)
    }
    func categories() async throws -> [CategorySummary] { try await api.categories() }
    func product(slug: String) async throws -> Product { try await api.product(slug: slug) }
    func productReviews(slug: String, page: Int, perPage: Int) async throws -> ReviewListData {
        try await api.productReviews(slug: slug, page: page, perPage: perPage)
    }
}
