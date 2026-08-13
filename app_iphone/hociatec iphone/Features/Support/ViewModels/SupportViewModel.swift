import Foundation

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

    init(service: SupportServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let data = try await service.mySupportRequests(page: 1, perPage: 20)
            items = data.items
            if let selectedId = selectedItem?.id {
                selectedItem = data.items.first(where: { $0.id == selectedId })
            }
        } catch {
            self.error = error.localizedDescription
            items = []
        }
    }

    func loadDetail(id: Int) async {
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let detail = try await service.mySupportRequest(id: id)
            selectedItem = detail
            if let index = items.firstIndex(where: { $0.id == detail.id }) {
                items[index] = detail
            }
        } catch {
            self.error = error.localizedDescription
        }
    }

    func create(subject: String, reason: String, message: String, orderId: Int?, attachments: [MultipartUploadFile]) async -> Bool {
        isSubmitting = true
        error = nil
        successMessage = nil
        defer { isSubmitting = false }

        do {
            let created = try await service.createSupportRequest(subject: subject, reason: reason, message: message, orderId: orderId, attachments: attachments)
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
        error = nil

        do {
            let data = try await service.mySupportAttachment(id: requestId, name: attachment.name)
            sharedFile = try TemporarySharedFileFactory.create(data: data, fileName: attachment.originalName)
        } catch {
            self.error = error.localizedDescription
        }
    }
}
