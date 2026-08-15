import Foundation

struct RentalService: RentalServing {
    let api: APIClient

    func myRentals() async throws -> MyRentalsResponse {
        try await api.myRentals()
    }

    func requestRentalChange(orderItemId: Int, action: RentalRequestAction, requestedEndDate: String) async throws -> RentalItem {
        try await api.requestRentalChange(
            orderItemId: orderItemId,
            action: action,
            requestedEndDate: requestedEndDate
        )
    }
}
