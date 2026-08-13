import Foundation

struct ProductsUseCases {
    let loadProducts: LoadProductsUseCase
    let loadCategories: LoadProductCategoriesUseCase
    let loadProductDetail: LoadProductDetailUseCase
    let loadProductReviews: LoadProductReviewsUseCase
    let loadFavoriteStatus: LoadProductFavoriteStatusUseCase
    let toggleFavorite: ToggleProductFavoriteUseCase
}

struct LoadProductsUseCase {
    let repository: ProductsRepository

    func execute(search: String?, categorySlug: String?, sellingType: SellingType?) async throws -> [Product] {
        try await repository.fetchProducts(search: search, categorySlug: categorySlug, sellingType: sellingType)
    }
}

struct LoadProductCategoriesUseCase {
    let repository: ProductsRepository

    func execute() async throws -> [CategorySummary] {
        try await repository.fetchCategories()
    }
}

struct LoadProductDetailUseCase {
    let repository: ProductsRepository

    func execute(slug: String) async throws -> Product {
        try await repository.fetchProduct(slug: slug)
    }
}

struct LoadProductReviewsUseCase {
    let repository: ProductsRepository

    func execute(slug: String, page: Int, perPage: Int) async throws -> ReviewListData {
        try await repository.fetchReviews(slug: slug, page: page, perPage: perPage)
    }
}

struct LoadProductFavoriteStatusUseCase {
    let repository: ProductsRepository

    func execute(productId: Int) async throws -> Bool {
        let favorites = try await repository.fetchFavorites()
        return favorites.contains { $0.product.id == productId }
    }
}

struct ToggleProductFavoriteUseCase {
    let repository: ProductsRepository

    func execute(productId: Int, isFavorite: Bool) async throws -> Bool {
        if isFavorite {
            try await repository.removeFavorite(productId: productId)
            return false
        } else {
            try await repository.addFavorite(productId: productId)
            return true
        }
    }
}
