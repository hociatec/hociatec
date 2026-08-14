import SwiftUI

struct FeedbackDialogState: Identifiable {
    let id = UUID()
    let title: String
    let message: String

    static func success(_ message: String, title: String = "Succès") -> FeedbackDialogState {
        FeedbackDialogState(title: title, message: message)
    }

    static func error(_ message: String, title: String = "Échec") -> FeedbackDialogState {
        FeedbackDialogState(title: title, message: message)
    }
}

private struct FeedbackDialogModifier: ViewModifier {
    @Binding var dialog: FeedbackDialogState?
    let onDismiss: (() -> Void)?

    func body(content: Content) -> some View {
        content.alert(item: $dialog) { dialog in
            Alert(
                title: Text(dialog.title),
                message: Text(dialog.message),
                dismissButton: .default(Text("OK")) {
                    onDismiss?()
                }
            )
        }
    }
}

extension View {
    func feedbackDialog(
        _ dialog: Binding<FeedbackDialogState?>,
        onDismiss: (() -> Void)? = nil
    ) -> some View {
        modifier(FeedbackDialogModifier(dialog: dialog, onDismiss: onDismiss))
    }

    func feedbackDialog(
        error: Binding<String?>,
        success: Binding<String?>? = nil,
        successTitle: String = "Succès",
        errorTitle: String = "Échec",
        onDismiss: (() -> Void)? = nil
    ) -> some View {
        let dialog = Binding<FeedbackDialogState?>(
            get: {
                if let errorMessage = error.wrappedValue?.trimmingCharacters(in: .whitespacesAndNewlines),
                   !errorMessage.isEmpty {
                    return .error(errorMessage, title: errorTitle)
                }

                if let success,
                   let successMessage = success.wrappedValue?.trimmingCharacters(in: .whitespacesAndNewlines),
                   !successMessage.isEmpty {
                    return .success(successMessage, title: successTitle)
                }

                return nil
            },
            set: { newValue in
                guard newValue == nil else { return }
                error.wrappedValue = nil
                success?.wrappedValue = nil
            }
        )

        return feedbackDialog(dialog, onDismiss: onDismiss)
    }
}
