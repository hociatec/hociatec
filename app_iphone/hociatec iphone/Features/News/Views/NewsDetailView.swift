import SwiftUI

struct NewsDetailView: View {
    @StateObject private var viewModel: NewsDetailViewModel
    @EnvironmentObject private var account: AccountViewModel

    init(api: NewsServing, slug: String) {
        _viewModel = StateObject(wrappedValue: NewsDetailViewModel(service: api, slug: slug))
    }

    var body: some View {
        List {
            if viewModel.isLoading && viewModel.article == nil {
                NewsDetailLoadingSection()
            } else if let error = viewModel.error {
                NewsDetailErrorSection(error: error)
            } else if let article = viewModel.article {
                NewsDetailHeroSection(article: article)
                NewsDetailContentSection(content: article.content)
                NewsDetailCommentsSection(viewModel: viewModel, isLoggedIn: account.isLoggedIn)
            }
        }
        .navigationTitle(viewModel.article?.title ?? "Actualité")
        .navigationBarTitleDisplayMode(.inline)
        .task {
            await viewModel.loadArticle()
            await viewModel.loadComments()
        }
    }
}
