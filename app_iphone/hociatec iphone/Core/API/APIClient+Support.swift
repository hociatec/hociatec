import Foundation

extension APIClient {
    func mySupportRequests(page: Int = 1, perPage: Int = 10) async throws -> SupportRequestListData {
        try await request(
            path: "api/support/me",
            query: [
                URLQueryItem(name: "page", value: String(page)),
                URLQueryItem(name: "perPage", value: String(perPage))
            ],
            authorized: true,
            attachCartToken: false
        )
    }

    func mySupportRequest(id: Int) async throws -> SupportRequestSummary {
        let data: SupportRequestItemData = try await request(
            path: "api/support/me/\(id)",
            authorized: true,
            attachCartToken: false
        )
        return data.item
    }

    func createSupportRequest(
        subject: String,
        reason: String,
        message: String,
        orderId: Int?,
        attachments: [MultipartUploadFile] = []
    ) async throws -> SupportRequestSummary {
        let fields: [String: String] = [
            "subject": subject,
            "reason": reason,
            "message": message,
            "orderId": orderId.map(String.init) ?? ""
        ]

        let data: SupportRequestItemData

        if attachments.isEmpty {
            data = try await request(
                path: "api/support/me",
                method: "POST",
                body: [
                    "subject": subject,
                    "reason": reason,
                    "message": message,
                    "orderId": orderId as Any
                ],
                authorized: true,
                attachCartToken: false
            )
        } else {
            data = try await multipartRequest(
                path: "api/support/me",
                fields: fields,
                files: attachments,
                authorized: true,
                attachCartToken: false
            )
        }
        return data.item
    }

    func replySupportRequest(
        id: Int,
        subject: String?,
        message: String,
        attachments: [MultipartUploadFile] = []
    ) async throws -> SupportRequestSummary {
        var body: [String: Any] = ["message": message]
        var fields: [String: String] = ["message": message]
        if let subject, !subject.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            body["subject"] = subject
            fields["subject"] = subject
        }

        let data: SupportRequestItemData

        if attachments.isEmpty {
            data = try await request(
                path: "api/support/me/\(id)/reply",
                method: "POST",
                body: body,
                authorized: true,
                attachCartToken: false
            )
        } else {
            data = try await multipartRequest(
                path: "api/support/me/\(id)/reply",
                fields: fields,
                files: attachments,
                authorized: true,
                attachCartToken: false
            )
        }
        return data.item
    }

    func mySupportAttachment(id: Int, name: String) async throws -> Data {
        let encodedName = name.addingPercentEncoding(withAllowedCharacters: .urlPathAllowed) ?? name
        return try await download(
            path: "api/support/me/\(id)/attachments/\(encodedName)",
            authorized: true,
            attachCartToken: false
        )
    }
}
