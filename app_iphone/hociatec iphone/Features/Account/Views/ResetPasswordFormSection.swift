import SwiftUI

struct ResetPasswordFormSection: View {
    @Binding var token: String
    @Binding var password: String
    @Binding var confirmPassword: String
    let allowsTokenEditing: Bool

    var body: some View {
        Section {
            if allowsTokenEditing {
                TextField("Token", text: $token)
                    .textInputAutocapitalization(.never)
                    .autocorrectionDisabled()
            } else {
                Label("Lien sécurisé détecté", systemImage: "checkmark.shield")
                    .foregroundStyle(.secondary)
            }
            SecureField("Nouveau mot de passe", text: $password)
            SecureField("Confirmation", text: $confirmPassword)
            Text("Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.")
                .font(.footnote)
                .foregroundStyle(.secondary)
        }
    }
}
