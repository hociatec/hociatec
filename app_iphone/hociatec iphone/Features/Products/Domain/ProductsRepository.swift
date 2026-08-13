import Foundation

protocol ProductsRepository {
    func fetchProductList(
        search: String?,
        categorySlug: String?,
        sellingType: SellingType?,
        page: Int,
        perPage: Int
    ) async throws -> ProductListData
    func fetchProducts(search: String?, categorySlug: String?, sellingType: SellingType?) async throws -> [Product]
    func fetchCategories() async throws -> [CategorySummary]
    func fetchProduct(slug: String) async throws -> Product
    func fetchReviews(slug: String, page: Int, perPage: Int) async throws -> ReviewListData
    func fetchFavorites() async throws -> [FavoriteEntry]
    func addFavorite(productId: Int) async throws
    func removeFavorite(productId: Int) async throws
}
