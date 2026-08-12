import SwiftUI

struct RegisterView: View {
    @ObservedObject var account: AccountViewModel
    
    @State private var firstName = ""
    @State private var lastName = ""
    @State private var email = ""
    @State private var password = ""
    @State private var confirmPassword = ""
    @State private var birthDate = Date()
    @State private var phoneNumber = ""
    @State private var gender = "autre"
    @State private var errorMessage: String?
    @Environment(\.dismiss) private var dismiss
    
    let genders = ["homme", "femme", "autre"]
    
    var body: some View {
        Form {
            Section(header: Text("Informations personnelles")) {
                TextField("Prénom", text: $firstName)
                TextField("Nom", text: $lastName)
                DatePicker("Date de naissance", selection: $birthDate, displayedComponents: .date)
                Picker("Genre", selection: $gender) {
                    ForEach(genders, id: \.self) { value in
                        Text(value.capitalized).tag(value)
                    }
                }
                TextField("Téléphone", text: $phoneNumber)
                    .keyboardType(.phonePad)
            }
            
            Section(header: Text("Connexion")) {
                TextField("Email", text: $email)
                    .keyboardType(.emailAddress)
                    .autocapitalization(.none)
                SecureField("Mot de passe", text: $password)
                SecureField("Confirmer mot de passe", text: $confirmPassword)
            }
            
            if let errorMessage = errorMessage {
                Section {
                    Text(errorMessage)
                        .foregroundColor(.red)
                }
            }
            
            Section {
                Button("Créer mon compte") {
                    errorMessage = nil
                    guard password == confirmPassword else {
                        errorMessage = "Les mots de passe ne correspondent pas."
                        return
                    }
                    Task {
                        let success = await account.register(
                            firstName: firstName,
                            lastName: lastName,
                            email: email,
                            password: password,
                            confirmPassword: confirmPassword,
                            birthDate: birthDate,
                            phoneNumber: phoneNumber,
                            gender: gender
                        )
                        if success {
                            dismiss()
                        } else {
                            errorMessage = account.error
                        }
                    }
                }
            }
        }
        .navigationTitle("Créer un compte")
    }
}
