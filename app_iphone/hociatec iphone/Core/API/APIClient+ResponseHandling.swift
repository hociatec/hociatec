import Foundation

extension APIClient {
    func makeURL(path: String, query: [URLQueryItem]?) throws -> URL {
        guard var components = URLComponents(url: baseURL.appendingPathComponent(path), resolvingAgainstBaseURL: false) else {
            throw APIError.invalidResponse
        }

        if let query {
            components.queryItems = query
        }

        guard let url = components.url else {
            throw APIError.invalidResponse
        }

        return url
    }

    func validatedHTTPResponse(from response: URLResponse) throws -> HTTPURLResponse {
        guard let http = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }
        return http
    }

    func decodeEnvelope<T: Decodable>(_ type: T.Type, from data: Data) throws -> T {
        do {
            let envelope = try decoder.decode(APIEnvelope<T>.self, from: data)
            return envelope.data
        } catch {
            throw APIError.decoding
        }
    }

    func throwIfHTTPError(statusCode: Int, data: Data) throws {
        guard !(200..<300).contains(statusCode) else { return }

        if let errorPayload = try? decoder.decode(APIErrorPayload.self, from: data) {
            throw APIError.httpStatus(statusCode, errorPayload.message ?? "Erreur \(statusCode)")
        }

        throw APIError.httpStatus(statusCode, "Erreur \(statusCode)")
    }

    func retryAfterUnauthorized<T>(
        statusCode: Int,
        authorized: Bool,
        attempt: Int,
        action: () async throws -> T
    ) async throws -> T? {
        guard statusCode == 401, authorized, attempt == 0 else {
            return nil
        }

        guard await refreshAuthTokenIfPossible() else {
            return nil
        }

        return try await action()
    }

    func retryAfterCsrfFailure<T>(
        statusCode: Int,
        method: String,
        attempt: Int,
        data: Data,
        action: () async throws -> T
    ) async throws -> T? {
        guard statusCode == 403,
              attempt == 0,
              methodRequiresCsrf(method),
              let errorPayload = try? decoder.decode(APIErrorPayload.self, from: data),
              (errorPayload.message ?? "").localizedCaseInsensitiveContains("csrf") else {
            return nil
        }

        sessionStore.csrfToken = nil
        return try await action()
    }

    func retryAfterMultipartCsrfFailure<T>(
        statusCode: Int,
        attempt: Int,
        data: Data,
        action: () async throws -> T
    ) async throws -> T? {
        guard statusCode == 403,
              attempt == 0,
              let errorPayload = try? decoder.decode(APIErrorPayload.self, from: data),
              (errorPayload.message ?? "").localizedCaseInsensitiveContains("csrf") else {
            return nil
        }

        sessionStore.csrfToken = nil
        return try await action()
    }

    func logRequest(method: String, url: String, statusCode: Int?) {
#if DEBUG
        if let statusCode {
            print("[API] \(method) \(url) -> \(statusCode)")
        } else {
            print("[API] \(method) \(url)")
        }
#endif
    }
}
