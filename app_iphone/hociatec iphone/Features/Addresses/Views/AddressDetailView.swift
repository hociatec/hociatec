import SwiftUI

struct AddressDetailView: View {
    @EnvironmentObject private var account: AccountViewModel
    let address: UserAddress

    var body: some View {
        Form {
            Section {
                LabeledContent("Libellé") { Text(address.label) }
                LabeledContent("Adresse") { Text(address.address) }
                if let addressComplement = address.addressComplement, !addressComplement.isEmpty {
                    LabeledContent("Complément") { Text(addressComplement) }
                }
                LabeledContent("Code postal") { Text(address.postalCode) }
                LabeledContent("Ville") { Text(address.city) }
            }
            if address.isDefault {
                Section {
                    HStack(spacing: 8) {
                        Image(systemName: "star.fill")
                            .foregroundStyle(.yellow)
                        Text("Adresse par défaut")
                    }
                }
            }
        }
        .navigationTitle(address.label)
        .navigationBarTitleDisplayMode(.inline)
        .toolbar {
            if !address.isDefault, let id = address.id {
                ToolbarItem(placement: .topBarTrailing) {
                    Button {
                        Task { await account.makeDefaultAddress(id: id) }
                    } label: {
                        Label("Par défaut", systemImage: "star.fill")
                    }
                }
            }
        }
    }
}

#Preview {
    let container = AppContainer()
    let sample = UserAddress(id: 1, type: "personal", label: "Domicile", address: "10 Rue des Fleurs", addressComplement: "Appartement 3B", postalCode: "75001", city: "Paris", company: nil, companySiren: nil, companyVatNumber: nil, isDefault: true)
    return NavigationStack {
        AddressDetailView(address: sample)
            .environmentObject(container.account)
    }
}
