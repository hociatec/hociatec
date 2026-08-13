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
                    NavigationLink {
                        NewsDetailView(api: container.services.news, slug: article.slug)
                    } label: {
                        GlobalSearchNewsRow(article: article)
                    }
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
