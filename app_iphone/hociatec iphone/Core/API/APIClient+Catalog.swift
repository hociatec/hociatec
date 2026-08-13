import Foundation

extension APIClient {
    func featuredProducts() async throws -> [Product] {
        let data: ProductListData = try await request(
            path: "api/public/catalog/products",
            query: [URLQueryItem(name: "homepage", value: "1")]
        )
        return data.items
    }

    func productList(
        search: String? = nil,
        categorySlug: String? = nil,
        sellingType: SellingType? = nil,
        page: Int? = nil,
        perPage: Int? = nil
    ) async throws -> ProductListData {
        var query: [URLQueryItem] = []
        if let search, !search.isEmpty {
            query.append(.init(name: "q", value: search))
        }
        if let categorySlug, !categorySlug.isEmpty {
            query.append(.init(name: "category", value: categorySlug))
        }
        if let sellingType {
            query.append(.init(name: "sellingType", value: sellingType.rawValue))
        }
        if let page {
            query.append(.init(name: "page", value: String(page)))
        }
        if let perPage {
            query.append(.init(name: "perPage", value: String(perPage)))
        }

        return try await request(
            path: "api/public/catalog/products",
            query: query.isEmpty ? nil : query
        )
    }

    func products(search: String? = nil, categorySlug: String? = nil, sellingType: SellingType? = nil) async throws -> [Product] {
        try await productList(
            search: search,
            categorySlug: categorySlug,
            sellingType: sellingType
        ).items
    }

    func product(slug: String) async throws -> Product {
        try await request(path: "api/public/catalog/products/\(slug)")
    }

    func productReviews(slug: String, page: Int = 1, perPage: Int = 10) async throws -> ReviewListData {
        let data: ReviewListData = try await request(
            path: "api/public/catalog/products/\(slug)/reviews",
            query: [
                URLQueryItem(name: "page", value: String(page)),
                URLQueryItem(name: "perPage", value: String(perPage))
            ],
            authorized: true,
            attachCartToken: false
        )
        if data.meta.total > 0, data.items.isEmpty, await refreshAuthTokenIfPossible() {
            let retried: ReviewListData = try await request(
                path: "api/public/catalog/products/\(slug)/reviews",
                query: [
                    URLQueryItem(name: "page", value: String(page)),
                    URLQueryItem(name: "perPage", value: String(perPage))
                ],
                authorized: true,
                attachCartToken: false
            )
            return retried
        }
        return data
    }

    func categories() async throws -> [CategorySummary] {
        let data: CategoryListData = try await request(
            path: "api/public/catalog/categories"
        )
        return data.items
    }
}
