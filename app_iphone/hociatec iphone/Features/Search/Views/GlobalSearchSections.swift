import SwiftUI

struct GlobalSearchIntroSection: View {
    var body: some View {
        Section {
            VStack(alignment: .leading, spacing: 10) {
                Text("Trouver un produit, un service ou une formation")
                    .font(.title3.weight(.bold))
                Text("Lancez une recherche globale, puis ouvrez la fiche qui correspond à votre besoin.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            .padding(.vertical, 4)
        }
    }
}

struct GlobalSearchControlsSection: View {
    @ObservedObject var viewModel: GlobalSearchViewModel

    var body: some View {
        Section {
            TextField("Exemple : ordinateur, audit, sécurité...", text: $viewModel.draftQuery)
                .textInputAutocapitalization(.never)
                .submitLabel(.search)
                .onSubmit {
                    Task { await viewModel.submit() }
                }

            Picker("Filtre", selection: $viewModel.selectedFilter) {
                ForEach(GlobalSearchFilter.allCases) { filter in
                    Text(filter.label).tag(filter)
                }
            }
            .pickerStyle(.segmented)

            Button("Rechercher") {
                Task { await viewModel.submit() }
            }
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
