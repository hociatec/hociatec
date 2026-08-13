import Foundation

struct ContactService: ContactServing {
    let api: APIClient

    func sendContact(name: String, email: String, subject: String, message: String) async throws {
        try await api.sendContact(name: name, email: email, subject: subject, message: message)
    }
}
