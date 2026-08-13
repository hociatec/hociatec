import Foundation
import Combine

@MainActor
final class NewsListViewModel: ObservableObject {
    @Published var articles: [NewsArticle] = []
    @Published var page = 1
    @Published var totalPages = 1
    @Published var searchText = ""
    @Published var appliedSearch = ""
    @Published var isLoading = false
    @Published var error: String?

    private let service: NewsServing

    init(service: NewsServing) {
        self.service = service
    }

    func applySearch() {
        appliedSearch = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        page = 1
    }

    func previousPage() { guard page > 1 else { return }; page -= 1 }
    func nextPage() { guard page < totalPages else { return }; page += 1 }

    func load() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await service.newsArticles(page: page, perPage: 9, query: appliedSearch.isEmpty ? nil : appliedSearch)
            articles = data.items
            totalPages = max(1, data.meta.totalPages)
        } catch {
            self.error = error.localizedDescription
        }
    }
}

@MainActor
final class NewsDetailViewModel: ObservableObject {
    @Published var article: NewsArticle?
    @Published var comments: [NewsComment] = []
    @Published var commentsPage = 1
    @Published var commentsTotalPages = 1
    @Published var isLoading = false
    @Published var isLoadingComments = false
    @Published var isSubmittingComment = false
    @Published var error: String?
    @Published var commentsError: String?
    @Published var newComment = ""

    private let service: NewsServing
    private let slug: String

    init(service: NewsServing, slug: String) {
        self.service = service
        self.slug = slug
    }

    func previousCommentsPage() { guard commentsPage > 1 else { return }; commentsPage -= 1 }
    func nextCommentsPage() { guard commentsPage < commentsTotalPages else { return }; commentsPage += 1 }

    func loadArticle() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            article = try await service.newsArticle(slug: slug)
        } catch {
            self.error = error.localizedDescription
        }
    }

    func loadComments() async {
        guard !isLoadingComments else { return }
        isLoadingComments = true
        commentsError = nil
        defer { isLoadingComments = false }

        do {
            let data = try await service.newsComments(slug: slug, page: commentsPage, perPage: 10)
            comments = data.items
            commentsTotalPages = max(1, data.meta.totalPages)
        } catch {
            self.commentsError = error.localizedDescription
        }
    }

    func submitComment() async {
        let content = newComment.trimmingCharacters(in: .whitespacesAndNewlines)
        guard content.count >= 3 else { return }
        guard !isSubmittingComment else { return }
        isSubmittingComment = true
        commentsError = nil
        defer { isSubmittingComment = false }

        do {
            _ = try await service.createNewsComment(slug: slug, content: content)
            newComment = ""
            commentsPage = 1
            await loadComments()
        } catch {
            commentsError = error.localizedDescription
        }
    }
}
