import SwiftUI

struct TrainingsCatalogFiltersSection: View {
    @ObservedObject var viewModel: TrainingsCatalogViewModel

    var body: some View {
        Section {
            TextField("Rechercher une formation", text: $viewModel.searchText)
            if !viewModel.categories.isEmpty {
                Picker("Catégorie", selection: $viewModel.selectedCategorySlug) {
                    Text("Toutes").tag("")
                    ForEach(viewModel.categories) { category in
                        Text(category.name).tag(category.slug)
                    }
                }
            }
            Button("Rechercher") {
                viewModel.applySearch()
                Task { await viewModel.load() }
            }
        }
    }
}

struct TrainingsCatalogResultsSection: View {
    @ObservedObject var viewModel: TrainingsCatalogViewModel
    let service: TrainingServing

    var body: some View {
        Section("Formations") {
            if viewModel.isLoading && viewModel.trainings.isEmpty {
                ProgressView("Chargement des formations...")
            } else if viewModel.trainings.isEmpty {
                Text("Aucune formation publiée pour le moment.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(viewModel.trainings) { training in
                    NavigationLink {
                        TrainingDetailView(api: service, slug: training.slug)
                    } label: {
                        TrainingCatalogRow(training: training)
                    }
                }
            }
        }
    }
}

struct TrainingsCatalogPaginationSection: View {
    @ObservedObject var viewModel: TrainingsCatalogViewModel

    var body: some View {
        if viewModel.totalPages > 1 {
            Section {
                HStack {
                    Button("Précédent") {
                        viewModel.previousPage()
                        Task { await viewModel.load() }
                    }
                    .disabled(viewModel.page <= 1 || viewModel.isLoading)
                    Spacer()
                    Text("\(viewModel.page)/\(viewModel.totalPages)")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                    Spacer()
                    Button("Suivant") {
                        viewModel.nextPage()
                        Task { await viewModel.load() }
                    }
                    .disabled(viewModel.page >= viewModel.totalPages || viewModel.isLoading)
                }
            }
        }
    }
}

private struct TrainingCatalogRow: View {
    let training: Training

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(training.title)
                .fontWeight(.semibold)
            Text(
                training.objective
                ?? training.shortDescription
                ?? "Formation accompagnée avec feuille de route pratique."
            )
            .lineLimit(3)
            .foregroundStyle(.secondary)
            HStack {
                Text(training.categoryDetails?.name ?? training.category)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
                Spacer()
                Text(PriceFormatter.format(cents: training.priceCents))
                    .font(.footnote)
                    .fontWeight(.semibold)
            }
        }
        .padding(.vertical, 4)
    }
}
