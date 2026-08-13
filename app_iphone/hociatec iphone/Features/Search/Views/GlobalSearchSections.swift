import SwiftUI

struct GlobalSearchIntroSection: View {
    var body: some View {
        Section {
            VStack(alignment: .leading, spacing: 10) {
                Text("Trouver un produit, un service ou une formation")
                    .font(.title3.weight(.bold))
                Text("Lancez une recherche, puis ouvrez la fiche qui correspond à votre besoin.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            .padding(.vertical, 4)
        }
    }
}

struct GlobalSearchControlsSection: View {
    @ObservedObject var viewModel: GlobalSearchViewModel
    @State private var showFilterSheet = false
    @State private var showSortSheet = false

    private var filtersCount: Int {
        viewModel.selectedFilter == .all ? 0 : 1
    }

    var body: some View {
        Section {
            TextField("Exemple : ordinateur, audit, sécurité...", text: $viewModel.draftQuery)
                .textInputAutocapitalization(.never)
                .submitLabel(.search)
                .onSubmit {
                    Task { await viewModel.submit() }
                }

            if !viewModel.query.isEmpty {
                HStack {
                    Button {
                        showFilterSheet = true
                    } label: {
                        Label(filtersCount > 0 ? "Filtrer (\(filtersCount))" : "Filtrer", systemImage: "line.3.horizontal.decrease.circle")
                    }

                    Spacer()

                    Button {
                        showSortSheet = true
                    } label: {
                        Label("Trier (\(viewModel.selectedSort.label))", systemImage: "arrow.up.arrow.down")
                    }
                }
            }

            Button("Rechercher") {
                Task { await viewModel.submit() }
            }
        }
        .sheet(isPresented: $showFilterSheet) {
            GlobalSearchFilterSheet(
                selectedFilter: $viewModel.selectedFilter,
                onClose: { showFilterSheet = false }
            )
        }
        .sheet(isPresented: $showSortSheet) {
            GlobalSearchSortSheet(
                selectedSort: $viewModel.selectedSort,
                onClose: { showSortSheet = false }
            )
        }
    }
}

struct GlobalSearchStatusSections: View {
    @ObservedObject var viewModel: GlobalSearchViewModel

    var body: some View {
        Group {
            if let error = viewModel.error, !error.isEmpty {
                Section { Text(error).foregroundStyle(.red) }
            }

            if !viewModel.query.isEmpty {
                Section {
                    Text("\(viewModel.totalResults) résultat\(viewModel.totalResults > 1 ? "s" : "") pour \"\(viewModel.query)\"")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
            }
        }
    }
}

struct GlobalSearchLoadingSection: View {
    var body: some View {
        Section {
            ProgressView("Recherche en cours...")
        }
    }
}

private struct GlobalSearchFilterSheet: View {
    @Binding var selectedFilter: GlobalSearchFilter
    let onClose: () -> Void

    var body: some View {
        NavigationStack {
            List {
                ForEach(GlobalSearchFilter.allCases) { filter in
                    Button {
                        selectedFilter = filter
                        onClose()
                    } label: {
                        HStack {
                            Text(filter.label)
                            if selectedFilter == filter {
                                Spacer()
                                Image(systemName: "checkmark")
                            }
                        }
                    }
                }
            }
            .navigationTitle("Filtrer")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Fermer", action: onClose)
                }
            }
        }
    }
}

private struct GlobalSearchSortSheet: View {
    @Binding var selectedSort: GlobalSearchSortOption
    let onClose: () -> Void

    var body: some View {
        NavigationStack {
            List {
                ForEach(GlobalSearchSortOption.allCases) { option in
                    Button {
                        selectedSort = option
                        onClose()
                    } label: {
                        HStack {
                            Text(option.label)
                            if selectedSort == option {
                                Spacer()
                                Image(systemName: "checkmark")
                            }
                        }
                    }
                }
            }
            .navigationTitle("Trier")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Fermer", action: onClose)
                }
            }
        }
    }
}
