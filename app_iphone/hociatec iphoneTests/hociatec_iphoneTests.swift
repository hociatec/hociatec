import Foundation
import Testing
@testable import hociatec_iphone

@MainActor
struct hociatec_iphoneTests {
    @Test
    func benignCancellationDetectsTransportWrappedURLCancellation() {
        let wrappedCancellation = APIError.transport(
            NSError(domain: NSURLErrorDomain, code: NSURLErrorCancelled)
        )

        #expect(wrappedCancellation.isBenignCancellation)
    }

    @Test
    func loginSuccessDoesNotSurfaceAddressLoadingFailure() async throws {
        let session = makeCleanSession()
        let expectedProfile = sampleProfile()
        let repository = MockAccountRepository()
        repository.onLogin = {
            session.jwtToken = "jwt-test"
        }
        repository.profileToReturn = expectedProfile
        repository.addressesError = SampleError(message: "Adresse indisponible")

        let viewModel = AccountViewModel(
            useCases: makeUseCases(repository: repository),
            session: session
        )
        viewModel.email = expectedProfile.email
        viewModel.password = "Secret123"

        await viewModel.login()

        #expect(viewModel.error == nil)
        #expect(viewModel.profile?.id == expectedProfile.id)
        #expect(viewModel.isLoggedIn)
        #expect(viewModel.addresses.isEmpty)
    }

    @Test
    func loadAddressesCanFailSilentlyWhenRequested() async throws {
        let session = makeCleanSession()
        session.jwtToken = "jwt-test"
        session.profile = sampleProfile()

        let repository = MockAccountRepository()
        repository.addressesError = SampleError(message: "Erreur adresses")

        let viewModel = AccountViewModel(
            useCases: makeUseCases(repository: repository),
            session: session
        )

        await viewModel.loadAddresses(reportErrors: false)

        #expect(viewModel.error == nil)
        #expect(viewModel.addresses.isEmpty)
    }

    @Test
    func addressMutationReturnsLocalErrorWithoutPollutingGlobalAccountError() async throws {
        let session = makeCleanSession()
        session.jwtToken = "jwt-test"
        session.profile = sampleProfile()

        let repository = MockAccountRepository()
        repository.createAddressError = SampleError(message: "Création impossible")

        let viewModel = AccountViewModel(
            useCases: makeUseCases(repository: repository),
            session: session
        )

        let error = await viewModel.addAddress(
            type: "personal",
            label: "Maison",
            address: "1 rue de Paris",
            addressComplement: nil,
            postalCode: "75001",
            company: nil,
            companySiren: nil,
            companyVatNumber: nil,
            city: "Paris",
            isDefault: true,
            reportErrors: false
        )

        #expect(error == "Création impossible")
        #expect(viewModel.error == nil)
    }

    private func makeUseCases(repository: AccountRepository) -> AccountUseCases {
        AccountUseCases(
            login: LoginUseCase(repository: repository),
            logout: LogoutUseCase(repository: repository),
            loadProfile: LoadAccountProfileUseCase(repository: repository),
            updateProfile: UpdateAccountProfileUseCase(repository: repository),
            deleteAccount: DeleteAccountUseCase(repository: repository),
            register: RegisterAccountUseCase(repository: repository),
            loadAddresses: LoadAccountAddressesUseCase(repository: repository),
            createAddress: CreateAccountAddressUseCase(repository: repository),
            updateAddress: UpdateAccountAddressUseCase(repository: repository),
            deleteAddress: DeleteAccountAddressUseCase(repository: repository),
            setDefaultAddress: SetDefaultAccountAddressUseCase(repository: repository)
        )
    }

    private func makeCleanSession() -> SessionStore {
        let session = SessionStore()
        session.clearSession()
        session.cartToken = nil
        session.loginEmail = nil
        session.profile = nil
        session.rememberSession = false
        return session
    }

    private func sampleProfile() -> UserProfile {
        UserProfile(
            id: 42,
            email: "test@hociatec.fr",
            firstName: "Test",
            lastName: "Client",
            roles: ["ROLE_USER"],
            address: nil,
            postalCode: nil,
            city: nil,
            birthDate: "1990-01-01",
            phoneNumber: "0600000000",
            gender: "autre",
            addresses: nil
        )
    }
}

private struct SampleError: LocalizedError {
    let message: String

    var errorDescription: String? { message }
}

@MainActor
private final class MockAccountRepository: AccountRepository {
    var onLogin: (() -> Void)?
    var profileToReturn = UserProfile(
        id: 1,
        email: "default@hociatec.fr",
        firstName: "Default",
        lastName: "User",
        roles: ["ROLE_USER"],
        address: nil,
        postalCode: nil,
        city: nil,
        birthDate: "1990-01-01",
        phoneNumber: "0600000000",
        gender: "autre",
        addresses: nil
    )
    var addressesToReturn: [UserAddress] = []
    var addressesError: Error?
    var createAddressError: Error?

    func login(email: String, password: String) async throws {
        onLogin?()
    }

    func logout() async {}

    func fetchProfile() async throws -> UserProfile {
        profileToReturn
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
        profileToReturn
    }

    func deleteAccount() async throws {}

    func register(
        email: String,
        password: String,
        confirmPassword: String,
        firstName: String,
        lastName: String,
        birthDate: String,
        phoneNumber: String,
        gender: String
    ) async throws {}

    func listAddresses() async throws -> [UserAddress] {
        if let addressesError {
            throw addressesError
        }

        return addressesToReturn
    }

    func createAddress(
        type: String,
        label: String,
        address: String,
        addressComplement: String?,
        postalCode: String,
        company: String?,
        companySiren: String?,
        companyVatNumber: String?,
        city: String,
        isDefault: Bool
    ) async throws {
        if let createAddressError {
            throw createAddressError
        }
    }

    func updateAddress(
        id: Int,
        type: String,
        label: String,
        address: String,
        addressComplement: String?,
        postalCode: String,
        company: String?,
        companySiren: String?,
        companyVatNumber: String?,
        city: String,
        isDefault: Bool
    ) async throws {}

    func deleteAddress(id: Int) async throws {}

    func setDefaultAddress(id: Int) async throws {}
}
