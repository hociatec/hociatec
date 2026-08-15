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
    private var loadRequestID = 0
    private var hasLoadedOnce = false

    init(service: NewsServing) {
        self.service = service
    }

    func applySearch() {
        appliedSearch = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        page = 1
    }

    func previousPage() { guard page > 1 else { return }; page -= 1 }
    func nextPage() { guard page < totalPages else { return }; page += 1 }

    func load(force: Bool = false) async {
        if (isLoading || hasLoadedOnce) && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil
        let requestedPage = page
        let requestedSearch = appliedSearch.isEmpty ? nil : appliedSearch

        do {
            let data = try await service.newsArticles(page: requestedPage, perPage: 9, query: requestedSearch)
            guard requestID == loadRequestID else { return }
            articles = data.items
            totalPages = max(1, data.meta.totalPages)
            hasLoadedOnce = true
        } catch {
            guard requestID == loadRequestID else { return }
            self.error = error.localizedDescription
        }

        if requestID == loadRequestID {
            isLoading = false
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
    private var articleRequestID = 0
    private var commentsRequestID = 0
    private var hasLoadedArticleOnce = false
    private var hasLoadedCommentsOnce = false

    init(service: NewsServing, slug: String) {
        self.service = service
        self.slug = slug
    }

    func previousCommentsPage() { guard commentsPage > 1 else { return }; commentsPage -= 1 }
    func nextCommentsPage() { guard commentsPage < commentsTotalPages else { return }; commentsPage += 1 }

    func loadArticle(force: Bool = false) async {
        if (isLoading || hasLoadedArticleOnce) && !force { return }
        articleRequestID += 1
        let requestID = articleRequestID
        isLoading = true
        error = nil

        do {
            let loadedArticle = try await service.newsArticle(slug: slug)
            guard requestID == articleRequestID else { return }
            article = loadedArticle
            hasLoadedArticleOnce = true
        } catch {
            guard requestID == articleRequestID else { return }
            self.error = error.localizedDescription
        }

        if requestID == articleRequestID {
            isLoading = false
        }
    }

    func loadComments(force: Bool = false) async {
        if (isLoadingComments || hasLoadedCommentsOnce) && !force { return }
        commentsRequestID += 1
        let requestID = commentsRequestID
        isLoadingComments = true
        commentsError = nil
        let requestedPage = commentsPage

        do {
            let data = try await service.newsComments(slug: slug, page: requestedPage, perPage: 10)
            guard requestID == commentsRequestID else { return }
            comments = data.items
            commentsTotalPages = max(1, data.meta.totalPages)
            hasLoadedCommentsOnce = true
        } catch {
            guard requestID == commentsRequestID else { return }
            self.commentsError = error.localizedDescription
        }

        if requestID == commentsRequestID {
            isLoadingComments = false
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
            hasLoadedCommentsOnce = false
            await loadComments(force: true)
        } catch {
            commentsError = error.localizedDescription
        }
    }
}
