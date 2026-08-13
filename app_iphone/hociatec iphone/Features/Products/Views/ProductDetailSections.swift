import SwiftUI

struct ProductDetailErrorBanner: View {
    let detailError: String?

    var body: some View {
        if let detailError, !detailError.isEmpty {
            Label(detailError, systemImage: "exclamationmark.triangle.fill")
                .foregroundStyle(.red)
                .font(.footnote)
        }
    }
}

struct ProductDetailDescriptionSection: View {
    let description: String

    var body: some View {
        Section("Description") {
            Text(description)
                .font(.body)
                .foregroundStyle(.primary)
        }
    }
}

struct ProductDetailReviewsSectionContainer: View {
    let container: AppContainer
    @ObservedObject var viewModel: ProductDetailViewModel

    var body: some View {
        ProductReviewsPreviewSection(
            reviewsAverage: viewModel.reviewsAverage,
            reviewsTotal: viewModel.reviewsTotal,
            reviews: viewModel.reviews,
            isLoadingReviews: viewModel.isLoadingReviews,
            reviewsError: viewModel.reviewsError,
            isLoggedIn: container.account.isLoggedIn,
            canLoadMore: viewModel.hasMoreReviews,
            loadMoreAction: {
                Task {
                    await viewModel.loadReviews(page: viewModel.nextReviewsPage)
                }
            },
            reviewsDestination: AnyView(
                ProductReviewsView(
                    service: container.services.products,
                    orderService: container.services.orders,
                    productName: viewModel.product.name,
                    productSlug: viewModel.product.slug,
                    productSku: viewModel.product.sku
                )
                .environmentObject(container)
            )
        )
    }
}

struct ProductDetailImagePlaceholder: View {
    var body: some View {
        ZStack {
            RoundedRectangle(cornerRadius: 12)
                .fill(.gray.opacity(0.08))
            Image(systemName: "photo")
                .foregroundStyle(.secondary)
        }
    }
}
