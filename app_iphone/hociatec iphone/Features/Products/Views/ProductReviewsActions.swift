import SwiftUI

struct ProductReviewsActions: View {
    let reviewsTotal: Int
    let reviewsDestination: AnyView
    let canLoadMore: Bool
    let isLoadingReviews: Bool
    let loadMoreAction: () -> Void

    var body: some View {
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
