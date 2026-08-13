import SwiftUI

struct TrainingDetailHeroSection: View {
    let training: Training

    var body: some View {
        Section {
            VStack(alignment: .leading, spacing: 10) {
                Text(training.title)
                    .font(.title3.weight(.semibold))
                Text(
                    training.objective
                    ?? training.shortDescription
                    ?? "Formation accompagnée avec feuille de route pratique."
                )
                .foregroundStyle(.secondary)
                LabeledContent("Catégorie", value: training.categoryDetails?.name ?? training.category)
                LabeledContent(
                    "Modalité",
                    value: nonEmptyText(training.availableFormatDetails.map(\.label).joined(separator: ", "))
                        ?? "À confirmer"
                )
                LabeledContent("Durée", value: trainingDurationLabel(training.durationMinutes))
                LabeledContent("Tarif", value: PriceFormatter.format(cents: training.priceCents))
                if let audience = nonEmptyText(training.audience) {
                    LabeledContent("Public concerné", value: audience)
                }
            }
            .padding(.vertical, 4)
        }
    }
}
