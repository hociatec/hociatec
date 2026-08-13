import SwiftUI

struct ProductReviewsHeader: View {
    let reviewsAverage: Double?

    var body: some View {
        if let avg = reviewsAverage {
            HStack(spacing: 6) {
                ProductRatingStarsView(average: avg)
                Text(String(format: "%.1f/5", avg))
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
            }
        }
    }
}
