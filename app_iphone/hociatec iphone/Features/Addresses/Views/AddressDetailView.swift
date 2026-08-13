import SwiftUI

struct AddressDetailView: View {
    @EnvironmentObject private var account: AccountViewModel
    let address: UserAddress
    @State private var showEdit = false

    var body: some View {
        Form {
            Section {
                LabeledContent("Libellé") { Text(address.label) }
                LabeledContent("Type") { Text(address.typeLabel) }
                if address.type == "professional", let company = address.company, !company.isEmpty {
                    LabeledContent("Société") { Text(company) }
                }
                ForEach(address.formattedLines, id: \.self) { line in
                    Text(line)
                }
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
            ToolbarItem(placement: .topBarTrailing) {
                Button("Modifier") {
                    showEdit = true
                }
            }

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
        .sheet(isPresented: $showEdit) {
            NavigationStack {
                AddressFormView(address: address)
                    .environmentObject(account)
                    .toolbar {
                        ToolbarItem(placement: .cancellationAction) {
                            Button("Fermer") {
                                showEdit = false
                            }
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
