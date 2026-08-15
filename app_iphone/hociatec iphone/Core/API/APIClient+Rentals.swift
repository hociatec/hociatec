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
    ) async throws -> RentalChangeData {
        try await request(
            path: "api/rentals/\(orderItemId)/request",
            method: "PATCH",
            body: [
                "action": action.rawValue,
                "requestedEndDate": requestedEndDate,
                "clientPlatform": "ios"
            ],
            authorized: true,
            attachCartToken: false
        )
    }

    func planRentalReturn(
        orderItemId: Int,
        mode: String,
        requestedDate: String
    ) async throws -> RentalItem {
        let data: RentalData = try await request(
            path: "api/rentals/\(orderItemId)/return-plan",
            method: "PUT",
            body: [
                "mode": mode,
                "requestedDate": requestedDate
            ],
            authorized: true,
            attachCartToken: false
        )
        return data.rental
    }

    func terminateRental(
        orderItemId: Int,
        requestedEndDate: String,
        returnMode: String,
        returnRequestedDate: String
    ) async throws -> RentalItem {
        let data: RentalData = try await request(
            path: "api/rentals/\(orderItemId)/terminate",
            method: "PUT",
            body: [
                "requestedEndDate": requestedEndDate,
                "returnMode": returnMode,
                "returnRequestedDate": returnRequestedDate
            ],
            authorized: true,
            attachCartToken: false
        )
        return data.rental
    }
}
