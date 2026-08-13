import SwiftUI

struct ClientTrainingsView: View {
    @StateObject private var viewModel: ClientTrainingsViewModel

    init(service: TrainingServing) {
        _viewModel = StateObject(wrappedValue: ClientTrainingsViewModel(service: service))
    }

    var body: some View {
        List {
            if let error = viewModel.error, !error.isEmpty {
                Section { Text(error).foregroundStyle(.red) }
            }

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
                        VStack(alignment: .leading, spacing: 6) {
                            Text(enrollment.session.training.title)
                                .font(.headline)
                            Text("\(enrollment.statusLabel) · \(DateFormatters.frDateTime.string(from: enrollment.scheduledStartsAt))")
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                            Text(PriceFormatter.format(cents: enrollment.priceCents))
                                .font(.footnote.weight(.semibold))
                        }
                        .padding(.vertical, 4)
                    }
                }
            }
        }
        .navigationTitle("Mes formations")
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
    }
}
