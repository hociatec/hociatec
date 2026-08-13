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
            if let avg = reviewsAverage {
                HStack(spacing: 6) {
                    ProductRatingStarsView(average: avg)
                    Text(String(format: "%.1f/5", avg))
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                }
            }

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

                NavigationLink {
                    reviewsDestination
                } label: {
                    HStack(spacing: 8) {
                        Text("Voir tous les avis")
                            .fontWeight(.semibold)
                        if reviewsTotal > 0 {
                            Text("(\(reviewsTotal))")
                                .foregroundStyle(.secondary)
                        }
                        Spacer()
                        Image(systemName: "chevron.right")
                            .foregroundStyle(.tertiary)
                    }
                    .font(.subheadline)
                    .padding(.horizontal, 12)
                    .padding(.vertical, 8)
                    .background(Color.blue.opacity(0.12))
                    .foregroundStyle(.blue)
                    .clipShape(Capsule())
                }
                .buttonStyle(.plain)
                .accessibilityLabel(reviewsTotal > 0 ? "Voir tous les avis (\(reviewsTotal))" : "Voir tous les avis")

                if canLoadMore {
                    Button(action: loadMoreAction) {
                        if isLoadingReviews {
                            ProgressView()
                                .frame(maxWidth: .infinity)
                        } else {
                            Text("Charger plus d’avis")
                                .fontWeight(.semibold)
                                .frame(maxWidth: .infinity)
                        }
                    }
                    .disabled(isLoadingReviews)
                }
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
