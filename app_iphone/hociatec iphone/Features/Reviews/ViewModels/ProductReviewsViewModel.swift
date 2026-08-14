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
    private var loadRequestID = 0

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
        isLoggedIn: Bool,
        force: Bool = false
    ) async {
        if isLoading && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil
        let requestedPage = page

        do {
            let data = try await productService.productReviews(slug: productSlug, page: requestedPage, perPage: perPage)
            guard requestID == loadRequestID else { return }
            self.page = data.meta.page
            self.perPage = data.meta.perPage
            self.total = data.meta.total
            self.average = data.meta.average
            if replace {
                reviews = data.items
            } else {
                reviews.append(contentsOf: data.items)
            }
            await loadMyReviewIfNeeded(orderService: orderService, isLoggedIn: isLoggedIn, requestID: requestID)
        } catch {
            guard requestID == loadRequestID else { return }
            self.error = error.localizedDescription
            if replace {
                reviews = []
                total = 0
                average = nil
            }
            await loadMyReviewIfNeeded(orderService: orderService, isLoggedIn: isLoggedIn, requestID: requestID)
        }

        if requestID == loadRequestID {
            isLoading = false
        }
    }

    private func loadMyReviewIfNeeded(orderService: OrderServing, isLoggedIn: Bool, requestID: Int) async {
        guard isLoggedIn else {
            guard requestID == loadRequestID else { return }
            myReview = nil
            return
        }
        if !reviews.isEmpty { return }

        do {
            let orders = try await orderService.myOrders()
            guard requestID == loadRequestID else { return }
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
            guard requestID == loadRequestID else { return }
            myReview = nil
        }
    }
}
