import SwiftUI

struct ReviewSheetView: View {
    let orderId: Int
    let orderItemId: Int
    let onSubmitted: (Review) -> Void

    @EnvironmentObject private var container: AppContainer
    @Environment(\.dismiss) private var dismiss
    @State private var score: Int = 5
    @State private var comment: String = ""
    @State private var isSubmitting = false
    @State private var error: String?
    @State private var successMessage: String? = nil

    var body: some View {
        NavigationStack {
            Form {
                if let successMessage {
                    Section { Label(successMessage, systemImage: "checkmark.seal.fill").foregroundStyle(.green) }
                }
                if let error { Section { Text(error).foregroundStyle(.red) } }
                Section("Note") {
                    Picker("Note", selection: $score) { ForEach(1...5, id: \.self) { v in Text(String(v)).tag(v) } }
                    .pickerStyle(.segmented)
                }
                Section("Commentaire (optionnel)") {
                    TextEditor(text: $comment).frame(minHeight: 120)
                }
                Section {
                    Button {
                        Task { await submitReview() }
                    } label: {
                        if isSubmitting { ProgressView().frame(maxWidth: .infinity) } else { Text("Envoyer l’avis").fontWeight(.semibold).frame(maxWidth: .infinity) }
                    }
                }
            }
            .navigationTitle("Donner votre avis")
            .navigationBarTitleDisplayMode(.inline)
        }
    }

    @MainActor
    private func submitReview() async {
        guard !isSubmitting else { return }
        isSubmitting = true
        error = nil
        defer { isSubmitting = false }
        do {
            let review = try await container.api.createReview(orderId: orderId, orderItemId: orderItemId, score: score, comment: comment.trimmingCharacters(in: .whitespacesAndNewlines))
            onSubmitted(review)
            let generator = UINotificationFeedbackGenerator(); generator.notificationOccurred(.success)
            successMessage = "Votre avis a bien été envoyé."
            try? await Task.sleep(nanoseconds: 1_000_000_000)
            dismiss()
        } catch { self.error = error.localizedDescription }
    }
}
