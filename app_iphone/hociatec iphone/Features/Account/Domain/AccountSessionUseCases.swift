import Foundation

struct LoginUseCase {
    let repository: AccountRepository

    func execute(email: String, password: String, rememberSession: Bool) async throws {
        try await repository.login(email: email, password: password, rememberSession: rememberSession)
    }
}

struct LogoutUseCase {
    let repository: AccountRepository

    func execute() async {
        await repository.logout()
    }
}

struct RegisterAccountUseCase {
    let repository: AccountRepository

    func execute(
        email: String,
        password: String,
        confirmPassword: String,
        firstName: String,
        lastName: String,
        birthDate: String,
        phoneNumber: String,
        gender: String
    ) async throws {
        try await repository.register(
            email: email,
            password: password,
            confirmPassword: confirmPassword,
            firstName: firstName,
            lastName: lastName,
            birthDate: birthDate,
            phoneNumber: phoneNumber,
            gender: gender
        )
    }
}

struct DeleteAccountUseCase {
    let repository: AccountRepository

    func execute() async throws {
        try await repository.deleteAccount()
    }
}

struct RevokeAllSessionsUseCase {
    let repository: AccountRepository

    func execute() async throws {
        try await repository.revokeAllSessions()
    }
}
