import SwiftUI

struct TrainingsCatalogView: View {
    let service: TrainingServing
    @StateObject private var viewModel: TrainingsCatalogViewModel

    init(api: TrainingServing) {
        self.service = api
        _viewModel = StateObject(wrappedValue: TrainingsCatalogViewModel(service: api))
    }

    var body: some View {
        List {
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

            Section("Formations") {
                if viewModel.isLoading && viewModel.trainings.isEmpty {
                    ProgressView("Chargement des formations...")
                } else if let error = viewModel.error {
                    Text(error).foregroundStyle(.red)
                } else if viewModel.trainings.isEmpty {
                    Text("Aucune formation publiée pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(viewModel.trainings) { training in
                        NavigationLink {
                            TrainingDetailView(api: service, slug: training.slug)
                        } label: {
                            VStack(alignment: .leading, spacing: 8) {
                                Text(training.title)
                                    .fontWeight(.semibold)
                                Text(training.objective ?? training.shortDescription ?? "Formation accompagnée avec feuille de route pratique.")
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
                }
            }

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
        .navigationTitle("Formations")
        .task {
            await viewModel.loadCategoriesIfNeeded()
            await viewModel.load()
        }
        .onChangeCompat(viewModel.selectedCategorySlug) { _ in
            viewModel.page = 1
            Task { await viewModel.load() }
        }
    }
}
