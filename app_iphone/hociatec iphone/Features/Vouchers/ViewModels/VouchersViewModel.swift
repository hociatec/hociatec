import Foundation
import Combine

@MainActor
final class VouchersViewModel: ObservableObject {
    @Published var items: [VoucherListItem] = []
    @Published var isLoading = false
    @Published var error: String?

    private let service: VoucherServing

    init(service: VoucherServing) {
        self.service = service
    }

    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            items = try await service.myVouchers(page: 1, perPage: 30).items
        } catch {
            self.error = error.localizedDescription
            items = []
        }
    }
}
