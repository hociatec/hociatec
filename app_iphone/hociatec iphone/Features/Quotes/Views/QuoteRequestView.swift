import SwiftUI
#if canImport(UIKit)
import UIKit
#endif

struct QuoteRequestView: View {
    @StateObject private var viewModel: QuoteViewModel
    @State private var showingAddLineSheet = false
    @Environment(\.dismiss) private var dismiss

    init(viewModel: QuoteViewModel) {
        _viewModel = StateObject(wrappedValue: viewModel)
    }

    var body: some View {
        Form {
            QuoteRequestFeedbackSection(error: viewModel.error, success: viewModel.successMessage)
            QuoteRequestIdentitySection(viewModel: viewModel)
            QuoteRequestItemsSection(
                viewModel: viewModel,
                showingAddLineSheet: $showingAddLineSheet,
                bindingForItem: binding(for:)
            )
            QuoteRequestSummarySection(estimatedTotalCents: estimatedTotalCents)
            QuoteRequestSubmitSection(
                isSubmitting: viewModel.isSubmitting,
                canSubmit: canSubmit,
                onSubmit: {
                    Task {
                        await viewModel.submit()
                        if viewModel.successMessage != nil {
#if canImport(UIKit)
                            UINotificationFeedbackGenerator().notificationOccurred(.success)
#endif
                            dismiss()
                        }
                    }
                }
            )
        }
        .navigationTitle("Devis")
        .task { await viewModel.loadServices() }
        .sheet(isPresented: $showingAddLineSheet) {
            QuoteAddLineSheet(viewModel: viewModel)
        }
    }

    private var canSubmit: Bool {
        !viewModel.isSubmitting
            && !viewModel.items.isEmpty
            && !viewModel.name.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
            && !viewModel.email.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
    }

    private var estimatedTotalCents: Int {
        viewModel.items.reduce(0) { $0 + $1.lineTotalCents }
    }

    private func binding(for item: QuoteDraftItem) -> Binding<QuoteDraftItem> {
        guard let idx = viewModel.items.firstIndex(where: { $0.id == item.id }) else {
            return .constant(item)
        }
        return $viewModel.items[idx]
    }
}
