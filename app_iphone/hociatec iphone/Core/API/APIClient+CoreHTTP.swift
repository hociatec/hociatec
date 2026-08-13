import Foundation

extension APIClient {
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

        let http = try validatedHTTPResponse(from: response)
        logRequest(method: method, url: http.url?.absoluteString ?? path, statusCode: http.statusCode)
        captureCartToken(from: http)

        if let retryValue = try await retryAfterUnauthorized(
            statusCode: http.statusCode,
            authorized: authorized,
            attempt: attempt,
            action: {
                try await request(
                    path: path,
                    method: method,
                    query: query,
                    body: body,
                    authorized: authorized,
                    attachCartToken: attachCartToken,
                    attempt: attempt + 1
                ) as T
            }
        ) {
            return retryValue
        }

        if let retryValue = try await retryAfterCsrfFailure(
            statusCode: http.statusCode,
            method: method,
            attempt: attempt,
            data: data,
            action: {
                try await request(
                    path: path,
                    method: method,
                    query: query,
                    body: body,
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

    func rawRequest(
        path: String,
        method: String,
        query: [URLQueryItem]? = nil,
        body: [String: Any]? = nil,
        authorized: Bool,
        attachCartToken: Bool
    ) async throws -> (Data, URLResponse) {
        let url = try makeURL(path: path, query: query)
        var request = URLRequest(url: url)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        logRequest(method: method, url: url.absoluteString, statusCode: nil)

        if let body {
            do {
                request.httpBody = try JSONSerialization.data(withJSONObject: body, options: [])
            } catch {
                throw APIError.transport(error)
            }
            request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        }

        try await applySecurityHeaders(to: &request, path: path, method: method, attachCartToken: attachCartToken)

        do {
            return try await session.data(for: request)
        } catch {
            throw APIError.transport(error)
        }
    }

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

        let http = try validatedHTTPResponse(from: response)
        captureCartToken(from: http)

        if let _: Void = try await retryAfterUnauthorized(
            statusCode: http.statusCode,
            authorized: authorized,
            attempt: attempt,
            action: {
                try await send(
                    path: path,
                    method: method,
                    query: query,
                    body: body,
                    authorized: authorized,
                    attachCartToken: attachCartToken,
                    attempt: attempt + 1
                )
            }
        ) {
            return
        }

        if let _: Void = try await retryAfterCsrfFailure(
            statusCode: http.statusCode,
            method: method,
            attempt: attempt,
            data: data,
            action: {
                try await send(
                    path: path,
                    method: method,
                    query: query,
                    body: body,
                    authorized: authorized,
                    attachCartToken: attachCartToken,
                    attempt: attempt + 1
                )
            }
        ) {
            return
        }

        try throwIfHTTPError(statusCode: http.statusCode, data: data)
    }

    func download(
        path: String,
        method: String = "GET",
        query: [URLQueryItem]? = nil,
        body: [String: Any]? = nil,
        authorized: Bool = false,
        attachCartToken: Bool = false,
        attempt: Int = 0
    ) async throws -> Data {
        let (data, response) = try await rawRequest(
            path: path,
            method: method,
            query: query,
            body: body,
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
                try await download(
                    path: path,
                    method: method,
                    query: query,
                    body: body,
                    authorized: authorized,
                    attachCartToken: attachCartToken,
                    attempt: attempt + 1
                )
            }
        ) {
            return retryValue
        }

        try throwIfHTTPError(statusCode: http.statusCode, data: data)
        return data
    }
}
