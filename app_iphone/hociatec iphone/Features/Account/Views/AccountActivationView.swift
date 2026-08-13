import SwiftUI

struct AccountActivationView: View {
    let service: AccountServing
    let token: String

    @State private var isLoading = true
    @State private var isSuccess = false
    @State private var message = ""

    var body: some View {
        List {
            Section {
                if isLoading {
                    ProgressView("Vérification en cours...")
                        .frame(maxWidth: .infinity, alignment: .center)
                } else {
                    Text(message)
                        .foregroundStyle(isSuccess ? .green : .red)
                }
            }
        }
        .navigationTitle("Activation du compte")
        .task {
            await verify()
        }
    }

    private func verify() async {
        guard isLoading else { return }

        do {
            try await service.verifyAccount(token: token)
            isSuccess = true
            message = "Votre compte a été activé avec succès."
        } catch {
            isSuccess = false
            message = error.localizedDescription
        }

        isLoading = false
    }
}
