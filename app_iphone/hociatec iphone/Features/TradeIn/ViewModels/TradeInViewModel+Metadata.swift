import Foundation

extension TradeInViewModel {
    func loadMetadata() async {
        guard !isLoading else { return }
        isLoading = true
        error = nil
        defer { isLoading = false }

        do {
            let metadata = try await service.tradeInMetadata()
            categories = metadata.categories
            conditions = metadata.conditions
            if selectedCategory.isEmpty {
                selectedCategory = metadata.categories.first?.value ?? ""
            }
            if selectedCondition.isEmpty {
                selectedCondition = metadata.conditions.first?.value ?? ""
            }
        } catch {
            self.error = error.localizedDescription
        }
    }

    func setRib(fileName: String, data: Data) {
        ribFileName = fileName
        ribData = data
    }
}
