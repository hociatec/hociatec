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
    private var loadRequestID = 0
    private var metadataRequestID = 0
    private var detailRequestID = 0
    private var attachmentRequestID = 0

    init(service: AuditServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil

        do {
            async let auditsTask = service.myAudits(page: 1, perPage: 20)
            async let metadataTask = service.auditMetadata()
            let audits = try await auditsTask
            let metadata = try await metadataTask
            guard requestID == loadRequestID else { return }
            self.items = audits.items
            self.metadata = metadata
            if let selectedAudit {
                if let refreshedItem = audits.items.first(where: { $0.id == selectedAudit.id }) {
                    self.selectedAudit = AuditDetail(
                        id: refreshedItem.id,
                        number: refreshedItem.number,
                        type: refreshedItem.type,
                        typeLabel: refreshedItem.typeLabel,
                        status: refreshedItem.status,
                        statusLabel: refreshedItem.statusLabel,
                        url: refreshedItem.url,
                        objectives: selectedAudit.objectives,
                        createdAt: refreshedItem.createdAt,
                        items: selectedAudit.items,
                        events: selectedAudit.events
                    )
                } else {
                    self.selectedAudit = nil
                }
            }
        } catch {
            guard requestID == loadRequestID else { return }
            self.error = error.localizedDescription
        }

        if requestID == loadRequestID {
            isLoading = false
        }
    }

    func loadMetadata(force: Bool = false) async {
        if isLoading && !force { return }
        metadataRequestID += 1
        let requestID = metadataRequestID
        isLoading = true
        error = nil

        do {
            let loadedMetadata = try await service.auditMetadata()
            guard requestID == metadataRequestID else { return }
            metadata = loadedMetadata
        } catch {
            guard requestID == metadataRequestID else { return }
            metadata = Self.fallbackMetadata
        }

        if requestID == metadataRequestID {
            isLoading = false
        }
    }

    func loadDetail(id: Int, force: Bool = false) async {
        if isLoading && !force { return }
        detailRequestID += 1
        let requestID = detailRequestID
        isLoading = true
        error = nil

        do {
            let detail = try await service.myAudit(id: id)
            guard requestID == detailRequestID else { return }
            selectedAudit = detail
            if let index = items.firstIndex(where: { $0.id == detail.id }) {
                items[index] = AuditListItem(
                    id: detail.id,
                    number: detail.number,
                    type: detail.type,
                    status: detail.status,
                    typeLabel: detail.typeLabel,
                    statusLabel: detail.statusLabel,
                    url: detail.url,
                    createdAt: detail.createdAt
                )
            }
        } catch {
            guard requestID == detailRequestID else { return }
            self.error = error.localizedDescription
        }

        if requestID == detailRequestID {
            isLoading = false
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
        attachmentRequestID += 1
        let requestID = attachmentRequestID
        error = nil

        do {
            let data = try await loader()
            guard requestID == attachmentRequestID else { return }
            sharedFile = try TemporarySharedFileFactory.create(data: data, fileName: fileName)
        } catch {
            guard requestID == attachmentRequestID else { return }
            self.error = error.localizedDescription
        }
    }

    private static let fallbackMetadata = AuditMetadata(
        types: [
            AuditOption(value: "performance", label: "Performance"),
            AuditOption(value: "security", label: "Sécurité"),
            AuditOption(value: "ux", label: "UX"),
            AuditOption(value: "seo", label: "SEO"),
            AuditOption(value: "technical", label: "Technique"),
            AuditOption(value: "accessibility", label: "Accessibilité"),
        ],
        statuses: []
    )
}
