import Foundation
import Combine

@MainActor
final class SupportViewModel: ObservableObject {
    @Published var items: [SupportRequestSummary] = []
    @Published var selectedItem: SupportRequestSummary?
    @Published var isLoading = false
    @Published var isSubmitting = false
    @Published var error: String?
    @Published var successMessage: String?
    @Published var sharedFile: TemporarySharedFile?

    private let service: SupportServing
    private var loadRequestID = 0
    private var detailRequestID = 0
    private var attachmentRequestID = 0

    init(service: SupportServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil

        do {
            let data = try await service.mySupportRequests(page: 1, perPage: 20)
            guard requestID == loadRequestID else { return }
            items = data.items
            if let selectedId = selectedItem?.id {
                selectedItem = data.items.first(where: { $0.id == selectedId })
            }
        } catch {
            guard requestID == loadRequestID else { return }
            self.error = error.localizedDescription
            items = []
        }

        if requestID == loadRequestID {
            isLoading = false
        }
    }

    func loadDetail(id: Int) async {
        detailRequestID += 1
        let requestID = detailRequestID
        isLoading = true
        error = nil

        do {
            let detail = try await service.mySupportRequest(id: id)
            guard requestID == detailRequestID else { return }
            selectedItem = detail
            if let index = items.firstIndex(where: { $0.id == detail.id }) {
                items[index] = detail
            }
        } catch {
            guard requestID == detailRequestID else { return }
            self.error = error.localizedDescription
        }

        if requestID == detailRequestID {
            isLoading = false
        }
    }

    func create(subject: String, reason: String, message: String, orderId: Int?, attachments: [MultipartUploadFile]) async -> Bool {
        isSubmitting = true
        error = nil
        successMessage = nil
        defer { isSubmitting = false }

        do {
            let created = try await service.createSupportRequest(subject: subject, reason: reason, message: message, orderId: orderId, attachments: attachments)
            loadRequestID += 1
            detailRequestID += 1
            items.insert(created, at: 0)
            selectedItem = created
            successMessage = "Demande SAV créée."
            return true
        } catch {
            self.error = error.localizedDescription
            return false
        }
    }

    func reply(id: Int, subject: String?, message: String, attachments: [MultipartUploadFile]) async -> Bool {
        isSubmitting = true
        error = nil
        successMessage = nil
        defer { isSubmitting = false }

        do {
            let updated = try await service.replySupportRequest(id: id, subject: subject, message: message, attachments: attachments)
            detailRequestID += 1
            selectedItem = updated
            if let index = items.firstIndex(where: { $0.id == updated.id }) {
                items[index] = updated
            }
            successMessage = "Réponse envoyée."
            return true
        } catch {
            self.error = error.localizedDescription
            return false
        }
    }

    func shareAttachment(requestId: Int, attachment: SupportAttachment) async {
        attachmentRequestID += 1
        let currentRequestID = attachmentRequestID
        error = nil

        do {
            let data = try await service.mySupportAttachment(id: requestId, name: attachment.name)
            guard currentRequestID == attachmentRequestID else { return }
            sharedFile = try TemporarySharedFileFactory.create(data: data, fileName: attachment.originalName)
        } catch {
            guard currentRequestID == attachmentRequestID else { return }
            self.error = error.localizedDescription
        }
    }
}
