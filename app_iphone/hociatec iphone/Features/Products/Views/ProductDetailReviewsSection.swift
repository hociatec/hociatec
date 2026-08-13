import SwiftUI

struct ProductReviewsPreviewSection: View {
    let reviewsAverage: Double?
    let reviewsTotal: Int
    let reviews: [Review]
    let isLoadingReviews: Bool
    let reviewsError: String?
    let isLoggedIn: Bool
    let canLoadMore: Bool
    let loadMoreAction: () -> Void
    let reviewsDestination: AnyView

    var body: some View {
        Section("Avis") {
            ProductReviewsHeader(reviewsAverage: reviewsAverage)

            if let reviewsError {
                Text(reviewsError)
                    .foregroundStyle(.red)
            } else if isLoadingReviews {
                ProgressView("Chargement des avis…")
            } else if reviews.isEmpty {
                Text(emptyMessage)
                    .foregroundStyle(.secondary)
            } else {
                ForEach(reviews.prefix(3), id: \.id) { review in
                    ProductReviewRow(review: review)
                }

                ProductReviewsActions(
                    reviewsTotal: reviewsTotal,
                    reviewsDestination: reviewsDestination,
                    canLoadMore: canLoadMore,
                    isLoadingReviews: isLoadingReviews,
                    loadMoreAction: loadMoreAction
                )
            }
        }
    }

    private var emptyMessage: String {
        if reviewsTotal == 0 { return "Aucun avis pour l’instant." }
        if isLoggedIn { return "Aucun commentaire publié pour le moment." }
        return "Connectez-vous pour voir les avis."
    }
}

struct ProductReviewRow: View {
    let review: Review

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            HStack {
                ProductRatingStarsView(average: Double(review.score))
                Spacer()
                Text(DateFormatters.frDay.string(from: review.createdAt))
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            if let comment = review.comment, !comment.isEmpty {
                Text(comment)
            } else {
                Text("Sans commentaire.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            Text(review.author.displayName)
                .font(.footnote)
                .foregroundStyle(.secondary)
        }
        .padding(.vertical, 4)
    }
}
