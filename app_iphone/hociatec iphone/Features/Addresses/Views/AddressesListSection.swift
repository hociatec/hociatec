import SwiftUI

struct AddressesListSection: View {
    @ObservedObject var account: AccountViewModel

    var body: some View {
        if account.addresses.isEmpty {
            Text("Aucune adresse disponible")
                .foregroundColor(.secondary)
        } else {
            ForEach(account.addresses) { address in
                AddressRow(account: account, address: address)
            }
        }
    }
}

private struct AddressRow: View {
    @ObservedObject var account: AccountViewModel
    let address: UserAddress

    var body: some View {
        VStack(alignment: .leading) {
            Text(address.label)
                .font(.headline)
            Text(address.address)
            Text("\(address.postalCode) \(address.city)")
                .font(.subheadline)
                .foregroundColor(.secondary)
            if !address.isDefault {
                Button("Définir par défaut") {
                    Task {
                        if let id = address.id {
                            await account.makeDefaultAddress(id: id)
                        }
                    }
                }
                .disabled(account.isLoading)
                .buttonStyle(BorderlessButtonStyle())
                .padding(.top, 4)
            }
        }
        .swipeActions(edge: .trailing, allowsFullSwipe: true) {
            Button(role: .destructive) {
                Task {
                    if let id = address.id {
                        await account.deleteAddress(id: id)
                    }
                }
            } label: {
                Label("Supprimer", systemImage: "trash")
            }
            .disabled(account.isLoading)
        }
        .padding(.vertical, 4)
    }
}
