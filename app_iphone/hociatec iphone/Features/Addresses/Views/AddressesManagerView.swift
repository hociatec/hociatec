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
                VStack(alignment: .leading, spacing: 10) {
                    NavigationLink(destination: AddressDetailView(address: addr).environmentObject(account)) {
                        VStack(alignment: .leading, spacing: 6) {
                            HStack(alignment: .firstTextBaseline, spacing: 8) {
                                Text(addr.label.isEmpty ? "Adresse" : addr.label)
                                    .font(.headline)
                                Text(addr.typeLabel)
                                    .font(.caption.weight(.semibold))
                                    .padding(.horizontal, 8)
                                    .padding(.vertical, 4)
                                    .background(Capsule().fill(Color.blue.opacity(0.1)))
                                    .foregroundStyle(.blue)
                                if addr.isDefault {
                                    Label("Par défaut", systemImage: "star.fill")
                                        .font(.caption)
                                        .foregroundStyle(.yellow)
                                        .accessibilityLabel("Adresse par défaut")
                                }
                            }

                            ForEach(addr.formattedLines, id: \.self) { line in
                                Text(line)
                                    .foregroundStyle(.secondary)
                            }
                        }
                    }
                    .accessibilityHint("Afficher les détails de l’adresse")

                    HStack {
                        if !addr.isDefault, let id = addr.id {
                            Button {
                                Task { await account.makeDefaultAddress(id: id) }
                            } label: {
                                Label("Par défaut", systemImage: "star")
                            }
                            .buttonStyle(.bordered)
                            .tint(.blue)
                        }

                        Spacer()

                        Button {
                            editSheet = EditSheet(address: addr)
                        } label: {
                            Label("Modifier", systemImage: "pencil")
                        }
                        .buttonStyle(.borderedProminent)
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
