import SwiftUI

struct ContactIdentitySection: View {
    @ObservedObject var viewModel: ContactViewModel

    var body: some View {
        Section {
            LabeledContent("Nom complet") {
                TextField("", text: $viewModel.name)
                    .textInputAutocapitalization(.words)
                    .textContentType(.name)
            }
            LabeledContent("Email") {
                TextField("", text: $viewModel.email)
                    .keyboardType(.emailAddress)
                    .textInputAutocapitalization(.never)
                    .textContentType(.emailAddress)
            }
            LabeledContent("Objet") {
                TextField("", text: $viewModel.subject)
            }
        }
    }
}

struct ContactMessageSection: View {
    @ObservedObject var viewModel: ContactViewModel

    var body: some View {
        Section {
            TextEditor(text: $viewModel.message)
                .frame(minHeight: 120)
                .overlay(
                    RoundedRectangle(cornerRadius: 8)
                        .stroke(Color.secondary.opacity(0.2))
                )
                .accessibilityHint("Saisissez votre message")
        }
    }
}

struct ContactSubmitSection: View {
    @ObservedObject var viewModel: ContactViewModel

    var body: some View {
        Section {
            Button {
                Task { await viewModel.send() }
            } label: {
                if viewModel.isSending {
                    ProgressView()
                        .frame(maxWidth: .infinity)
                } else {
                    Text("Envoyer")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
            }
            .buttonStyle(.borderedProminent)
            .accessibilityLabel("Envoyer le message")
            .accessibilityHint("Envoie votre message au support")
            .disabled(viewModel.isSending || !viewModel.canSend)
        }
    }
}

struct ContactFeedbackSection: View {
    let success: String?
    let error: String?

    var body: some View {
        if let success {
            Section {
                Text(success)
                    .foregroundColor(.green)
            }
        }

        if let error {
            Section {
                Text(error)
                    .foregroundColor(.red)
            }
        }
    }
}
