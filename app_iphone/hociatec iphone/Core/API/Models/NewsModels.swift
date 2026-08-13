import Foundation

struct NewsArticle: Decodable, Identifiable {
    let id: Int
    let title: String
    let slug: String
    let excerpt: String
    let content: String
    let category: String?
    let isPublished: Bool
    let viewsCount: Int
    let publishedAt: Date?
    let createdAt: Date
    let updatedAt: Date
}

struct NewsArticleListData: Decodable {
    let items: [NewsArticle]
    let meta: PaginationMeta
}

struct NewsArticleData: Decodable {
    let article: NewsArticle
}

struct NewsCommentAuthor: Decodable {
    let id: Int
    let name: String
}

struct NewsComment: Decodable, Identifiable {
    let id: Int
    let content: String
    let createdAt: Date
    let author: NewsCommentAuthor
}

struct NewsCommentListData: Decodable {
    let items: [NewsComment]
    let meta: PaginationMeta
}

struct NewsCommentData: Decodable {
    let comment: NewsComment
}
