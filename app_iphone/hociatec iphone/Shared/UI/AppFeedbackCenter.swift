import Foundation
import Combine

@MainActor
final class AppFeedbackCenter: ObservableObject {
    @Published var dialog: FeedbackDialogState?

    func present(_ dialog: FeedbackDialogState) {
        self.dialog = dialog
    }

    func presentSuccess(_ message: String, title: String = "Succès") {
        present(.success(message, title: title))
    }

    func presentError(_ message: String, title: String = "Échec") {
        present(.error(message, title: title))
    }

    func clear() {
        dialog = nil
    }
}
