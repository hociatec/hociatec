import Foundation

extension MyQuotesViewModel {
    func load(force: Bool = false) async {
        if (isLoading || hasLoadedOnce) && !force { return }
        loadRequestID += 1
        let requestID = loadRequestID
        isLoading = true
        error = nil
        successMessage = nil
        do {
            let loadedQuotes = try await loadMyQuotesUseCase.execute()
            guard requestID == loadRequestID else { return }
            quotes = loadedQuotes
            hasLoadedOnce = true
        } catch let err {
            guard requestID == loadRequestID else { return }
            error = err.localizedDescription
        }
        if requestID == loadRequestID {
            isLoading = false
        }
    }

    func delete(id: Int) async {
        if isLoading { return }
        deleteRequestID += 1
        let requestID = deleteRequestID
        isLoading = true
        error = nil
        successMessage = nil
        do {
            try await deleteQuoteUseCase.execute(id: id)
            guard requestID == deleteRequestID else { return }
            quotes.removeAll { $0.id == id }
            successMessage = "Devis supprimé."
        } catch let err {
            guard requestID == deleteRequestID else { return }
            error = err.localizedDescription
        }
        if requestID == deleteRequestID {
            isLoading = false
        }
    }
}
