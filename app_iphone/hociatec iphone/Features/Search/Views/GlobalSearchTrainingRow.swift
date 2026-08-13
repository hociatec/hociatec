import SwiftUI

struct GlobalSearchTrainingRow: View {
    let training: Training

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(training.title)
                .fontWeight(.semibold)
            Text(training.shortDescription ?? training.objective ?? "")
                .font(.footnote)
                .foregroundStyle(.secondary)
                .lineLimit(2)
            Text(PriceFormatter.format(cents: training.priceCents))
                .font(.footnote.weight(.semibold))
        }
        .padding(.vertical, 4)
    }
}
