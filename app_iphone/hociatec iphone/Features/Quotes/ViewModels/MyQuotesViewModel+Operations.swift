import Foundation

extension MyQuotesViewModel {
    func load(force: Bool = false) async {
        if isLoading && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil
        do {
            let loadedQuotes = try await loadMyQuotesUseCase.execute()
            guard requestID == loadRequestID else { return }
            quotes = loadedQuotes
        } catch let err {
            guard requestID == loadRequestID else { return }
            error = err.localizedDescription
        }
        if requestID == loadRequestID {
            isLoading = false
        }
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
