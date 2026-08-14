import Foundation

extension BetaProgramViewModel {
    func load(force: Bool = false) async {
        if isLoading && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil

        do {
            async let profileTask = service.myBetaProfile()
            async let choicesTask = service.betaProfileChoices()
            async let campaignsTask = service.betaCampaigns()
            async let reportsTask = service.myBetaReports(page: 1, perPage: 20)

            let profile = try await profileTask
            guard requestID == loadRequestID else { return }
            self.profile = profile
            choices = try await choicesTask
            campaigns = try await campaignsTask
            reports = try await reportsTask.items
            syncProfileFields(with: profile)
        } catch {
            guard requestID == loadRequestID else { return }
            self.error = error.localizedDescription
        }

        if requestID == loadRequestID {
            isLoading = false
        }
    }

    func loadChoicesIfNeeded() async {
        guard choices.isEmpty else { return }
        choicesRequestID += 1
        let requestID = choicesRequestID
        do {
            let loadedChoices = try await service.betaProfileChoices()
            guard requestID == choicesRequestID else { return }
            choices = loadedChoices
        } catch {
            guard requestID == choicesRequestID else { return }
            self.error = error.localizedDescription
        }
    }

    func loadReport(id: Int) async {
        reportRequestID += 1
        let requestID = reportRequestID
        isLoading = true
        error = nil

        do {
            async let reportTask = service.myBetaReport(id: id)
            async let commentsTask = service.betaReportComments(id: id, page: 1, perPage: 20)
            let report = try await reportTask
            let comments = try await commentsTask.items
            guard requestID == reportRequestID else { return }
            selectedReport = report
            reportComments = comments
        } catch {
            guard requestID == reportRequestID else { return }
            self.error = error.localizedDescription
        }

        if requestID == reportRequestID {
            isLoading = false
        }
    }
}
