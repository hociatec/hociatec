import SwiftUI

struct NewsListView: View {
    let service: NewsServing
    @StateObject private var viewModel: NewsListViewModel

    init(api: NewsServing, initialSearch: String = "") {
        self.service = api
        let viewModel = NewsListViewModel(service: api)
        viewModel.searchText = initialSearch
        viewModel.appliedSearch = initialSearch.trimmingCharacters(in: .whitespacesAndNewlines)
        _viewModel = StateObject(wrappedValue: viewModel)
    }

    var body: some View {
        List {
            Section {
                TextField("Rechercher une actualité", text: $viewModel.searchText)
                Button("Rechercher") {
                    viewModel.applySearch()
                    Task { await viewModel.load() }
                }
            }

            Section("Actualités") {
                if viewModel.isLoading && viewModel.articles.isEmpty {
                    ProgressView("Chargement des actualités...")
                } else if let error = viewModel.error {
                    Text(error).foregroundStyle(.red)
                } else if viewModel.articles.isEmpty {
                    Text("Aucune actualité disponible pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(viewModel.articles) { article in
                        NavigationLink {
                            NewsDetailView(api: service, slug: article.slug)
                        } label: {
                            VStack(alignment: .leading, spacing: 6) {
                                HStack {
                                    if let publishedAt = article.publishedAt {
                                        Text(newsDateFormatter.string(from: publishedAt))
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                    }
                                    if let category = article.category, !category.isEmpty {
                                        Spacer()
                                        Text(category)
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                    }
                                }
                                Text(article.title)
                                    .fontWeight(.semibold)
                                Text(article.excerpt)
                                    .lineLimit(3)
                                    .foregroundStyle(.secondary)
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
        .navigationTitle("Actualités")
        .task { await viewModel.load() }
    }
}
