import Foundation

struct SupportService: SupportServing {
    let api: APIClient

    func mySupportRequests(page: Int, perPage: Int) async throws -> SupportRequestListData {
        try await api.mySupportRequests(page: page, perPage: perPage)
    }

    func mySupportRequest(id: Int) async throws -> SupportRequestSummary {
        try await api.mySupportRequest(id: id)
    }

    func createSupportRequest(subject: String, reason: String, message: String, orderId: Int?, attachments: [MultipartUploadFile]) async throws -> SupportRequestSummary {
        try await api.createSupportRequest(subject: subject, reason: reason, message: message, orderId: orderId, attachments: attachments)
    }

    func replySupportRequest(id: Int, subject: String?, message: String, attachments: [MultipartUploadFile]) async throws -> SupportRequestSummary {
        try await api.replySupportRequest(id: id, subject: subject, message: message, attachments: attachments)
    }

    func mySupportAttachment(id: Int, name: String) async throws -> Data {
        try await api.mySupportAttachment(id: id, name: name)
    }
}
