import Foundation
import Combine

@MainActor
final class AuditsViewModel: ObservableObject {
    @Published var items: [AuditListItem] = []
    @Published var selectedAudit: AuditDetail?
    @Published var metadata: AuditMetadata?
    @Published var isLoading = false
    @Published var isSubmitting = false
    @Published var error: String?
    @Published var successMessage: String?
    @Published var sharedFile: TemporarySharedFile?

    private let service: AuditServing

    init(service: AuditServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            async let auditsTask = service.myAudits(page: 1, perPage: 20)
            async let metadataTask = service.auditMetadata()
            let audits = try await auditsTask
            let metadata = try await metadataTask
            self.items = audits.items
            self.metadata = metadata
        } catch {
            self.error = error.localizedDescription
        }
    }

    func loadDetail(id: Int) async {
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            selectedAudit = try await service.myAudit(id: id)
        } catch {
            self.error = error.localizedDescription
        }
    }

    func createAudit(type: String, url: String, objectives: String) async -> Bool {
        isSubmitting = true
        error = nil
        successMessage = nil
        defer { isSubmitting = false }

        do {
            let created = try await service.createAudit(
                type: type,
                url: url,
                objectives: objectives.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty ? nil : objectives
            )
            successMessage = "Dossier créé: \(created.number)."
            await load(force: true)
            return true
        } catch {
            self.error = error.localizedDescription
            return false
        }
    }

    func shareAuditReport(id: Int, number: String) async {
        await shareAuditFile(fileName: "\(number)-rapport.pdf") {
            try await service.myAuditPdf(id: id)
        }
    }

    func shareAuditSummary(id: Int, number: String) async {
        await shareAuditFile(fileName: "\(number)-synthese.pdf") {
            try await service.myAuditSummaryPdf(id: id)
        }
    }

    private func shareAuditFile(
        fileName: String,
        loader: () async throws -> Data
    ) async {
        error = nil

        do {
            let data = try await loader()
            sharedFile = try TemporarySharedFileFactory.create(data: data, fileName: fileName)
        } catch {
            self.error = error.localizedDescription
        }
    }
}
