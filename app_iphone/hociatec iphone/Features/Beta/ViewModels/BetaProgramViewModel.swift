import Foundation

@MainActor
final class BetaProgramViewModel: ObservableObject {
    @Published var isLoading = false
    @Published var error: String?
    @Published var profile: BetaProfile?
    @Published var choices: [String: [BetaChoice]] = [:]
    @Published var campaigns: [BetaCampaign] = []
    @Published var reports: [BetaBugReport] = []
    @Published var selectedReport: BetaBugReport?
    @Published var reportComments: [BetaBugReportComment] = []
    @Published var isSubmittingProfile = false
    @Published var isSubmittingReport = false
    @Published var motivation = ""
    @Published var testingExperience: [String] = []
    @Published var bugDescriptionAbility: [String] = []
    @Published var technicalKnowledge: [String] = []
    @Published var availability: [String] = []
    @Published var accessibilityNeed = "none"
    @Published var assistiveTools: [String] = []
    @Published var devices: [String] = []
    @Published var browsers: [String] = []
    @Published var testingTypes: [String] = []
    @Published var betaConsent = true
    @Published var reportTitle = ""
    @Published var reportDescription = ""
    @Published var reportExpectedBehavior = ""
    @Published var reportActualBehavior = ""
    @Published var reportPageURL = ""
    @Published var reportSeverity = "normal"
    @Published var selectedCampaignID = ""

    let service: BetaServing

    init(service: BetaServing) {
        self.service = service
    }

    var canReport: Bool {
        profile?.status == "accepted"
    }

    var isProfileComplete: Bool {
        !motivation.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
            && !testingExperience.isEmpty
            && !bugDescriptionAbility.isEmpty
            && !technicalKnowledge.isEmpty
            && !availability.isEmpty
            && !assistiveTools.isEmpty
            && !devices.isEmpty
            && !browsers.isEmpty
            && !testingTypes.isEmpty
            && betaConsent
    }
}
