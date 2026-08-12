import SwiftUI
import UIKit

struct ContactView: View {
    let api: APIClient

    @EnvironmentObject private var container: AppContainer

    @State private var name = ""
    @State private var email = ""
    @State private var subject = ""
    @State private var message = ""
    @State private var isSending = false
    @State private var success: String? = nil
    @State private var error: String? = nil

    var body: some View {
        Form {
            Section("Coordonnées") {
                LabeledContent("Nom complet") {
                    TextField("", text: $name)
                        .textInputAutocapitalization(.words)
                        .textContentType(.name)
                }
                LabeledContent("Email") {
                    TextField("", text: $email)
                        .keyboardType(.emailAddress)
                        .textInputAutocapitalization(.never)
                        .textContentType(.emailAddress)
                }
                LabeledContent("Objet") {
                    TextField("", text: $subject)
                }
            }

            Section {
                TextEditor(text: $message)
                    .frame(minHeight: 120)
                    .overlay(
                        RoundedRectangle(cornerRadius: 8)
                            .stroke(Color.secondary.opacity(0.2))
                    )
                    .accessibilityHint("Saisissez votre message")
            }

            Section {
                Button {
                    Task { await send() }
                } label: {
                    if isSending {
                        ProgressView().frame(maxWidth: .infinity)
                    } else {
                        Text("Envoyer")
                            .fontWeight(.semibold)
                            .frame(maxWidth: .infinity)
                    }
                }
                .buttonStyle(.borderedProminent)
                .accessibilityLabel("Envoyer le message")
                .accessibilityHint("Envoie votre message au support")
                .disabled(isSending || name.isEmpty || email.isEmpty || subject.isEmpty || message.isEmpty)
            }

            if let success = success {
                Section {
                    Text(success)
                        .foregroundColor(.green)
                }
            }

            if let error = error {
                Section {
                    Text(error)
                        .foregroundColor(.red)
                }
            }
        }
        .formStyle(.grouped)
        .navigationTitle("Contact")
        .onAppear {
            if let profile = container.account.profile {
                if name.isEmpty {
                    name = profile.fullName
                }
                if email.isEmpty {
                    email = profile.email
                }
            }
        }
    }

    private func send() async {
        guard !isSending else { return }
        isSending = true
        defer { isSending = false }
        success = nil
        error = nil
        do {
            try await api.sendContact(name: name, email: email, subject: subject, message: message)
            let confirmation = "Message envoyé."
            success = confirmation
            announce(confirmation)
            name = ""
            email = ""
            subject = ""
            message = ""
        } catch {
            let message = error.localizedDescription
            self.error = message
            announce(message)
        }
    }

    private func announce(_ message: String) {
        UIAccessibility.post(notification: .announcement, argument: message)
    }
}

