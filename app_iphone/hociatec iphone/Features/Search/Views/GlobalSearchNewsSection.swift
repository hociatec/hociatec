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
                        NavigationLink {
                            NewsDetailView(api: container.services.news, slug: article.slug)
                        } label: {
                            Text(article.title)
                                .fontWeight(.semibold)
                                .multilineTextAlignment(.leading)
                                .frame(maxWidth: .infinity, alignment: .leading)
                        }
                        .buttonStyle(.plain)
                        .accessibilityAddTraits(.isHeader)
                        .accessibilityHint("Ouvrir l’actualité")

                        GlobalSearchNewsRow(article: article, showsTitle: false)
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
        }
    }
}
