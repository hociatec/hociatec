import Foundation

extension APIClient {
    func myRentals() async throws -> MyRentalsResponse {
        try await request(
            path: "api/rentals/me",
            authorized: true,
            attachCartToken: false
        )
    }

    func requestRentalChange(
        orderItemId: Int,
        action: RentalRequestAction,
        requestedEndDate: String
    ) async throws -> RentalItem {
        let data: RentalData = try await request(
            path: "api/rentals/\(orderItemId)/request",
            method: "PATCH",
            body: [
                "action": action.rawValue,
                "requestedEndDate": requestedEndDate
            ],
            authorized: true,
            attachCartToken: false
        )
        return data.rental
    }
}
