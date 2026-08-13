import Foundation

struct TradeInService: TradeInServing {
    let api: APIClient

    func tradeInMetadata() async throws -> TradeInMetadata { try await api.tradeInMetadata() }
    func createTradeIn(payload: TradeInRequestPayload, ribFilename: String, ribData: Data, authorized: Bool) async throws -> TradeInSummary {
        try await api.createTradeIn(payload: payload, ribFilename: ribFilename, ribData: ribData, authorized: authorized)
    }
}
