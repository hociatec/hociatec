import Foundation

extension APIClient {
    func tradeInMetadata() async throws -> TradeInMetadata {
        try await request(
            path: "api/public/trade-ins/metadata",
            authorized: false,
            attachCartToken: false
        )
    }

    func createTradeIn(
        payload: TradeInRequestPayload,
        ribFilename: String,
        ribData: Data,
        authorized: Bool
    ) async throws -> TradeInSummary {
        let fields: [String: String] = [
            "firstName": payload.firstName,
            "lastName": payload.lastName,
            "email": payload.email,
            "phone": payload.phone,
            "category": payload.category,
            "productName": payload.productName,
            "purchasePriceCents": String(payload.purchasePriceCents),
            "purchaseYear": String(payload.purchaseYear),
            "brand": payload.brand ?? "",
            "model": payload.model ?? "",
            "serialNumber": payload.serialNumber ?? "",
            "conditionGrade": payload.conditionGrade,
            "functional": payload.functional ? "1" : "0",
            "hasAccessories": payload.hasAccessories ? "1" : "0",
            "hasProofOfPurchase": payload.hasProofOfPurchase ? "1" : "0",
            "description": payload.description,
            "catalogProductId": payload.catalogProductId.map(String.init) ?? "",
            "consent": payload.consent ? "1" : "0"
        ]

        return try await multipartRequest(
            path: authorized ? "api/trade-ins" : "api/public/trade-ins",
            fields: fields,
            fileFieldName: "rib",
            filename: ribFilename,
            mimeType: "application/pdf",
            fileData: ribData,
            authorized: authorized,
            attachCartToken: false
        )
    }

    func myTradeIns(page: Int = 1, perPage: Int = 10) async throws -> TradeInListData {
        try await request(
            path: "api/trade-ins/me",
            query: [
                URLQueryItem(name: "page", value: String(page)),
                URLQueryItem(name: "perPage", value: String(perPage))
            ],
            authorized: true,
            attachCartToken: false
        )
    }

    func respondToTradeIn(id: Int, action: String) async throws {
        try await send(
            path: "api/trade-ins/\(id)/respond/\(action)",
            method: "POST",
            authorized: true,
            attachCartToken: false
        )
    }

    func myTradeInReceipt(id: Int) async throws -> Data {
        try await download(
            path: "api/trade-ins/\(id)/receipt",
            authorized: true,
            attachCartToken: false
        )
    }
}
