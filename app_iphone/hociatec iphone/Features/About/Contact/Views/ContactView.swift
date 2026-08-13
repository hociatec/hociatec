import SwiftUI
import UIKit
import Combine

struct ContactView: View {
    @EnvironmentObject private var container: AppContainer
    @StateObject private var viewModel: ContactViewModel
    private let initialSubject: String?

    init(service: ContactServing, initialSubject: String? = nil) {
        self.initialSubject = initialSubject
        _viewModel = StateObject(
            wrappedValue: ContactViewModel(
                service: service,
                initialSubject: initialSubject
            )
        )
    }

    var body: some View {
        Form {
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

            Section {
                TextEditor(text: $viewModel.message)
                    .frame(minHeight: 120)
                    .overlay(
                        RoundedRectangle(cornerRadius: 8)
                            .stroke(Color.secondary.opacity(0.2))
                    )
                    .accessibilityHint("Saisissez votre message")
            }

            Section {
                Button {
                    Task { await viewModel.send() }
                } label: {
                    if viewModel.isSending {
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
                .disabled(viewModel.isSending || !viewModel.canSend)
            }

            if let success = viewModel.success {
                Section {
                    Text(success)
                        .foregroundColor(.green)
                }
            }

            if let error = viewModel.error {
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
                viewModel.prefill(name: profile.fullName, email: profile.email)
            }
            viewModel.prefillSubject(initialSubject)
        }
    }
}

@MainActor
private final class ContactViewModel: ObservableObject {
    @Published var name = ""
    @Published var email = ""
    @Published var subject = ""
    @Published var message = ""
    @Published var isSending = false
    @Published var success: String?
    @Published var error: String?

    private let service: ContactServing

    init(service: ContactServing, initialSubject: String? = nil) {
        self.service = service
        self.subject = initialSubject ?? ""
    }

    var canSend: Bool {
        !name.isEmpty && !email.isEmpty && !subject.isEmpty && !message.isEmpty
    }

    func prefill(name: String, email: String) {
        if self.name.isEmpty { self.name = name }
        if self.email.isEmpty { self.email = email }
    }

    func prefillSubject(_ subject: String?) {
        guard self.subject.isEmpty else { return }
        if let subject, !subject.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            self.subject = subject
        }
    }

    func send() async {
        guard !isSending else { return }
        isSending = true
        defer { isSending = false }
        success = nil
        error = nil
        do {
            try await service.sendContact(name: name, email: email, subject: subject, message: message)
            let confirmation = "Message envoyé."
            success = confirmation
            UIAccessibility.post(notification: .announcement, argument: confirmation)
            name = ""
            email = ""
            subject = ""
            message = ""
        } catch {
            let message = error.localizedDescription
            self.error = message
            UIAccessibility.post(notification: .announcement, argument: message)
        }
    }
}
