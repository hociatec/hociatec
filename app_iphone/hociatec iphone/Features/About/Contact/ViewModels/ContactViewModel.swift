import Foundation
import UIKit

@MainActor
final class ContactViewModel: ObservableObject {
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
