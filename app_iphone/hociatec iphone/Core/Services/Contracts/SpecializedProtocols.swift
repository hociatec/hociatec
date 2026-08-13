import Foundation

protocol TradeInServing {
    func tradeInMetadata() async throws -> TradeInMetadata
    func createTradeIn(payload: TradeInRequestPayload, ribFilename: String, ribData: Data, authorized: Bool) async throws -> TradeInSummary
    func myTradeIns(page: Int, perPage: Int) async throws -> TradeInListData
    func myTradeInReceipt(id: Int) async throws -> Data
    func respondToTradeIn(id: Int, action: String) async throws
}

protocol AuditServing {
    func auditMetadata() async throws -> AuditMetadata
    func createAudit(type: String, url: String, objectives: String?) async throws -> AuditCreateResponse
    func myAudits(page: Int, perPage: Int) async throws -> AuditListData
    func myAudit(id: Int) async throws -> AuditDetail
    func myAuditPdf(id: Int) async throws -> Data
    func myAuditSummaryPdf(id: Int) async throws -> Data
}

protocol BetaServing {
    func betaProfileChoices() async throws -> [String: [BetaChoice]]
    func myBetaProfile() async throws -> BetaProfile?
    func updateMyBetaProfile(payload: [String: Any]) async throws -> BetaProfile
    func deleteMyBetaProfile() async throws
    func betaCampaigns() async throws -> [BetaCampaign]
    func myBetaReports(page: Int, perPage: Int) async throws -> BetaReportsData
    func myBetaReport(id: Int) async throws -> BetaBugReport
    func createBetaReport(payload: [String: String], screenshots: [MultipartUploadFile]) async throws
    func betaReportComments(id: Int, page: Int, perPage: Int) async throws -> BetaCommentsData
    func createBetaReportComment(id: Int, content: String) async throws -> BetaBugReportComment
}
