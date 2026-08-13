import SwiftUI

struct TradeInContactSection: View {
    @ObservedObject var viewModel: TradeInViewModel

    var body: some View {
        Section("Contact") {
            TextField("Prénom", text: $viewModel.firstName)
                .textInputAutocapitalization(.words)
            TextField("Nom", text: $viewModel.lastName)
                .textInputAutocapitalization(.words)
            TextField("E-mail", text: $viewModel.email)
                .keyboardType(.emailAddress)
                .textInputAutocapitalization(.never)
                .autocorrectionDisabled()
            TextField("Téléphone", text: $viewModel.phone)
                .keyboardType(.phonePad)
        }
    }
}
