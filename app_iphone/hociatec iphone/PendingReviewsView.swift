import SwiftUI

@MainActor
struct PendingReviewsView: View {
    @EnvironmentObject private var container: AppContainer
    @State private var items: [PendingReviewItem] = []
    @State private var isLoading = false
    @State private var error: String? = nil
    @State private var selected: PendingReviewItem? = nil
    @State private var showSheet = false

    var body: some View {
        List {
            if let error = error {
                Section { Text(error).foregroundColor(.red) }
            }
            if isLoading {
                Section { HStack { Spacer(); ProgressView(); Spacer() } }
            }
            Section {
                if items.isEmpty && !isLoading && error == nil {
                    Text("Aucun avis à donner").foregroundStyle(.secondary)
                } else {
                    ForEach(items) { item in
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
                    Task { await load() }
                }
                .environmentObject(container)
            }
        }
        .task { await load() }
    }

    private func load() async {
        isLoading = true
        error = nil
        defer { isLoading = false }
        do {
            items = try await container.api.pendingReviews()
        } catch {
            self.error = error.localizedDescription
            items = []
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
