import SwiftUI

struct ProfileView: View {
    @ObservedObject var account: AccountViewModel
    
    var genders = ["homme", "femme", "autre"]
    @State private var birthDateDate: Date = Date()
    
    var body: some View {
        Form {
            Section {
                TextField("Prénom", text: $account.firstName)
                    .textContentType(.givenName)
                    .textInputAutocapitalization(.words)
                TextField("Nom", text: $account.lastName)
                    .textContentType(.familyName)
                    .textInputAutocapitalization(.words)
                TextField("Email", text: $account.email)
                    .keyboardType(.emailAddress)
                    .textContentType(.emailAddress)
                TextField("Adresse", text: Binding(
                    get: { account.address ?? "" },
                    set: { account.address = $0.isEmpty ? nil : $0 }
                ))
                .textContentType(.fullStreetAddress)
                TextField("Code postal", text: Binding(
                    get: { account.postalCode ?? "" },
                    set: { account.postalCode = $0.isEmpty ? nil : $0 }
                ))
                .textContentType(.postalCode)
                TextField("Ville", text: Binding(
                    get: { account.city ?? "" },
                    set: { account.city = $0.isEmpty ? nil : $0 }
                ))
                .textContentType(.addressCity)
                DatePicker("Date de naissance", selection: $birthDateDate, displayedComponents: .date)
                TextField("Téléphone", text: $account.phoneNumber)
                    .keyboardType(.phonePad)
                    .textContentType(.telephoneNumber)
                Picker("Genre", selection: $account.gender) {
                    ForEach(genders, id: \.self) { gender in
                        Text(gender.capitalized).tag(gender)
                    }
                }
            }
            Section {
                Button {
                    Task { await account.updateProfile() }
                } label: {
                    HStack {
                        Text("Enregistrer")
                        if account.isLoading {
                            Spacer()
                            ProgressView()
                        }
                    }
                }
                .disabled(account.isLoading || account.firstName.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty || account.lastName.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty || account.email.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
            }
        }
        .onAppear {
            if let d = AccountViewModel.birthDateFormatter.date(from: account.birthDate) {
                birthDateDate = d
            }
        }
        .onChangeCompat(birthDateDate) { newVal in
            account.birthDate = AccountViewModel.birthDateFormatter.string(from: newVal)
        }
        .navigationTitle("Profil")
    }
}
