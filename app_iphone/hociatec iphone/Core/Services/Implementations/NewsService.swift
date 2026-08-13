import Foundation

struct NewsService: NewsServing {
    let api: APIClient

    func latestNews(limit: Int) async throws -> [NewsArticle] { try await api.latestNews(limit: limit) }
    func newsArticles(page: Int, perPage: Int, query: String?) async throws -> NewsArticleListData {
        try await api.newsArticles(page: page, perPage: perPage, query: query)
    }
    func newsArticle(slug: String) async throws -> NewsArticle { try await api.newsArticle(slug: slug) }
    func newsComments(slug: String, page: Int, perPage: Int) async throws -> NewsCommentListData {
        try await api.newsComments(slug: slug, page: page, perPage: perPage)
    }
    func createNewsComment(slug: String, content: String) async throws -> NewsComment {
        try await api.createNewsComment(slug: slug, content: content)
    }
}
