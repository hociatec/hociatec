import SwiftUI

struct AddressesManagerView: View {
    @ObservedObject var account: AccountViewModel
    @State private var showNew = false
    @State private var editSheet: EditSheet? = nil

    private struct EditSheet: Identifiable {
        let id = UUID()
        let address: UserAddress
    }

    var body: some View {
        List {
            ForEach(account.addresses.indices, id: \.self) { index in
                let addr = account.addresses[index]
                NavigationLink(destination: AddressDetailView(address: addr).environmentObject(account)) {
                    VStack(alignment: .leading, spacing: 4) {
                        Text(addr.label.isEmpty ? "Adresse" : addr.label)
                            .font(.headline)
                        Text("\(addr.address), \(addr.postalCode) \(addr.city)")
                            .foregroundStyle(.secondary)
                        if addr.isDefault {
                            Label("Par défaut", systemImage: "star.fill")
                                .font(.caption)
                                .foregroundStyle(.yellow)
                                .accessibilityLabel("Adresse par défaut")
                        }
                    }
                }
                .accessibilityHint("Afficher les détails de l’adresse")
                .swipeActions(edge: .leading) {
                    if !addr.isDefault, let id = addr.id {
                        Button {
                            Task { await account.makeDefaultAddress(id: id) }
                        } label: {
                            Label("Définir par défaut", systemImage: "star")
                        }
                        .tint(.blue)
                    }
                }
                .swipeActions(edge: .trailing) {
                    Button {
                        editSheet = EditSheet(address: addr)
                    } label: {
                        Label("Modifier", systemImage: "pencil")
                    }
                }
            }
        }
        .listStyle(.insetGrouped)
        .navigationTitle("Adresses")
        .toolbar {
            ToolbarItem(placement: .navigationBarTrailing) {
                Button {
                    showNew = true
                } label: {
                    Image(systemName: "plus")
                }
                .accessibilityLabel("Ajouter une adresse")
            }
        }
        .sheet(isPresented: $showNew) {
            NavigationStack {
                AddressFormView(address: nil)
                    .environmentObject(account)
                    .toolbar {
                        ToolbarItem(placement: .cancellationAction) {
                            Button("Annuler") {
                                showNew = false
                            }
                        }
                    }
            }
        }
        .sheet(item: $editSheet) { sheet in
            NavigationStack {
                AddressFormView(address: sheet.address)
                    .environmentObject(account)
                    .toolbar {
                        ToolbarItem(placement: .cancellationAction) {
                            Button("Fermer") {
                                editSheet = nil
                            }
                        }
                    }
            }
        }
    }
}
