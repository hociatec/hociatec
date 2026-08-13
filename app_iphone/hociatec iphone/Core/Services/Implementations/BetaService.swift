import Foundation

struct BetaService: BetaServing {
    let api: APIClient

    func betaProfileChoices() async throws -> [String: [BetaChoice]] { try await api.betaProfileChoices() }
    func myBetaProfile() async throws -> BetaProfile? { try await api.myBetaProfile() }
    func updateMyBetaProfile(payload: [String: Any]) async throws -> BetaProfile { try await api.updateMyBetaProfile(payload: payload) }
    func deleteMyBetaProfile() async throws { try await api.deleteMyBetaProfile() }
    func betaCampaigns() async throws -> [BetaCampaign] { try await api.betaCampaigns() }
    func myBetaReports(page: Int, perPage: Int) async throws -> BetaReportsData { try await api.myBetaReports(page: page, perPage: perPage) }
    func myBetaReport(id: Int) async throws -> BetaBugReport { try await api.myBetaReport(id: id) }
    func createBetaReport(payload: [String: String], screenshots: [MultipartUploadFile]) async throws { try await api.createBetaReport(payload: payload, screenshots: screenshots) }
    func betaReportComments(id: Int, page: Int, perPage: Int) async throws -> BetaCommentsData { try await api.betaReportComments(id: id, page: page, perPage: perPage) }
    func createBetaReportComment(id: Int, content: String) async throws -> BetaBugReportComment { try await api.createBetaReportComment(id: id, content: content) }
}
