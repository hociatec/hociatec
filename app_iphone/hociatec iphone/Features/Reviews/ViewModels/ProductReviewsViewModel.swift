import Foundation
import Combine

@MainActor
final class ProductReviewsViewModel: ObservableObject {
    @Published var reviews: [Review] = []
    @Published var myReview: Review?
    @Published var page: Int = 1
    @Published var perPage: Int = 20
    @Published var total: Int = 0
    @Published var average: Double?
    @Published var isLoading = false
    @Published var error: String?

    private let productSlug: String
    private let productSku: String

    init(productSlug: String, productSku: String) {
        self.productSlug = productSlug
        self.productSku = productSku
    }

    func loadMore(productService: ProductServing, orderService: OrderServing, isLoggedIn: Bool) async {
        guard !isLoading else { return }
        guard reviews.count < total else { return }
        await load(
            productService: productService,
            orderService: orderService,
            page: page + 1,
            replace: false,
            isLoggedIn: isLoggedIn
        )
    }

    func load(
        productService: ProductServing,
        orderService: OrderServing,
        page: Int,
        replace: Bool,
        isLoggedIn: Bool
    ) async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await productService.productReviews(slug: productSlug, page: page, perPage: perPage)
            self.page = data.meta.page
            self.perPage = data.meta.perPage
            self.total = data.meta.total
            self.average = data.meta.average
            if replace {
                reviews = data.items
            } else {
                reviews.append(contentsOf: data.items)
            }
            await loadMyReviewIfNeeded(orderService: orderService, isLoggedIn: isLoggedIn)
        } catch {
            self.error = error.localizedDescription
            if replace {
                reviews = []
                total = 0
                average = nil
            }
            await loadMyReviewIfNeeded(orderService: orderService, isLoggedIn: isLoggedIn)
        }
    }

    private func loadMyReviewIfNeeded(orderService: OrderServing, isLoggedIn: Bool) async {
        guard isLoggedIn else {
            myReview = nil
            return
        }
        if !reviews.isEmpty { return }

        do {
            let orders = try await orderService.myOrders()
            var candidates: [Review] = []
            for order in orders {
                for item in order.items where item.productSku == productSku {
                    if let review = item.review {
                        candidates.append(review)
                    }
                }
            }
            myReview = candidates.sorted(by: { $0.createdAt > $1.createdAt }).first
        } catch {
            myReview = nil
        }
    }
}
