import Foundation

struct VoucherService: VoucherServing {
    let api: APIClient

    func myVouchers(page: Int, perPage: Int) async throws -> VoucherListData {
        try await api.myVouchers(page: page, perPage: perPage)
    }
}
