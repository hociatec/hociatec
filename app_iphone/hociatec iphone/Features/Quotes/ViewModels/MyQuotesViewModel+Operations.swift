import Foundation

extension MyQuotesViewModel {
    func load(force: Bool = false) async {
        if isLoading && !force { return }
        isLoading = true
        error = nil
        do {
            quotes = try await loadMyQuotesUseCase.execute()
        } catch let err {
            error = err.localizedDescription
        }
        isLoading = false
    }

    func delete(id: Int) async {
        isLoading = true
        error = nil
        do {
            try await deleteQuoteUseCase.execute(id: id)
            quotes.removeAll { $0.id == id }
        } catch let err {
            error = err.localizedDescription
        }
        isLoading = false
    }
}
