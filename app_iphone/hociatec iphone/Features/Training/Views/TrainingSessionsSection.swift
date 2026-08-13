import SwiftUI

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
