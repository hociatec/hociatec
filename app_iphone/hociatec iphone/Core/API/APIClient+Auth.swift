import Foundation

extension APIClient {
    func login(email: String, password: String) async throws -> String {
        let payload = ["email": email, "password": password]
        let (data, response) = try await rawRequest(
            path: "api/auth/login",
            method: "POST",
            body: payload,
            authorized: false,
            attachCartToken: false
        )

        guard let http = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }

        if !(200..<300).contains(http.statusCode) {
            if let errorPayload = try? decoder.decode(APIErrorPayload.self, from: data) {
                throw APIError.httpStatus(http.statusCode, errorPayload.message ?? "Identifiants incorrects.")
            }
            throw APIError.httpStatus(http.statusCode, "Identifiants incorrects.")
        }

        sessionStore.jwtToken = authenticatedSessionMarker
        return authenticatedSessionMarker
    }

    func profile() async throws -> UserProfile {
        let authSession: AuthSessionData = try await request(
            path: "api/auth/me",
            authorized: true
        )
        guard let profile = authSession.profile else {
            sessionStore.clearSession()
            throw APIError.httpStatus(401, "Session expirée. Veuillez vous reconnecter.")
        }
        sessionStore.profile = profile
        return profile
    }

    func logout() async {
        do {
            try await send(
                path: "api/auth/logout",
                method: "POST",
                authorized: false,
                attachCartToken: false
            )
        } catch {
        }
        sessionStore.clearSession()
    }

    func updateProfile(
        firstName: String,
        lastName: String,
        email: String,
        address: String?,
        postalCode: String?,
        city: String?,
        birthDate: String,
        phoneNumber: String,
        gender: String
    ) async throws -> UserProfile {
        var body: [String: Any] = [
            "firstName": firstName,
            "lastName": lastName,
            "email": email,
            "birthDate": birthDate,
            "phoneNumber": phoneNumber
        ]
        if let address { body["address"] = address }
        if let postalCode { body["postalCode"] = postalCode }
        if let city { body["city"] = city }
        body["gender"] = gender

        let profile: UserProfile = try await request(
            path: "api/auth/profile",
            method: "PUT",
            body: body,
            authorized: true,
            attachCartToken: false
        )
        sessionStore.profile = profile
        return profile
    }

    func deleteAccount() async throws {
        let _: APIEnvelope<APIErrorPayload?> = try await request(
            path: "api/auth/profile",
            method: "DELETE",
            authorized: true,
            attachCartToken: false
        )
        sessionStore.clearSession()
    }

    func register(
        email: String,
        password: String,
        confirmPassword: String,
        firstName: String,
        lastName: String,
        birthDate: String,
        phoneNumber: String,
        gender: String
    ) async throws {
        let body: [String: Any] = [
            "email": email,
            "password": password,
            "confirmPassword": confirmPassword,
            "firstName": firstName,
            "lastName": lastName,
            "birthDate": birthDate,
            "phoneNumber": phoneNumber,
            "gender": gender
        ]

        try await send(
            path: "api/auth/register",
            method: "POST",
            body: body,
            authorized: false,
            attachCartToken: false
        )
    }

    func verifyAccount(token: String) async throws {
        let encodedToken = token.addingPercentEncoding(withAllowedCharacters: .urlPathAllowed) ?? token
        try await send(
            path: "api/auth/verify/\(encodedToken)",
            method: "GET",
            authorized: false,
            attachCartToken: false
        )
    }

    func requestPasswordReset(email: String) async throws {
        try await send(
            path: "api/auth/password-reset/request",
            method: "POST",
            body: ["email": email],
            authorized: false,
            attachCartToken: false
        )
    }

    func resetPassword(token: String, password: String, confirmPassword: String) async throws {
        let encodedToken = token.addingPercentEncoding(withAllowedCharacters: .urlPathAllowed) ?? token
        try await send(
            path: "api/auth/password-reset/reset/\(encodedToken)",
            method: "POST",
            body: [
                "password": password,
                "confirmPassword": confirmPassword
            ],
            authorized: false,
            attachCartToken: false
        )
    }
}
