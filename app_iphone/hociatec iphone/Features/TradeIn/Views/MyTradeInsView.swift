import SwiftUI
import Combine

struct MyTradeInsView: View {
    @StateObject private var viewModel: MyTradeInsViewModel

    init(service: TradeInServing) {
        _viewModel = StateObject(wrappedValue: MyTradeInsViewModel(service: service))
    }

    var body: some View {
        List {
            TradeInListSection(viewModel: viewModel)
        }
        .navigationTitle("Mes reprises")
        .sheet(item: $viewModel.sharedFile) { file in
            ActivityView(activityItems: [file.url])
        }
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
        .feedbackDialog(error: $viewModel.error, success: $viewModel.message)
    }
}

@MainActor
final class MyTradeInsViewModel: ObservableObject {
    @Published var items: [TradeInSummary] = []
    @Published var isLoading = false
    @Published var error: String?
    @Published var message: String?
    @Published var sharedFile: TemporarySharedFile?
    @Published var respondingTradeInID: Int?

    private let service: TradeInServing
    private var loadRequestID = 0
    private var respondRequestID = 0
    private var shareRequestID = 0
    private var hasLoadedOnce = false

    init(service: TradeInServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if (isLoading || hasLoadedOnce) && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil

        do {
            let loadedItems = try await service.myTradeIns(page: 1, perPage: 20).items
            guard requestID == loadRequestID else { return }
            items = loadedItems
            hasLoadedOnce = true
        } catch {
            guard requestID == loadRequestID else { return }
            self.error = error.localizedDescription
        }
        if requestID == loadRequestID {
            isLoading = false
        }
    }

    func respond(id: Int, action: String) async {
        guard respondingTradeInID == nil else { return }
        respondRequestID += 1
        let requestID = respondRequestID
        respondingTradeInID = id
        isLoading = true
        error = nil
        message = nil

        do {
            try await service.respondToTradeIn(id: id, action: action)
            guard requestID == respondRequestID else { return }
            message = action == "accept" ? "Votre accord a été enregistré." : "Votre refus a été enregistré."
            let refreshedItems = try await service.myTradeIns(page: 1, perPage: 20).items
            guard requestID == respondRequestID else { return }
            items = refreshedItems
        } catch {
            guard requestID == respondRequestID else { return }
            self.error = error.localizedDescription
        }
        if requestID == respondRequestID {
            respondingTradeInID = nil
            isLoading = false
        }
    }

    func shareReceipt(id: Int, reference: String) async {
        shareRequestID += 1
        let requestID = shareRequestID
        error = nil

        do {
            let data = try await service.myTradeInReceipt(id: id)
            guard requestID == shareRequestID else { return }
            sharedFile = try TemporarySharedFileFactory.create(
                data: data,
                fileName: "justificatif-reprise-\(reference).pdf"
            )
        } catch {
            guard requestID == shareRequestID else { return }
            self.error = error.localizedDescription
        }
    }
}
