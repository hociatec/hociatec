import SwiftUI

struct AddressCreateSection: View {
    @ObservedObject var account: AccountViewModel
    @Binding var draft: AddressCreateDraft

    var body: some View {
        Section {
            TextField("Libellé", text: $draft.label)
                .disabled(account.isLoading)
            TextField("Adresse", text: $draft.address)
                .disabled(account.isLoading)
            TextField("Code postal", text: $draft.postalCode)
                .disabled(account.isLoading)
            TextField("Ville", text: $draft.city)
                .disabled(account.isLoading)
            Toggle("Défaut", isOn: $draft.isDefault)
                .disabled(account.isLoading)
            Button("Ajouter") {
                Task {
                    await account.addAddress(
                        label: draft.label,
                        address: draft.address,
                        postalCode: draft.postalCode,
                        city: draft.city,
                        isDefault: draft.isDefault
                    )
                    draft.reset()
                }
            }
            .disabled(account.isLoading || !draft.isValid)
        }
    }
}
