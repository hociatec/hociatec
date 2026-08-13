import Foundation

struct AccountUseCases {
    let login: LoginUseCase
    let logout: LogoutUseCase
    let loadProfile: LoadAccountProfileUseCase
    let updateProfile: UpdateAccountProfileUseCase
    let deleteAccount: DeleteAccountUseCase
    let register: RegisterAccountUseCase
    let loadAddresses: LoadAccountAddressesUseCase
    let createAddress: CreateAccountAddressUseCase
    let updateAddress: UpdateAccountAddressUseCase
    let deleteAddress: DeleteAccountAddressUseCase
    let setDefaultAddress: SetDefaultAccountAddressUseCase
}
