import Foundation

struct ProductsRepositoryLive: ProductsRepository {
    let productsService: ProductServing
    let favoritesService: FavoritesServing

    func fetchProductList(
        search: String?,
        categorySlug: String?,
        sellingType: SellingType?,
        page: Int,
        perPage: Int
    ) async throws -> ProductListData {
        try await productsService.productList(
            search: search,
            categorySlug: categorySlug,
            sellingType: sellingType,
            page: page,
            perPage: perPage
        )
    }

    func fetchProducts(search: String?, categorySlug: String?, sellingType: SellingType?) async throws -> [Product] {
        try await productsService.products(search: search, categorySlug: categorySlug, sellingType: sellingType)
    }

    func fetchCategories() async throws -> [CategorySummary] {
        try await productsService.categories()
    }

    func fetchProduct(slug: String) async throws -> Product {
        try await productsService.product(slug: slug)
    }

    func fetchReviews(slug: String, page: Int, perPage: Int) async throws -> ReviewListData {
        try await productsService.productReviews(slug: slug, page: page, perPage: perPage)
    }

    func fetchFavoriteStatus(productId: Int) async throws -> Bool {
        try await favoritesService.favoriteStatus(category: .product, targetId: productId).isFavorite
    }

    func addFavorite(productId: Int) async throws {
        _ = try await favoritesService.addFavorite(category: .product, targetId: productId)
    }

    func removeFavorite(productId: Int) async throws {
        _ = try await favoritesService.removeFavorite(category: .product, targetId: productId)
    }
}
