import Foundation

extension APIClient {
    func auditMetadata() async throws -> AuditMetadata {
        try await request(
            path: "api/audits/metadata",
            authorized: true,
            attachCartToken: false
        )
    }

    func createAudit(type: String, url: String, objectives: String?) async throws -> AuditCreateResponse {
        var body: [String: Any] = [
            "type": type,
            "url": url
        ]
        if let objectives, !objectives.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            body["objectives"] = objectives
        }

        return try await request(
            path: "api/audits",
            method: "POST",
            body: body,
            authorized: true,
            attachCartToken: false
        )
    }

    func myAudits(page: Int = 1, perPage: Int = 10) async throws -> AuditListData {
        try await request(
            path: "api/audits",
            query: [
                URLQueryItem(name: "page", value: String(page)),
                URLQueryItem(name: "perPage", value: String(perPage))
            ],
            authorized: true,
            attachCartToken: false
        )
    }

    func myAudit(id: Int) async throws -> AuditDetail {
        try await request(
            path: "api/audits/\(id)",
            authorized: true,
            attachCartToken: false
        )
    }

    func myAuditPdf(id: Int) async throws -> Data {
        try await download(
            path: "api/audits/\(id)/pdf",
            method: "POST",
            authorized: true,
            attachCartToken: false
        )
    }

    func myAuditSummaryPdf(id: Int) async throws -> Data {
        try await download(
            path: "api/audits/\(id)/pdf-summary",
            method: "POST",
            authorized: true,
            attachCartToken: false
        )
    }
}
