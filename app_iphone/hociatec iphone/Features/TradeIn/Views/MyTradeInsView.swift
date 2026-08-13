import SwiftUI

struct MyTradeInsView: View {
    @StateObject private var viewModel: MyTradeInsViewModel

    init(service: TradeInServing) {
        _viewModel = StateObject(wrappedValue: MyTradeInsViewModel(service: service))
    }

    var body: some View {
        List {
            if let error = viewModel.error, !error.isEmpty {
                Section { Text(error).foregroundStyle(.red) }
            }

            if let message = viewModel.message, !message.isEmpty {
                Section { Text(message).foregroundStyle(.green) }
            }

            Section("Mes reprises") {
                if viewModel.isLoading && viewModel.items.isEmpty {
                    ProgressView("Chargement...")
                } else if viewModel.items.isEmpty {
                    Text("Aucune demande de reprise pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(viewModel.items) { item in
                        VStack(alignment: .leading, spacing: 8) {
                            HStack {
                                Text(item.reference)
                                    .fontWeight(.semibold)
                                Spacer()
                                Text(item.statusLabel)
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                            Text(item.productName)
                            if let offerCents = item.offerCents {
                                Text(PriceFormatter.format(cents: offerCents))
                                    .font(.footnote.weight(.semibold))
                            }
                            if let adminNote = item.adminNote, !adminNote.isEmpty {
                                Text(adminNote)
                                    .font(.footnote)
                                    .foregroundStyle(.secondary)
                            }
                            if let voucherCode = item.voucherCode, !voucherCode.isEmpty {
                                Text("Avoir client: \(voucherCode)")
                                    .font(.footnote)
                            }
                            if item.status == "offer_sent" {
                                HStack {
                                    Button("Accepter") {
                                        Task { await viewModel.respond(id: item.id, action: "accept") }
                                    }
                                    Button("Refuser", role: .destructive) {
                                        Task { await viewModel.respond(id: item.id, action: "decline") }
                                    }
                                }
                            }
                            Button("Télécharger le justificatif") {
                                Task { await viewModel.shareReceipt(id: item.id, reference: item.reference) }
                            }
                            .font(.footnote)
                        }
                        .padding(.vertical, 6)
                    }
                }
            }
        }
        .navigationTitle("Mes reprises")
        .sheet(item: $viewModel.sharedFile) { file in
            ActivityView(activityItems: [file.url])
        }
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
    }
}

@MainActor
private final class MyTradeInsViewModel: ObservableObject {
    @Published var items: [TradeInSummary] = []
    @Published var isLoading = false
    @Published var error: String?
    @Published var message: String?
    @Published var sharedFile: TemporarySharedFile?

    private let service: TradeInServing

    init(service: TradeInServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            items = try await service.myTradeIns(page: 1, perPage: 20).items
        } catch {
            self.error = error.localizedDescription
        }
    }

    func respond(id: Int, action: String) async {
        isLoading = true
        error = nil
        message = nil
        defer { isLoading = false }

        do {
            try await service.respondToTradeIn(id: id, action: action)
            message = action == "accept" ? "Votre accord a été enregistré." : "Votre refus a été enregistré."
            items = try await service.myTradeIns(page: 1, perPage: 20).items
        } catch {
            self.error = error.localizedDescription
        }
    }

    func shareReceipt(id: Int, reference: String) async {
        error = nil

        do {
            let data = try await service.myTradeInReceipt(id: id)
            sharedFile = try TemporarySharedFileFactory.create(
                data: data,
                fileName: "justificatif-reprise-\(reference).pdf"
            )
        } catch {
            self.error = error.localizedDescription
        }
    }
}
