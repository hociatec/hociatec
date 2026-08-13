import SwiftUI

struct TrainingEnrollmentDetailView: View {
    let enrollment: TrainingEnrollment

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 8) {
                    Text(enrollment.session.training.title)
                        .font(.title3.weight(.semibold))

                    if let shortDescription = nonEmptyText(enrollment.session.training.shortDescription) {
                        Text(shortDescription)
                            .foregroundStyle(.secondary)
                    }

                    Text(enrollment.statusLabel)
                        .font(.footnote.weight(.semibold))
                        .foregroundStyle(.secondary)
                }
                .padding(.vertical, 4)
            }

            Section("Feuille de route") {
                if enrollment.session.training.roadmap.isEmpty {
                    Text("Feuille de route à venir.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(enrollment.session.training.roadmap) { step in
                        Text(step.title)
                    }
                }
            }

            Section("Session") {
                LabeledContent(
                    "Créneau réservé",
                    value: "\(trainingDateTimeFormatter.string(from: enrollment.scheduledStartsAt)) - \(trainingTimeOnlyFormatter.string(from: enrollment.scheduledEndsAt))"
                )
                LabeledContent("Format", value: enrollment.session.formatLabel)

                if enrollment.session.format == "remote" {
                    if let meetingURL = nonEmptyText(enrollment.session.meetingUrl),
                       let url = URL(string: meetingURL) {
                        Link(destination: url) {
                            Label("Lien de session", systemImage: "link")
                        }
                    } else {
                        LabeledContent("Lien", value: "Lien transmis après confirmation")
                    }
                } else {
                    LabeledContent("Lieu", value: nonEmptyText(enrollment.session.location) ?? "Lieu à confirmer")
                }
            }

            Section("Inscription") {
                LabeledContent("Prix", value: PriceFormatter.format(cents: enrollment.priceCents))
                LabeledContent("Statut", value: enrollment.statusLabel)
                LabeledContent("Réservée le", value: trainingDateTimeFormatter.string(from: enrollment.createdAt))
            }
        }
        .navigationTitle(enrollment.session.training.title)
        .navigationBarTitleDisplayMode(.inline)
    }
}

private let trainingTimeOnlyFormatter: DateFormatter = {
    let formatter = DateFormatter()
    formatter.locale = Locale(identifier: "fr_FR")
    formatter.dateStyle = .none
    formatter.timeStyle = .short
    return formatter
}()
