import SwiftUI

struct ProductReviewSummaryView: View {
    let average: Double
    let total: Int

    var body: some View {
        HStack(spacing: 4) {
            Image(systemName: "star.fill")
                .foregroundStyle(.yellow)
            Text(String(format: "%.1f", average))
                .font(.footnote)
                .fontWeight(.semibold)
            Text("(\(total))")
                .font(.footnote)
                .foregroundStyle(.secondary)
        }
    }
}

struct ProductHighlightCard: View {
    let title: String
    let value: String
    let detail: String

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.caption)
                .foregroundStyle(.secondary)
            Text(value)
                .font(.headline)
                .fontWeight(.semibold)
            Text(detail)
                .font(.footnote)
                .foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(14)
        .background(Color(.secondarySystemBackground))
        .clipShape(RoundedRectangle(cornerRadius: 14))
    }
}

struct ProductRatingStarsView: View {
    let average: Double

    var body: some View {
        HStack(spacing: 2) {
            ForEach(0..<5, id: \.self) { idx in
                let threshold = Double(idx + 1)
                Image(systemName: average >= threshold ? "star.fill" : (average > Double(idx) ? "star.leadinghalf.filled" : "star"))
                    .foregroundStyle(.yellow)
                    .accessibilityHidden(true)
            }
        }
        .accessibilityLabel(String(format: "Note moyenne %.1f sur 5", average))
    }
}
