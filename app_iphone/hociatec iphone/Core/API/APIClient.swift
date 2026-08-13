import Foundation
import Combine

/// Client HTTP léger pour l’API hociatec.fr.
final class APIClient: ObservableObject {
    let baseURL = URL(string: "https://api.hociatec.fr")!
    let authenticatedSessionMarker = "__cookie_session__"

    let session: URLSession
    let decoder: JSONDecoder
    let sessionStore: SessionStore
    let isoFormatter: ISO8601DateFormatter

    init(sessionStore: SessionStore, session: URLSession = .shared) {
        self.sessionStore = sessionStore
        self.session = session

        let decoder = JSONDecoder()
        decoder.dateDecodingStrategy = .iso8601
        self.decoder = decoder

        let formatter = ISO8601DateFormatter()
        formatter.formatOptions = [.withInternetDateTime]
        formatter.timeZone = .init(secondsFromGMT: 0)
        self.isoFormatter = formatter
    }

    func assetURL(for path: String?) -> URL? {
        guard var path, !path.isEmpty else { return nil }

        if path.hasPrefix("http") {
            return URL(string: path)
        }

        if path.hasPrefix("/") {
            path.removeFirst()
        }

        return baseURL.appendingPathComponent(path)
    }

    // MARK: - Core HTTP helpers

    func request<T: Decodable>(
        path: String,
        method: String = "GET",
        query: [URLQueryItem]? = nil,
        body: [String: Any]? = nil,
        authorized: Bool = false,
        attachCartToken: Bool = false,
        attempt: Int = 0
    ) async throws -> T {
        let (data, response) = try await rawRequest(
            path: path,
            method: method,
            query: query,
            body: body,
            authorized: authorized,
            attachCartToken: attachCartToken
        )

        guard let http = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }

#if DEBUG
        print("[API] \(method) \(http.url?.absoluteString ?? path) -> \(http.statusCode)")
#endif

        captureCartToken(from: http)
        
        if http.statusCode == 401, authorized, attempt == 0 {
            if await refreshAuthTokenIfPossible() {
                return try await request(
                    path: path,
                    method: method,
                    query: query,
                    body: body,
                    authorized: authorized,
                    attachCartToken: attachCartToken,
                    attempt: attempt + 1
                )
            }
        }

        if !(200..<300).contains(http.statusCode) {
            if let errorPayload = try? decoder.decode(APIErrorPayload.self, from: data) {
                if http.statusCode == 403,
                   attempt == 0,
                   methodRequiresCsrf(method),
                   (errorPayload.message ?? "").localizedCaseInsensitiveContains("csrf") {
                    sessionStore.csrfToken = nil
                    return try await request(
                        path: path,
                        method: method,
                        query: query,
                        body: body,
                        authorized: authorized,
                        attachCartToken: attachCartToken,
                        attempt: attempt + 1
                    )
                }
                throw APIError.httpStatus(http.statusCode, errorPayload.message ?? "Erreur \(http.statusCode)")
            }

            throw APIError.httpStatus(http.statusCode, "Erreur \(http.statusCode)")
        }

        do {
            let envelope = try decoder.decode(APIEnvelope<T>.self, from: data)
            return envelope.data
        } catch {
            throw APIError.decoding
        }
    }

    func rawRequest(
        path: String,
        method: String,
        query: [URLQueryItem]? = nil,
        body: [String: Any]? = nil,
        authorized: Bool,
        attachCartToken: Bool
    ) async throws -> (Data, URLResponse) {
        guard var components = URLComponents(url: baseURL.appendingPathComponent(path), resolvingAgainstBaseURL: false) else {
            throw APIError.invalidResponse
        }

        if let query {
            components.queryItems = query
        }

        guard let url = components.url else {
            throw APIError.invalidResponse
        }

        var request = URLRequest(url: url)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Accept")

#if DEBUG
        print("[API] \(method) \(url.absoluteString)")
#endif

        if let body {
            do {
                request.httpBody = try JSONSerialization.data(withJSONObject: body, options: [])
            } catch {
                throw APIError.transport(error)
            }
            request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        }

        if requiresCsrf(path: path, method: method) {
            let csrfToken = try await currentCsrfToken()
            request.setValue(csrfToken, forHTTPHeaderField: "X-CSRF-Token")
        }

        if attachCartToken, let token = sessionStore.cartToken {
            request.setValue(token, forHTTPHeaderField: "X-Cart-Token")
        }

        do {
            return try await session.data(for: request)
        } catch {
            throw APIError.transport(error)
        }
    }

    func captureCartToken(from response: HTTPURLResponse) {
        if let headerToken = response.value(forHTTPHeaderField: "X-Cart-Token"), !headerToken.isEmpty {
            sessionStore.cartToken = headerToken
        }
    }

    func currentCsrfToken() async throws -> String {
        if let csrfToken = sessionStore.csrfToken,
           !csrfToken.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            return csrfToken
        }

        let data: CsrfTokenData = try await request(
            path: "api/csrf-token",
            authorized: false,
            attachCartToken: false
        )
        sessionStore.csrfToken = data.token
        return data.token
    }

    func requiresCsrf(path: String, method: String) -> Bool {
        guard methodRequiresCsrf(method), path.hasPrefix("api/") else {
            return false
        }

        return !["api/auth/login", "api/auth/register", "api/auth/refresh"].contains(path)
    }

    func methodRequiresCsrf(_ method: String) -> Bool {
        !["GET", "HEAD", "OPTIONS"].contains(method.uppercased())
    }
    
    func refreshAuthTokenIfPossible() async -> Bool {
        guard let credentials = sessionStore.storedCredentials else {
            return false
        }
        
        do {
            _ = try await login(email: credentials.email, password: credentials.password)
            return true
        } catch {
            sessionStore.clearSession()
            return false
        }
    }

    // MARK: - Generic sender for endpoints without envelope decoding
    func send(
        path: String,
        method: String,
        query: [URLQueryItem]? = nil,
        body: [String: Any]? = nil,
        authorized: Bool = false,
        attachCartToken: Bool = false,
        attempt: Int = 0
    ) async throws {
        let (data, response) = try await rawRequest(
            path: path,
            method: method,
            query: query,
            body: body,
            authorized: authorized,
            attachCartToken: attachCartToken
        )

        guard let http = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }

        captureCartToken(from: http)
        
        if http.statusCode == 401, authorized, attempt == 0 {
            if await refreshAuthTokenIfPossible() {
                return try await send(
                    path: path,
                    method: method,
                    query: query,
                    body: body,
                    authorized: authorized,
                    attachCartToken: attachCartToken,
                    attempt: attempt + 1
                )
            }
        }

        if !(200..<300).contains(http.statusCode) {
            if let errorPayload = try? decoder.decode(APIErrorPayload.self, from: data) {
                if http.statusCode == 403,
                   attempt == 0,
                   methodRequiresCsrf(method),
                   (errorPayload.message ?? "").localizedCaseInsensitiveContains("csrf") {
                    sessionStore.csrfToken = nil
                    return try await send(
                        path: path,
                        method: method,
                        query: query,
                        body: body,
                        authorized: authorized,
                        attachCartToken: attachCartToken,
                        attempt: attempt + 1
                    )
                }
                throw APIError.httpStatus(http.statusCode, errorPayload.message ?? "Erreur \(http.statusCode)")
            }
            throw APIError.httpStatus(http.statusCode, "Erreur \(http.statusCode)")
        }
    }

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
        let boundary = "Boundary-\(UUID().uuidString)"
        let (data, response) = try await rawMultipartRequest(
            path: path,
            fields: fields,
            fileFieldName: fileFieldName,
            filename: filename,
            mimeType: mimeType,
            fileData: fileData,
            boundary: boundary,
            authorized: authorized,
            attachCartToken: attachCartToken
        )

        guard let http = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }

        captureCartToken(from: http)

        if http.statusCode == 401, authorized, attempt == 0 {
            if await refreshAuthTokenIfPossible() {
                return try await multipartRequest(
                    path: path,
                    fields: fields,
                    fileFieldName: fileFieldName,
                    filename: filename,
                    mimeType: mimeType,
                    fileData: fileData,
                    authorized: authorized,
                    attachCartToken: attachCartToken,
                    attempt: attempt + 1
                )
            }
        }

        if !(200..<300).contains(http.statusCode) {
            if let errorPayload = try? decoder.decode(APIErrorPayload.self, from: data) {
                if http.statusCode == 403,
                   attempt == 0,
                   (errorPayload.message ?? "").localizedCaseInsensitiveContains("csrf") {
                    sessionStore.csrfToken = nil
                    return try await multipartRequest(
                        path: path,
                        fields: fields,
                        fileFieldName: fileFieldName,
                        filename: filename,
                        mimeType: mimeType,
                        fileData: fileData,
                        authorized: authorized,
                        attachCartToken: attachCartToken,
                        attempt: attempt + 1
                    )
                }
                throw APIError.httpStatus(http.statusCode, errorPayload.message ?? "Erreur \(http.statusCode)")
            }
            throw APIError.httpStatus(http.statusCode, "Erreur \(http.statusCode)")
        }

        do {
            let envelope = try decoder.decode(APIEnvelope<T>.self, from: data)
            return envelope.data
        } catch {
            throw APIError.decoding
        }
    }

    func rawMultipartRequest(
        path: String,
        fields: [String: String],
        fileFieldName: String,
        filename: String,
        mimeType: String,
        fileData: Data,
        boundary: String,
        authorized: Bool,
        attachCartToken: Bool
    ) async throws -> (Data, URLResponse) {
        let url = baseURL.appendingPathComponent(path)
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("multipart/form-data; boundary=\(boundary)", forHTTPHeaderField: "Content-Type")

        if requiresCsrf(path: path, method: "POST") {
            let csrfToken = try await currentCsrfToken()
            request.setValue(csrfToken, forHTTPHeaderField: "X-CSRF-Token")
        }

        if attachCartToken, let token = sessionStore.cartToken {
            request.setValue(token, forHTTPHeaderField: "X-Cart-Token")
        }

        request.httpBody = buildMultipartBody(
            fields: fields,
            fileFieldName: fileFieldName,
            filename: filename,
            mimeType: mimeType,
            fileData: fileData,
            boundary: boundary
        )

        do {
            return try await session.data(for: request)
        } catch {
            throw APIError.transport(error)
        }
    }

    func buildMultipartBody(
        fields: [String: String],
        fileFieldName: String,
        filename: String,
        mimeType: String,
        fileData: Data,
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

        body.append("--\(boundary)\(lineBreak)")
        body.append("Content-Disposition: form-data; name=\"\(fileFieldName)\"; filename=\"\(filename)\"\(lineBreak)")
        body.append("Content-Type: \(mimeType)\(lineBreak)\(lineBreak)")
        body.append(fileData)
        body.append(lineBreak)
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
