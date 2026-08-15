import Foundation
import Combine

@MainActor
final class ProductDetailViewModel: ObservableObject {
    @Published var product: Product
    @Published var rentalMonths: Int = 1
    @Published var isLoadingDetail = false
    @Published var detailError: String?
    @Published var reviews: [Review] = []
    @Published var reviewsPerPage: Int = 3
    @Published var reviewsTotal: Int = 0
    @Published var reviewsAverage: Double?
    @Published var isLoadingReviews = false
    @Published var reviewsError: String?
    @Published var isFavorite = false
    @Published var favoriteFeedback: FeedbackDialogState?
    var hasLoadedInitialDataOnce = false
    var hasLoadedFavoriteOnce = false
    var hasLoadedFirstReviewsPageOnce = false

    let loadDetailUseCase: LoadProductDetailUseCase
    let loadReviewsUseCase: LoadProductReviewsUseCase
    let loadFavoriteStatusUseCase: LoadProductFavoriteStatusUseCase
    let toggleFavoriteUseCase: ToggleProductFavoriteUseCase

    init(product: Product, useCases: ProductsUseCases) {
        self.product = product
        self.loadDetailUseCase = useCases.loadProductDetail
        self.loadReviewsUseCase = useCases.loadProductReviews
        self.loadFavoriteStatusUseCase = useCases.loadFavoriteStatus
        self.toggleFavoriteUseCase = useCases.toggleFavorite
    }

    var hasMoreReviews: Bool {
        reviews.count < reviewsTotal
    }

    var nextReviewsPage: Int {
        max(2, (reviews.count / max(1, reviewsPerPage)) + 1)
    }
}
