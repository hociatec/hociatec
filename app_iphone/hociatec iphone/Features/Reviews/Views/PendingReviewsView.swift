import SwiftUI
import Combine

@MainActor
struct PendingReviewsView: View {
    @EnvironmentObject private var container: AppContainer
    @StateObject private var viewModel = PendingReviewsViewModel()
    @State private var selected: PendingReviewItem? = nil
    @State private var showSheet = false

    var body: some View {
        List {
            if viewModel.isLoading {
                Section { HStack { Spacer(); ProgressView(); Spacer() } }
            }
            Section {
                if viewModel.items.isEmpty && !viewModel.isLoading && viewModel.error == nil {
                    Text("Aucun avis à donner").foregroundStyle(.secondary)
                } else {
                    ForEach(viewModel.items) { item in
                        VStack(alignment: .leading, spacing: 4) {
                            Text(item.product.name).font(.headline)
                            Text("Commande #\(item.orderNumber) du \(orderDateFormatter.string(from: item.orderCreatedAt))")
                                .font(.subheadline)
                                .foregroundStyle(.secondary)
                            Button("Donner un avis") {
                                selected = item
                                showSheet = true
                            }
                            .padding(.top, 4)
                        }
                        .padding(.vertical, 8)
                    }
                }
            }
        }
        .navigationTitle("Avis à donner")
        .sheet(isPresented: $showSheet) {
            if let sel = selected {
                ReviewSheetView(orderId: sel.orderId, orderItemId: sel.orderItemId) { _ in
                    Task { await viewModel.load(service: container.services.orders, force: true) }
                }
                .environmentObject(container)
            }
        }
        .task { await viewModel.load(service: container.services.orders) }
        .feedbackDialog(error: $viewModel.error)
    }
}

@MainActor
private final class PendingReviewsViewModel: ObservableObject {
    @Published var items: [PendingReviewItem] = []
    @Published var isLoading = false
    @Published var error: String?
    private var hasLoadedOnce = false

    func load(service: OrderServing, force: Bool = false) async {
        guard !(isLoading || hasLoadedOnce) || force else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }
        do {
            items = try await service.pendingReviews()
            hasLoadedOnce = true
        } catch {
            self.error = error.localizedDescription
        }
    }
}

private let orderDateFormatter: DateFormatter = {
    let df = DateFormatter()
    df.locale = Locale(identifier: "fr_FR")
    df.dateStyle = .medium
    df.timeStyle = .none
    return df
}()
