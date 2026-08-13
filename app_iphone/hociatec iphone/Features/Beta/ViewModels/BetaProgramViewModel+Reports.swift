import Foundation

extension BetaProgramViewModel {
    var severities: [String] {
        BetaProgramLabels.severities
    }

    func submitReport() async -> Bool {
        isSubmittingReport = true
        error = nil
        defer { isSubmittingReport = false }

        do {
            try await service.createBetaReport(payload: reportPayload, screenshots: [])
            reportTitle = ""
            reportDescription = ""
            reportExpectedBehavior = ""
            reportActualBehavior = ""
            reportPageURL = ""
            reportSeverity = "normal"
            selectedCampaignID = ""
            reports = try await service.myBetaReports(page: 1, perPage: 20).items
            return true
        } catch {
            self.error = error.localizedDescription
            return false
        }
    }

    func postComment(reportId: Int, content: String) async -> Bool {
        let trimmed = content.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty else { return false }

        do {
            _ = try await service.createBetaReportComment(id: reportId, content: trimmed)
            reportComments = try await service.betaReportComments(id: reportId, page: 1, perPage: 20).items
            return true
        } catch {
            self.error = error.localizedDescription
            return false
        }
    }

    var reportPayload: [String: String] {
        var payload: [String: String] = [
            "title": reportTitle,
            "description": reportDescription,
            "severity": reportSeverity
        ]
        if !reportExpectedBehavior.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            payload["expectedBehavior"] = reportExpectedBehavior
        }
        if !reportActualBehavior.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            payload["actualBehavior"] = reportActualBehavior
        }
        if !reportPageURL.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            payload["pageUrl"] = reportPageURL
        }
        if let campaignId = Int(selectedCampaignID) {
            payload["campaignId"] = String(campaignId)
        }
        return payload
    }
}
