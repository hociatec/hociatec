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
    @State private var successMessage: String?
    @State private var shouldDismissAfterSuccess = false
    @Environment(\.dismiss) private var dismiss
    
    let genders = ["homme", "femme", "autre"]
    
    var body: some View {
        Form {
            Section {
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
            
            Section {
                TextField("Email", text: $email)
                    .keyboardType(.emailAddress)
                    .textInputAutocapitalization(.never)
                    .autocorrectionDisabled()
                SecureField("Mot de passe", text: $password)
                SecureField("Confirmer mot de passe", text: $confirmPassword)
            }
            
            Section {
                Button("Créer mon compte") {
                    errorMessage = nil
                    successMessage = nil
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
                            shouldDismissAfterSuccess = true
                            successMessage = account.statusMessage ?? "Compte créé."
                            account.statusMessage = nil
                        } else {
                            errorMessage = account.error
                            account.error = nil
                        }
                    }
                }
            }
        }
        .navigationTitle("Inscription")
        .feedbackDialog(error: $errorMessage, success: $successMessage) {
            if shouldDismissAfterSuccess {
                shouldDismissAfterSuccess = false
                dismiss()
            }
        }
    }
}
