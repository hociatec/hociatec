import SwiftUI

struct TrainingDetailView: View {
    @StateObject private var viewModel: TrainingDetailViewModel

    init(api: TrainingServing, slug: String) {
        _viewModel = StateObject(wrappedValue: TrainingDetailViewModel(service: api, slug: slug))
    }

    var body: some View {
        List {
            if viewModel.isLoading && viewModel.training == nil {
                Section {
                    ProgressView("Chargement de la formation...")
                }
            } else if let error = viewModel.error, viewModel.training == nil {
                Section {
                    Text(error).foregroundStyle(.red)
                }
            } else if let training = viewModel.training {
                Section {
                    VStack(alignment: .leading, spacing: 10) {
                        Text(training.title)
                            .font(.title3.weight(.semibold))
                        Text(training.objective ?? training.shortDescription ?? "Formation accompagnée avec feuille de route pratique.")
                            .foregroundStyle(.secondary)
                        LabeledContent("Catégorie", value: training.categoryDetails?.name ?? training.category)
                        LabeledContent("Modalité", value: nonEmptyText(training.availableFormatDetails.map(\.label).joined(separator: ", ")) ?? "À confirmer")
                        LabeledContent("Durée", value: trainingDurationLabel(training.durationMinutes))
                        LabeledContent("Tarif", value: PriceFormatter.format(cents: training.priceCents))
                        if let audience = nonEmptyText(training.audience) {
                            LabeledContent("Public concerné", value: audience)
                        }
                    }
                    .padding(.vertical, 4)
                }

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

                Section("Sessions") {
                    if viewModel.sessions.isEmpty {
                        Text("Aucune session ouverte pour le moment.")
                            .foregroundStyle(.secondary)
                    } else {
                        ForEach(viewModel.sessions) { session in
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
                                    Link(destination: URL(string: meetingURL) ?? URL(string: "https://hociatec.fr/formations/\(training.slug)")!) {
                                        Label("Lien de session", systemImage: "link")
                                    }
                                }
                            }
                            .padding(.vertical, 4)
                        }
                    }
                }
            }
        }
        .navigationTitle("Formation")
        .task { await viewModel.load() }
    }
}
