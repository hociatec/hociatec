import Foundation
import Combine

@MainActor
final class VouchersViewModel: ObservableObject {
    @Published var items: [VoucherListItem] = []
    @Published var isLoading = false
    @Published var error: String?

    private let service: VoucherServing
    private var loadRequestID = 0
    private var hasLoadedOnce = false

    init(service: VoucherServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if (isLoading || hasLoadedOnce) && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil

        do {
            let loadedItems = try await service.myVouchers(page: 1, perPage: 30).items
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
}
