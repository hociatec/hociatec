import SwiftUI

struct ProductReviewsSummarySection: View {
    let average: Double
    let total: Int

    var body: some View {
        Section {
            HStack(spacing: 10) {
                ProductReviewsRatingStars(average: average)
                Text(String(format: "%.1f/5", average))
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
                Spacer()
                Text("\(total) avis")
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
            }
        }
    }
}

struct ProductMyReviewSection: View {
    let review: Review

    var body: some View {
        Section("Votre avis") {
            ProductReviewCard(review: review, showsAuthor: false)
                .padding(.vertical, 4)
        }
    }
}

struct ProductReviewsListSection: View {
    let reviews: [Review]

    var body: some View {
        Section {
            ForEach(reviews, id: \.id) { review in
                ProductReviewCard(review: review, showsAuthor: true)
                    .padding(.vertical, 6)
            }
        }
    }
}

struct ProductReviewsLoadMoreSection: View {
    let isLoading: Bool
    let action: () -> Void

    var body: some View {
        Section {
            Button(action: action) {
                if isLoading {
                    ProgressView().frame(maxWidth: .infinity)
                } else {
                    Text("Charger plus")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
            }
            .disabled(isLoading)
        }
    }
}

struct ProductReviewsEmptySection: View {
    let message: String

    var body: some View {
        Section {
            Text(message)
                .foregroundStyle(.secondary)
        }
    }
}

struct ProductReviewCard: View {
    let review: Review
    let showsAuthor: Bool

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack {
                ProductReviewsRatingStars(average: Double(review.score))
                Spacer()
                Text(DateFormatters.frDay.string(from: review.createdAt))
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            if let comment = review.comment, !comment.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
                Text(comment)
            } else {
                Text("Sans commentaire.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            if showsAuthor {
                Text(review.author.displayName)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
        }
    }
}

struct ProductReviewsRatingStars: View {
    let average: Double

    var body: some View {
        HStack(spacing: 2) {
            ForEach(0..<5, id: \.self) { idx in
                let threshold = Double(idx + 1)
                Image(
                    systemName: average >= threshold
                        ? "star.fill"
                        : (average > Double(idx) ? "star.leadinghalf.filled" : "star")
                )
                .foregroundStyle(.yellow)
                .accessibilityHidden(true)
            }
        }
        .accessibilityLabel(String(format: "Note %.1f sur 5", average))
    }
}
