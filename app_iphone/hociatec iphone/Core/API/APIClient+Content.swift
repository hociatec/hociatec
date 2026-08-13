import Foundation

extension APIClient {
    func sendContact(
        name: String,
        email: String,
        subject: String,
        message: String
    ) async throws {
        try await send(
            path: "api/public/contact",
            method: "POST",
            body: [
                "name": name,
                "email": email,
                "subject": subject,
                "message": message
            ],
            authorized: false,
            attachCartToken: false
        )
    }

    func latestNews(limit: Int = 3) async throws -> [NewsArticle] {
        let data = try await newsArticles(page: 1, perPage: limit)
        return Array(data.items.prefix(limit))
    }

    func newsArticles(page: Int = 1, perPage: Int = 9, query search: String? = nil) async throws -> NewsArticleListData {
        var query = [
            URLQueryItem(name: "page", value: String(page)),
            URLQueryItem(name: "perPage", value: String(perPage))
        ]
        if let search, !search.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            query.append(URLQueryItem(name: "q", value: search))
        }
        return try await request(
            path: "api/public/news",
            query: query
        )
    }

    func newsArticle(slug: String) async throws -> NewsArticle {
        let encodedSlug = slug.addingPercentEncoding(withAllowedCharacters: .urlPathAllowed) ?? slug
        let data: NewsArticleData = try await request(
            path: "api/public/news/\(encodedSlug)"
        )
        return data.article
    }

    func newsComments(slug: String, page: Int = 1, perPage: Int = 10) async throws -> NewsCommentListData {
        let encodedSlug = slug.addingPercentEncoding(withAllowedCharacters: .urlPathAllowed) ?? slug
        return try await request(
            path: "api/public/news/\(encodedSlug)/comments",
            query: [
                URLQueryItem(name: "page", value: String(page)),
                URLQueryItem(name: "perPage", value: String(perPage))
            ]
        )
    }

    func createNewsComment(slug: String, content: String) async throws -> NewsComment {
        let encodedSlug = slug.addingPercentEncoding(withAllowedCharacters: .urlPathAllowed) ?? slug
        let data: NewsCommentData = try await request(
            path: "api/public/news/\(encodedSlug)/comments",
            method: "POST",
            body: ["content": content],
            authorized: true,
            attachCartToken: false
        )
        return data.comment
    }

    func trainingCategories() async throws -> [TrainingCategory] {
        let data: TrainingCategoryListData = try await request(
            path: "api/public/training-categories"
        )
        return data.items
    }

    func trainings(
        page: Int = 1,
        perPage: Int = 10,
        query search: String? = nil,
        category: String? = nil
    ) async throws -> TrainingListData {
        var query = [
            URLQueryItem(name: "page", value: String(page)),
            URLQueryItem(name: "perPage", value: String(perPage))
        ]
        if let search, !search.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            query.append(URLQueryItem(name: "q", value: search))
        }
        if let category, !category.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            query.append(URLQueryItem(name: "category", value: category))
        }
        return try await request(
            path: "api/public/trainings",
            query: query
        )
    }

    func training(slug: String) async throws -> TrainingDetailData {
        let encodedSlug = slug.addingPercentEncoding(withAllowedCharacters: .urlPathAllowed) ?? slug
        return try await request(
            path: "api/public/trainings/\(encodedSlug)"
        )
    }

    func myTrainingEnrollments(page: Int = 1, perPage: Int = 10) async throws -> TrainingEnrollmentListData {
        try await request(
            path: "api/trainings/enrollments/me",
            query: [
                URLQueryItem(name: "page", value: String(page)),
                URLQueryItem(name: "perPage", value: String(perPage))
            ],
            authorized: true,
            attachCartToken: false
        )
    }
}
