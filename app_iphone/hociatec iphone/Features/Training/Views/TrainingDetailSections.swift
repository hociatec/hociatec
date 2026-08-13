import SwiftUI

struct TrainingDetailLoadingSection: View {
    var body: some View {
        Section {
            ProgressView("Chargement de la formation...")
        }
    }
}

struct TrainingDetailErrorSection: View {
    let error: String

    var body: some View {
        Section {
            Text(error)
                .foregroundStyle(.red)
        }
    }
}

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

struct TrainingRoadmapSection: View {
    let training: Training

    var body: some View {
        Section("Feuille de route") {
            if training.roadmap.isEmpty {
                Text("Le programme détaillé sera communiqué avec les informations de session.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(training.roadmap.sorted { $0.position < $1.position }) { item in
                    VStack(alignment: .leading, spacing: 4) {
                        Text("\(item.position). \(item.title)")
                            .fontWeight(.semibold)
                    }
                    .padding(.vertical, 2)
                }
            }
        }
    }
}

struct TrainingSessionsSection: View {
    let training: Training
    let sessions: [TrainingSession]

    var body: some View {
        Section("Sessions") {
            if sessions.isEmpty {
                Text("Aucune session ouverte pour le moment.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(sessions) { session in
                    TrainingSessionRow(training: training, session: session)
                }
            }
        }
    }
}

private struct TrainingSessionRow: View {
    let training: Training
    let session: TrainingSession

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack {
                Text(session.formatLabel)
                    .fontWeight(.semibold)
                Spacer()
                Text(session.statusLabel)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            LabeledContent("Début", value: trainingDateTimeFormatter.string(from: session.startsAt))
            LabeledContent("Fin", value: trainingDateTimeFormatter.string(from: session.endsAt))
            LabeledContent("Places restantes", value: "\(max(0, session.remainingSeats))/\(session.capacity)")
            if let location = nonEmptyText(session.location) {
                LabeledContent("Lieu", value: location)
            }
            if let meetingURL = nonEmptyText(session.meetingUrl) {
                Link(
                    destination: URL(string: meetingURL)
                        ?? URL(string: "https://hociatec.fr/formations/\(training.slug)")!
                ) {
                    Label("Lien de session", systemImage: "link")
                }
            }
        }
        .padding(.vertical, 4)
    }
}
