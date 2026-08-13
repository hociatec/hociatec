import Foundation

extension BetaProgramViewModel {
    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            async let profileTask = service.myBetaProfile()
            async let choicesTask = service.betaProfileChoices()
            async let campaignsTask = service.betaCampaigns()
            async let reportsTask = service.myBetaReports(page: 1, perPage: 20)

            let profile = try await profileTask
            self.profile = profile
            choices = try await choicesTask
            campaigns = try await campaignsTask
            reports = try await reportsTask.items
            syncProfileFields(with: profile)
        } catch {
            self.error = error.localizedDescription
        }
    }

    func loadChoicesIfNeeded() async {
        guard choices.isEmpty else { return }
        do {
            choices = try await service.betaProfileChoices()
        } catch {
            self.error = error.localizedDescription
        }
    }

    func loadReport(id: Int) async {
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            async let reportTask = service.myBetaReport(id: id)
            async let commentsTask = service.betaReportComments(id: id, page: 1, perPage: 20)
            selectedReport = try await reportTask
            reportComments = try await commentsTask.items
        } catch {
            self.error = error.localizedDescription
        }
    }
}
