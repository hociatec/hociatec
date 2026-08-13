import Foundation

struct AuditService: AuditServing {
    let api: APIClient

    func auditMetadata() async throws -> AuditMetadata { try await api.auditMetadata() }
    func createAudit(type: String, url: String, objectives: String?) async throws -> AuditCreateResponse {
        try await api.createAudit(type: type, url: url, objectives: objectives)
    }
    func myAudits(page: Int, perPage: Int) async throws -> AuditListData {
        try await api.myAudits(page: page, perPage: perPage)
    }
    func myAudit(id: Int) async throws -> AuditDetail { try await api.myAudit(id: id) }
    func myAuditPdf(id: Int) async throws -> Data { try await api.myAuditPdf(id: id) }
    func myAuditSummaryPdf(id: Int) async throws -> Data { try await api.myAuditSummaryPdf(id: id) }
}
