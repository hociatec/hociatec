import SwiftUI

struct AccountActivationView: View {
    let service: AccountServing
    let token: String

    @State private var isLoading = true
    @State private var dialog: FeedbackDialogState?

    var body: some View {
        List {
            Section {
                if isLoading {
                    ProgressView("Vérification en cours...")
                        .frame(maxWidth: .infinity, alignment: .center)
                }
            }
        }
        .navigationTitle("Activation du compte")
        .task {
            await verify()
        }
        .feedbackDialog($dialog)
    }

    private func verify() async {
        guard isLoading else { return }

        do {
            try await service.verifyAccount(token: token)
            dialog = .success("Votre compte a été activé avec succès.")
        } catch {
            dialog = .error(error.localizedDescription)
        }

        isLoading = false
    }
}
