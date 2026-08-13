import SwiftUI

struct AddressesView: View {
    @ObservedObject var account: AccountViewModel
    
    @State private var newLabel = ""
    @State private var newAddress = ""
    @State private var newPostalCode = ""
    @State private var newCity = ""
    @State private var newIsDefault = false
    
    var body: some View {
        List {
            if account.addresses.isEmpty {
                Text("Aucune adresse disponible")
                    .foregroundColor(.secondary)
            } else {
                ForEach(account.addresses) { address in
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
            
            Section {
                TextField("Libellé", text: $newLabel)
                    .disabled(account.isLoading)
                TextField("Adresse", text: $newAddress)
                    .disabled(account.isLoading)
                TextField("Code postal", text: $newPostalCode)
                    .disabled(account.isLoading)
                TextField("Ville", text: $newCity)
                    .disabled(account.isLoading)
                Toggle("Défaut", isOn: $newIsDefault)
                    .disabled(account.isLoading)
                Button("Ajouter") {
                    Task {
                        await account.addAddress(label: newLabel,
                                                 address: newAddress,
                                                 postalCode: newPostalCode,
                                                 city: newCity,
                                                 isDefault: newIsDefault)
                        newLabel = ""
                        newAddress = ""
                        newPostalCode = ""
                        newCity = ""
                        newIsDefault = false
                    }
                }
                .disabled(account.isLoading || newLabel.isEmpty || newAddress.isEmpty || newPostalCode.isEmpty || newCity.isEmpty)
            }
            
            if let error = account.error {
                Text(error)
                    .foregroundColor(.red)
            }
        }
        .navigationTitle("Adresses")
    }
}
