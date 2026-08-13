import SwiftUI

struct ServicesCatalogView: View {
    let serviceCatalog: ServiceCatalogServing
    @StateObject private var viewModel: ServicesCatalogViewModel

    init(api: ServiceCatalogServing) {
        self.serviceCatalog = api
        _viewModel = StateObject(wrappedValue: ServicesCatalogViewModel(serviceCatalog: api))
    }

    var body: some View {
        List {
            Section {
                TextField("Rechercher un service", text: $viewModel.searchText)
                Button("Rechercher") {
                    viewModel.applySearch()
                    Task { await viewModel.load() }
                }
            }

            Section("Services") {
                if viewModel.isLoading && viewModel.services.isEmpty {
                    ProgressView("Chargement des services...")
                } else if let error = viewModel.error {
                    Text(error).foregroundStyle(.red)
                } else if viewModel.services.isEmpty {
                    Text(viewModel.appliedSearch.isEmpty ? "Aucun service publié pour le moment." : "Aucun service ne correspond à cette recherche.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(viewModel.services) { service in
                        NavigationLink {
                            ServiceDetailView(api: serviceCatalog, serviceID: service.id)
                        } label: {
                            VStack(alignment: .leading, spacing: 6) {
                                Text(service.title)
                                    .fontWeight(.semibold)
                                if let description = service.description, !description.isEmpty {
                                    Text(description)
                                        .lineLimit(2)
                                        .foregroundStyle(.secondary)
                                }
                                HStack {
                                    Text(PriceFormatter.format(cents: service.priceCents))
                                        .font(.footnote)
                                        .fontWeight(.semibold)
                                    Spacer()
                                    Text(service.durationLabel ?? "Sur étude")
                                        .font(.footnote)
                                        .foregroundStyle(.secondary)
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
        .navigationTitle("Services")
        .task { await viewModel.load() }
    }
}
