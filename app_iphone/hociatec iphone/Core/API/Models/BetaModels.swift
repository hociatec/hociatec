import Foundation

struct BetaChoice: Decodable, Identifiable, Hashable {
    var id: String { value }
    let value: String
    let label: String
}

struct BetaProfileChoicesData: Decodable {
    let choices: [String: [BetaChoice]]
}

struct BetaProfileData: Decodable {
    let profile: BetaProfile?
}

struct BetaCampaignListData: Decodable {
    let items: [BetaCampaign]
}

struct BetaReportsData: Decodable {
    let items: [BetaBugReport]
    let meta: PaginationMeta
    let stats: BetaReportStats
}

struct BetaReportData: Decodable {
    let report: BetaBugReport
}

struct BetaCommentsData: Decodable {
    let items: [BetaBugReportComment]
    let meta: PaginationMeta
}

struct BetaCommentEnvelope: Decodable {
    let id: Int
    let content: String
    let createdAt: Date
    let author: BetaCommentAuthor
}

struct BetaReportStats: Decodable {
    let openReports: Int
    let resolvedReports: Int
    let totalReports: Int
}

struct BetaProfile: Decodable {
    let status: String?
    let motivation: String?
    let testingExperience: [String]?
    let bugDescriptionAbility: [String]?
    let technicalKnowledge: [String]?
    let availability: [String]?
    let accessibilityNeed: String?
    let assistiveTools: [String]?
    let devices: [String]?
    let browsers: [String]?
    let testingTypes: [String]?
    let betaConsent: Bool?
}

struct BetaCampaign: Decodable, Identifiable {
    let id: Int
    let name: String
    let description: String
    let status: String
    let startsAt: Date?
    let endsAt: Date?
}

struct BetaBugReport: Decodable, Identifiable {
    let id: Int
    let title: String
    let description: String
    let expectedBehavior: String?
    let actualBehavior: String?
    let severity: String
    let status: String
    let pageUrl: String?
    let campaignId: Int?
    let campaign: String?
    let attachments: [String]
    let attachmentUrls: [String]
    let createdAt: Date
    let updatedAt: Date?
    let lastAdminReplyAt: Date?
    let lastReporterReplyAt: Date?
}

struct BetaBugReportComment: Decodable, Identifiable {
    let id: Int
    let content: String
    let createdAt: Date
    let author: BetaCommentAuthor
}

struct BetaCommentAuthor: Decodable {
    let id: Int?
    let firstName: String
    let lastName: String
    let email: String
    let role: String
}
