import Foundation

extension TradeInViewModel {
    func loadMetadata(force: Bool = false) async {
        if isLoading && !force { return }
        metadataRequestID += 1
        let requestID = metadataRequestID
        isLoading = true
        error = nil

        do {
            let metadata = try await service.tradeInMetadata()
            guard requestID == metadataRequestID else { return }
            categories = metadata.categories
            conditions = metadata.conditions
            if selectedCategory.isEmpty {
                selectedCategory = metadata.categories.first?.value ?? ""
            }
            if selectedCondition.isEmpty {
                selectedCondition = metadata.conditions.first?.value ?? ""
            }
        } catch {
            guard requestID == metadataRequestID else { return }
            self.error = error.localizedDescription
        }
        if requestID == metadataRequestID {
            isLoading = false
        }
    }

    func setRib(fileName: String, data: Data) {
        ribFileName = fileName
        ribData = data
    }
}
