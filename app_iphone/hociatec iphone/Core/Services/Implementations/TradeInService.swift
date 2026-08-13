import Foundation

struct TradeInService: TradeInServing {
    let api: APIClient

    func tradeInMetadata() async throws -> TradeInMetadata { try await api.tradeInMetadata() }
    func myTradeIns(page: Int, perPage: Int) async throws -> TradeInListData { try await api.myTradeIns(page: page, perPage: perPage) }
    func myTradeInReceipt(id: Int) async throws -> Data { try await api.myTradeInReceipt(id: id) }
    func respondToTradeIn(id: Int, action: String) async throws { try await api.respondToTradeIn(id: id, action: action) }
    func createTradeIn(payload: TradeInRequestPayload, ribFilename: String, ribData: Data, authorized: Bool) async throws -> TradeInSummary {
        try await api.createTradeIn(payload: payload, ribFilename: ribFilename, ribData: ribData, authorized: authorized)
    }
}
