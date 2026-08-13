import Foundation

extension APIClient {
    func betaProfileChoices() async throws -> [String: [BetaChoice]] {
        let data: BetaProfileChoicesData = try await request(
            path: "api/public/beta/profile-options",
            authorized: false,
            attachCartToken: false
        )
        return data.choices
    }

    func myBetaProfile() async throws -> BetaProfile? {
        let data: BetaProfileData = try await request(
            path: "api/beta/profile",
            authorized: true,
            attachCartToken: false
        )
        return data.profile
    }

    func updateMyBetaProfile(payload: [String: Any]) async throws -> BetaProfile {
        try await request(
            path: "api/beta/profile",
            method: "PUT",
            body: payload,
            authorized: true,
            attachCartToken: false
        )
    }

    func deleteMyBetaProfile() async throws {
        try await send(
            path: "api/beta/profile",
            method: "DELETE",
            authorized: true,
            attachCartToken: false
        )
    }

    func betaCampaigns() async throws -> [BetaCampaign] {
        let data: BetaCampaignListData = try await request(
            path: "api/beta/campaigns",
            authorized: true,
            attachCartToken: false
        )
        return data.items
    }

    func myBetaReports(page: Int = 1, perPage: Int = 10) async throws -> BetaReportsData {
        try await request(
            path: "api/beta/reports",
            query: [
                URLQueryItem(name: "page", value: String(page)),
                URLQueryItem(name: "perPage", value: String(perPage))
            ],
            authorized: true,
            attachCartToken: false
        )
    }

    func myBetaReport(id: Int) async throws -> BetaBugReport {
        let data: BetaReportData = try await request(
            path: "api/beta/reports/\(id)",
            authorized: true,
            attachCartToken: false
        )
        return data.report
    }

    func createBetaReport(
        payload: [String: String],
        screenshots: [MultipartUploadFile]
    ) async throws {
        let mapped = screenshots.map {
            MultipartUploadFile(
                fieldName: "screenshots[]",
                filename: $0.filename,
                mimeType: $0.mimeType,
                data: $0.data
            )
        }

        if mapped.isEmpty {
            let _: APIEnvelope<[String: Int]> = try await request(
                path: "api/beta/reports",
                method: "POST",
                body: payload,
                authorized: true,
                attachCartToken: false
            )
        } else {
            let _: [String: Int] = try await multipartRequest(
                path: "api/beta/reports",
                fields: payload,
                files: mapped,
                authorized: true,
                attachCartToken: false
            )
        }
    }

    func betaReportComments(id: Int, page: Int = 1, perPage: Int = 10) async throws -> BetaCommentsData {
        try await request(
            path: "api/beta/reports/\(id)/comments",
            query: [
                URLQueryItem(name: "page", value: String(page)),
                URLQueryItem(name: "perPage", value: String(perPage))
            ],
            authorized: true,
            attachCartToken: false
        )
    }

    func createBetaReportComment(id: Int, content: String) async throws -> BetaBugReportComment {
        try await request(
            path: "api/beta/reports/\(id)/comments",
            method: "POST",
            body: ["content": content],
            authorized: true,
            attachCartToken: false
        )
    }
}
