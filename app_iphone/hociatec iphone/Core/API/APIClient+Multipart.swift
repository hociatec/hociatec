import Foundation

extension APIClient {
    func multipartRequest<T: Decodable>(
        path: String,
        fields: [String: String],
        fileFieldName: String,
        filename: String,
        mimeType: String,
        fileData: Data,
        authorized: Bool,
        attachCartToken: Bool,
        attempt: Int = 0
    ) async throws -> T {
        try await multipartRequest(
            path: path,
            fields: fields,
            files: [
                MultipartUploadFile(
                    fieldName: fileFieldName,
                    filename: filename,
                    mimeType: mimeType,
                    data: fileData
                )
            ],
            authorized: authorized,
            attachCartToken: attachCartToken,
            attempt: attempt
        )
    }

    func multipartRequest<T: Decodable>(
        path: String,
        fields: [String: String],
        files: [MultipartUploadFile],
        authorized: Bool,
        attachCartToken: Bool,
        attempt: Int = 0
    ) async throws -> T {
        let boundary = "Boundary-\(UUID().uuidString)"
        let (data, response) = try await rawMultipartRequest(
            path: path,
            fields: fields,
            files: files,
            boundary: boundary,
            authorized: authorized,
            attachCartToken: attachCartToken
        )

        let http = try validatedHTTPResponse(from: response)
        captureCartToken(from: http)

        if let retryValue = try await retryAfterUnauthorized(
            statusCode: http.statusCode,
            authorized: authorized,
            attempt: attempt,
            action: {
                try await multipartRequest(
                    path: path,
                    fields: fields,
                    files: files,
                    authorized: authorized,
                    attachCartToken: attachCartToken,
                    attempt: attempt + 1
                ) as T
            }
        ) {
            return retryValue
        }

        if let retryValue = try await retryAfterMultipartCsrfFailure(
            statusCode: http.statusCode,
            attempt: attempt,
            data: data,
            action: {
                try await multipartRequest(
                    path: path,
                    fields: fields,
                    files: files,
                    authorized: authorized,
                    attachCartToken: attachCartToken,
                    attempt: attempt + 1
                ) as T
            }
        ) {
            return retryValue
        }

        try throwIfHTTPError(statusCode: http.statusCode, data: data)
        return try decodeEnvelope(T.self, from: data)
    }

    func rawMultipartRequest(
        path: String,
        fields: [String: String],
        files: [MultipartUploadFile],
        boundary: String,
        authorized: Bool,
        attachCartToken: Bool
    ) async throws -> (Data, URLResponse) {
        let url = baseURL.appendingPathComponent(path)
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("multipart/form-data; boundary=\(boundary)", forHTTPHeaderField: "Content-Type")

        try await applySecurityHeaders(to: &request, path: path, method: "POST", attachCartToken: attachCartToken)
        request.httpBody = buildMultipartBody(fields: fields, files: files, boundary: boundary)

        do {
            return try await session.data(for: request)
        } catch {
            throw APIError.transport(error)
        }
    }

    func buildMultipartBody(
        fields: [String: String],
        files: [MultipartUploadFile],
        boundary: String
    ) -> Data {
        var body = Data()
        let lineBreak = "\r\n"

        for key in fields.keys.sorted() {
            let value = fields[key] ?? ""
            body.append("--\(boundary)\(lineBreak)")
            body.append("Content-Disposition: form-data; name=\"\(key)\"\(lineBreak)\(lineBreak)")
            body.append("\(value)\(lineBreak)")
        }

        for file in files {
            body.append("--\(boundary)\(lineBreak)")
            body.append("Content-Disposition: form-data; name=\"\(file.fieldName)\"; filename=\"\(file.filename)\"\(lineBreak)")
            body.append("Content-Type: \(file.mimeType)\(lineBreak)\(lineBreak)")
            body.append(file.data)
            body.append(lineBreak)
        }

        body.append("--\(boundary)--\(lineBreak)")
        return body
    }
}

private extension Data {
    mutating func append(_ string: String) {
        if let data = string.data(using: .utf8) {
            append(data)
        }
    }
}
