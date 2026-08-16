import SwiftUI

struct GlobalSearchNewsSection: View {
    @EnvironmentObject private var container: AppContainer

    @ObservedObject var viewModel: GlobalSearchViewModel

    var body: some View {
        Section {
            if viewModel.news.isEmpty {
                GlobalSearchEmptyRow(message: "Aucun résultat actualité.")
            } else {
                ForEach(viewModel.news) { article in
                    VStack(alignment: .leading, spacing: 6) {
                        Text(article.title)
                            .fontWeight(.semibold)
                            .multilineTextAlignment(.leading)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .accessibilityAddTraits(.isHeader)

                        GlobalSearchNewsRow(article: article, showsTitle: false)
                        NavigationLink {
                            NewsDetailView(api: container.services.news, slug: article.slug)
                        } label: {
                            Label("Lire l’actualité", systemImage: "arrow.right.circle")
                                .font(.footnote.weight(.semibold))
                        }
                        .buttonStyle(.borderless)
                        .accessibilityHint("Ouvrir l’actualité")
                    }
                    .padding(.vertical, 4)
                    .accessibilityElement(children: .contain)
                }
            }
        } header: {
            GlobalSearchResultHeader(title: "Actualités", total: viewModel.newsTotal, query: viewModel.query) {
                NewsListView(
                    api: container.services.news,
                    initialSearch: viewModel.query
                )
            }
        } footer: {
            GlobalSearchPaginationSection(viewModel: viewModel, filter: .news)
        }
    }
}
