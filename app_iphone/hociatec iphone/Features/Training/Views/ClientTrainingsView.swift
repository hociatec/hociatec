import SwiftUI

struct ClientTrainingsView: View {
    @StateObject private var viewModel: ClientTrainingsViewModel

    init(service: TrainingServing) {
        _viewModel = StateObject(wrappedValue: ClientTrainingsViewModel(service: service))
    }

    var body: some View {
        List {
            if viewModel.isLoading && viewModel.items.isEmpty {
                Section { ProgressView("Chargement...") }
            } else if viewModel.items.isEmpty {
                Section {
                    Text("Aucune formation réservée.")
                        .foregroundStyle(.secondary)
                }
            } else {
                Section {
                    ForEach(viewModel.items) { enrollment in
                        VStack(alignment: .leading, spacing: 8) {
                            Text(enrollment.session.training.title)
                                .font(.headline)
                            Text("\(enrollment.statusLabel) · \(DateFormatters.frDateTime.string(from: enrollment.scheduledStartsAt))")
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                            Text(PriceFormatter.format(cents: enrollment.priceCents))
                                .font(.footnote.weight(.semibold))
                            NavigationLink {
                                TrainingEnrollmentDetailView(enrollment: enrollment)
                            } label: {
                                Label("Voir la formation", systemImage: "arrow.right.circle")
                                    .font(.footnote.weight(.semibold))
                            }
                            .buttonStyle(.borderless)
                        }
                        .padding(.vertical, 4)
                    }
                }
            }
        }
        .navigationTitle("Mes formations")
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
        .feedbackDialog(error: $viewModel.error)
    }
}
