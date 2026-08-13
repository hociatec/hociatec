import SwiftUI
import Combine

struct ReviewSheetView: View {
    let orderId: Int
    let orderItemId: Int
    let onSubmitted: (Review) -> Void

    @EnvironmentObject private var container: AppContainer
    @Environment(\.dismiss) private var dismiss
    @StateObject private var viewModel = ReviewSheetViewModel()

    var body: some View {
        NavigationStack {
            Form {
                if let successMessage = viewModel.successMessage {
                    Section { Label(successMessage, systemImage: "checkmark.seal.fill").foregroundStyle(.green) }
                }
                if let error = viewModel.error { Section { Text(error).foregroundStyle(.red) } }
                Section("Note") {
                    Picker("Note", selection: $viewModel.score) { ForEach(1...5, id: \.self) { v in Text(String(v)).tag(v) } }
                    .pickerStyle(.segmented)
                }
                Section("Commentaire (optionnel)") {
                    TextEditor(text: $viewModel.comment).frame(minHeight: 120)
                }
                Section {
                    Button {
                        Task { await submitReview() }
                    } label: {
                        if viewModel.isSubmitting { ProgressView().frame(maxWidth: .infinity) } else { Text("Envoyer l’avis").fontWeight(.semibold).frame(maxWidth: .infinity) }
                    }
                }
            }
            .navigationTitle("Donner votre avis")
            .navigationBarTitleDisplayMode(.inline)
        }
    }

    @MainActor
    private func submitReview() async {
        guard !viewModel.isSubmitting else { return }
        do {
            let review = try await viewModel.submit(service: container.services.orders, orderId: orderId, orderItemId: orderItemId)
            onSubmitted(review)
            let generator = UINotificationFeedbackGenerator(); generator.notificationOccurred(.success)
            viewModel.successMessage = "Votre avis a bien été envoyé."
            try? await Task.sleep(nanoseconds: 1_000_000_000)
            dismiss()
        } catch { viewModel.error = error.localizedDescription }
    }
}

@MainActor
private final class ReviewSheetViewModel: ObservableObject {
    @Published var score: Int = 5
    @Published var comment: String = ""
    @Published var isSubmitting = false
    @Published var error: String?
    @Published var successMessage: String?

    func submit(service: OrderServing, orderId: Int, orderItemId: Int) async throws -> Review {
        isSubmitting = true
        error = nil
        defer { isSubmitting = false }
        return try await service.createReview(
            orderId: orderId,
            orderItemId: orderItemId,
            score: score,
            comment: comment.trimmingCharacters(in: .whitespacesAndNewlines)
        )
    }
}
