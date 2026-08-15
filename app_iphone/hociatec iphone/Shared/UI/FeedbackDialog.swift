import SwiftUI

enum FeedbackDialogButtonRole {
    case cancel
    case destructive
    case standard
}

struct FeedbackDialogButton {
    let title: String
    let role: FeedbackDialogButtonRole
    let action: (() -> Void)?

    static func cancel(_ title: String = "OK", action: (() -> Void)? = nil) -> FeedbackDialogButton {
        FeedbackDialogButton(title: title, role: .cancel, action: action)
    }

    static func destructive(_ title: String, action: (() -> Void)? = nil) -> FeedbackDialogButton {
        FeedbackDialogButton(title: title, role: .destructive, action: action)
    }

    static func standard(_ title: String, action: (() -> Void)? = nil) -> FeedbackDialogButton {
        FeedbackDialogButton(title: title, role: .standard, action: action)
    }
}

struct FeedbackDialogState: Identifiable {
    let id = UUID()
    let title: String
    let message: String
    let primaryButton: FeedbackDialogButton?
    let secondaryButton: FeedbackDialogButton?

    init(
        title: String,
        message: String,
        primaryButton: FeedbackDialogButton? = nil,
        secondaryButton: FeedbackDialogButton? = nil
    ) {
        self.title = title
        self.message = message
        self.primaryButton = primaryButton
        self.secondaryButton = secondaryButton
    }

    static func success(
        _ message: String,
        title: String = "Succès",
        primaryButton: FeedbackDialogButton? = nil,
        secondaryButton: FeedbackDialogButton? = nil
    ) -> FeedbackDialogState {
        FeedbackDialogState(
            title: title,
            message: message,
            primaryButton: primaryButton,
            secondaryButton: secondaryButton
        )
    }

    static func error(
        _ message: String,
        title: String = "Échec",
        primaryButton: FeedbackDialogButton? = nil,
        secondaryButton: FeedbackDialogButton? = nil
    ) -> FeedbackDialogState {
        FeedbackDialogState(
            title: title,
            message: message,
            primaryButton: primaryButton,
            secondaryButton: secondaryButton
        )
    }
}

private struct FeedbackDialogModifier: ViewModifier {
    @Binding var dialog: FeedbackDialogState?
    let onDismiss: (() -> Void)?

    func body(content: Content) -> some View {
        content.alert(item: $dialog) { dialog in
            if let primaryButton = dialog.primaryButton, let secondaryButton = dialog.secondaryButton {
                Alert(
                    title: Text(dialog.title),
                    message: Text(dialog.message),
                    primaryButton: alertButton(for: primaryButton),
                    secondaryButton: alertButton(for: secondaryButton)
                )
            } else {
                Alert(
                    title: Text(dialog.title),
                    message: Text(dialog.message),
                    dismissButton: alertButton(for: dialog.primaryButton ?? .cancel(), triggersDismiss: true)
                )
            }
        }
    }

    private func alertButton(
        for button: FeedbackDialogButton,
        triggersDismiss: Bool = false
    ) -> Alert.Button {
        let action = {
            button.action?()
            if triggersDismiss {
                onDismiss?()
            }
        }

        switch button.role {
        case .cancel:
            return .cancel(Text(button.title), action: action)
        case .destructive:
            return .destructive(Text(button.title), action: action)
        case .standard:
            return .default(Text(button.title), action: action)
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
