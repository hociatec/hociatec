import Foundation

struct LoadAccountProfileUseCase {
    let repository: AccountRepository

    func execute() async throws -> UserProfile {
        try await repository.fetchProfile()
    }
}

struct RestoreAccountProfileUseCase {
    let repository: AccountRepository

    func execute() async throws -> UserProfile? {
        try await repository.restoreProfileIfPossible()
    }
}

struct UpdateAccountProfileUseCase {
    let repository: AccountRepository

    func execute(
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
        try await repository.updateProfile(
            firstName: firstName,
            lastName: lastName,
            email: email,
            address: address,
            postalCode: postalCode,
            city: city,
            birthDate: birthDate,
            phoneNumber: phoneNumber,
            gender: gender
        )
    }
}
